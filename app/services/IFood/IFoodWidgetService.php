<?php

declare(strict_types=1);

namespace App\Services\IFood;

use PDO;
use Throwable;

/**
 * Widget iFood — configuração + geração de URLs e snippet JS.
 *
 * Permite que a loja do merchant embarque um botão/iframe que abre o cardápio
 * iFood (com merchant pré-selecionado) e renderize tracking de pedidos iFood
 * existentes.
 *
 * Não tenta replicar o cardápio — usa a URL pública do merchant no iFood
 * (que o admin configura). Para tracking, usa o `ifood_display_id` que já
 * armazenamos em `ifood_orders` quando o evento chega via webhook/polling.
 *
 * Cache: o JS snippet é cacheado em filesystem (key inclui `cache_version`).
 * Cada `saveConfig` bumpa o version → próximo GET regenera.
 */
final class IFoodWidgetService
{
    /**
     * Lista branca dos campos que admin pode salvar. Tudo fora dessa
     * lista é ignorado em saveConfig (defensive).
     */
    private const ADMIN_FIELDS = [
        'enabled', 'widget_type', 'merchant_slug', 'merchant_url',
        'tracking_enabled', 'theme', 'position', 'button_label',
        'fallback_url', 'allowed_origins', 'custom_css',
    ];

    /**
     * Campos seguros para devolver no endpoint público (sem leakage de
     * dados internos como timestamps de admin, allowed_origins, etc.).
     */
    private const PUBLIC_FIELDS = [
        'enabled', 'widget_type', 'theme', 'position',
        'button_label', 'tracking_enabled', 'cache_version',
    ];

    private const TRACKING_BASE_URL = 'https://pedido.ifood.com.br/r/';
    private const MERCHANT_BASE_URL = 'https://www.ifood.com.br/delivery';

    private PDO $db;
    private ?string $cacheDir;

