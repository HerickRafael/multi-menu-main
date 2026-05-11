<?php
/**
 * iFood Integration Configuration Page
 */
$title = 'Integração iFood - ' . ($company['name'] ?? '');
$slug = rawurlencode((string)($activeSlug ?? $company['slug'] ?? ''));

ob_start(); ?>

<div class="mx-auto max-w-4xl p-4">
    <?php
    // Header
    $pageTitle = 'Integração iFood';
    $pageDescription = 'Configure a integração com o iFood para receber pedidos automaticamente';
    $pageIcon = '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $breadcrumbs = [
        ['label' => 'Configurações', 'url' => base_url('admin/' . $slug . '/settings')],
        ['label' => 'iFood']
    ];
    $actions = [];
    include __DIR__ . '/../components/page-header.php';
    ?>

    <!-- Status Badge -->
    <div class="mb-6 flex items-center gap-3">
        <?php if ($config && $config['is_active']): ?>
            <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-4 py-2 text-sm font-medium text-green-700">
                <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                Integração Ativa
            </span>
        <?php else: ?>
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                Integração Inativa
            </span>
        <?php endif; ?>
    </div>

    <!-- Error Banner -->
    <?php if ($config && !empty($config['last_error'])): ?>
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-amber-800">Último erro</p>
                    <p class="text-sm text-amber-700"><?= htmlspecialchars($config['last_error']) ?></p>
                </div>
                <button type="button" onclick="clearError()" class="shrink-0 text-amber-600 hover:text-amber-800" title="Limpar erro">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-medium text-blue-800">Como configurar</p>
                <ol class="mt-2 text-sm text-blue-700 list-decimal list-inside space-y-1">
                    <li>Acesse <a href="https://developer.ifood.com.br" target="_blank" class="underline hover:text-blue-900">developer.ifood.com.br</a> e crie uma conta</li>
                    <li>Crie um aplicativo e obtenha o <strong>Client ID</strong> e <strong>Client Secret</strong></li>
                    <li>Solicite as permissões necessárias (Order, Merchant)</li>
                    <li>Preencha os dados abaixo e salve</li>
                    <li>Clique em "Testar Conexão" para verificar</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="POST" action="<?= base_url('admin/' . $slug . '/ifood/config/save') ?>" id="ifoodConfigForm">
            <div class="p-6 space-y-6">
                <!-- Credentials Section -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Credenciais da API</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Client ID <span class="text-red-500">*</span>
                                <a href="/admin/<?= $slug ?>/guide/ifood#credentials" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
                            </label>
                            <input type="text" name="client_id" 
                                   value="<?= htmlspecialchars($config['client_id'] ?? '') ?>"
                                   placeholder="Ex: 3c587f8f-fb22-46a7-88f8-781246a3ea3f"
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            <p class="mt-1 text-xs text-slate-500">Obtido no painel de desenvolvedores do iFood</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Client Secret <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="client_secret" id="clientSecret"
                                       placeholder="<?= !empty($config['client_secret']) ? '••••••••••••••••' : 'Cole seu Client Secret' ?>"
                                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pr-10 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Deixe em branco para manter o atual</p>
                        </div>
                    </div>
                </div>

                <!-- Merchant Section -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Loja</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Merchant ID</label>
                            <?php if (!empty($merchants)): ?>
                                <select name="merchant_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                    <option value="">Selecione uma loja...</option>
                                    <?php foreach ($merchants as $merchant): ?>
                                        <option value="<?= htmlspecialchars($merchant['id']) ?>"
                                                <?= ($config['merchant_id'] ?? '') === $merchant['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($merchant['name'] ?? $merchant['id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="merchant_id" 
                                       value="<?= htmlspecialchars($config['merchant_id'] ?? '') ?>"
                                       placeholder="ID da sua loja no iFood"
                                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            <?php endif; ?>
                            <p class="mt-1 text-xs text-slate-500">Após testar a conexão, as lojas disponíveis aparecerão aqui</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Status da Loja</label>
                            <?php
                            // getMerchantStatus retorna array de status por canal
                            $isOnline = false;
                            $statusMessage = '';
                            if ($merchantStatus && is_array($merchantStatus)) {
                                foreach ($merchantStatus as $s) {
                                    if (isset($s['available']) && $s['available']) {
                                        $isOnline = true;
                                        $statusMessage = $s['message']['title'] ?? 'Online';
                                        break;
                                    }
                                }
                                if (!$isOnline && !empty($merchantStatus[0]['message']['title'])) {
                                    $statusMessage = $merchantStatus[0]['message']['title'];
                                }
                            }
                            ?>
                            <?php if ($merchantStatus): ?>
                                <div class="flex items-center gap-2 py-2.5">
                                    <?php if ($isOnline): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">
                                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                            <?= htmlspecialchars($statusMessage ?: 'Online') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                            <?= htmlspecialchars($statusMessage ?: 'Offline') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="py-2.5 text-sm text-slate-500">Configure e salve para ver o status</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Webhook Section -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Webhook</h3>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-slate-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-700 mb-1">URL do Webhook</p>
                                <div class="flex items-center gap-2">
                                    <code class="block flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 font-mono select-all truncate"><?= htmlspecialchars(base_url('webhook/ifood')) ?></code>
                                    <button type="button" onclick="copyWebhookUrl()" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" title="Copiar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                        Copiar
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    Configure esta URL no <a href="https://developer.ifood.com.br" target="_blank" class="underline hover:text-slate-700">portal do desenvolvedor iFood</a> 
                                    (seção Webhook) para receber eventos em tempo real.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options Section -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Opções</h3>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= ($config['is_active'] ?? false) ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-slate-800">Integração Ativa</span>
                                <p class="text-sm text-slate-500">Ative para começar a receber pedidos</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="auto_confirm" value="1"
                                   <?= ($config['auto_confirm'] ?? false) ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-slate-800">Confirmar Automaticamente</span>
                                <a href="/admin/<?= $slug ?>/guide/ifood#credentials" class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-xs text-slate-400 hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50 transition-colors" title="Ajuda">?</a>
                                <p class="text-sm text-slate-500">Confirma pedidos sem intervenção manual</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between gap-4 border-t border-slate-200 bg-slate-50 px-6 py-4 rounded-b-2xl">
                <button type="button" onclick="testConnection()" 
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Testar Conexão
                </button>
                <button type="submit" 
                        class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Salvar Configuração
                </button>
            </div>
        </form>
    </div>

    <!-- Test Connection Result -->
    <div id="connectionResult" class="mt-6 hidden">
        <div class="rounded-xl border p-4" id="connectionResultContent"></div>
    </div>

    <!-- Quick Actions -->
    <?php if ($config && $config['is_active']): ?>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="<?= base_url('admin/' . $slug . '/orders?source=ifood') ?>" 
           class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-slate-800">Ver Pedidos iFood</h4>
                <p class="text-sm text-slate-500">Gerenciar pedidos recebidos do iFood</p>
            </div>
            <svg class="ml-auto h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        <button type="button" onclick="pollOrders()"
                class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow text-left">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-slate-800">Buscar Pedidos</h4>
                <p class="text-sm text-slate-500">Sincronizar pedidos manualmente</p>
            </div>
            <svg class="ml-auto h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function copyWebhookUrl() {
    const url = '<?= htmlspecialchars(base_url('webhook/ifood'), ENT_QUOTES) ?>';
    navigator.clipboard.writeText(url).then(() => {
        const btn = event.currentTarget;
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg> Copiado!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}

function clearError() {
    fetch('<?= base_url('admin/' . $slug . '/ifood/clear-error') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
        const banner = event.currentTarget.closest('.mb-6');
        if (banner) banner.remove();
    });
}

function togglePassword() {
    const input = document.getElementById('clientSecret');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
    }
}

