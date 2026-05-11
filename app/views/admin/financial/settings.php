<?php
/**
 * Configurações Financeiras
 * Estilo consistente com Analytics
 */

$title = 'Configurações Financeiras - ' . ($company['name'] ?? '');
ob_start();

// Configuração do header padronizado
$pageTitle = 'Configurações Financeiras';
$pageDescription = 'Configure taxas e custos padrão';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $activeSlug . '/financial')],
    ['label' => 'Configurações']
];
$actions = [];
?>

<div class="mx-auto max-w-3xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<?php if ($success): ?>
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-emerald-700">Configurações salvas com sucesso!</span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex items-center gap-3">
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="text-red-700">Erro ao salvar configurações.</span>
</div>
<?php endif; ?>

<form action="<?= base_url('admin/' . $activeSlug . '/financial/settings') ?>" method="POST" class="space-y-6">
  
  <!-- Impostos -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Impostos</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa de Imposto Padrão (%) <a href="/admin/<?= $activeSlug ?>/guide/financial#config" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <input type="number" name="default_tax_percentage" value="<?= htmlspecialchars($settings['default_tax_percentage'] ?? '0') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
        <p class="text-sm text-slate-500 mt-1">Impostos sobre venda (ICMS, ISS, etc.)</p>
      </div>
    </div>
  </div>

  <!-- Taxas de Canais -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Taxas de Canais de Venda</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa iFood (%) <a href="/admin/<?= $activeSlug ?>/guide/financial#config" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <input type="number" name="ifood_fee_percentage" value="<?= htmlspecialchars($settings['ifood_fee_percentage'] ?? '0') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa Rappi (%)</label>
        <input type="number" name="rappi_fee_percentage" value="<?= htmlspecialchars($settings['rappi_fee_percentage'] ?? '0') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa UberEats (%)</label>
        <input type="number" name="ubereats_fee_percentage" value="<?= htmlspecialchars($settings['ubereats_fee_percentage'] ?? '0') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
        <p class="text-sm text-slate-500 mt-1">UberEats e similares</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Taxa Delivery Próprio (%)</label>
        <input type="number" name="own_delivery_fee_percentage" value="<?= htmlspecialchars($settings['own_delivery_fee_percentage'] ?? '0') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
        <p class="text-sm text-slate-500 mt-1">Entrega própria</p>
      </div>
    </div>
  </div>

  <!-- Custos e Metas -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="text-lg font-semibold text-slate-900">Custos e Metas</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Custo Mão de Obra/hora (R$) <a href="/admin/<?= $activeSlug ?>/guide/financial#config" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <input type="number" name="hourly_labor_cost" value="<?= htmlspecialchars($settings['hourly_labor_cost'] ?? '0') ?>" step="0.01" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Margem de Lucro Alvo (%) <a href="/admin/<?= $activeSlug ?>/guide/financial#config" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a></label>
        <input type="number" name="target_profit_margin" value="<?= htmlspecialchars($settings['target_profit_margin'] ?? '30') ?>" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Meta Faturamento Mensal (R$)</label>
        <input type="number" name="monthly_revenue_goal" value="<?= htmlspecialchars($settings['monthly_revenue_goal'] ?? '0') ?>" step="0.01" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Meta Lucro Mensal (R$)</label>
        <input type="number" name="monthly_profit_goal" value="<?= htmlspecialchars($settings['monthly_profit_goal'] ?? '0') ?>" step="0.01" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-purple-500">
      </div>
    </div>
  </div>

  <!-- Botões -->
  <div class="flex justify-end gap-3">
    <a href="<?= base_url('admin/' . $activeSlug . '/financial') ?>" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">Cancelar</a>
    <button type="submit" class="px-6 py-2.5 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95 transition inline-flex items-center gap-2">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Salvar Configurações
    </button>
  </div>
</form>

<!-- Ações -->
<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="mb-4 flex items-center gap-2">
    <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <h2 class="text-lg font-semibold text-slate-900">Ações</h2>
  </div>
  <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
    <div>
      <p class="font-medium text-slate-800">Atualizar Custos dos Produtos</p>
      <p class="text-sm text-slate-500">Recalcula os custos de todos os produtos ativos</p>
    </div>
    <button type="button" onclick="updateSnapshots()" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Atualizar
    </button>
  </div>
</div>

</div>

<script>
const baseUrl = '<?= base_url('admin/' . $activeSlug) ?>';

// Sistema de diálogo de confirmação customizado
function showConfirmDialog(message, onConfirm) {
    // Criar modal se não existir
    let dialog = document.getElementById('confirmDialog');
    if (!dialog) {
        dialog = document.createElement('div');
        dialog.id = 'confirmDialog';
        dialog.className = 'fixed inset-0 bg-black/50 hidden items-center justify-center z-[60]';
        dialog.innerHTML = `
            <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4 shadow-xl">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <h3 class="text-lg font-semibold text-slate-900">Confirmar Ação</h3>
                </div>
                <p id="confirmDialogMessage" class="text-slate-600 mb-6"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" id="confirmDialogCancel" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="button" id="confirmDialogAccept" class="px-4 py-2 rounded-xl admin-gradient-bg text-white font-medium hover:opacity-95">Confirmar</button>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);
        dialog.querySelector('#confirmDialogCancel').onclick = closeConfirmDialog;
        dialog.addEventListener('click', function(e) { if (e.target === dialog) closeConfirmDialog(); });
    }
    document.getElementById('confirmDialogMessage').textContent = message;
    dialog.classList.remove('hidden');
    dialog.classList.add('flex');
    window._pendingConfirmCallback = onConfirm;
    dialog.querySelector('#confirmDialogAccept').onclick = function() {
        if (window._pendingConfirmCallback) window._pendingConfirmCallback();
        closeConfirmDialog();
    };
}

function closeConfirmDialog() {
    const dialog = document.getElementById('confirmDialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
    }
    window._pendingConfirmCallback = null;
}

// Fechar com Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeConfirmDialog();
});

async function updateSnapshots() {
    showConfirmDialog('Isso irá recalcular os custos de todos os produtos. Continuar?', async () => {
        try {
            const response = await fetch(baseUrl + '/financial/recalculate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                ToastSystem.success(data.message);
            } else {
                ToastSystem.error('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        } catch (error) {
            ToastSystem.error('Erro ao atualizar: ' + error.message);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
