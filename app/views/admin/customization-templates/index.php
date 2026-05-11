<?php
// admin/customization-templates/index.php — Lista de Grupos de Personalização Reutilizáveis

$title = 'Grupos de Personalização - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

$templates = $templates ?? [];

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<?php
// Configuração do Header Padrão
$pageTitle = 'Grupos de Personalização';
$pageDescription = 'Crie grupos reutilizáveis para adicionar rapidamente aos produtos';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5"/><path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85l-7.214 1.923a2.5 2.5 0 0 1-1.286 0L0 6.85z"/></svg>';
$breadcrumbs = [
    ['label' => 'Produtos', 'url' => base_url('admin/' . $slug . '/products')],
    ['label' => 'Grupos de Personalização']
];
$actions = [
    ['label' => 'Novo Grupo', 'url' => base_url('admin/' . $slug . '/customization-templates/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<!-- Alertas -->
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
    </div>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
    </div>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if (empty($templates)): ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 16 16">
        <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5"/>
        <path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85l-7.214 1.923a2.5 2.5 0 0 1-1.286 0L0 6.85z"/>
      </svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhum grupo criado</h2>
    <p class="mt-1 text-sm text-slate-500">Crie grupos de personalização reutilizáveis para adicionar rapidamente aos seus produtos.</p>
    <div class="mt-4">
      <a href="<?= base_url('admin/' . $slug . '/customization-templates/create') ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        Criar primeiro grupo
      </a>
    </div>
  </div>
<?php else: ?>

  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[700px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Nome do Grupo</th>
            <th class="p-3">Tipo</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($templates as $tpl): ?>
            <?php 
              $typeLabels = [
                'single' => ['Seleção única', 'bg-blue-50 text-blue-700 ring-blue-200'],
                'extra' => ['Quantidade', 'bg-purple-50 text-purple-700 ring-purple-200'],
                'addon' => ['Adicional', 'bg-green-50 text-green-700 ring-green-200'],
                'component' => ['Componente', 'bg-orange-50 text-orange-700 ring-orange-200']
              ];
              $typeInfo = $typeLabels[$tpl['type']] ?? ['Extra', 'bg-slate-100 text-slate-700 ring-slate-200'];
              $productsCount = (int)($tpl['products_count'] ?? 0);
              $isActive = (bool)$tpl['active'];
            ?>
            <tr class="hover:bg-slate-50/60" id="row-<?= $tpl['id'] ?>">
              
              <!-- Nome do Grupo -->
              <td class="p-3 align-middle">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5"/>
                    </svg>
                  </span>
                  <span class="font-medium text-slate-800"><?= htmlspecialchars($tpl['name']) ?></span>
                </div>
              </td>

              <!-- Tipo -->
              <td class="p-3 align-middle">
                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-medium ring-1 <?= $typeInfo[1] ?>">
                  <?= $typeInfo[0] ?>
                </span>
              </td>



              <!-- Status -->
              <td class="p-3 align-middle">
                <?php if ($isActive): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativo
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-600 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inativo
                  </span>
                <?php endif; ?>
              </td>

              <!-- Ações -->
              <td class="p-3 align-middle">
                <div class="flex justify-end gap-2">
                  <a href="<?= base_url('admin/' . $slug . '/customization-templates/' . $tpl['id'] . '/edit') ?>"
                     class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                     title="Editar">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                    Editar
                  </a>

                  <button onclick="toggleTemplate(<?= $tpl['id'] ?>, <?= $isActive ? 'true' : 'false' ?>)"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                          title="<?= $isActive ? 'Desativar' : 'Ativar' ?>"
                          id="toggle-btn-<?= $tpl['id'] ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                      <?php if ($isActive): ?>
                        <path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      <?php else: ?>
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      <?php endif; ?>
                    </svg>
                    <span id="toggle-text-<?= $tpl['id'] ?>"><?= $isActive ? 'Desativar' : 'Ativar' ?></span>
                  </button>

                  <?php if ($productsCount === 0): ?>
                    <button onclick="deleteTemplate(<?= $tpl['id'] ?>)"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm hover:bg-red-50"
                            title="Excluir">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Excluir
                    </button>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-400 cursor-not-allowed"
                          title="Remova primeiro dos produtos">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Excluir
                    </span>
                  <?php endif; ?>
                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>

<!-- Dica -->
<div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
  <div class="flex gap-3">
    <div class="flex-shrink-0">
      <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
      </svg>
    </div>
    <div class="text-sm text-blue-800">
      <p class="font-medium mb-1">Como usar os grupos</p>
      <p class="text-blue-600">
        Ao editar um produto, clique em "Copiar grupo" na seção de Personalização para adicionar 
        rapidamente um destes grupos pré-configurados.
      </p>
    </div>
  </div>
</div>

</div>

<script>
const slug = '<?= $slug ?>';

async function toggleTemplate(id, currentlyActive) {
  try {
    const response = await fetch(`<?= base_url('admin/') ?>${slug}/customization-templates/${id}/toggle`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      // Reload page to update UI
      window.location.reload();
    } else {
      const data = await response.json();
      alert(data.error || 'Erro ao alterar status');
    }
  } catch (error) {
    console.error('Erro:', error);
    alert('Erro ao alterar status');
  }
}

async function deleteTemplate(id) {
  if (!confirm('Tem certeza que deseja excluir este grupo?')) {
    return;
  }
  
  // Create a form and submit it
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = `<?= base_url('admin/') ?>${slug}/customization-templates/${id}/del`;
  document.body.appendChild(form);
  form.submit();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