function testConnection() {
    const resultDiv = document.getElementById('connectionResult');
    const resultContent = document.getElementById('connectionResultContent');
    
    resultDiv.classList.remove('hidden');
    resultContent.className = 'rounded-xl border border-blue-200 bg-blue-50 p-4';
    resultContent.innerHTML = '<div class="flex items-center gap-3"><svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-blue-800">Testando conexão com o iFood...</span></div>';

    fetch('<?= base_url('admin/' . $slug . '/ifood/test-connection') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultContent.className = 'rounded-xl border border-green-200 bg-green-50 p-4';
            let merchantsList = '';
            if (data.merchants && data.merchants.length > 0) {
                merchantsList = '<ul class="mt-2 space-y-1">';
                data.merchants.forEach(m => {
                    merchantsList += '<li class="text-sm text-green-700">• ' + (m.name || m.id) + '</li>';
                });
                merchantsList += '</ul>';
            }
            resultContent.innerHTML = '<div class="flex items-start gap-3"><svg class="h-5 w-5 text-green-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        } else {
            resultContent.className = 'rounded-xl border border-red-200 bg-red-50 p-4';
            resultContent.innerHTML = '<div class="flex items-start gap-3"><svg class="h-5 w-5 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    })
    .catch(error => {
        resultContent.className = 'rounded-xl border border-red-200 bg-red-50 p-4';
        resultContent.innerHTML = '<div class="flex items-start gap-3"><svg class="h-5 w-5 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    });
}

function pollOrders() {
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.querySelector('h4').textContent = 'Buscando...';

    fetch('<?= base_url('admin/' . $slug . '/ifood/poll') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        
        if (data.success) {
            if (data.processed > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pedidos Encontrados!',
                        text: data.processed + ' evento(s) processado(s)',
                        confirmButtonText: 'Ver Pedidos',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '<?= base_url('admin/' . $slug . '/ifood/orders') ?>';
                        }
                    });
                } else {
                    alert('Encontrados ' + data.processed + ' pedidos!');
                    window.location.href = '<?= base_url('admin/' . $slug . '/ifood/orders') ?>';
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Nenhum pedido novo',
                        text: 'Não há novos pedidos no momento.',
                    });
                } else {
                    alert('Nenhum pedido novo encontrado.');
                }
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.error || 'Erro ao buscar pedidos',
                });
            } else {
                alert('Erro: ' + (data.error || 'Erro ao buscar pedidos'));
            }
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message,
            });
        } else {
            alert('Erro: ' + error.message);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
