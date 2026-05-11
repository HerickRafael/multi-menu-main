<?php
// admin/api/index.php - Gerenciamento de API
ob_start();

// Normalização de dados
$company = is_array($company ?? null) ? $company : [];
$user = is_array($user ?? null) ? $user : [];
$apiData = is_array($apiData ?? null) ? $apiData : ['tokens' => [], 'api_keys' => []];
$stats = is_array($stats ?? null) ? $stats : ['requests_today' => 0, 'total_requests' => 0, 'top_endpoints' => []];
$endpoints = is_array($endpoints ?? null) ? $endpoints : [];

$activeSlug = (string)($activeSlug ?? '');
$baseUrl = (string)($baseUrl ?? '');
$title = 'Gerenciamento de API - ' . ($company['name'] ?? 'Empresa');

// Helpers
$formatDate = function($date) {
    return $date ? date('d/m/Y H:i', strtotime($date)) : '--';
};

$formatScopes = function($scopes) {
    if (is_string($scopes)) {
        $scopes = json_decode($scopes, true) ?? [];
    }
    return is_array($scopes) ? implode(', ', $scopes) : '';
};

$getStatusBadge = function($expiresAt = null, $isActive = true) {
    if (!$isActive) {
        return '<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Revogado</span>';
    }
    if ($expiresAt && strtotime($expiresAt) < time()) {
        return '<span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Expirado</span>';
    }
    return '<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Ativo</span>';
};

ob_start(); ?>

<div class="mx-auto max-w-7xl p-4" x-data="apiManagement()">

<!-- BREADCRUMB -->
<nav class="mb-4 flex items-center gap-2 text-sm text-slate-500 flex-wrap">
  <a href="<?= e(base_url('admin/' . $activeSlug . '/dashboard')) ?>" class="hover:text-slate-700 transition flex items-center gap-1">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Dashboard
  </a>
  <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <span class="text-slate-900 font-medium">API</span>
</nav>

<!-- HERO / TOPO -->
<section class="relative mb-8 overflow-hidden rounded-3xl border border-slate-200 admin-gradient-bg text-white">
    <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-black/10 blur-3xl"></div>

    <div class="relative z-10 grid gap-4 p-6 md:grid-cols-[auto_1fr_auto] md:items-center md:p-8">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 p-0.5 ring-1 ring-white/30">
            <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>

        <div class="text-white">
            <h1 class="text-3xl font-bold leading-tight">
                API Management
            </h1>
            <p class="mt-2 text-lg text-white/90">
                Gerencie tokens JWT e chaves de API para <?= e($company['name'] ?? 'sua empresa') ?>
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/20 px-3 py-1 text-sm">JWT Tokens</span>
                <span class="rounded-full bg-white/20 px-3 py-1 text-sm">API Keys</span>
                <span class="rounded-full bg-white/20 px-3 py-1 text-sm">Rate Limited</span>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="<?= e(base_url('admin/' . $activeSlug .'/dashboard')) ?>" 
               class="inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/30 hover:bg-white/30 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" /></svg>
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</section>

<!-- ESTATÍSTICAS -->
<div class="mb-8 grid gap-6 md:grid-cols-3">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Requisições Hoje</p>
                <p class="text-2xl font-bold text-blue-600"><?= number_format($stats['requests_today']) ?></p>
            </div>
            <div class="rounded-xl bg-blue-100 p-3">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Total de Requisições</p>
                <p class="text-2xl font-bold text-green-600"><?= number_format($stats['total_requests']) ?></p>
            </div>
            <div class="rounded-xl bg-green-100 p-3">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Tokens Ativos</p>
                <p class="text-2xl font-bold text-purple-600"><?= count($apiData['tokens']) ?></p>
            </div>
            <div class="rounded-xl bg-purple-100 p-3">
                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- CONTEÚDO PRINCIPAL -->
