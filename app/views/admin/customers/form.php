<?php
/**
 * View: Admin - Formulário de Cliente (Criar/Editar)
 * UI/UX melhorado com layout responsivo e edição de endereços
 */

$slug = $company['slug'] ?? '';
$activeSlug = $slug;
$isEdit = !empty($customer) && !empty($customer['id']);
$customerId = $customer['id'] ?? null;

// Configuração do header padronizado
$pageTitle = $isEdit ? 'Editar Cliente' : 'Novo Cliente';
$pageDescription = $isEdit ? 'Altere as informações do cliente' : 'Cadastre um novo cliente';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4"/></svg>';
$breadcrumbs = [
    ['label' => 'Clientes', 'url' => base_url("admin/{$slug}/customers")],
    ['label' => $isEdit ? e($customer['name'] ?? 'Editar') : 'Novo']
];

// Garantir que variáveis existem
$stats = $stats ?? [];
$addresses = $addresses ?? [];
$recentOrders = $recentOrders ?? [];
$errors = $errors ?? [];

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<?php include __DIR__ . '/../components/page-header.php'; ?>

<!-- Mensagens de erro -->
<?php if (!empty($error)): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 flex items-center gap-3">
    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
        <svg class="h-5 w-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M15 9l-6 6m0-6l6 6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div>
        <p class="font-medium">Erro</p>
        <p class="text-sm"><?= e($error) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
    <div class="flex items-center gap-3 mb-3">
        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="h-5 w-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M15 9l-6 6m0-6l6 6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div>
            <p class="font-medium">Corrija os erros abaixo:</p>
        </div>
    </div>
    <ul class="list-disc list-inside pl-14 text-sm space-y-1">
        <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($isEdit): ?>