    public function __construct(PDO $db, ?string $cacheDir = null)
    {
        $this->db = $db;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Lê config (ou retorna defaults se a row não existir).
     *
     * @return array<string,mixed>
     */
    public function getConfig(int $companyId): array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM ifood_widget_config WHERE company_id = ? LIMIT 1'
            );
            $stmt->execute([$companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['enabled'] = (int) $row['enabled'] === 1;
                $row['tracking_enabled'] = (int) $row['tracking_enabled'] === 1;
                return $row;
            }
        } catch (Throwable $e) {
            error_log('[IFoodWidget] getConfig falhou: ' . $e->getMessage());
        }
        return $this->defaultConfig($companyId);
    }

    /**
     * Versão pública: sanitizada para exposição em endpoints sem auth.
     * Inclui as URLs já calculadas (sem secrets).
     *
     * @return array<string,mixed>
     */
    public function getPublicConfig(int $companyId): array
    {
        $config = $this->getConfig($companyId);
        $out = [];
        foreach (self::PUBLIC_FIELDS as $field) {
            $out[$field] = $config[$field] ?? null;
        }
        $out['merchant_url']  = $this->buildMerchantUrl($config);
        $out['fallback_url']  = (string) ($config['fallback_url'] ?? '') ?: $out['merchant_url'];
        return $out;
    }

    /**
     * Faz upsert da config. Whitelist + bump do cache_version.
     *
     * @param array<string,mixed> $data
     * @return array{ok:bool, message:?string}
     */
    public function saveConfig(int $companyId, array $data): array
    {
        if ($companyId <= 0) {
            return ['ok' => false, 'message' => 'company_id inválido'];
        }

        // Sanitização e validação
        $clean = [];
        foreach (self::ADMIN_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];

            if (in_array($field, ['enabled', 'tracking_enabled'], true)) {
                $clean[$field] = !empty($value) ? 1 : 0;
                continue;
            }
            if ($field === 'widget_type' && !in_array($value, ['button', 'embedded', 'tracking_only'], true)) {
                return ['ok' => false, 'message' => 'widget_type inválido'];
            }
            if ($field === 'theme' && !in_array($value, ['light', 'dark', 'auto'], true)) {
                return ['ok' => false, 'message' => 'theme inválido'];
            }
            if ($field === 'position' && !in_array($value, ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'inline'], true)) {
                return ['ok' => false, 'message' => 'position inválido'];
            }
            if (in_array($field, ['merchant_url', 'fallback_url'], true) && $value !== null && $value !== '') {
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    return ['ok' => false, 'message' => "{$field} não é URL válida"];
                }
            }
            $clean[$field] = $value;
        }

        if (empty($clean)) {
            return ['ok' => false, 'message' => 'nenhum campo válido pra salvar'];
        }

        try {
            $fields = array_keys($clean);
            $placeholders = array_map(static fn($f) => ":{$f}", $fields);
            $updates = array_map(static fn($f) => "{$f} = VALUES({$f})", $fields);

            $sql = 'INSERT INTO ifood_widget_config (company_id, ' . implode(', ', $fields) . ', cache_version)
                    VALUES (:company_id, ' . implode(', ', $placeholders) . ', 1)
                    ON DUPLICATE KEY UPDATE '
                    . implode(', ', $updates) . ',
                      cache_version = cache_version + 1';

            $bind = [':company_id' => $companyId];
            foreach ($clean as $f => $v) {
                $bind[":{$f}"] = $v;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($bind);

            $this->invalidateCache($companyId);

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            error_log('[IFoodWidget] saveConfig falhou: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'erro ao salvar: ' . $e->getMessage()];
        }
    }

    /**
     * Constrói URL do merchant no iFood — prefere merchant_url se setado,
     * senão usa merchant_slug com base URL conhecida.
     *
     * @param array<string,mixed> $config
     */
    public function buildMerchantUrl(array $config): string
    {
        $url = trim((string) ($config['merchant_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        $slug = trim((string) ($config['merchant_slug'] ?? ''));
        if ($slug === '') {
            return '';
        }
        // Se admin passou só o slug, construímos com base default. Pode não bater
        // com a URL real do iFood — por isso preferimos merchant_url quando disponível.
        return self::MERCHANT_BASE_URL . '/' . rawurlencode($slug);
    }

    /**
     * URL pública de tracking dado um display_id do iFood.
     */
    public function buildTrackingUrlFromDisplayId(string $displayId): string
    {
        $displayId = trim($displayId);
        if ($displayId === '') {
            return '';
        }
        return self::TRACKING_BASE_URL . rawurlencode($displayId);
    }

    /**
     * Busca o display_id em ifood_orders e devolve URL de tracking
     * (ou string vazia se não achar / tracking desabilitado).
     *
     * Aceita lookup por:
     *   - local order_id (orders.id; coluna `ifood_display_id` ou `ifood_order_id`)
     *   - direto ifood_order_id (UUID)
     *   - display_id (formato ABC1)
     */
    public function trackingUrlForOrder(int $companyId, string $orderRef): array
    {
        $config = $this->getConfig($companyId);
        if (!$config['enabled'] || !$config['tracking_enabled']) {
            return ['ok' => false, 'url' => '', 'message' => 'tracking desabilitado'];
        }

        $ref = trim($orderRef);
        if ($ref === '') {
            return ['ok' => false, 'url' => '', 'message' => 'order_ref ausente'];
        }

        try {
            // PDO native prepares não permite reusar o mesmo placeholder
            // em locais diferentes → usamos nomes distintos com mesmo valor.
            $sql = "SELECT ifood_display_id, ifood_order_id
                      FROM ifood_orders
                     WHERE company_id = :cid
                       AND (
                            ifood_order_id    = :ref_uuid
                         OR ifood_display_id  = :ref_display";
            $bind = [':cid' => $companyId, ':ref_uuid' => $ref, ':ref_display' => $ref];

            // Se for número, também tenta como orders.id local
            if (ctype_digit($ref)) {
                $sql .= " OR order_id = :oid";
                $bind[':oid'] = (int) $ref;
            }
            $sql .= ") LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($bind);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['ok' => false, 'url' => '', 'message' => 'pedido não encontrado'];
            }

            $displayId = trim((string) ($row['ifood_display_id'] ?? ''));
            if ($displayId === '') {
                // Fallback: tenta com o UUID (alguns links do iFood aceitam UUID também)
                $displayId = trim((string) ($row['ifood_order_id'] ?? ''));
            }
            if ($displayId === '') {
                return ['ok' => false, 'url' => '', 'message' => 'pedido sem identificador iFood'];
            }

            return [
                'ok' => true,
                'url' => $this->buildTrackingUrlFromDisplayId($displayId),
                'message' => null,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'url' => '', 'message' => 'erro: ' . $e->getMessage()];
        }
    }

    /**
     * Gera o snippet JS que o site público inclui via <script src="...">.
     * Cacheado em FS quando $cacheDir está disponível.
     */
    public function renderJsSnippet(int $companyId): string
    {
        $config = $this->getConfig($companyId);
        $cacheKey = $companyId . '_v' . (int) $config['cache_version'];

        $cached = $this->cacheGet($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $public = $this->getPublicConfig($companyId);
        $publicJson = json_encode($public, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        $cssEsc = json_encode((string) ($config['custom_css'] ?? ''));

        // JS auto-contido: cria botão flutuante ou iframe inline + fallback se merchant_url vazia.
        $js = <<<JS
/*! iFood Widget — company={$companyId} v{$config['cache_version']} */
(function(){
  var CFG = {$publicJson};
  var CSS = {$cssEsc};

  if (!CFG.enabled) return;
  if (!CFG.merchant_url && !CFG.fallback_url) {
    console.warn('[iFood Widget] sem merchant_url nem fallback_url configurado; abortando.');
    return;
  }

  function openMerchant() {
    var url = CFG.merchant_url || CFG.fallback_url;
    try {
      window.open(url, '_blank', 'noopener,noreferrer');
    } catch (e) {
      window.location.href = url;
    }
  }

  function injectCss() {
    if (!CSS) return;
    var style = document.createElement('style');
    style.setAttribute('data-ifood-widget', '1');
    style.textContent = CSS;
    document.head.appendChild(style);
  }

  function createButton() {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-ifood-widget', 'btn');
    btn.setAttribute('aria-label', CFG.button_label);
    btn.textContent = CFG.button_label || 'Peça pelo iFood';
    btn.style.cssText = [
      'position:fixed','z-index:99999','padding:12px 18px',
      'border:none','border-radius:24px','cursor:pointer',
      'font-family:system-ui,sans-serif','font-weight:600','font-size:14px',
      'box-shadow:0 4px 12px rgba(0,0,0,0.15)',
      CFG.theme === 'dark' ? 'background:#1a1a1a;color:#fff' : 'background:#EA1D2C;color:#fff'
    ].join(';');
    var pos = CFG.position || 'bottom-right';
    if (pos === 'bottom-right') { btn.style.bottom = '20px'; btn.style.right = '20px'; }
    else if (pos === 'bottom-left') { btn.style.bottom = '20px'; btn.style.left = '20px'; }
    else if (pos === 'top-right') { btn.style.top = '20px'; btn.style.right = '20px'; }
    else if (pos === 'top-left') { btn.style.top = '20px'; btn.style.left = '20px'; }
    btn.onclick = openMerchant;
    document.body.appendChild(btn);
  }

  function createEmbedded() {
    var container = document.querySelector('[data-ifood-widget-slot]');
    if (!container) {
      console.warn('[iFood Widget] nenhum [data-ifood-widget-slot] encontrado para embedded');
      return createButton(); // fallback
    }
    var iframe = document.createElement('iframe');
    iframe.src = CFG.merchant_url;
    iframe.style.cssText = 'width:100%;min-height:600px;border:0';
    iframe.setAttribute('loading', 'lazy');
    iframe.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
    iframe.onerror = function() {
      container.innerHTML = '<a href="' + CFG.fallback_url + '" target="_blank" rel="noopener">Abrir no iFood</a>';
    };
    container.appendChild(iframe);
  }

  function boot() {
    injectCss();
    if (CFG.widget_type === 'embedded') createEmbedded();
    else if (CFG.widget_type === 'tracking_only') { /* nada — só helpers expostos */ }
    else createButton();
  }

  // Expõe helper de tracking pra páginas de pedido
  window.iFoodWidget = {
    config: CFG,
    open: openMerchant,
    track: function(orderRef, opts) {
      opts = opts || {};
      var slug = (opts.slug || document.body.getAttribute('data-store-slug') || '').toString();
      if (!slug) { console.warn('[iFood Widget] slug ausente em track()'); return; }
      var url = '/api/' + encodeURIComponent(slug) + '/ifood-widget/track/' + encodeURIComponent(orderRef);
      return fetch(url).then(function(r){ return r.json(); }).then(function(j){
        if (j && j.success && j.url) {
          if (opts.target === '_self') window.location.href = j.url;
          else window.open(j.url, '_blank', 'noopener,noreferrer');
        }
        return j;
      });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else { boot(); }
})();
JS;

        $this->cacheSet($cacheKey, $js);
        return $js;
    }

    /**
     * @return array<string,mixed>
     */
    private function defaultConfig(int $companyId): array
    {
        return [
            'company_id'        => $companyId,
            'enabled'           => false,
            'widget_type'       => 'button',
            'merchant_slug'     => null,
            'merchant_url'      => null,
            'tracking_enabled'  => true,
            'theme'             => 'light',
            'position'          => 'bottom-right',
            'button_label'      => 'Peça pelo iFood',
            'fallback_url'      => null,
            'allowed_origins'   => null,
            'custom_css'        => null,
            'cache_version'     => 0,
        ];
    }

    private function cacheGet(string $key): ?string
    {
        if ($this->cacheDir === null) {
            return null;
        }
        $path = $this->cacheDir . '/ifood_widget_' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.js';
        if (is_file($path) && (time() - filemtime($path)) < 3600) {
            $c = @file_get_contents($path);
            return $c === false ? null : $c;
        }
        return null;
    }

    private function cacheSet(string $key, string $content): void
    {
        if ($this->cacheDir === null) {
            return;
        }
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
        $path = $this->cacheDir . '/ifood_widget_' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.js';
        @file_put_contents($path, $content);
    }

    private function invalidateCache(int $companyId): void
    {
        if ($this->cacheDir === null || !is_dir($this->cacheDir)) {
            return;
        }
        foreach (glob($this->cacheDir . '/ifood_widget_' . $companyId . '_v*.js') ?: [] as $f) {
            @unlink($f);
        }
    }
}