<div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
    
    <!-- TOKENS E CHAVES -->
    <main class="space-y-8">
        
        <!-- JWT TOKENS -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900">🔑 JWT Tokens</h2>
                <button @click="showGenerateToken = true" 
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Gerar Token
                </button>
            </div>

            <?php if (empty($apiData['tokens'])): ?>
                <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <p class="mt-2 text-sm text-slate-500">Nenhum token JWT encontrado</p>
                    <p class="text-xs text-slate-400">Gere seu primeiro token para começar a usar a API</p>
                </div>
            <?php else: ?>
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Token</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Scopes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Expira</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($apiData['tokens'] as $token): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-slate-500 mb-1">
                                            Criado em <?= $formatDate($token['created_at']) ?>
                                        </div>
                                        <div class="text-xs text-slate-700 break-all font-mono bg-slate-100 rounded p-1">
                                            <?= e($token['jwt_raw'] ?? '—') ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-500">
                                        <?= e($formatScopes($token['scopes'])) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-500">
                                        <?= $token['expires_at'] ? $formatDate($token['expires_at']) : 'Nunca' ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= $getStatusBadge($token['expires_at']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        $isExpired = $token['expires_at'] && strtotime($token['expires_at']) < time();
                                        if (!$isExpired): 
                                        ?>
                                            <button @click="revokeToken(<?= $token['id'] ?>)" 
                                                    class="text-sm text-red-600 hover:text-red-900">
                                                Revogar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- API KEYS -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900">🗝️ API Keys</h2>
                <button @click="showGenerateApiKey = true" 
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Gerar API Key
                </button>
            </div>

            <?php if (empty($apiData['api_keys'])): ?>
                <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <p class="mt-2 text-sm text-slate-500">Nenhuma API Key encontrada</p>
                    <p class="text-xs text-slate-400">Gere sua primeira chave para acesso à API</p>
                </div>
            <?php else: ?>
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Chave</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Scopes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($apiData['api_keys'] as $key): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-slate-900">
                                            <?= e($key['name']) ?>
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Criada em <?= $formatDate($key['created_at']) ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-sm text-slate-600">
                                            <?= substr($key['key_hash'], 0, 12) ?>...
                                        </code>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-500">
                                        <?= e($formatScopes($key['scopes'])) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= $getStatusBadge($key['expires_at'], $key['is_active'] ?? true) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        $isActive = $key['is_active'] ?? true;
                                        $isExpired = $key['expires_at'] && strtotime($key['expires_at']) < time();
                                        if ($isActive && !$isExpired): 
                                        ?>
                                            <button @click="revokeApiKey(<?= $key['id'] ?>)" 
                                                    class="text-sm text-red-600 hover:text-red-900">
                                                Revogar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- SIDEBAR -->
    <aside class="space-y-6">
        
        <!-- ENDPOINTS -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-4 font-semibold text-slate-900">📡 Endpoints Disponíveis</h3>
            <div class="space-y-2">
                <?php foreach ($endpoints as $endpoint): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="rounded px-2 py-1 text-xs font-medium <?= $endpoint['method'] === 'GET' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                            <?= e($endpoint['method']) ?>
                        </span>
                        <code class="text-slate-600"><?= e($endpoint['path']) ?></code>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ESTATÍSTICAS DETALHADAS -->
        <?php if (!empty($stats['top_endpoints'])): ?>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 font-semibold text-slate-900">📊 Endpoints Mais Usados</h3>
                <div class="space-y-3">
                    <?php foreach ($stats['top_endpoints'] as $endpoint): ?>
                        <div class="flex items-center justify-between">
                            <code class="text-sm text-slate-600"><?= e($endpoint['endpoint']) ?></code>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                <?= number_format($endpoint['count']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- INFORMAÇÕES DA API -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-4 font-semibold text-slate-900">ℹ️ Informações da API</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-slate-600">Base URL:</span>
                    <code class="block text-slate-900"><?= e($baseUrl) ?></code>
                </div>
                <div>
                    <span class="text-slate-600">Rate Limit:</span>
                    <span class="text-slate-900">1000 req/min</span>
                </div>
                <div>
                    <span class="text-slate-600">Formato:</span>
                    <span class="text-slate-900">JSON</span>
                </div>
                <div>
                    <span class="text-slate-600">Autenticação:</span>
                    <span class="text-slate-900">JWT + API Key</span>
                </div>
            </div>
        </div>

    </aside>
</div>

<!-- MODAL GERAR TOKEN -->
<div x-show="showGenerateToken" x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @click.self="showGenerateToken = false">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold">Gerar JWT Token</h3>
        
        <form @submit.prevent="generateToken()">
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-slate-700">Expiração (segundos)</label>
                <select x-model="tokenForm.expires_in" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="3600">1 hora</option>
                    <option value="86400">24 horas</option>
                    <option value="604800">7 dias</option>
                    <option value="2592000">30 dias</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-slate-700">Permissões</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="tokenForm.scopes" value="read" class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Leitura (read)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="tokenForm.scopes" value="write" class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Escrita (write)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="tokenForm.scopes" value="admin" class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Administração (admin)</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" @click="showGenerateToken = false" 
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Gerar Token
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL GERAR API KEY -->
<div x-show="showGenerateApiKey" x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @click.self="showGenerateApiKey = false">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold">Gerar API Key</h3>
        
        <form @submit.prevent="generateApiKey()">
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-slate-700">Nome</label>
                <input type="text" x-model="apiKeyForm.name" placeholder="Ex: App Mobile"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-slate-700">Permissões</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="apiKeyForm.scopes" value="read" class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Leitura (read)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="apiKeyForm.scopes" value="write" class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Escrita (write)</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" @click="showGenerateApiKey = false" 
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Gerar Chave
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RESULTADO -->
<div x-show="showResult" x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @click.self="showResult = false">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold" x-text="resultTitle"></h3>
        <div class="mb-4 rounded-lg bg-slate-100 p-4">
            <code class="break-all text-sm" x-text="resultContent"></code>
        </div>
        <div class="flex gap-3">
            <button @click="copyToClipboard(resultContent)" 
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                📋 Copiar
            </button>
            <button @click="showResult = false" 
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div x-show="showToast" x-transition 
     class="fixed bottom-4 right-4 z-50 rounded-lg bg-green-600 px-4 py-3 text-white shadow-lg">
    <span x-text="toastMessage"></span>
</div>

</div>

<style>
[x-cloak] { display: none !important; }
</style>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function apiManagement() {
    return {
        showGenerateToken: false,
        showGenerateApiKey: false,
        showResult: false,
        showToast: false,
        toastMessage: '',
        resultTitle: '',
        resultContent: '',
        
        tokenForm: {
            expires_in: 86400,
            scopes: ['read', 'write']
        },
        
        apiKeyForm: {
            name: '',
            scopes: ['read']
        },

        async generateToken() {
            try {
                const response = await fetch(`<?= base_url("admin/{$activeSlug}/api/generate-token") ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.tokenForm)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showGenerateToken = false;
                    this.resultTitle = 'JWT Token Gerado';
                    this.resultContent = data.data.token;
                    this.showResult = true;
                    this.showToastMessage('Token gerado com sucesso!');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    this.showToastMessage('Erro: ' + data.message);
                }
            } catch (error) {
                this.showToastMessage('Erro ao gerar token');
            }
        },

        async generateApiKey() {
            try {
                const response = await fetch(`<?= base_url("admin/{$activeSlug}/api/generate-key") ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.apiKeyForm)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showGenerateApiKey = false;
                    this.resultTitle = 'API Key Gerada';
                    this.resultContent = data.data.api_key;
                    this.showResult = true;
                    this.showToastMessage('API Key gerada com sucesso!');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    this.showToastMessage('Erro: ' + data.message);
                }
            } catch (error) {
                this.showToastMessage('Erro ao gerar API Key');
            }
        },

        async revokeToken(tokenId) {
            if (!confirm('Tem certeza que deseja revogar este token?')) return;
            
            try {
                const response = await fetch(`<?= base_url("admin/{$activeSlug}/api/revoke-token") ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({token_id: tokenId})
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToastMessage('Token revogado com sucesso!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToastMessage('Erro: ' + data.message);
                }
            } catch (error) {
                this.showToastMessage('Erro ao revogar token');
            }
        },

        async revokeApiKey(keyId) {
            if (!confirm('Tem certeza que deseja revogar esta API Key?')) return;
            
            try {
                const response = await fetch(`<?= base_url("admin/{$activeSlug}/api/revoke-key") ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({key_id: keyId})
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToastMessage('API Key revogada com sucesso!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToastMessage('Erro: ' + data.message);
                }
            } catch (error) {
                this.showToastMessage('Erro ao revogar API Key');
            }
        },

        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showToastMessage('Copiado para área de transferência!');
            } catch (error) {
                this.showToastMessage('Erro ao copiar');
            }
        },

        showToastMessage(message) {
            this.toastMessage = message;
            this.showToast = true;
            setTimeout(() => {
                this.showToast = false;
            }, 3000);
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>