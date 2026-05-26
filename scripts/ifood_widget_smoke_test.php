#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smoke test da Fase 8 (Widget iFood).
 *
 * Cobre:
 *   1) getConfig sem row → defaults com enabled=false.
 *   2) saveConfig com whitelist — campo desconhecido é ignorado.
 *   3) saveConfig com widget_type inválido → rejeita.
 *   4) saveConfig com URL inválida → rejeita.
 *   5) saveConfig sucesso → cache_version bumpa para 1.
 *   6) Segundo saveConfig bumpa para 2.
 *   7) getPublicConfig não devolve campos sensíveis (allowed_origins, custom_css).
 *   8) buildMerchantUrl prefere merchant_url, cai pra slug.
 *   9) trackingUrlForOrder retorna 'desabilitado' quando enabled=false.
 *  10) trackingUrlForOrder por ifood_display_id.
 *  11) trackingUrlForOrder por ifood_order_id (UUID).
 *  12) trackingUrlForOrder por orders.id local.
 *  13) trackingUrlForOrder para ref inexistente → ok=false friendly.
 *  14) renderJsSnippet inclui config inline.
 *  15) renderJsSnippet usa cache (segunda chamada idêntica).
 *  16) Após saveConfig, cache_version no JS reflete novo valor.
 *
 * Uso: php scripts/ifood_widget_smoke_test.php [company_id]
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodWidgetService.php';

use App\Services\IFood\IFoodWidgetService;

$db = db();
$companyId = (int) ($argv[1] ?? 1);

echo "→ Smoke test widget — company={$companyId}\n";

$passed = 0;
$failed = 0;
$check = static function (string $label, bool $ok, ?string $detail = null) use (&$passed, &$failed): void {
    $out = '  ' . ($ok ? '✓ ' : '× ') . $label;
    if (!$ok && $detail !== null) {
        $out .= " [{$detail}]";
    }
    echo $out . "\n";
    $ok ? $passed++ : $failed++;
};

$cacheDir = sys_get_temp_dir() . '/ifood_widget_smoke_' . bin2hex(random_bytes(4));
@mkdir($cacheDir, 0777, true);

$cleanup = static function () use ($db, $companyId, $cacheDir) {
    $db->prepare("DELETE FROM ifood_widget_config WHERE company_id = ?")->execute([$companyId]);
    $db->prepare("DELETE FROM ifood_orders WHERE ifood_order_id LIKE 'SMOKE-WID-%'")->execute();
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '/*') ?: [] as $f) @unlink($f);
        @rmdir($cacheDir);
    }
};
$cleanup();

$service = new IFoodWidgetService($db, $cacheDir);

// 1) Config default sem row
$cfg = $service->getConfig($companyId);
$check('getConfig sem row → defaults', $cfg['enabled'] === false && $cfg['widget_type'] === 'button' && $cfg['cache_version'] === 0);

// 2) saveConfig com whitelist
$r = $service->saveConfig($companyId, [
    'enabled' => true,
    'merchant_slug' => 'wollburger',
    'button_label' => 'Pedir no iFood',
    'random_field' => 'ignored',  // não está na whitelist
]);
$check('saveConfig com whitelist → ok', $r['ok'] === true);

// 3) widget_type inválido
$r = $service->saveConfig($companyId, ['widget_type' => 'hologram']);
$check('saveConfig com widget_type inválido → rejeita', $r['ok'] === false);

// 4) URL inválida
$r = $service->saveConfig($companyId, ['merchant_url' => 'not a url']);
$check('saveConfig com merchant_url inválida → rejeita', $r['ok'] === false);

// 5) cache_version = 1 após primeiro save (após etapas 3/4 falhas que não bumpam)
$cfg = $service->getConfig($companyId);
$check('cache_version = 1 após primeiro save válido', (int) $cfg['cache_version'] === 1,
    'got=' . $cfg['cache_version']);

// 6) Segundo save bumpa
$r = $service->saveConfig($companyId, ['theme' => 'dark']);
$cfg = $service->getConfig($companyId);
$check('cache_version = 2 após segundo save', (int) $cfg['cache_version'] === 2);

