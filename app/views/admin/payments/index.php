<?php

if (!function_exists('render_payment_method_row')) {
    function render_payment_method_row(array $method, array $pixTypeLabels, string $base)
    {
        $method = is_array($method) ? $method : [];
        $methodId = (int)($method['id'] ?? 0);
        $type = $method['type'] ?? 'others';
        $meta = is_array($method['meta'] ?? null) ? $method['meta'] : [];
        $isActive = !empty($method['active']);
        $methodJson = htmlspecialchars(json_encode($method, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  $trackClass = $isActive ? 'admin-primary-bg' : 'bg-slate-200';
        $thumbTransform = $isActive ? 'translateX(20px)' : 'translateX(0)';
  $typeBadgeClass = 'admin-primary-soft-badge';
        $typeLabel = ucfirst($type);

        ob_start();
        ?>
        <div class="pm-row flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3" data-id="<?= $methodId ?>" data-type="<?= e($type) ?>" data-method="<?= $methodJson ?>">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200 overflow-hidden">
              <?php 
              $icon = is_string($meta['icon'] ?? null) ? trim((string)$meta['icon']) : ''; 
              
              // Se é PIX e não tem ícone definido, usar o ícone PIX padrão
              if ($type === 'pix' && $icon === '') {
                $icon = 'assets/card-brands/pix.svg';
              }
              ?>
              <?php if ($icon !== ''): ?>
                <?php
                  $isAbs = preg_match('/^https?:\/\//i', $icon) === 1;
                  if ($isAbs) {
                    $src = $icon;
                  } else {
                    // Para URLs relativas (começando com / ou não), sempre usar base_url
                    $cleanIcon = ltrim($icon, '/');
                    $src = function_exists('base_url') ? base_url($cleanIcon) : '/' . $cleanIcon;
                  }
                ?>
                <img src="<?= e($src . (str_contains($src, '?') ? '&' : '?') . 'v=' . time()) ?>" alt="Bandeira" class="max-w-full max-h-full object-contain" />
              <?php else: ?>
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M7 11h10M5 15h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php endif; ?>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <div class="font-semibold text-slate-800"><?= e($method['name'] ?? '') ?></div>
                <div class="text-xs rounded-full px-2 py-1 <?= $typeBadgeClass ?>"><?= e($typeLabel) ?></div>
              </div>
              <?php if ($type === 'pix'): ?>
                <?php if (!empty($meta['px_key'])): ?><div class="text-xs text-slate-500">Chave Pix: <?= e($meta['px_key']) ?></div><?php endif; ?>
                <?php if (!empty($meta['px_key_type'])): ?><div class="text-xs text-slate-500">Tipo da chave: <?= e($pixTypeLabels[$meta['px_key_type']] ?? ucfirst($meta['px_key_type'])) ?></div><?php endif; ?>
                <?php if (!empty($meta['px_holder_name'])): ?><div class="text-xs text-slate-500">Titular: <?= e($meta['px_holder_name']) ?></div><?php endif; ?>
              <?php endif; ?>
              <div class="text-xs text-slate-500">ID #<?= $methodId ?></div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="inline-flex items-center cursor-pointer">
              <input data-id="<?= $methodId ?>" type="checkbox" class="pm-toggle sr-only" <?= $isActive ? 'checked' : '' ?> />
              <span class="pm-toggle-track w-10 h-6 <?= $trackClass ?> rounded-full relative transition-colors">
                <span class="pm-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: <?= $thumbTransform ?>"></span>
              </span>
            </label>
            <button type="button" class="pm-edit text-sm admin-primary-text hover:underline admin-primary-underline" data-id="<?= $methodId ?>">Editar</button>
            <button type="button" class="pm-delete text-sm text-red-600 hover:underline underline-offset-4 decoration-red-600" data-id="<?= $methodId ?>">Apagar</button>
          </div>
        </div>
        <?php
        return trim((string)ob_get_clean());
    }
}

$company = is_array($company ?? null) ? $company : [];
$methods = is_array($methods ?? null) ? $methods : [];
$flash   = is_array($flash ?? null) ? $flash : null;
$old     = is_array($old ?? null) ? $old : ['name' => '', 'instructions' => '', 'sort_order' => 0, 'active' => 1, 'type' => 'credit', 'meta' => []];
$errors  = is_array($errors ?? null) ? $errors : [];
$user    = $user ?? null;

$old['meta'] = is_array($old['meta'] ?? null) ? $old['meta'] : [];
$oldType = is_string($old['type'] ?? null) ? $old['type'] : 'credit';
$allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix', 'cash'];
if (!in_array($oldType, $allowedTypes, true)) {
    $oldType = 'credit';
}

// --- Normalizações e labels Pix (mantidos do branch com melhorias)
$pixTypeLabels = [
    'email' => 'E-mail',
    'cpf' => 'CPF',
    'cnpj' => 'CNPJ',
    'telefone' => 'Telefone',
    'aleatoria' => 'Chave aleatória',
];

$normaliseMeta = function ($meta) {
    if (is_string($meta)) {
        $decoded = json_decode($meta, true);
        $meta = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($meta)) {
        return [];
    }

    $clean = [];
    foreach ($meta as $k => $v) {
        if (!is_string($k)) {
            continue;
        }
        $value = trim((string)$v);
        if ($value === '') {
            continue;
        }
        $clean[$k] = $value;
    }

    return $clean;
};

foreach ($methods as &$methodItem) {
    $methodItem = is_array($methodItem) ? $methodItem : [];
    $methodItem['meta'] = $normaliseMeta($methodItem['meta'] ?? []);
    $methodItem['type'] = is_string($methodItem['type'] ?? null) ? $methodItem['type'] : 'others';
    $methodItem['name'] = (string)($methodItem['name'] ?? '');
    $methodItem['instructions'] = (string)($methodItem['instructions'] ?? '');
    $methodItem['sort_order'] = isset($methodItem['sort_order']) ? (int)$methodItem['sort_order'] : 0;
    $methodItem['active'] = !empty($methodItem['active']) ? 1 : 0;
}
unset($methodItem);

$pixMethods = array_filter($methods, fn($m) => ($m['type'] ?? '') === 'pix');
$otherMethods = array_filter($methods, fn($m) => ($m['type'] ?? '') !== 'pix');

$slug = rawurlencode((string)($company['slug'] ?? ''));
$title = $title ?? ('Métodos de pagamento - ' . ($company['name'] ?? ''));
$base  = base_url('admin/' . $slug . '/payment-methods');

$baseUrlFull = function_exists('base_url') ? (string)base_url() : '';
$baseUrlTrimmed = $baseUrlFull !== '' ? rtrim($baseUrlFull, '/') : '';
$basePath = $baseUrlFull !== '' ? rtrim((string)(parse_url($baseUrlFull, PHP_URL_PATH) ?? ''), '/') : '';

$normaliseIconPath = static function ($icon) use ($basePath) {
    if (!is_string($icon)) {
        return '';
    }
    $icon = trim($icon);
    if ($icon === '') {
        return '';
    }
    if (str_starts_with($icon, '/assets/card-brands/')) {
        return $icon;
    }
    if (str_starts_with($icon, 'assets/card-brands/')) {
        return '/' . ltrim($icon, '/');
    }
    if (preg_match('#^https?://#i', $icon)) {
        $path = parse_url($icon, PHP_URL_PATH) ?: '';
        if ($path !== '') {
            $basePathLocal = $basePath !== '' ? $basePath : '';
            if ($basePathLocal !== '' && str_starts_with($path, $basePathLocal)) {
                $path = substr($path, strlen($basePathLocal));
                if ($path === '' || $path[0] !== '/') {
                    $path = '/' . ltrim($path, '/');
                }
            }
            if (str_starts_with($path, '/assets/card-brands/')) {
                return $path;
            }
        }
    }
    return $icon;
};

$oldIconRaw = is_string($old['meta']['icon'] ?? null) ? trim((string)$old['meta']['icon']) : '';
$oldIconValue = $normaliseIconPath($oldIconRaw);
$oldIconPreview = '';
if ($oldIconValue !== '') {
    if ($oldIconRaw !== '' && preg_match('#^https?://#i', $oldIconRaw)) {
        $oldIconPreview = $oldIconRaw;
    } elseif ($baseUrlTrimmed !== '' && $oldIconValue[0] === '/') {
        $oldIconPreview = $baseUrlTrimmed . $oldIconValue;
    } else {
        $oldIconPreview = $oldIconValue;
    }
}

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do header padronizado
$pageTitle = 'Métodos de pagamento';
$pageDescription = 'Cadastre as formas de pagamento disponíveis para o cliente escolher no checkout.';
$pageIcon = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5H0zm11.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM0 11v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1z"/></svg>';
$breadcrumbs = [
    ['label' => 'Pagamentos']
];
$actions = [
    ['label' => 'Dashboard', 'url' => base_url('admin/' . $slug . '/dashboard'), 'icon' => '<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>']
];
include __DIR__ . '/../components/page-header.php';
?>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        const type = document.getElementById('pm-type');
  const pixFields = document.getElementById('pm-pix-fields');
        const nameField = document.getElementById('pm-name-field');
  const nameInput = document.getElementById('pm-name');
  const libInput = document.getElementById('pm-brand-lib-input');
  const uploadField = document.getElementById('pm-upload-field');
  const libraryField = document.getElementById('pm-library-field');

        function togglePixFields(){
          if (!type) return;
          const isPix = type.value === 'pix';
          const isCash = type.value === 'cash';
          const isFixedType = isPix || isCash; // PIX e Cash têm ícones fixos
          const libSelected = libInput && libInput.value ? true : false;
          if (pixFields) {
            pixFields.classList.toggle('hidden', !isPix);
          }
          if (nameField) {
            nameField.classList.toggle('hidden', isFixedType || libSelected);
          }
          if (uploadField) uploadField.classList.toggle('hidden', isFixedType || libSelected);
          if (libraryField) libraryField.classList.toggle('hidden', isFixedType);
          if (nameInput) {
            if (!nameInput.dataset.originalRequired) {
              nameInput.dataset.originalRequired = nameInput.hasAttribute('required') ? '1' : '0';
            }
            if (isPix) {
              nameInput.removeAttribute('required');
              if (!nameInput.value) {
                nameInput.value = 'Pix';
              }
            } else if (isCash) {
              nameInput.removeAttribute('required');
              if (!nameInput.value) {
                nameInput.value = 'Dinheiro';
              }
            } else if (nameInput.dataset.originalRequired === '1') {
              nameInput.setAttribute('required', 'required');
            }
          }
          if (typeof window.pmUpdatePixFeedback === 'function') {
            window.pmUpdatePixFeedback();
          }
        }

        window.pmTogglePixFields = togglePixFields;

        if (type){
          type.addEventListener('change', togglePixFields);
          togglePixFields();
        }
      });
    </script>