<!-- Layout para Edição - Cards em grid responsivo -->
<div class="space-y-6">
    
    <!-- Linha 1: Perfil do Cliente + Estatísticas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Card Principal - Perfil -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <!-- Header do Card com Avatar -->
                <div class="admin-gradient-bg from-indigo-500 to-purple-600 px-6 py-8">
                    <div class="flex items-center gap-4">
                        <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white text-3xl font-bold ring-4 ring-white/30">
                            <?= strtoupper(mb_substr($customer['name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <div class="text-white">
                            <h2 class="text-2xl font-bold"><?= e($customer['name'] ?? '') ?></h2>
                            <div class="flex items-center gap-2 mt-1 text-white/80">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                <?= e(format_phone_br($customer['whatsapp'] ?? '')) ?>
                            </div>
                            <p class="text-sm text-white/60 mt-1">Cliente desde <?= !empty($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : '-' ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de Edição -->
                <form method="post" action="<?= e(base_url("admin/{$slug}/customers/{$customerId}/store")) ?>">
                    <div class="p-6 space-y-5">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Editar Informações
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Nome -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Nome Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?= e($customer['name'] ?? '') ?>"
                                       required
                                       maxlength="255"
                                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                       placeholder="Nome do cliente">
                            </div>
                            
                            <!-- WhatsApp -->
                            <div>
                                <label for="whatsapp" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-green-500">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                    </div>
                                    <input type="tel" 
                                           id="whatsapp" 
                                           name="whatsapp" 
                                           value="<?= e($customer['whatsapp'] ?? '') ?>"
                                           required
                                           maxlength="20"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    E-mail
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                    </div>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?= e($customer['email'] ?? '') ?>"
                                           maxlength="255"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="email@exemplo.com">
                                </div>
                            </div>
                            
                            <!-- CPF -->
                            <div>
                                <label for="cpf" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    CPF
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <path d="M7 8h2m-2 4h6m-6 4h10"/>
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           id="cpf" 
                                           name="cpf" 
                                           value="<?= e($customer['cpf'] ?? '') ?>"
                                           maxlength="14"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="000.000.000-00">
                                </div>
                            </div>
                            
                            <!-- Data de Nascimento -->
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Data de Nascimento
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    </div>
                                    <input type="date" 
                                           id="birth_date" 
                                           name="birth_date" 
                                           value="<?= e($customer['birth_date'] ?? '') ?>"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="<?= e(base_url("admin/{$slug}/customers")) ?>" 
                           class="w-full sm:w-auto order-2 sm:order-1 text-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition">
                            ← Voltar para Lista
                        </a>
                        <button type="submit" 
                                class="w-full sm:w-auto order-1 sm:order-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 transition flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <path d="M17 21v-8H7v8m0-16v5h8"/>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Card de Estatísticas -->
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                                <rect x="9" y="3" width="6" height="4" rx="1"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900"><?= number_format((int)($stats['total_orders'] ?? 0), 0, ',', '.') ?></div>
                            <div class="text-xs text-slate-500">Pedidos</div>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-green-600">R$ <?= number_format((float)($stats['total_spent'] ?? 0), 0, ',', '.') ?></div>
                            <div class="text-xs text-slate-500">Total Gasto</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ticket Médio + Info -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="space-y-3">
                    <?php if (!empty($stats['avg_ticket']) && $stats['avg_ticket'] > 0): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Ticket Médio</span>
                        <span class="font-semibold text-indigo-600">R$ <?= number_format((float)$stats['avg_ticket'], 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($customer['last_login_at']) && $customer['last_login_at'] !== '0000-00-00 00:00:00'): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Último Acesso</span>
                        <span class="text-sm text-slate-700"><?= date('d/m/Y H:i', strtotime($customer['last_login_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h4 class="font-semibold text-slate-900 text-sm">Ações Rápidas</h4>
                </div>
                <div class="p-3 space-y-2">
                    <a href="https://wa.me/<?= e($customer['whatsapp_e164'] ?? preg_replace('/\D/', '', $customer['whatsapp'] ?? '')) ?>" 
                       target="_blank"
                       class="flex items-center gap-3 p-3 rounded-xl bg-green-50 hover:bg-green-100 border border-green-200 transition group">
                        <div class="h-8 w-8 rounded-lg bg-green-500 flex items-center justify-center">
                            <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-700">Abrir WhatsApp</span>
                    </a>
                    
                    <form method="post" 
                          action="<?= e(base_url("admin/{$slug}/customers/{$customerId}/delete")) ?>" 
                          onsubmit="return confirm('Tem certeza que deseja remover este cliente?');">
                        <button type="submit" 
                                class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-red-50 border border-transparent hover:border-red-200 transition group">
                            <div class="h-8 w-8 rounded-lg bg-slate-100 group-hover:bg-red-100 flex items-center justify-center transition">
                                <svg class="h-4 w-4 text-slate-400 group-hover:text-red-600 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-600 group-hover:text-red-700 transition">Excluir Cliente</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Linha 2: Endereços e Pedidos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Card de Endereços -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Endereços Cadastrados
                </h4>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-full"><?= count($addresses) ?> endereço(s)</span>
            </div>
            
            <?php if (empty($addresses)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-slate-100 mb-4">
                    <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <p class="text-slate-500 text-sm">Nenhum endereço cadastrado</p>
                <p class="text-slate-400 text-xs mt-1">Os endereços serão adicionados quando o cliente fizer pedidos</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto" id="addresses-list">
                <?php foreach ($addresses as $addr): ?>
                <div class="p-4 hover:bg-slate-50 transition address-item" data-address-id="<?= (int)$addr['id'] ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <!-- Label e Badge -->
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <?php if (!empty($addr['label'])): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium">
                                    <?php if (stripos($addr['label'], 'casa') !== false): ?>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        <polyline points="9,22 9,12 15,12 15,22"/>
                                    </svg>
                                    <?php elseif (stripos($addr['label'], 'trabalho') !== false || stripos($addr['label'], 'empresa') !== false): ?>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <?php endif; ?>
                                    <?= e($addr['label']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($addr['is_default'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-medium">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                    Principal
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Endereço -->
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-slate-900">
                                    <?= e($addr['street'] ?? '') ?><?= !empty($addr['number']) ? ', ' . e($addr['number']) : '' ?>
                                </p>
                                <?php if (!empty($addr['complement'])): ?>
                                <p class="text-sm text-slate-600"><?= e($addr['complement']) ?></p>
                                <?php endif; ?>
                                <p class="text-sm text-slate-500">
                                    <?= e($addr['neighborhood'] ?? '') ?>
                                    <?php if (!empty($addr['city'])): ?>
                                    - <?= e($addr['city']) ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($addr['reference'])): ?>
                                <p class="text-xs text-slate-400 italic">Ref: <?= e($addr['reference']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex-shrink-0 flex items-center gap-1">
                            <button type="button" 
                                    onclick="openEditAddressModal(<?= (int)$addr['id'] ?>, <?= htmlspecialchars(json_encode($addr), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="p-2 rounded-lg hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 transition"
                                    title="Editar endereço">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button type="button" 
                                    onclick="deleteAddress(<?= (int)$addr['id'] ?>)"
                                    class="p-2 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition"
                                    title="Remover endereço">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Botão Adicionar Endereço -->
            <div class="p-4 border-t border-slate-100">
                <button type="button" 
                        onclick="openCreateAddressModal()"
                        class="w-full flex items-center justify-center gap-2 p-3 rounded-xl border-2 border-dashed border-slate-300 text-slate-600 hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Adicionar Endereço
                </button>
            </div>
        </div>
        
        <!-- Card de Últimos Pedidos -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                    </svg>
                    Últimos Pedidos
                </h4>
                <?php if (!empty($recentOrders)): ?>
                <?php
                    $ordersQuery = (string)($customer['whatsapp'] ?? '');
                    $ordersDigits = preg_replace('/\D/', '', $ordersQuery);
                    if ($ordersDigits !== '') {
                        $ordersQuery = $ordersDigits;
                    } elseif (!empty($customer['whatsapp_e164'])) {
                        $ordersQuery = preg_replace('/\D/', '', (string)$customer['whatsapp_e164']);
                    }
                ?>
                <a href="<?= e(base_url("admin/{$slug}/orders?q=" . urlencode($ordersQuery))) ?>" 
                   class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                    Ver todos →
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($recentOrders)): ?>
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-slate-100 mb-4">
                    <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                    </svg>
                </div>
                <p class="text-slate-500 text-sm">Nenhum pedido realizado</p>
                <p class="text-slate-400 text-xs mt-1">Os pedidos aparecerão aqui quando forem feitos</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                <?php 
                $statusConfig = [
                    'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'label' => 'Pendente', 'icon' => '⏳'],
                    'paid' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'label' => 'Concluído', 'icon' => '✅'],
                    'completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'label' => 'Concluído', 'icon' => '✅'],
                    'canceled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'label' => 'Cancelado', 'icon' => '❌'],
                ];
                foreach ($recentOrders as $order): 
                    $status = $statusConfig[$order['status']] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'label' => $order['status'], 'icon' => '📋'];
                    $orderNum = $order['order_number'] ?? $order['id'] ?? 0;
                ?>
                <a href="<?= e(base_url("admin/{$slug}/orders/show?id={$order['id']}")) ?>" 
                   class="block p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg <?= $status['bg'] ?> flex items-center justify-center text-lg">
                                <?= $status['icon'] ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-900">Pedido #<?= $orderNum ?></div>
                                <div class="text-xs text-slate-500"><?= date('d/m/Y \à\s H:i', strtotime($order['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-slate-900">R$ <?= number_format((float)$order['total'], 2, ',', '.') ?></div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $status['bg'] ?> <?= $status['text'] ?>">
                                <?= $status['label'] ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php else: ?>
<!-- Layout para Criação - Mesmo design da edição -->
<div class="space-y-6">
    
    <!-- Linha 1: Formulário Principal + Dicas Laterais -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Card Principal - Formulário -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <!-- Header do Card com Gradiente -->
                <div class="admin-gradient-bg from-indigo-500 to-purple-600 px-6 py-8">
                    <div class="flex items-center gap-4">
                        <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white ring-4 ring-white/30">
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <path d="M20 8v6m3-3h-6"/>
                            </svg>
                        </div>
                        <div class="text-white">
                            <h2 class="text-2xl font-bold">Novo Cliente</h2>
                            <p class="text-white/80 mt-1">Preencha as informações para cadastrar</p>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de Criação -->
                <form id="createCustomerForm" method="post" action="<?= e(base_url("admin/{$slug}/customers/store")) ?>">
                    <div class="p-6 space-y-5">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Informações do Cliente
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Nome -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Nome Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?= e($customer['name'] ?? '') ?>"
                                       required
                                       maxlength="255"
                                       autofocus
                                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                       placeholder="Nome do cliente">
                            </div>
                            
                            <!-- WhatsApp -->
                            <div>
                                <label for="whatsapp_create" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-green-500" id="whatsapp-icon">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                    </div>
                                    <input type="tel" 
                                           id="whatsapp_create" 
                                           name="whatsapp" 
                                           value="<?= e($customer['whatsapp'] ?? '') ?>"
                                           required
                                           maxlength="20"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="(00) 00000-0000">
                                </div>
                                <p id="whatsapp-status" class="mt-1.5 text-xs hidden"></p>
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label for="email_create" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    E-mail
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                    </div>
                                    <input type="email" 
                                           id="email_create" 
                                           name="email" 
                                           value="<?= e($customer['email'] ?? '') ?>"
                                           maxlength="255"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="email@exemplo.com">
                                </div>
                            </div>
                            
                            <!-- CPF -->
                            <div>
                                <label for="cpf_create" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    CPF
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <path d="M7 8h2m-2 4h6m-6 4h10"/>
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           id="cpf_create" 
                                           name="cpf" 
                                           value="<?= e($customer['cpf'] ?? '') ?>"
                                           maxlength="14"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                                           placeholder="000.000.000-00">
                                </div>
                            </div>
                            
                            <!-- Data de Nascimento -->
                            <div class="md:col-span-2">
                                <label for="birth_date_create" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Data de Nascimento
                                </label>
                                <div class="relative max-w-xs">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    </div>
                                    <input type="date" 
                                           id="birth_date_create" 
                                           name="birth_date" 
                                           value="<?= e($customer['birth_date'] ?? '') ?>"
                                           class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a href="<?= e(base_url("admin/{$slug}/customers")) ?>" 
                           class="w-full sm:w-auto order-2 sm:order-1 text-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition">
                            ← Voltar para Lista
                        </a>
                        <button type="submit" 
                                class="w-full sm:w-auto order-1 sm:order-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 transition flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <path d="M20 8v6m3-3h-6"/>
                            </svg>
                            Cadastrar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Coluna Lateral - Dicas e Info -->
        <div class="space-y-6">
            <!-- Card de Dicas -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h4 class="font-semibold text-slate-900 flex items-center gap-2 text-sm">
                        <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4m0-4h.01"/>
                        </svg>
                        Dicas de Cadastro
                    </h4>
                </div>
                <div class="p-4">
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                            <span>Use o <strong>nome completo</strong> para facilitar a identificação</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                            <span>O sistema <strong>verifica duplicatas</strong> pelo WhatsApp</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                            <span>CPF e data de nascimento são <strong>opcionais</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                            <span>Endereço pode ser cadastrado <strong>agora ou depois</strong></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Card de Campos Obrigatórios -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h4 class="font-semibold text-slate-900 text-sm flex items-center gap-2">
                        <svg class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Campos Obrigatórios
                    </h4>
                </div>
                <div class="p-4 space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        <span class="text-slate-700">Nome Completo</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        <span class="text-slate-700">WhatsApp</span>
                    </div>
                </div>
            </div>
            
            <!-- Card de Atalhos -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h4 class="font-semibold text-slate-900 text-sm">Ações Rápidas</h4>
                </div>
                <div class="p-3 space-y-2">
                    <a href="<?= e(base_url("admin/{$slug}/customers")) ?>" 
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition group">
                        <div class="h-8 w-8 rounded-lg bg-slate-100 group-hover:bg-indigo-100 flex items-center justify-center transition">
                            <svg class="h-4 w-4 text-slate-400 group-hover:text-indigo-600 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-600 group-hover:text-indigo-700 transition">Ver Lista de Clientes</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Linha 2: Card de Endereço + Card Informativo -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Card de Endereço -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Endereço (Opcional)
                </h4>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-full">Opcional</span>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Cidade -->
                    <div>
                        <label for="create_city_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Cidade
                        </label>
                        <select id="create_city_id" 
                                name="address_city_id"
                                form="createCustomerForm"
                                onchange="filterCreateZonesByCity(this.value)"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                            <option value="">Selecione uma cidade</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= e($city['id']) ?>"><?= e($city['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Bairro -->
                    <div>
                        <label for="create_zone_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Bairro
                        </label>
                        <select id="create_zone_id" 
                                name="address_zone_id"
                                form="createCustomerForm"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                            <option value="">Selecione primeiro a cidade</option>
                        </select>
                    </div>
                </div>
                
                <!-- Rua -->
                <div>
                    <label for="create_street" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Rua
                    </label>
                    <input type="text" 
                           id="create_street" 
                           name="address_street"
                           form="createCustomerForm"
                           maxlength="255"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                           placeholder="Nome da rua">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Número -->
                    <div>
                        <label for="create_number" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Número
                        </label>
                        <input type="text" 
                               id="create_number" 
                               name="address_number"
                               form="createCustomerForm"
                               maxlength="20"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="123">
                    </div>
                    
                    <!-- Complemento -->
                    <div>
                        <label for="create_complement" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Complemento
                        </label>
                        <input type="text" 
                               id="create_complement" 
                               name="address_complement"
                               form="createCustomerForm"
                               maxlength="100"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Apto, Bloco, etc.">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Referência -->
                    <div>
                        <label for="create_reference" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Ponto de Referência
                        </label>
                        <input type="text" 
                               id="create_reference" 
                               name="address_reference"
                               form="createCustomerForm"
                               maxlength="255"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Próximo ao mercado...">
                    </div>
                    
                    <!-- Label do Endereço -->
                    <div>
                        <label for="create_address_label" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Identificação
                        </label>
                        <input type="text" 
                               id="create_address_label" 
                               name="address_label"
                               form="createCustomerForm"
                               maxlength="50"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Casa, Trabalho, etc.">
                    </div>
                </div>
                
                <div class="mt-2 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4m0-4h.01"/>
                        </svg>
                        O endereço é opcional. Você pode cadastrar depois ou quando o cliente fizer um pedido.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Card Informativo -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4m0-4h.01"/>
                    </svg>
                    Sobre o Cadastro
                </h4>
            </div>
            
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-green-50 border border-green-100">
                        <div class="h-8 w-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-800">WhatsApp como Identificador</p>
                            <p class="text-xs text-green-600 mt-0.5">O número de WhatsApp é usado para identificar o cliente nos pedidos.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 border border-blue-100">
                        <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Endereços Múltiplos</p>
                            <p class="text-xs text-blue-600 mt-0.5">O cliente poderá ter vários endereços cadastrados depois.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-purple-50 border border-purple-100">
                        <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-purple-800">Histórico Automático</p>
                            <p class="text-xs text-purple-600 mt-0.5">Pedidos serão vinculados automaticamente pelo WhatsApp.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<?php endif; ?>

</div>

<?php 
// Preparar dados de cidades e bairros para JavaScript
$cities = $cities ?? [];
$zones = $zones ?? [];
?>

<!-- Modal de Endereço (Criar/Editar) -->
<div id="addressModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddressModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-slate-900">Novo Endereço</h3>
            </div>
            
            <form id="addressForm" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <input type="hidden" id="addressId" name="addressId" value="">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Rótulo</label>
                        <select id="addrLabel" name="label" 
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                            <option value="">Selecione...</option>
                            <option value="Casa">🏠 Casa</option>
                            <option value="Trabalho">🏢 Trabalho</option>
                            <option value="Outro">📍 Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Cidade</label>
                        <?php if (!empty($cities)): ?>
                        <select id="addrCitySelect" name="city_id" onchange="filterNeighborhoods()"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                            <option value="">Selecione a cidade...</option>
                            <?php foreach ($cities as $city): ?>
                            <option value="<?= (int)$city['id'] ?>"><?= e($city['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" id="addrCity" name="city" 
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Nome da cidade">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Bairro</label>
                    <?php if (!empty($zones)): ?>
                    <select id="addrNeighborhoodSelect" name="neighborhood_select"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition">
                        <option value="">Selecione o bairro...</option>
                        <?php foreach ($zones as $zone): ?>
                        <option value="<?= e($zone['neighborhood']) ?>" data-city-id="<?= (int)$zone['city_id'] ?>">
                            <?= e($zone['neighborhood']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="addrNeighborhood" name="neighborhood" value="">
                    <?php else: ?>
                    <input type="text" id="addrNeighborhood" name="neighborhood" 
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                           placeholder="Nome do bairro">
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Rua <span class="text-red-500">*</span></label>
                        <input type="text" id="addrStreet" name="street" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Nome da rua">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Número <span class="text-red-500">*</span></label>
                        <input type="text" id="addrNumber" name="number" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Nº">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Complemento</label>
                    <input type="text" id="addrComplement" name="complement" 
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                           placeholder="Apto, Bloco, etc.">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Referência</label>
                    <input type="text" id="addrReference" name="reference" 
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 transition"
                           placeholder="Ponto de referência">
                </div>
                
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="addrIsDefault" name="is_default" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="addrIsDefault" class="text-sm text-slate-700">Definir como endereço principal</label>
                </div>
            </form>
            
            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end gap-3">
                <button type="button" onclick="closeAddressModal()" 
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="button" onclick="saveAddress()" 
                        class="rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white hover:opacity-90 transition">
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script para máscara de telefone e gerenciamento de endereços -->
<script>
const customerId = <?= (int)($customerId ?? 0) ?>;
const slug = '<?= e($slug) ?>';
const baseUrl = '<?= rtrim(base_url(''), '/') ?>/';

document.addEventListener('DOMContentLoaded', function() {
    // Máscara de WhatsApp
    const whatsappInput = document.getElementById('whatsapp');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            
            if (value.length > 2) {
                value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
            }
            if (value.length > 10) {
                value = value.slice(0, 10) + '-' + value.slice(10);
            }
            
            e.target.value = value;
        });
    }
    
    // Máscara de CPF
    const cpfInputs = document.querySelectorAll('input[name="cpf"]');
    cpfInputs.forEach(function(cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            
            if (value.length > 9) {
                value = value.slice(0, 3) + '.' + value.slice(3, 6) + '.' + value.slice(6, 9) + '-' + value.slice(9);
            } else if (value.length > 6) {
                value = value.slice(0, 3) + '.' + value.slice(3, 6) + '.' + value.slice(6);
            } else if (value.length > 3) {
                value = value.slice(0, 3) + '.' + value.slice(3);
            }
            
            e.target.value = value;
        });
    });
});

// Dados de cidades para filtrar bairros
const citiesData = <?= json_encode($cities) ?>;
const zonesData = <?= json_encode($zones) ?>;

function filterNeighborhoods() {
    const citySelect = document.getElementById('addrCitySelect');
    const neighborhoodSelect = document.getElementById('addrNeighborhoodSelect');
    
    if (!citySelect || !neighborhoodSelect) return;
    
    const selectedCityId = citySelect.value;
    const options = neighborhoodSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') {
            option.style.display = '';
            return;
        }
        const cityId = option.getAttribute('data-city-id');
        if (!selectedCityId || cityId === selectedCityId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Reset selection if current is hidden
    const currentOption = neighborhoodSelect.querySelector(`option[value="${neighborhoodSelect.value}"]`);
    if (currentOption && currentOption.style.display === 'none') {
        neighborhoodSelect.value = '';
    }
}

function openCreateAddressModal() {
    document.getElementById('modalTitle').textContent = 'Novo Endereço';
    document.getElementById('addressId').value = '';
    
    // Limpar campos
    const labelSelect = document.getElementById('addrLabel');
    if (labelSelect.tagName === 'SELECT') {
        labelSelect.value = '';
    } else {
        labelSelect.value = '';
    }
    
    const citySelect = document.getElementById('addrCitySelect');
    const cityInput = document.getElementById('addrCity');
    if (citySelect) citySelect.value = '';
    if (cityInput) cityInput.value = '';
    
    const neighborhoodSelect = document.getElementById('addrNeighborhoodSelect');
    const neighborhoodInput = document.getElementById('addrNeighborhood');
    if (neighborhoodSelect) neighborhoodSelect.value = '';
    if (neighborhoodInput) neighborhoodInput.value = '';
    
    document.getElementById('addrStreet').value = '';
    document.getElementById('addrNumber').value = '';
    document.getElementById('addrComplement').value = '';
    document.getElementById('addrReference').value = '';
    document.getElementById('addrIsDefault').checked = false;
    
    // Resetar filtro de bairros
    filterNeighborhoods();
    
    document.getElementById('addressModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditAddressModal(addressId, addressData) {
    document.getElementById('modalTitle').textContent = 'Editar Endereço';
    document.getElementById('addressId').value = addressId;
    
    // Label
    const labelSelect = document.getElementById('addrLabel');
    if (labelSelect.tagName === 'SELECT') {
        // Tentar selecionar opção existente
        const labelValue = addressData.label || '';
        let found = false;
        for (let opt of labelSelect.options) {
            if (opt.value.toLowerCase() === labelValue.toLowerCase()) {
                labelSelect.value = opt.value;
                found = true;
                break;
            }
        }
        if (!found && labelValue) {
            // Adicionar como "Outro" ou valor customizado
            labelSelect.value = 'Outro';
        }
    } else {
        labelSelect.value = addressData.label || '';
    }
    
    // Cidade
    const citySelect = document.getElementById('addrCitySelect');
    const cityInput = document.getElementById('addrCity');
    if (citySelect && addressData.city_id) {
        citySelect.value = addressData.city_id;
        filterNeighborhoods();
    } else if (citySelect && addressData.city) {
        // Tentar encontrar cidade pelo nome
        for (let opt of citySelect.options) {
            if (opt.textContent.toLowerCase() === addressData.city.toLowerCase()) {
                citySelect.value = opt.value;
                filterNeighborhoods();
                break;
            }
        }
    }
    if (cityInput) cityInput.value = addressData.city || '';
    
    // Bairro
    const neighborhoodSelect = document.getElementById('addrNeighborhoodSelect');
    const neighborhoodInput = document.getElementById('addrNeighborhood');
    if (neighborhoodSelect && addressData.neighborhood) {
        neighborhoodSelect.value = addressData.neighborhood;
    }
    if (neighborhoodInput) neighborhoodInput.value = addressData.neighborhood || '';
    
    document.getElementById('addrStreet').value = addressData.street || '';
    document.getElementById('addrNumber').value = addressData.number || '';
    document.getElementById('addrComplement').value = addressData.complement || '';
    document.getElementById('addrReference').value = addressData.reference || '';
    document.getElementById('addrIsDefault').checked = addressData.is_default == 1;
    
    document.getElementById('addressModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function saveAddress() {
    const addressId = document.getElementById('addressId').value;
    
    // Obter cidade
    let city = '';
    const citySelect = document.getElementById('addrCitySelect');
    const cityInput = document.getElementById('addrCity');
    if (citySelect && citySelect.value) {
        city = citySelect.options[citySelect.selectedIndex].textContent;
    } else if (cityInput) {
        city = cityInput.value;
    }
    
    // Obter bairro
    let neighborhood = '';
    const neighborhoodSelect = document.getElementById('addrNeighborhoodSelect');
    const neighborhoodInput = document.getElementById('addrNeighborhood');
    if (neighborhoodSelect && neighborhoodSelect.value) {
        neighborhood = neighborhoodSelect.value;
    } else if (neighborhoodInput) {
        neighborhood = neighborhoodInput.value;
    }
    
    // Obter label
    let label = '';
    const labelEl = document.getElementById('addrLabel');
    if (labelEl.tagName === 'SELECT') {
        label = labelEl.value;
    } else {
        label = labelEl.value;
    }
    
    const formData = {
        label: label,
        city: city,
        neighborhood: neighborhood,
        street: document.getElementById('addrStreet').value,
        number: document.getElementById('addrNumber').value,
        complement: document.getElementById('addrComplement').value,
        reference: document.getElementById('addrReference').value,
        is_default: document.getElementById('addrIsDefault').checked ? 1 : 0
    };
    
    // Validação
    if (!formData.street || !formData.number) {
        alert('Rua e número são obrigatórios');
        return;
    }
    
    try {
        let url;
        if (addressId) {
            url = `${baseUrl}admin/${slug}/customers/${customerId}/addresses/${addressId}`;
        } else {
            url = `${baseUrl}admin/${slug}/customers/${customerId}/addresses`;
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAddressModal();
            location.reload(); // Recarregar para mostrar mudanças
        } else {
            alert(result.error || 'Erro ao salvar endereço');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar endereço');
    }
}

async function deleteAddress(addressId) {
    if (!confirm('Tem certeza que deseja remover este endereço?')) {
        return;
    }
    
    try {
        const response = await fetch(`${baseUrl}admin/${slug}/customers/${customerId}/addresses/${addressId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.error || 'Erro ao remover endereço');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao remover endereço');
    }
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddressModal();
    }
});

// ==========================================
// Funções para formulário de CRIAÇÃO de cliente
// ==========================================

// Filtrar bairros por cidade na tela de criação
function filterCreateZonesByCity(cityId) {
    const zoneSelect = document.getElementById('create_zone_id');
    if (!zoneSelect) return;
    
    // Limpar opções atuais
    zoneSelect.innerHTML = '<option value="">Selecione um bairro</option>';
    
    if (!cityId) {
        zoneSelect.innerHTML = '<option value="">Selecione primeiro a cidade</option>';
        return;
    }
    
    // Filtrar zonas pela cidade selecionada
    const filteredZones = zonesData.filter(zone => zone.city_id == cityId);
    
    filteredZones.forEach(zone => {
        const option = document.createElement('option');
        option.value = zone.id;
        option.textContent = zone.neighborhood;
        zoneSelect.appendChild(option);
    });
    
    if (filteredZones.length === 0) {
        zoneSelect.innerHTML = '<option value="">Nenhum bairro cadastrado para esta cidade</option>';
    }
}

// ==========================================
// Validação de WhatsApp em tempo real
// ==========================================

let whatsappValidateTimeout = null;
let lastValidatedNumber = '';

function initWhatsappValidation() {
    const whatsappInput = document.getElementById('whatsapp_create');
    if (!whatsappInput) return;
    
    // Aplicar máscara de telefone
    whatsappInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        
        let formatted = '';
        if (value.length > 0) {
            formatted = '(' + value.substring(0, 2);
            if (value.length > 2) {
                formatted += ') ' + value.substring(2, 7);
            }
            if (value.length > 7) {
                formatted += '-' + value.substring(7, 11);
            }
        }
        e.target.value = formatted;
        
        // Validar após digitar
        clearTimeout(whatsappValidateTimeout);
        const cleanNumber = value;
        
        if (cleanNumber.length >= 10) {
            whatsappValidateTimeout = setTimeout(() => {
                validateWhatsappNumber(cleanNumber);
            }, 800); // Aguardar 800ms após parar de digitar
        } else {
            resetWhatsappStatus();
        }
    });
    
    // Validar ao sair do campo
    whatsappInput.addEventListener('blur', function(e) {
        const value = e.target.value.replace(/\D/g, '');
        if (value.length >= 10 && value !== lastValidatedNumber) {
            clearTimeout(whatsappValidateTimeout);
            validateWhatsappNumber(value);
        }
    });
}

function resetWhatsappStatus() {
    const statusEl = document.getElementById('whatsapp-status');
    const inputEl = document.getElementById('whatsapp_create');
    
    if (statusEl) {
        statusEl.classList.add('hidden');
        statusEl.textContent = '';
    }
    if (inputEl) {
        inputEl.classList.remove('border-green-500', 'border-red-500', 'border-amber-500');
    }
}

async function validateWhatsappNumber(number) {
    const statusEl = document.getElementById('whatsapp-status');
    const inputEl = document.getElementById('whatsapp_create');
    
    if (!statusEl || !inputEl) return;
    
    // Mostrar loading
    statusEl.textContent = '⏳ Verificando número...';
    statusEl.className = 'mt-1.5 text-xs text-slate-500';
    statusEl.classList.remove('hidden');
    inputEl.classList.remove('border-green-500', 'border-red-500', 'border-amber-500');
    
    try {
        const response = await fetch(`${baseUrl}admin/${slug}/customers/api/validate-whatsapp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ whatsapp: number })
        });
        
        const result = await response.json();
        lastValidatedNumber = number;
        
        if (result.exists_in_system) {
            // Número já cadastrado no sistema
            statusEl.innerHTML = `<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6m0-6l6 6"/></svg> ${result.error}</span>`;
            statusEl.className = 'mt-1.5 text-xs text-red-600 font-medium';
            inputEl.classList.add('border-red-500');
        } else if (result.success) {
            if (result.whatsapp_checked && result.whatsapp_valid === false) {
                // Número não existe no WhatsApp
                statusEl.innerHTML = '<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6m0-6l6 6"/></svg> Este número não existe no WhatsApp</span>';
                statusEl.className = 'mt-1.5 text-xs text-red-600 font-medium';
                inputEl.classList.add('border-red-500');
            } else if (result.whatsapp_checked && result.whatsapp_valid === true) {
                // Número válido no WhatsApp
                statusEl.innerHTML = '<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg> Número válido no WhatsApp!</span>';
                statusEl.className = 'mt-1.5 text-xs text-green-600 font-medium';
                inputEl.classList.add('border-green-500');
            } else {
                // Não foi possível verificar (Evolution offline)
                statusEl.innerHTML = '<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg> Número disponível (não foi possível verificar no WhatsApp)</span>';
                statusEl.className = 'mt-1.5 text-xs text-amber-600';
                inputEl.classList.add('border-amber-500');
            }
        } else {
            // Erro na validação
            statusEl.innerHTML = `<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6m0-6l6 6"/></svg> ${result.error || 'Erro ao validar número'}</span>`;
            statusEl.className = 'mt-1.5 text-xs text-red-600';
            inputEl.classList.add('border-red-500');
        }
        
    } catch (error) {
        console.error('Erro ao validar WhatsApp:', error);
        statusEl.innerHTML = '<span class="flex items-center gap-1"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg> Erro ao verificar número</span>';
        statusEl.className = 'mt-1.5 text-xs text-amber-600';
        inputEl.classList.add('border-amber-500');
    }
}

// Inicializar validação quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', initWhatsappValidation);
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