// 7) getPublicConfig esconde campos sensíveis
$pub = $service->getPublicConfig($companyId);
$check('getPublicConfig NÃO inclui custom_css/allowed_origins', !isset($pub['allowed_origins']) && !isset($pub['custom_css']));
$check('getPublicConfig inclui merchant_url calculado', !empty($pub['merchant_url']));

// 8) buildMerchantUrl: prefere URL completa, cai pra slug
$urlFromSlug = $service->buildMerchantUrl(['merchant_slug' => 'X', 'merchant_url' => null]);
$urlFromFull = $service->buildMerchantUrl(['merchant_slug' => 'X', 'merchant_url' => 'https://www.ifood.com.br/delivery/sp/burger-y']);
$check('buildMerchantUrl prefere merchant_url quando setado',
    $urlFromFull === 'https://www.ifood.com.br/delivery/sp/burger-y' && $urlFromSlug !== $urlFromFull);

// 9) tracking desabilitado quando enabled=false
$db->prepare("UPDATE ifood_widget_config SET enabled = 0 WHERE company_id = ?")->execute([$companyId]);
$r = $service->trackingUrlForOrder($companyId, 'ANY');
$check('tracking quando enabled=false → ok=false', $r['ok'] === false && str_contains((string) $r['message'], 'desabilit'));

// Reabilita
$service->saveConfig($companyId, ['enabled' => true]);

// Setup pedidos para tracking lookup
$idDisplay = 'ABC' . bin2hex(random_bytes(2)); // 7 chars
$idUuid = 'SMOKE-WID-' . bin2hex(random_bytes(6));
$localOrderId = 42;
$db->prepare(
    "INSERT INTO ifood_orders
        (company_id, ifood_order_id, ifood_display_id, ifood_merchant_id, status,
         items, payments, delivered_by, order_type, order_id)
     VALUES (?, ?, ?, 'M', 'CONFIRMED', '[]', '[]', 'MERCHANT', 'DELIVERY', ?)"
)->execute([$companyId, $idUuid, $idDisplay, $localOrderId]);

// 10) por display_id
$r = $service->trackingUrlForOrder($companyId, $idDisplay);
$check('tracking por display_id', $r['ok'] === true && str_contains($r['url'], $idDisplay));

// 11) por UUID
$r = $service->trackingUrlForOrder($companyId, $idUuid);
$check('tracking por UUID', $r['ok'] === true && str_contains($r['url'], $idDisplay)); // ainda usa display_id na URL

// 12) por orders.id local
$r = $service->trackingUrlForOrder($companyId, (string) $localOrderId);
$check('tracking por orders.id local', $r['ok'] === true && str_contains($r['url'], $idDisplay));

// 13) ref inexistente
$r = $service->trackingUrlForOrder($companyId, 'NONEXISTENT-' . bin2hex(random_bytes(4)));
$check('tracking ref inexistente → ok=false friendly', $r['ok'] === false && str_contains((string) $r['message'], 'não encontrado'));

// 14) renderJsSnippet
$js = $service->renderJsSnippet($companyId);
$check('renderJsSnippet contém config inline', str_contains($js, '"enabled":true') && str_contains($js, 'iFoodWidget'));

// 15) cache hit
$jsAgain = $service->renderJsSnippet($companyId);
$check('renderJsSnippet retorna mesmo conteúdo (cache hit)', $js === $jsAgain);

// 16) Após save, cache_version no JS muda
$service->saveConfig($companyId, ['button_label' => 'Novo Label']);
$cfg = $service->getConfig($companyId);
$jsNew = $service->renderJsSnippet($companyId);
$check(
    'novo cache_version reflete no JS (' . $cfg['cache_version'] . ')',
    str_contains($jsNew, 'v' . $cfg['cache_version']) && str_contains($jsNew, 'Novo Label')
);

echo "\n  Total: {$passed} ok, {$failed} falha(s)\n";

$cleanup();
echo "→ Smoke test concluído. Estado limpo.\n";
exit($failed === 0 ? 0 : 1);