<?php if ($flash): ?>
  <div class="mb-4 rounded-xl border <?= ($flash['type'] ?? '') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> px-4 py-3 text-sm">
    <?= e($flash['message'] ?? '') ?>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
    <?php foreach ($errors as $message): ?>
      <div><?= e($message) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Adicionar novo método</h2>
    <p class="mb-4 text-sm text-slate-500">Defina o nome que será exibido para o cliente e, se necessário, descreva como o pagamento será realizado.</p>

  <form method="post" action="<?= e($base) ?>" class="grid gap-3" id="pm-create-form" enctype="multipart/form-data">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <!-- ...existing code... -->

      <label id="pm-name-field" class="grid gap-1 text-sm <?= $oldType === 'pix' ? 'hidden' : '' ?>">
        <span class="font-semibold text-slate-700">Nome da bandeira <a href="<?= e(base_url('admin/' . $slug . '/guide/payment-methods#form')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        <input id="pm-name" type="text" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Ex.: Visa, MasterCard, Pix, Dinheiro" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required autofocus aria-describedby="pm-name-help">
        <div id="pm-name-help" class="text-xs text-slate-400">Nome exibido ao cliente no checkout (ex.: Visa, Pix).</div>
      </label>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Tipo <a href="<?= e(base_url('admin/' . $slug . '/guide/payment-methods#form')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        <select name="type" id="pm-type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          <option value="credit" <?= $oldType === 'credit' ? 'selected' : '' ?>>Crédito</option>
          <option value="debit" <?= $oldType === 'debit' ? 'selected' : '' ?>>Débito</option>
          <option value="others" <?= $oldType === 'others' ? 'selected' : '' ?>>Outros</option>
          <option value="voucher" <?= $oldType === 'voucher' ? 'selected' : '' ?>>Vale-refeição</option>
          <option value="pix" <?= $oldType === 'pix' ? 'selected' : '' ?>>Pix</option>
          <option value="cash" <?= $oldType === 'cash' ? 'selected' : '' ?>>Dinheiro</option>
        </select>
      </label>

      <label id="pm-upload-field" class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Bandeira (SVG/PNG/JPG)</span>
  <div id="pm-brand-dropzone" class="rounded-xl border-2 border-dashed bg-white p-4 relative admin-primary-border" style="min-height:96px;"> <!-- usa cor primária do admin -->
          <input id="pm-brand-icon" type="file" name="brand_icon" accept=".svg,.png,.jpg,.jpeg,.webp" class="sr-only">

          <div id="pm-brand-drop-hint" class="flex flex-col items-center justify-center text-center py-6">
            <div class="text-slate-600 mb-2">Arraste arquivos para cá ou se preferir</div>
            <button type="button" id="pm-brand-choose" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm admin-primary-text admin-primary-border">anexar arquivos</button>
            <div class="text-xs text-slate-400 mt-2">Use preferencialmente SVG ou PNG quadrado até ~1MB.</div>
          </div>

          <!-- preview ocupa todo o interior quando houver imagem -->
    <img id="pm-brand-preview" src="<?= e($oldIconPreview) ?>" alt="preview"
      class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 rounded-xl <?= $oldIconPreview === '' ? 'hidden' : '' ?>"
      style="max-width:calc(100% - 12px); max-height:calc(100% - 12px); width:auto; height:auto;" />

          <!-- botão para limpar a seleção -->
          <button type="button" id="pm-brand-clear" class="absolute top-3 right-3 <?= $oldIconPreview === '' ? 'hidden' : '' ?> rounded-full bg-white text-slate-700 shadow-sm px-2 py-0.5 border admin-primary-border">✕</button>
        </div>
      </label>

      <?php if (!empty($brandLibrary)): ?>
      <div id="pm-library-field" class="grid gap-2 text-sm">
        <span class="font-semibold text-slate-700">Escolher da biblioteca</span>
        <input type="hidden" name="meta[icon]" id="pm-brand-lib-input" value="<?= e($oldIconValue) ?>">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="pm-brand-grid">
          <?php foreach ($brandLibrary as $lib):
              $libValue = $normaliseIconPath($lib['value'] ?? ($lib['url'] ?? ''));
              $isSel = $libValue !== '' && $libValue === $oldIconValue;
          ?>
            <button type="button"
                    class="pm-brand-item group rounded-xl border <?= $isSel ? 'border-indigo-500 ring-2 ring-indigo-300' : 'border-slate-200' ?> bg-white p-2 hover:border-indigo-400 hover:ring-1 hover:ring-indigo-200 flex items-center gap-2"
                    data-url="<?= e($lib['url']) ?>"
                    data-value="<?= e($libValue) ?>"
                    data-label="<?= e($lib['label']) ?>">
              <span class="inline-flex h-6 w-6 items-center justify-center overflow-hidden rounded bg-white">
                <img src="<?= e($lib['url']) ?>" alt="<?= e($lib['label']) ?>" class="max-w-full max-h-full object-contain" />
              </span>
              <span class="text-xs text-slate-700 truncate" title="<?= e($lib['label']) ?>"><?= e($lib['label']) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
        <!-- preview moved to upload field -->
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          const grid = document.getElementById('pm-brand-grid');
          const input = document.getElementById('pm-brand-lib-input');
          const prev = document.getElementById('pm-brand-preview');
          const file = document.getElementById('pm-brand-icon');
          const nameField = document.getElementById('pm-name-field');
          const uploadField = document.getElementById('pm-upload-field');
          const libraryField = document.getElementById('pm-library-field');
          const nameInput = document.getElementById('pm-name');
          const typeSelect = document.getElementById('pm-type');
          const siteBase = '<?= e($baseUrlTrimmed) ?>';
          const basePath = '<?= e($basePath) ?>';

          function normaliseIcon(value) {
            value = (value || '').trim();
            if (!value) return '';
            if (value.startsWith('/assets/card-brands/')) return value;
            if (value.startsWith('assets/card-brands/')) return '/' + value.replace(/^\/+/,'');
            if (/^https?:\/\//i.test(value)) {
              try {
                const url = new URL(value, window.location.origin);
                let path = url.pathname || '';
                if (basePath && path.startsWith(basePath)) {
                  path = path.slice(basePath.length);
                  if (!path.startsWith('/')) {
                    path = '/' + path;
                  }
                }
                if (path.startsWith('/assets/card-brands/')) {
                  return path;
                }
              } catch (_) {}
            }
            return value;
          }

          function buildPreviewUrl(value) {
            if (!value) return '';
            if (/^https?:\/\//i.test(value)) return value;
            if (value.startsWith('/')) {
              return siteBase ? (siteBase + value) : value;
            }
            return value;
          }

          function selectBrand(value, label){
            if (!input) return;
            const normalised = normaliseIcon(value);
            input.value = normalised;
            let previewUrl = '';
            if (grid) {
              grid.querySelectorAll('.pm-brand-item').forEach(btn => {
                const btnValue = normaliseIcon(btn.dataset.value || btn.dataset.url || '');
                const on = !!normalised && btnValue === normalised;
                btn.classList.toggle('border-indigo-500', on);
                btn.classList.toggle('ring-2', on);
                btn.classList.toggle('ring-indigo-300', on);
                btn.classList.toggle('border-slate-200', !on);
                btn.classList.toggle('ring-0', !on);
                if (on && !previewUrl) {
                  previewUrl = btn.dataset.url || '';
                  if (!label) {
                    label = btn.getAttribute('data-label') || '';
                  }
                }
              });
            }
            if (!previewUrl) {
              previewUrl = buildPreviewUrl(normalised);
            }
            if (prev) {
              if (previewUrl) {
                prev.src = previewUrl;
                prev.classList.remove('hidden');
              } else {
                prev.src = '';
                prev.classList.add('hidden');
              }
            }
            const libSelected = !!normalised;
            if (nameField) {
              const isPix = typeSelect && typeSelect.value === 'pix';
              nameField.classList.toggle('hidden', libSelected || isPix);
            }
            if (uploadField) {
              const isPix = typeSelect && typeSelect.value === 'pix';
              uploadField.classList.toggle('hidden', libSelected || isPix);
            }
            if (libraryField) {
              const isPixNow = typeSelect && typeSelect.value === 'pix';
              libraryField.classList.toggle('hidden', !!isPixNow);
            }
            if (nameInput) {
              if (!nameInput.dataset.originalRequired) {
                nameInput.dataset.originalRequired = nameInput.hasAttribute('required') ? '1' : '0';
              }
              if (libSelected) {
                nameInput.removeAttribute('required');
              } else if (nameInput.dataset.originalRequired === '1' && !(typeSelect && (typeSelect.value === 'pix' || typeSelect.value === 'cash'))) {
                nameInput.setAttribute('required', 'required');
              }
              if (libSelected && label) {
                nameInput.value = label;
              }
            }
          }

          try { window.pmSelectBrand = selectBrand; window.pmNormalizeBrandIcon = normaliseIcon; } catch(_) {}

          if (grid) {
            grid.querySelectorAll('.pm-brand-item').forEach(btn => {
              btn.addEventListener('click', function(){
                const btnVal = normaliseIcon(this.dataset.value || this.dataset.url || '');
                const current = (input && input.value) ? input.value.trim() : '';
                const label = this.dataset.label || '';
                if (btnVal && current && btnVal === current) {
                  // já selecionado: desmarcar e restaurar campos
                  selectBrand('');
                  if (file) file.value = '';
                } else {
                  selectBrand(this.dataset.value || this.dataset.url || '', label);
                  if (file) file.value = '';
                }
              });
            });
          }
          if (file) {
            file.addEventListener('change', function(){
              if (this.files && this.files.length > 0) {
                // limpa seleção da biblioteca
                selectBrand('');
                try { if (this.files && this.files.length > 0 && prev) {
                  const f = this.files[0];
                  const url = URL.createObjectURL(f);
                  prev.src = url;
                  prev.classList.remove('hidden');
                  // revogar o objectURL depois de carregado para liberar memória
                  prev.onload = () => { try { URL.revokeObjectURL(url); } catch(_){} };
                } } catch(_){}
              }
            });
          }
          if (input && input.value) {
            selectBrand(input.value);
          }
          if (libraryField && typeSelect && (typeSelect.value === 'pix' || typeSelect.value === 'cash')) {
            libraryField.classList.add('hidden');
            if (uploadField) uploadField.classList.add('hidden');
          }
        });
      </script>
      <?php endif; ?>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Instruções (opcional) <a href="<?= e(base_url('admin/' . $slug . '/guide/payment-methods#form')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></span>
        <textarea name="instructions" rows="3" placeholder="Recados exibidos após a escolha do cliente" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= e($old['instructions'] ?? '') ?></textarea>
      </label>

      <div id="pm-pix-fields" class="<?= $oldType === 'pix' ? 'grid gap-2' : 'hidden grid gap-2' ?>">
        <h3 class="text-sm font-semibold">Credenciais Pix <a href="<?= e(base_url('admin/' . $slug . '/guide/payment-methods#pix')) ?>" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></h3>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Chave Pix</span>
          <input id="pm-pix-key" type="text" name="meta[px_key]" value="<?= e($old['meta']['px_key'] ?? '') ?>" placeholder="Ex.: 11999999999 ou chave aleatória" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
          <div id="pm-pix-key-feedback" class="text-xs text-slate-400">
            <?php if (!empty($old['meta']['px_key_type'])): ?>
              Tipo identificado: <?= e($pixTypeLabels[$old['meta']['px_key_type']] ?? ucfirst($old['meta']['px_key_type'])) ?>
            <?php else: ?>
              Informe a chave para identificar automaticamente o tipo.
            <?php endif; ?>
          </div>
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Provedor (opcional)</span>
          <input type="text" name="meta[px_provider]" value="<?= e($old['meta']['px_provider'] ?? '') ?>" placeholder="Ex.: Gerencianet, Pagar.me" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Nome do titular Pix</span>
          <input type="text" name="meta[px_holder_name]" value="<?= e($old['meta']['px_holder_name'] ?? '') ?>" placeholder="Ex.: João da Silva" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>
      </div>

      <div class="grid gap-3 sm:grid-cols-1">
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Ordem de exibição</span>
          <input type="number" name="sort_order" value="<?= e($old['sort_order'] ?? 0) ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </label>
      </div>

      <input type="hidden" name="method_id" id="pm-method-id" value="<?= isset($old['id']) ? (int)$old['id'] : '' ?>">

      <div class="flex gap-3">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span id="pm-submit-label">Adicionar método</span>
        </button>
        <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Cancelar</a>
      </div>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Métodos cadastrados</h2>
    <div class="mt-1 h-0.5 w-12 rounded admin-primary-bg"></div>
    <!-- Abas de tipos -->
    <div class="mt-3 flex items-center gap-6 text-sm">
      <button type="button" class="pm-tab admin-primary-text underline underline-offset-8 decoration-2 admin-primary-underline" data-type="credit">Crédito</button>
      <button type="button" class="pm-tab text-slate-500 hover:text-slate-700" data-type="debit">Débito</button>
      <button type="button" class="pm-tab text-slate-500 hover:text-slate-700" data-type="others">Outros</button>
      <button type="button" class="pm-tab text-slate-500 hover:text-slate-700" data-type="voucher">Vale-refeição</button>
      <button type="button" class="pm-tab text-slate-500 hover:text-slate-700" data-type="pix">Pix</button>
      <button type="button" class="pm-tab text-slate-500 hover:text-slate-700" data-type="cash">Dinheiro</button>
    </div>

    <!-- Cabeçalho da lista -->
    <div class="mt-3 flex items-center justify-between">
      <div class="text-sm font-semibold text-slate-700">Bandeira</div>
      <div class="flex items-center gap-3 text-sm text-slate-600">
        <span>Ativar todas</span>
        <label class="inline-flex items-center cursor-pointer">
          <input id="pm-toggle-all" type="checkbox" class="sr-only">
          <span class="pm-toggle-all-track w-10 h-6 bg-slate-200 rounded-full relative transition-colors">
            <span class="pm-toggle-all-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(0)"></span>
          </span>
        </label>
      </div>
    </div>

    <div class="mt-4 space-y-4">
      <div id="pm-pix-block" class="<?= $pixMethods ? '' : 'hidden' ?>">
        <h3 class="mb-2 text-sm font-semibold text-slate-700">Pix</h3>
        <div class="space-y-2" id="pm-pix-list">
          <?php foreach ($pixMethods as $method): ?>
            <?= render_payment_method_row($method, $pixTypeLabels, $base) ?>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="space-y-2" id="pm-list">
        <?php foreach ($otherMethods as $method): ?>
          <?= render_payment_method_row($method, $pixTypeLabels, $base) ?>
        <?php endforeach; ?>
      </div>

      <div id="pm-empty" class="<?= $methods ? 'hidden' : 'text-sm text-slate-500' ?>">
        Ainda não há métodos cadastrados. Utilize o formulário ao lado para iniciar.
      </div>
    </div>

        <script>
      (function(){
  const base = '<?= $base ?>';
  const siteBase = '<?= e(rtrim(base_url(), '/')) ?>';
        const csrftoken = <?= function_exists('csrf_token') ? ('"' . addslashes(csrf_token()) . '"') : 'null' ?>;
  const list = document.getElementById('pm-list');
        const pixList = document.getElementById('pm-pix-list');
        const pixBlock = document.getElementById('pm-pix-block');
        const emptyMessage = document.getElementById('pm-empty');
  const tabs = document.querySelectorAll('.pm-tab');
  let currentType = 'credit';
        const toggleAll = document.getElementById('pm-toggle-all');
        const toggleAllTrack = document.querySelector('.pm-toggle-all-track');
        const toggleAllThumb = document.querySelector('.pm-toggle-all-thumb');
        const form = document.getElementById('pm-create-form');
        const typeSelect = document.getElementById('pm-type');
        const nameInput = document.getElementById('pm-name');
        const pixKeyInput = document.getElementById('pm-pix-key');
        const pixKeyFeedback = document.getElementById('pm-pix-key-feedback');
        const pixProviderInput = document.querySelector('input[name="meta[px_provider]"]');
        const pixHolderInput = document.querySelector('input[name="meta[px_holder_name]"]');
        const methodIdInput = document.getElementById('pm-method-id');
    // expõe para outros scripts (ex.: modal)
    try { window.PM_BASE = base; window.PM_CSRF = csrftoken; } catch (e) {}
        const submitLabel = document.getElementById('pm-submit-label');
        const instructionsInput = document.querySelector('textarea[name="instructions"]');
        const sortOrderInput = document.querySelector('input[name="sort_order"]');
  // active é controlado apenas na lista; não há mais input 'active' no formulário
        let editingId = null;
        let defaultSortOrder = sortOrderInput ? sortOrderInput.value : '';

        const pixTypeLabels = <?= json_encode($pixTypeLabels, JSON_UNESCAPED_UNICODE) ?>;

        function normalizeIcon(value) {
          if (typeof window.pmNormalizeBrandIcon === 'function') {
            return window.pmNormalizeBrandIcon(value);
          }
          value = (value || '').trim();
          if (!value) return '';
          if (value.startsWith('assets/card-brands/')) {
            return '/' + value.replace(/^\/+/,'');
          }
          return value.startsWith('/assets/card-brands/') ? value : value;
        }

        function detectPixKeyType(key) {
          key = (key || '').trim();
          if (!key) return '';
          if (/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(key)) return 'email';
          const digits = key.replace(/\D+/g, '');
          if (digits.length === 11) return 'cpf';
          if (digits.length === 14) return 'cnpj';
          if (digits.length >= 10 && digits.length <= 13) return 'telefone';
          return 'aleatoria';
        }

        function formatPixKeyType(type) {
          if (!type) return '';
          return pixTypeLabels[type] || (type.charAt(0).toUpperCase() + type.slice(1));
        }

        function escapeHtml(value) {
          return (value == null ? '' : String(value))
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        function resolveIconUrl(url) {
          url = (url || '').trim();
          if (!url) return '';
          // URLs absolutas (http(s)) retornam como estão
          if (/^https?:\/\//i.test(url)) return url;
          // caminhos começando com '/' devem respeitar o base da aplicação
          if (url.startsWith('/')) {
            return siteBase ? (siteBase + url) : url;
          }
          // caminhos relativos: prefixa com siteBase se disponível, senão torna absoluto a partir da raiz
          return siteBase ? (siteBase + '/' + url.replace(/^\//, '')) : '/' + url.replace(/^\//, '');
        }

        try { window.pmResolveIconUrl = resolveIconUrl; } catch(_) {}

        function updatePixKeyFeedback() {
          if (!pixKeyFeedback) return;
          const key = pixKeyInput ? pixKeyInput.value : '';
          if (!key) {
            pixKeyFeedback.textContent = 'Informe a chave para identificar automaticamente o tipo.';
            return;
          }
          const detected = detectPixKeyType(key);
          pixKeyFeedback.textContent = 'Tipo identificado: ' + formatPixKeyType(detected || 'aleatoria');
        }

        window.pmUpdatePixFeedback = updatePixKeyFeedback;

        if (pixKeyInput) {
          pixKeyInput.addEventListener('input', updatePixKeyFeedback);
          updatePixKeyFeedback();
        }

        function updateEmptyStates() {
          if (emptyMessage && list) {
            let total = 0;
            if (currentType === 'pix') {
              total = pixList ? pixList.children.length : 0;
            } else {
              total = Array.from(list.children).filter(ch => ch.style.display !== 'none').length;
            }
            emptyMessage.classList.toggle('hidden', total > 0);
          }
        }

        function setActiveTabVisual(type) {
          tabs.forEach(btn => {
            const isActive = btn.dataset.type === type;
            btn.classList.toggle('admin-primary-text', isActive);
            btn.classList.toggle('underline', isActive);
            btn.classList.toggle('decoration-2', isActive);
            btn.classList.toggle('admin-primary-underline', isActive);
            btn.classList.toggle('underline-offset-8', isActive);
            btn.classList.toggle('text-slate-500', !isActive);
          });
        }

        function applyTypeFilter() {
          if (!list) return;
          if (currentType === 'pix') {
            // esconde lista principal e mostra bloco Pix (se houver itens)
            Array.from(list.children).forEach(row => { row.style.display = 'none'; });
            if (pixBlock && pixList) {
              pixBlock.classList.toggle('hidden', pixList.children.length === 0);
            }
          } else {
            // mostra apenas itens do tipo selecionado e esconde bloco Pix
            Array.from(list.children).forEach(row => {
              const type = row.dataset.type || 'others';
              row.style.display = (type === currentType) ? '' : 'none';
            });
            if (pixBlock) pixBlock.classList.add('hidden');
          }
          updateEmptyStates();
        }

        tabs.forEach(btn => {
          btn.addEventListener('click', function(){
            currentType = this.dataset.type || 'credit';
            setActiveTabVisual(currentType);
            applyTypeFilter();
            // Sincronizar o select de tipo do formulário com a aba selecionada
            if (typeSelect && currentType) {
              typeSelect.value = currentType;
              // Disparar evento change para atualizar campos dependentes (ex.: campos Pix)
              typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
          });
        });

        function setToggleVisual(track, on) {
          if (!track) return;
          track.classList.toggle('bg-rose-500', false);
          track.classList.toggle('admin-primary-bg', !!on);
          track.classList.toggle('bg-slate-200', !on);
          const thumb = track.querySelector('.pm-toggle-thumb');
          if (thumb) {
            thumb.style.transform = on ? 'translateX(20px)' : 'translateX(0)';
          }
        }

        function setToggleAllVisual(on) {
          if (!toggleAllTrack || !toggleAllThumb) return;
          toggleAllTrack.classList.toggle('bg-rose-500', false);
          toggleAllTrack.classList.toggle('admin-primary-bg', !!on);
          toggleAllTrack.classList.toggle('bg-slate-200', !on);
          toggleAllThumb.style.transform = on ? 'translateX(20px)' : 'translateX(0)';
        }

        function refreshToggleAllState() {
          // no-op: o estado do "Ativar todas" não deve ser atualizado automaticamente por toggles individuais
          return;
        }

        function parseMethodData(node) {
          if (!node) return null;
          try {
            return JSON.parse(node.dataset.method || '{}');
          } catch (err) {
            return null;
          }
        }

        function wireRowInteractions(row) {
          if (!row) return;
          const checkbox = row.querySelector('.pm-toggle');
          if (checkbox && !checkbox.dataset.wired) {
            checkbox.dataset.wired = '1';
            checkbox.addEventListener('change', function(){
              const id = this.dataset.id;
              const on = this.checked;
              const track = row.querySelector('.pm-toggle-track');
              setToggleVisual(track, on);
              toggleMethod(id, on, row, function(success){
                if (!success) {
                  checkbox.checked = !on;
                  setToggleVisual(track, checkbox.checked);
                }
              });
            });
          }

          // garantir que clicar na trilha também acione o toggle (sem duplicar)
          const track = row.querySelector('.pm-toggle-track');
          if (track && !track.dataset.wired) {
            track.dataset.wired = '1';
            track.addEventListener('click', function(e){
              // prevenir comportamento padrão e controlar toggle manualmente
              e.preventDefault();
              e.stopPropagation();
              const cb = row.querySelector('.pm-toggle');
              if (!cb) return;
              const newState = !cb.checked;
              cb.checked = newState;
              // disparar change para reutilizar lógica existente
              cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
          }

          const editBtn = row.querySelector('.pm-edit');
          if (editBtn && !editBtn.dataset.wired) {
            editBtn.dataset.wired = '1';
            editBtn.addEventListener('click', function(){
              const data = parseMethodData(row);
              if (data && typeof window.fillFormWithMethod === 'function') {
                // expõe a função no escopo global para ser chamada aqui
                window.fillFormWithMethod(data);
              } else if (data) {
                // fallback: chama a função local se estiver acessível
                try { fillFormWithMethod(data); } catch(_) {}
              }
            });
          }

          const deleteBtn = row.querySelector('.pm-delete');
          if (deleteBtn && !deleteBtn.dataset.wired) {
            deleteBtn.dataset.wired = '1';
            deleteBtn.addEventListener('click', function(){
              const id = this.dataset.id;
              if (!confirm('Confirma remoção deste método de pagamento?')) return;
              deleteMethod(id, row);
            });
          }
        }

        function ensureMethodMeta(method) {
          if (!method) return {};
          let meta = method.meta;
          if (typeof meta === 'string') {
            try {
              meta = JSON.parse(meta) || {};
            } catch (_) {
              meta = {};
            }
          } else if (!meta || typeof meta !== 'object') {
            meta = {};
          }
          method.meta = meta;
          return meta;
        }

        function createPixInfo(method) {
          if (method.type !== 'pix') return '';
          const meta = ensureMethodMeta(method);
          let html = '';
          if (meta.px_key) {
            html += `
                <div class="text-xs text-slate-500">Chave Pix: ${escapeHtml(meta.px_key)}</div>`;
          }
          if (meta.px_key_type) {
            html += `
                <div class="text-xs text-slate-500">Tipo da chave: ${escapeHtml(formatPixKeyType(meta.px_key_type))}</div>`;
          }
          if (meta.px_holder_name) {
            html += `
                <div class="text-xs text-slate-500">Titular: ${escapeHtml(meta.px_holder_name)}</div>`;
          }
          return html;
        }

        function renderMethodRow(method) {
          const id = parseInt(method.id, 10);
          const type = method.type || 'others';
          const isActive = parseInt(method.active, 10) === 1;
          const meta = ensureMethodMeta(method);
          const div = document.createElement('div');
          div.className = 'pm-row flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3';
          div.dataset.id = String(id);
          div.dataset.type = type;
          div.dataset.method = JSON.stringify(method);
          const typeBadge = 'admin-primary-soft-badge';
          const iconValueRaw = meta && meta.icon ? meta.icon : '';
          const iconValue = normalizeIcon(iconValueRaw || (typeof method.icon === 'string' ? method.icon : ''));
          if (meta) {
            meta.icon = iconValue;
          }
          let iconUrl = (typeof method.icon_url === 'string' && method.icon_url.trim() !== '') ? method.icon_url : '';
          if (!iconUrl && iconValue) {
            iconUrl = resolveIconUrl(iconValue);
          } else if (!iconUrl && iconValueRaw) {
            iconUrl = resolveIconUrl(iconValueRaw);
          }

          if (iconUrl) {
            const sep = iconUrl.includes('?') ? '&' : '?';
            iconUrl = iconUrl + sep + 'v=' + Date.now();
          }
          div.innerHTML = `
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200 overflow-hidden">
                ${iconUrl ? `<img src="${escapeHtml(iconUrl)}" alt="Bandeira" class="max-w-full max-h-full object-contain" />` : `<svg class=\"h-4 w-4 text-slate-500\" viewBox=\"0 0 24 24\" fill=\"none\"><path d=\"M3 7h18M7 11h10M5 15h14\" stroke=\"currentColor\" stroke-width=\"1.4\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>`}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <div class="font-semibold text-slate-800">${escapeHtml(method.name || '')}</div>
                  <div class="text-xs rounded-full px-2 py-1 ${typeBadge}">${escapeHtml(type.charAt(0).toUpperCase() + type.slice(1))}</div>
                </div>${createPixInfo(method)}
                <div class="text-xs text-slate-500">ID #${id}</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center cursor-pointer">
                <input data-id="${id}" type="checkbox" class="pm-toggle sr-only" ${isActive ? 'checked' : ''} />
                <span class="pm-toggle-track w-10 h-6 ${isActive ? 'admin-primary-bg' : 'bg-slate-200'} rounded-full relative transition-colors">
                  <span class="pm-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: ${isActive ? 'translateX(20px)' : 'translateX(0)'}"></span>
                </span>
              </label>
              <button type="button" class="pm-edit text-sm text-slate-500" data-id="${id}">Editar</button>
              <button type="button" class="pm-delete text-sm text-red-600" data-id="${id}">Apagar</button>
            </div>
          `;
          wireRowInteractions(div);
          return div;
        }

        function replaceMethodRow(method) {
          const id = String(method.id);
          const newRow = renderMethodRow(method);
          const existing = document.querySelector('.pm-row[data-id="' + id + '"]');
          const target = method.type === 'pix' ? pixList : list;
          if (existing && existing.parentNode) {
            const parent = existing.parentNode;
            const nextSibling = existing.nextSibling;
            existing.parentNode.removeChild(existing);
            if (target && parent === target && nextSibling) {
              target.insertBefore(newRow, nextSibling);
            } else if (target && parent === target) {
              target.appendChild(newRow);
            } else if (target) {
              target.insertBefore(newRow, target.firstChild);
            }
          } else if (target) {
            target.insertBefore(newRow, target.firstChild);
          }
          updateEmptyStates();
          // re-aplica filtro de tipo para respeitar a aba atual
          if (typeof applyTypeFilter === 'function') applyTypeFilter();
          refreshToggleAllState();
          return newRow;
        }

  function fillFormWithMethod(method) {
          if (!form) return;
          editingId = parseInt(method.id, 10) || null;
          form.action = editingId ? (base + '/' + editingId) : base;
          if (methodIdInput) {
            methodIdInput.value = editingId ? editingId : '';
          }
          const meta = ensureMethodMeta(method);
          if (nameInput) {
            nameInput.value = method.type === 'pix' ? 'Pix' : method.type === 'cash' ? 'Dinheiro' : (method.name || '');
          }
          if (instructionsInput) {
            instructionsInput.value = method.instructions || '';
          }
          if (sortOrderInput) {
            sortOrderInput.value = method.sort_order != null ? method.sort_order : '';
          }
          // não ajusta 'active' no formulário; controle apenas via lista
          if (typeSelect) {
            typeSelect.value = method.type || 'others';
          }
          if (pixKeyInput) {
            pixKeyInput.value = meta && meta.px_key ? meta.px_key : '';
          }
          if (pixProviderInput) {
            pixProviderInput.value = meta && meta.px_provider ? meta.px_provider : '';
          }
          if (pixHolderInput) {
            pixHolderInput.value = meta && meta.px_holder_name ? meta.px_holder_name : '';
          }
          // sincroniza biblioteca de ícones
          const brandInput = document.getElementById('pm-brand-lib-input');
          const brandPrev = document.getElementById('pm-brand-preview');
          const brandFile = document.getElementById('pm-brand-icon');
          const rawIcon = meta && meta.icon ? meta.icon : '';
          const normalisedIcon = normalizeIcon(rawIcon);
          // Prioriza a URL completa enviada pelo servidor (method.icon_url), quando disponível
          let previewUrl = '';
          if (typeof method.icon_url === 'string' && method.icon_url.trim() !== '') {
            previewUrl = method.icon_url;
          } else if (normalisedIcon) {
            previewUrl = resolveIconUrl(normalisedIcon);
          } else if (rawIcon) {
            previewUrl = resolveIconUrl(rawIcon);
          }
          if (brandInput) brandInput.value = normalisedIcon;
          if (brandPrev) {
            if (previewUrl) {
              brandPrev.src = previewUrl;
              brandPrev.classList.remove('hidden');
            } else {
              brandPrev.src = '';
              brandPrev.classList.add('hidden');
            }
          }
          if (brandFile) brandFile.value = '';
          // Encontra o label correto a partir do grid da biblioteca (para garantir alinhamento ícone/label)
          let labelForIcon = '';
          try {
            const grid = document.getElementById('pm-brand-grid');
            const targetValue = normalizeIcon(normalisedIcon);
            if (grid && targetValue) {
              grid.querySelectorAll('.pm-brand-item').forEach(btn => {
                if (labelForIcon) return;
                const btnValue = normalizeIcon(btn.dataset.value || btn.dataset.url || '');
                if (btnValue && btnValue === targetValue) {
                  labelForIcon = (btn.getAttribute('data-label') || '').trim();
                }
              });
            }
          } catch(_) {}
          // Usa a mesma função de seleção usada no clique da biblioteca para garantir o mesmo destaque/ocultação
          try { if (typeof window.pmSelectBrand === 'function') window.pmSelectBrand(normalisedIcon || '', labelForIcon); } catch(_) {}
          if (submitLabel) {
            submitLabel.textContent = 'Atualizar método';
          }
          if (typeof window.pmTogglePixFields === 'function') {
            window.pmTogglePixFields();
          }
          updatePixKeyFeedback();
          if (form.scrollIntoView) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
  }
  // expõe para uso fora do escopo imediato (ex.: handlers de linha)
  try { window.fillFormWithMethod = fillFormWithMethod; } catch(_) {}

  function resetForm(nextSortOrder) {
          if (!form) return;
          editingId = null;
          form.action = base;
          if (submitLabel) {
            submitLabel.textContent = 'Adicionar método';
          }
          if (methodIdInput) {
            methodIdInput.value = '';
          }
          if (nameInput) {
            nameInput.value = '';
          }
          if (instructionsInput) {
            instructionsInput.value = '';
          }
          if (sortOrderInput) {
            sortOrderInput.value = nextSortOrder !== undefined ? nextSortOrder : defaultSortOrder;
          }
          // sem campo 'active' no formulário
          if (typeSelect) {
            typeSelect.value = 'credit';
          }
          if (pixKeyInput) {
            pixKeyInput.value = '';
          }
          if (pixProviderInput) {
            pixProviderInput.value = '';
          }
          if (pixHolderInput) {
            pixHolderInput.value = '';
          }
          // reset biblioteca de ícones
          const brandInput = document.getElementById('pm-brand-lib-input');
          const brandPrev = document.getElementById('pm-brand-preview');
          const brandFile = document.getElementById('pm-brand-icon');
          if (brandFile) brandFile.value = '';
          if (typeof window.pmSelectBrand === 'function') {
            window.pmSelectBrand('');
          } else {
            if (brandInput) brandInput.value = '';
            if (brandPrev) {
              brandPrev.src = '';
              brandPrev.classList.add('hidden');
            }
            const grid = document.getElementById('pm-brand-grid');
            if (grid) {
              grid.querySelectorAll('.pm-brand-item').forEach(btn => {
                btn.classList.remove('border-indigo-500','ring-2','ring-indigo-300');
                btn.classList.add('border-slate-200');
              });
            }
          }
          if (typeof window.pmTogglePixFields === 'function') {
            window.pmTogglePixFields();
          }
          updatePixKeyFeedback();
  }
  try { window.pmResetForm = resetForm; } catch(_) {}

        function applyToggleStateToRow(row, on) {
          if (!row) return;
          const checkbox = row.querySelector('.pm-toggle');
          if (checkbox) {
            checkbox.checked = !!on;
          }
          const track = row.querySelector('.pm-toggle-track');
          setToggleVisual(track, on);
          // sem botão textual; nada a atualizar além do visual do toggle
        }

        async function toggleMethod(id, on, row, cb) {
          try {
            const url = base + '/' + id;
            const body = new URLSearchParams();
            body.append('active', on ? '1' : '0');
            if (csrftoken) body.append('csrf_token', csrftoken);
            const res = await fetch(url, {
              method: 'POST',
              body,
              credentials: 'same-origin',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) throw new Error('Network');
            const json = await res.json().catch(() => null);
            if (!json || !json.success) throw new Error('Invalid response');
            if (json.method) {
              replaceMethodRow(json.method);
            } else if (row) {
              applyToggleStateToRow(row, on);
            }
            if (json.message) {
              showToast(json.message, 'success');
            }
            if (cb) cb(true);
            return true;
          } catch (err) {
            console.error(err);
            showToast('Erro ao atualizar o método. Atualize a página e tente novamente.', 'error');
            if (cb) cb(false);
            return false;
          } finally {
            // master permanece independente
          }
        }

        async function deleteMethod(id, row) {
          try {
            const url = base + '/' + id + '/delete';
            const body = new URLSearchParams();
            if (csrftoken) body.append('csrf_token', csrftoken);
            const res = await fetch(url, {
              method: 'POST',
              body,
              credentials: 'same-origin',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) throw new Error('Network');
            const json = await res.json().catch(() => null);
            if (!json || !json.success) throw new Error('Invalid response');
            // remove DOM row
            if (row && row.parentNode) {
              row.parentNode.removeChild(row);
            }
            updateEmptyStates();
            refreshToggleAllState();
            if (json.message) showToast(json.message, 'success');
            return true;
          } catch (err) {
            console.error(err);
            showToast('Erro ao apagar o método. Atualize a página e tente novamente.', 'error');
            return false;
          }
        }

        if (toggleAll) {
          toggleAll.addEventListener('change', async function(){
            const on = this.checked;
            setToggleAllVisual(on);
            try {
              const url = base + '/batch';
              const body = new URLSearchParams();
              body.append('active', on ? '1' : '0');
              if (csrftoken) body.append('csrf_token', csrftoken);
              const res = await fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });
              if (!res.ok) throw new Error('Network');
              const json = await res.json().catch(() => null);
              if (!json || !json.success) throw new Error('Batch failed');
              document.querySelectorAll('.pm-row').forEach(function(row){
                applyToggleStateToRow(row, on);
              });
              if (json.message) showToast(json.message, 'success');
            } catch (err) {
              console.error(err);
              showToast('Erro ao atualizar todos os métodos. Atualize a página e tente novamente.', 'error');
              this.checked = !on;
              setToggleAllVisual(this.checked);
            }
          });
        }

        if (form) {
          form.addEventListener('submit', async function(e){
            e.preventDefault();
            const data = new FormData(form);
            // garante que a seleção da biblioteca siga no payload
            try {
              const lib = document.getElementById('pm-brand-lib-input');
              const normalizedIcon = lib ? normalizeIcon(lib.value || '') : '';
              if (normalizedIcon) {
                data.set('meta[icon]', normalizedIcon);
              }
              // se nome vazio e biblioteca selecionada, tentar preencher o nome com o label da opção
              const nameEl = document.getElementById('pm-name');
              if (nameEl && (!nameEl.value || nameEl.value.trim()==='') && normalizedIcon) {
                let label = '';
                const grid = document.getElementById('pm-brand-grid');
                if (grid) {
                  grid.querySelectorAll('.pm-brand-item').forEach(btn => {
                    if (label) return;
                    const btnValue = normalizeIcon(btn.dataset.value || btn.dataset.url || '');
                    if (btnValue && btnValue === normalizedIcon) {
                      label = (btn.getAttribute('data-label') || '').trim();
                    }
                  });
                }
                if (label) data.set('name', label);
              }
            } catch(_) {}
            const editing = methodIdInput ? methodIdInput.value : '';
            try {
              const res = await fetch(form.action, {
                method: form.method || 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });
              if (!res.ok) throw new Error('Network');
              const ct = res.headers.get('content-type') || '';
              if (ct.includes('application/json')) {
                const json = await res.json().catch(() => null);
                if (!json) throw new Error('Invalid response');
                if (!json.success) {
                  // mostra mensagem amigável quando backend reporta erro (ex.: duplicata)
                  if (json.message) {
                    showToast(json.message, 'error');
                    return;
                  }
                  throw new Error('Invalid response');
                }
                if (!json.method) throw new Error('Invalid response');
                const inserted = replaceMethodRow(json.method);
                // atualizar preview do formulário para refletir o ícone retornado pelo servidor
                try {
                  const brandPrev = document.getElementById('pm-brand-preview');
                  if (brandPrev) {
                    if (typeof json.method.icon_url === 'string' && json.method.icon_url.trim() !== '') {
                      brandPrev.src = json.method.icon_url;
                      brandPrev.classList.remove('hidden');
                    } else if (json.method.meta && json.method.meta.icon) {
                      brandPrev.src = resolveIconUrl(normalizeIcon(json.method.meta.icon));
                      brandPrev.classList.remove('hidden');
                    } else {
                      brandPrev.src = '';
                      brandPrev.classList.add('hidden');
                    }
                  }
                } catch(_) {}
                // foca na aba do tipo salvo e destaca o item
                try {
                  currentType = json.method.type || currentType;
                  setActiveTabVisual(currentType);
                  applyTypeFilter();
                  if (inserted && inserted.scrollIntoView) inserted.scrollIntoView({ behavior: 'smooth', block: 'center' });
                  if (inserted) {
                    inserted.classList.add('ring-2','ring-indigo-300');
                    setTimeout(() => inserted.classList.remove('ring-2','ring-indigo-300'), 1500);
                  }
                } catch(_) {}
                if (json.message) showToast(json.message, 'success');
                if (!editing && json.method && json.method.sort_order !== undefined) {
                  const nextSort = (parseInt(json.method.sort_order, 10) || 0) + 1;
                  defaultSortOrder = String(nextSort);
                  resetForm(defaultSortOrder);
                } else {
                  resetForm();
                }
              } else {
                // Conteúdo não-JSON com 200 OK: tratar como sucesso e recarregar
                window.location.reload();
                return;
              }
                } catch (err) {
              console.error(err);
              // fallback não-AJAX
              form.submit();
            }
          });
        }

  document.querySelectorAll('.pm-row').forEach(wireRowInteractions);
  // define visual da aba inicial e aplica filtro
  setActiveTabVisual(currentType);
  applyTypeFilter();
  updateEmptyStates();
        // master permanece independente
      })();
    </script>
  </section>
</div>

</div>

<!-- Modal de edição de método de pagamento -->
<div id="pm-edit-modal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="absolute inset-0 flex items-start justify-center p-4 sm:p-6 overflow-auto">
    <div class="mt-6 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
        <h3 class="text-base font-semibold text-slate-800">Editar método</h3>
        <button type="button" id="pm-edit-close" class="text-slate-500 hover:text-slate-700">✕</button>
      </div>
      <form id="pm-edit-modal-form" class="p-5 grid gap-3">
        <input type="hidden" id="pm-edit-id" value="">
        <label id="pm-edit-name-field" class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Nome da bandeira</span>
          <input id="pm-edit-name" type="text" name="name" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm" placeholder="Ex.: Visa, MasterCard">
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Tipo</span>
          <select id="pm-edit-type" name="type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
            <option value="credit">Crédito</option>
            <option value="debit">Débito</option>
            <option value="others">Outros</option>
            <option value="voucher">Vale-refeição</option>
            <option value="pix">Pix</option>
            <option value="cash">Dinheiro</option>
          </select>
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Instruções (opcional)</span>
          <textarea id="pm-edit-instructions" name="instructions" rows="3" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm"></textarea>
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Ordem de exibição</span>
          <input id="pm-edit-sort" type="number" name="sort_order" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>

        <label id="pm-edit-upload-field" class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Bandeira (SVG/PNG/JPG)</span>
          <div id="pm-edit-brand-dropzone" class="rounded-xl border-2 border-dashed bg-white p-4 relative admin-primary-border" style="min-height:96px;">
            <input id="pm-edit-brand-icon" type="file" name="brand_icon" accept=".svg,.png,.jpg,.jpeg,.webp" class="sr-only">

            <div id="pm-edit-brand-drop-hint" class="flex flex-col items-center justify-center text-center py-6">
              <div class="text-slate-600 mb-2">Arraste arquivos para cá ou se preferir</div>
              <button type="button" id="pm-edit-brand-choose" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm admin-primary-text admin-primary-border">anexar arquivos</button>
              <div class="text-xs text-slate-400 mt-2">Use preferencialmente SVG ou PNG quadrado até ~1MB.</div>
            </div>

      <img id="pm-edit-brand-preview" src="" alt="preview"
        class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 rounded-xl hidden"
        style="max-width:calc(100% - 12px); max-height:calc(100% - 12px); width:auto; height:auto;" />
            <button type="button" id="pm-edit-brand-clear" class="absolute top-3 right-3 hidden rounded-full bg-white text-slate-700 shadow-sm px-2 py-0.5 border admin-primary-border">✕</button>
          </div>
        </label>

        <?php if (!empty($brandLibrary)): ?>
        <div id="pm-edit-library-field" class="grid gap-2 text-sm">
          <span class="font-semibold text-slate-700">Escolher da biblioteca</span>
          <input type="hidden" name="meta[icon]" id="pm-edit-brand-lib-input" value="">
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="pm-edit-brand-grid">
            <?php foreach ($brandLibrary as $lib):
                $libValue = $normaliseIconPath($lib['value'] ?? ($lib['url'] ?? ''));
            ?>
              <button type="button"
                      class="pm-edit-brand-item group rounded-xl border border-slate-200 bg-white p-2 hover:border-indigo-400 hover:ring-1 hover:ring-indigo-200 flex items-center gap-2"
                      data-url="<?= e($lib['url']) ?>"
                      data-value="<?= e($libValue) ?>"
                      data-label="<?= e($lib['label']) ?>">
                <span class="inline-flex h-6 w-6 items-center justify-center overflow-hidden rounded bg-white">
                  <img src="<?= e($lib['url']) ?>" alt="<?= e($lib['label']) ?>" class="max-w-full max-h-full object-contain" />
                </span>
                <span class="text-xs text-slate-700 truncate" title="<?= e($lib['label']) ?>"><?= e($lib['label']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <!-- preview moved to upload field in modal -->
        </div>
        <?php endif; ?>

        <div class="mt-2 flex items-center justify-end gap-2">
          <button type="button" id="pm-edit-cancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Cancelar</button>
          <button type="submit" class="rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      // Helper: wire a dropzone block (dropzone element, file input id, preview img id, choose button id, clear button id)
      function wireDropzone(opts) {
        const dz = document.getElementById(opts.dropzone);
        const input = document.getElementById(opts.input);
        const preview = document.getElementById(opts.preview);
        const choose = document.getElementById(opts.choose);
        const clearBtn = document.getElementById(opts.clear);
        const hint = dz ? dz.querySelector('[id$="-drop-hint"]') : null;

        if (!dz || !input) return;

        function showPreviewFile(file) {
          try {
            const url = URL.createObjectURL(file);
            if (preview) {
              preview.src = url;
              preview.classList.remove('hidden');
            }
            if (hint) hint.classList.add('hidden');
            if (clearBtn) clearBtn.classList.remove('hidden');
            // revoke after load
            if (preview) preview.onload = () => { try { URL.revokeObjectURL(url); } catch(_){} };
          } catch(_){}
        }

        function clearSelection() {
          try {
            input.value = '';
          } catch(_){}
          if (preview) { preview.src = ''; preview.classList.add('hidden'); }
          if (hint) hint.classList.remove('hidden');
          if (clearBtn) clearBtn.classList.add('hidden');
        }

        // choose button opens file picker
        if (choose) choose.addEventListener('click', function(e){ e.preventDefault(); input.click(); });

        // clear button
        if (clearBtn) clearBtn.addEventListener('click', function(e){ e.preventDefault(); clearSelection(); });

        // input change
        input.addEventListener('change', function(){
          if (this.files && this.files.length > 0) {
            showPreviewFile(this.files[0]);
            // also clear library selection to prioritize upload
            try { if (typeof window.pmSelectBrand === 'function') window.pmSelectBrand(''); } catch(_){}
          } else {
            clearSelection();
          }
        });

        // drag/drop
        dz.addEventListener('dragover', function(e){ e.preventDefault(); dz.classList.add('opacity-80'); });
        dz.addEventListener('dragleave', function(e){ dz.classList.remove('opacity-80'); });
        dz.addEventListener('drop', function(e){
          e.preventDefault(); dz.classList.remove('opacity-80');
          const dt = e.dataTransfer;
          if (dt && dt.files && dt.files.length > 0) {
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      }

      // wire main and modal dropzones
      wireDropzone({ dropzone: 'pm-brand-dropzone', input: 'pm-brand-icon', preview: 'pm-brand-preview', choose: 'pm-brand-choose', clear: 'pm-brand-clear' });
      wireDropzone({ dropzone: 'pm-edit-brand-dropzone', input: 'pm-edit-brand-icon', preview: 'pm-edit-brand-preview', choose: 'pm-edit-brand-choose', clear: 'pm-edit-brand-clear' });
    })();
    (function(){
      const modal = document.getElementById('pm-edit-modal');
      const form = document.getElementById('pm-edit-modal-form');
      const btnClose = document.getElementById('pm-edit-close');
      const btnCancel = document.getElementById('pm-edit-cancel');

      function open(){ modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
      function close(){ modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }

      const normalizeIconModal = (typeof window.pmNormalizeBrandIcon === 'function') ? window.pmNormalizeBrandIcon : function(value){ return (value || '').trim(); };
      const resolveIconModal = (typeof window.pmResolveIconUrl === 'function') ? window.pmResolveIconUrl : function(value){ return value || ''; };

      function selectBrand(value, label){
        const input = document.getElementById('pm-edit-brand-lib-input');
        const prev = document.getElementById('pm-edit-brand-preview');
        const grid = document.getElementById('pm-edit-brand-grid');
        const file = document.getElementById('pm-edit-brand-icon');
        const uploadField = document.getElementById('pm-edit-upload-field');
        const nameInput = document.getElementById('pm-edit-name');
        const nameWrapper = document.getElementById('pm-edit-name-field');
        const typeSelect = document.getElementById('pm-edit-type');
        const normalised = normalizeIconModal(value);
        if (input) input.value = normalised;
        let previewUrl = '';
        if (grid) {
          grid.querySelectorAll('.pm-edit-brand-item').forEach(btn => {
            const btnValue = normalizeIconModal(btn.dataset.value || btn.dataset.url || '');
            const on = !!normalised && btnValue === normalised;
            btn.classList.toggle('border-indigo-500', on);
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-indigo-300', on);
            btn.classList.toggle('border-slate-200', !on);
            btn.classList.toggle('ring-0', !on);
            if (on && !previewUrl) {
              previewUrl = btn.dataset.url || '';
              if (!label) {
                label = btn.getAttribute('data-label') || '';
              }
            }
          });
        }
        if (!previewUrl) {
          previewUrl = normalised ? resolveIconModal(normalised) : '';
        }
        if (prev) {
          if (previewUrl) {
            prev.src = previewUrl;
            prev.classList.remove('hidden');
          } else {
            prev.src = '';
            prev.classList.add('hidden');
          }
        }
        if (file && normalised) file.value = '';
        const libSelected = !!normalised;
        if (uploadField) {
          const isPix = typeSelect && typeSelect.value === 'pix';
          uploadField.classList.toggle('hidden', libSelected || isPix);
        }
        if (nameWrapper) {
          const isPix = typeSelect && typeSelect.value === 'pix';
          nameWrapper.classList.toggle('hidden', libSelected || isPix);
        }
        if (nameInput) {
          if (!nameInput.dataset.originalRequired) {
            nameInput.dataset.originalRequired = nameInput.hasAttribute('required') ? '1' : '0';
          }
          if (libSelected) {
            nameInput.removeAttribute('required');
          } else if (nameInput.dataset.originalRequired === '1' && !(typeSelect && (typeSelect.value === 'pix' || typeSelect.value === 'cash'))) {
            nameInput.setAttribute('required', 'required');
          }
          if (libSelected && label) {
            nameInput.value = label;
          }
        }
      }

      if (btnClose) btnClose.addEventListener('click', close);
      if (btnCancel) btnCancel.addEventListener('click', close);
      if (modal) {
        modal.addEventListener('click', function(e){ if (e.target === modal) close(); });
      }

  const grid = document.getElementById('pm-edit-brand-grid');
  const file = document.getElementById('pm-edit-brand-icon');
  const libraryField = document.getElementById('pm-edit-library-field');
  const typeSelect = document.getElementById('pm-edit-type');
      if (grid) {
        grid.querySelectorAll('.pm-edit-brand-item').forEach(btn => {
          btn.addEventListener('click', function(){ selectBrand(this.dataset.value || this.dataset.url || '', this.dataset.label || ''); });
        });
      }
      if (file) {
        file.addEventListener('change', function(){
          if (this.files && this.files.length > 0) {
            try { selectBrand(''); } catch(_){}
            try {
              const f = this.files[0];
              const brandPrev = document.getElementById('pm-edit-brand-preview');
              if (brandPrev) {
                const url = URL.createObjectURL(f);
                brandPrev.src = url;
                brandPrev.classList.remove('hidden');
                brandPrev.onload = () => { try { URL.revokeObjectURL(url); } catch(_){} };
              }
            } catch(_){}
          }
        });
      }

      // Modal desativado: edição agora usa o formulário "Adicionar novo método".
      // Mantemos o código do modal por compatibilidade, mas não abrimos mais.
      window.pmOpenEditModal = function(method){
        if (!method) return;
        try { if (typeof window.fillFormWithMethod === 'function') window.fillFormWithMethod(method); } catch(_) {}
        close();
      }

      // esconde biblioteca/upload quando tipo = pix ou cash
      function syncPixVisibility(){
        const isPix = typeSelect && typeSelect.value === 'pix';
        const isCash = typeSelect && typeSelect.value === 'cash';
        const isFixedType = isPix || isCash;
        if (libraryField) libraryField.classList.toggle('hidden', isFixedType);
        const uploadField = document.getElementById('pm-edit-upload-field');
        if (uploadField) uploadField.classList.toggle('hidden', isFixedType);
        const nameWrapper = document.getElementById('pm-edit-name-field');
        if (nameWrapper) nameWrapper.classList.toggle('hidden', isFixedType);
      }
      if (typeSelect) typeSelect.addEventListener('change', syncPixVisibility);
      syncPixVisibility();

      if (form) {
        form.addEventListener('submit', async function(e){
          e.preventDefault();
          const id = document.getElementById('pm-edit-id').value;
          // constrói URL de ação de forma robusta
          let actionBase = '';
          if (typeof window.PM_BASE !== 'undefined' && window.PM_BASE) {
            actionBase = window.PM_BASE;
          } else {
            const createForm = document.getElementById('pm-create-form');
            actionBase = createForm ? (createForm.getAttribute('action') || '') : '';
          }
          const action = actionBase.replace(/\/?$/, '') + '/' + id;
          const data = new FormData(form);
          // se nome vazio e biblioteca selecionada, preenche com label
          try {
            const nameEl = document.getElementById('pm-edit-name');
            const lib = document.getElementById('pm-edit-brand-lib-input');
            const normalizedIcon = lib ? normalizeIconModal(lib.value || '') : '';
            if (nameEl && (!nameEl.value || nameEl.value.trim() === '') && normalizedIcon) {
              let label = '';
              const grid = document.getElementById('pm-edit-brand-grid');
              if (grid) {
                grid.querySelectorAll('.pm-edit-brand-item').forEach(btn => {
                  if (label) return;
                  const btnValue = normalizeIconModal(btn.dataset.value || btn.dataset.url || '');
                  if (btnValue && btnValue === normalizedIcon) {
                    label = (btn.getAttribute('data-label') || '').trim();
                  }
                });
              }
              if (label) data.set('name', label);
            }
          } catch(_) {}
          // garante envio do meta[icon] selecionado
          const libInput = document.getElementById('pm-edit-brand-lib-input');
          if (libInput) {
            const normalizedIcon = normalizeIconModal(libInput.value || '');
            if (normalizedIcon) {
              data.set('meta[icon]', normalizedIcon);
            }
          }
          // tenta obter CSRF de várias fontes
          let csrf = (typeof window.PM_CSRF !== 'undefined' && window.PM_CSRF) ? window.PM_CSRF : '';
          if (!csrf) {
            const createForm = document.getElementById('pm-create-form');
            const tokenInput = createForm ? createForm.querySelector('input[name="csrf_token"]') : null;
            csrf = tokenInput ? (tokenInput.value || '') : '';
          }
          if (csrf) data.append('csrf_token', csrf);
          try {
            const res = await fetch(action, {
              method: 'POST',
              body: data,
              credentials: 'same-origin',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) {
              const text = await res.text().catch(() => '');
              throw new Error(`HTTP ${res.status} ${res.statusText} - ${text.slice(0,200)}`);
            }
            const ct = res.headers.get('content-type') || '';
            if (ct.includes('application/json')) {
              let json = null;
              try { json = await res.json(); } catch(err) {
                const text = await res.text().catch(() => '');
                throw new Error('JSON parse failed: ' + text.slice(0,200));
              }
              if (!json || !json.success || !json.method) throw new Error('Invalid response payload');
              replaceMethodRow(json.method);
                if (json.message) showToast(json.message, 'success');
                close();
            } else {
              // Conteúdo não-JSON (possível redirect/HTML), mas HTTP 200: trata como sucesso e recarrega
              close();
              window.location.reload();
              return;
            }
          } catch (err) {
            console.error(err);
            showToast('Erro ao salvar. Tente novamente.', 'error');
          }
        });
      }
    })();
  </script>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
