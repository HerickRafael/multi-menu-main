<?php
/**
 * View: Admin - Programa de Fidelidade Progressiva
 * Configuração do programa de stamps / cartão fidelidade
 */

if (empty($company) || empty($slug)) {
    echo '<p>Erro: dados da empresa não encontrados.</p>';
    return;
}

$pageTitle = 'Programa de Fidelidade';
ob_start();

// Configuração do header padronizado
$pageDescription = 'Configure o cartão fidelidade para recompensar clientes frequentes';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>';
$breadcrumbs = [
    ['label' => 'Fidelidade', 'url' => base_url('admin/' . $slug . '/loyalty-discount')],
    ['label' => 'Programa de Fidelidade']
];
$actions = [];
$activeSlug = $slug;

include __DIR__ . '/../components/page-header.php';

// Flash messages
$error = $_SESSION['loyalty_error'] ?? null;
unset($_SESSION['loyalty_error']);

$successMap = [
    'created'     => 'Programa de fidelidade criado com sucesso!',
    'updated'     => 'Programa atualizado com sucesso!',
    'activated'   => 'Programa ativado!',
    'deactivated' => 'Programa desativado.',
];
$success = isset($_GET['success']) ? ($successMap[$_GET['success']] ?? null) : null;
?>

<div class="mx-auto max-w-4xl p-4 space-y-6">

    <?php if ($error): ?>
    <div class="rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-800 shadow-sm">
        <div class="flex items-start gap-2">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <div><?= e($error) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="rounded-xl border border-green-200 bg-green-50/90 p-4 text-sm text-green-800 shadow-sm">
        <div class="flex items-start gap-2">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div><?= e($success) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estatísticas do programa (se existir) -->
    <?php if ($program && $stats): ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase">Participantes</div>
            <div class="mt-1 text-2xl font-bold text-slate-900"><?= (int)$stats['total_participants'] ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase">Ativos</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600"><?= (int)$stats['active_participants'] ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase">Progresso Médio</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600"><?= number_format((float)$stats['avg_progress'], 1) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase">Ciclos Completos</div>
            <div class="mt-1 text-2xl font-bold text-amber-600"><?= (int)$stats['total_completions'] ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="POST" action="<?= base_url('admin/' . rawurlencode($slug) . '/loyalty-program') ?>" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
        <?= csrf_field() ?>
        <?php if ($program): ?>
            <input type="hidden" name="program_id" value="<?= (int)$program['id'] ?>">
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">
                <?= $program ? 'Editar Programa' : 'Criar Programa de Fidelidade' ?>
            </h2>
            <?php if ($program): ?>
                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium <?= (int)$program['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= (int)$program['is_active'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                    <?= (int)$program['is_active'] ? 'Ativo' : 'Inativo' ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Nome do programa -->
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Nome do programa *</label>
                <input type="text" name="name" value="<?= e($program['name'] ?? '') ?>" required
                       placeholder="Ex: Cartão Fidelidade Wollburger"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <span class="mt-1 block text-xs text-slate-500">Visível para o cliente no perfil</span>
            </div>

            <!-- Pedidos necessários -->
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Pedidos necessários *</label>
                <input type="number" name="required_orders" value="<?= (int)($program['required_orders'] ?? 10) ?>" min="2" max="100" required
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <span class="mt-1 block text-xs text-slate-500">Quantos pedidos para completar um ciclo (mín. 2)</span>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Tipo de recompensa -->
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de recompensa *</label>
                <select name="reward_type" id="reward_type" required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        onchange="toggleRewardValue()">
                    <option value="">Selecione...</option>
                    <option value="discount_percentage" <?= ($program['reward_type'] ?? '') === 'discount_percentage' ? 'selected' : '' ?>>Desconto em %</option>
                    <option value="discount_fixed" <?= ($program['reward_type'] ?? '') === 'discount_fixed' ? 'selected' : '' ?>>Desconto fixo (R$)</option>
                    <option value="free_delivery" <?= ($program['reward_type'] ?? '') === 'free_delivery' ? 'selected' : '' ?>>Frete grátis</option>
                    <option value="free_item" <?= ($program['reward_type'] ?? '') === 'free_item' ? 'selected' : '' ?>>Item grátis</option>
                </select>
            </div>

            <!-- Valor da recompensa -->
            <div id="reward_value_container">
                <label class="mb-2 block text-sm font-medium text-slate-700" id="reward_value_label">Valor da recompensa</label>
                <input type="number" name="reward_value" id="reward_value" step="0.01" min="0"
                       value="<?= (float)($program['reward_value'] ?? 0) ?>"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <span class="mt-1 block text-xs text-slate-500" id="reward_value_hint">Percentual ou valor em reais</span>
            </div>
        </div>

        <!-- Descrição da recompensa -->
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Descrição da recompensa *</label>
            <textarea name="reward_description" rows="2" required
                      placeholder="Ex: Ganhe 15% de desconto no próximo pedido!"
                      class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= e($program['reward_description'] ?? '') ?></textarea>
            <span class="mt-1 block text-xs text-slate-500">Exibida ao cliente quando completar o ciclo</span>
        </div>

        <!-- Toggle ativo -->
        <div class="flex items-center gap-3">
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                       <?= !$program || (int)($program['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
            </label>
            <span class="text-sm font-medium text-slate-700">Programa ativo</span>
        </div>

        <!-- Botões -->
        <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 transition-opacity">
                <?= $program ? 'Salvar Alterações' : 'Criar Programa' ?>
            </button>
            <a href="<?= base_url('admin/' . rawurlencode($slug) . '/loyalty-discount') ?>"
               class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Voltar
            </a>
        </div>
    </form>

    <?php if ($program): ?>
    <div class="flex justify-end">
        <form method="POST" action="<?= base_url('admin/' . rawurlencode($slug) . '/loyalty-program/' . (int)$program['id'] . '/toggle') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="rounded-xl border px-4 py-2.5 text-sm font-medium shadow-sm transition-colors
                <?= (int)$program['is_active'] ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                <?= (int)$program['is_active'] ? 'Desativar Programa' : 'Ativar Programa' ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Info box -->
    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5 text-sm text-indigo-900">
        <h3 class="mb-2 font-semibold flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            Como funciona
        </h3>
        <ul class="space-y-1 text-indigo-800">
            <li>• O cliente vê uma barra de progresso no perfil</li>
            <li>• A cada pedido finalizado, o contador avança automaticamente</li>
            <li>• Ao completar o ciclo, um cupom de recompensa é gerado automaticamente</li>
            <li>• O ciclo reinicia, permitindo acumular novamente</li>
        </ul>
    </div>
</div>

<script>
function toggleRewardValue() {
    const type = document.getElementById('reward_type').value;
    const container = document.getElementById('reward_value_container');
    const label = document.getElementById('reward_value_label');
    const hint = document.getElementById('reward_value_hint');
    const input = document.getElementById('reward_value');

    if (type === 'free_delivery') {
        container.style.opacity = '0.4';
        input.disabled = true;
        input.value = '0';
        label.textContent = 'Valor (não aplicável)';
        hint.textContent = 'Frete grátis não requer valor';
    } else if (type === 'discount_percentage') {
        container.style.opacity = '1';
        input.disabled = false;
        label.textContent = 'Percentual de desconto (%)';
        hint.textContent = 'Ex: 15 para 15% de desconto';
        input.step = '1';
        input.max = '100';
    } else if (type === 'discount_fixed') {
        container.style.opacity = '1';
        input.disabled = false;
        label.textContent = 'Valor do desconto (R$)';
        hint.textContent = 'Ex: 10.00 para R$ 10,00 de desconto';
        input.step = '0.01';
        input.removeAttribute('max');
    } else if (type === 'free_item') {
        container.style.opacity = '0.4';
        input.disabled = true;
        input.value = '0';
        label.textContent = 'Valor (não aplicável)';
        hint.textContent = 'Item grátis — o cupom será de 100%';
    } else {
        container.style.opacity = '1';
        input.disabled = false;
        label.textContent = 'Valor da recompensa';
        hint.textContent = 'Percentual ou valor em reais';
    }
}
// Inicializar estado do campo
document.addEventListener('DOMContentLoaded', toggleRewardValue);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
