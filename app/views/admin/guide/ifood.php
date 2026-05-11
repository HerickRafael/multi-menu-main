<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia iFood';
$pageDescription = 'Credenciais, webhook, pedidos e fluxo de integração';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>';
$breadcrumbs = [
    ['label' => 'iFood', 'url' => base_url('admin/' . $slug . '/ifood/config')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Configuração iFood', 'url' => base_url('admin/' . $slug . '/ifood/config'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>', 'primary' => true]
];

ob_start();
?>

<style>
.gc-nav{position:sticky;top:80px}
.gc-nav a{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;font-size:13px;color:#64748b;text-decoration:none;transition:all .2s;border-left:3px solid transparent}
.gc-nav a:hover{color:#334155;background:#f1f5f9}
.gc-nav a.active{color:#fff;background:var(--admin-primary-color);border-left-color:var(--admin-primary-color);font-weight:600}
.gc-nav a.active svg{color:#fff}
.gc-nav .nav-group{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:16px 12px 4px}
.gc-cta{display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--admin-primary-gradient,var(--admin-primary-color))!important;background-color:var(--admin-primary-color)!important;border-radius:12px;color:#fff!important;text-decoration:none;font-size:14px;font-weight:600;margin-top:16px;transition:opacity .2s}
.gc-cta:hover{opacity:.9;color:#fff!important}
.gc-cta svg{color:#fff!important}
.gc-sec{scroll-margin-top:100px}
.gc-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;margin-bottom:24px}
.gc-card h2{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;gap:10px}
.gc-card h2 svg{color:var(--admin-primary-color)}
.gc-card h2 .ic{width:38px;height:38px;border-radius:10px;background:var(--admin-primary-color,#6366f1);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.gc-card h2 .ic svg{color:#fff}
.gc-card h3{font-size:16px;font-weight:600;color:#1e293b;margin:20px 0 8px;display:flex;align-items:center;gap:8px}
.gc-card p{font-size:14px;color:#475569;line-height:1.7;margin-bottom:12px}
.gc-sec > h2{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.gc-sec > h2 svg{color:var(--admin-primary-color)}
.gc-sec > h2 .gc-icon{width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--admin-primary-color,#6366f1) 12%,white);color:var(--admin-primary-color,#6366f1);font-size:15px}
.gc-table{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px}
.gc-table th{text-align:left;padding:8px 10px;background:color-mix(in srgb,var(--admin-primary-color,#6366f1) 8%,white);color:#334155;font-weight:600;border-bottom:2px solid #e2e8f0}
.gc-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#475569}
.gc-table tr:last-child td{border-bottom:none}
.gc-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#92400e;margin-top:10px}
.gc-tip strong{color:#78350f}
.gc-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#991b1b;margin-top:10px}
.gc-warn strong{color:#7f1d1d}
.gc-code{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.82rem;color:#334155;margin-top:8px;word-break:break-all}
.gc-badge{display:inline-block;padding:2px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.gc-badge-green{background:#dcfce7;color:#166534}
.gc-badge-yellow{background:#fef3c7;color:#92400e}
.gc-badge-blue{background:#dbeafe;color:#1e40af}
.gc-badge-red{background:#fee2e2;color:#991b1b}
</style>

<div class="mx-auto max-w-7xl p-4">
<?php include __DIR__ . '/../components/page-header.php'; ?>

<div style="display:grid;grid-template-columns:240px 1fr;gap:32px;align-items:start;">

<!-- Sidebar -->
<nav class="gc-nav" style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px 10px;">
    <div class="nav-group">Guia</div>
    <a href="#overview" class="active" data-section="overview">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        Visão Geral
    </a>
    <a href="#setup" data-section="setup">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Configuração
    </a>
    <a href="#credentials" data-section="credentials">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        Credenciais
    </a>
    <a href="#webhook" data-section="webhook">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
        Webhook
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#orders" data-section="orders">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        Pedidos
    </a>
    <a href="#workflow" data-section="workflow">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Fluxo
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/ifood/config')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Ir para Config iFood
    </a>
</nav>

<!-- Main -->
<div>

<!-- Visão Geral -->
  <section class="gc-sec" id="overview">
    <h2><span class="gc-icon">🍔</span> Visão Geral</h2>
    <div class="gc-card">
      <p>A integração com o iFood permite receber pedidos diretamente no painel Multi-Menu, sem precisar do app do iFood aberto.</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Funcionalidade</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Receber pedidos</strong></td><td>Pedidos do iFood aparecem automaticamente no painel</td></tr>
          <tr><td><strong>Gerenciar status</strong></td><td>Confirmar, preparar, despachar e cancelar pela plataforma</td></tr>
          <tr><td><strong>Auto-confirmar</strong></td><td>Opção para aceitar pedidos automaticamente</td></tr>
          <tr><td><strong>Status da loja</strong></td><td>Veja se a loja está Online ou Offline no iFood</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Configuração Inicial -->
  <section class="gc-sec" id="setup">
    <h2><span class="gc-icon">📋</span> Passo a Passo</h2>
    <div class="gc-card">
      <ol>
        <li>Acesse o <strong>Portal do Desenvolvedor iFood</strong> e crie um aplicativo</li>
        <li>Copie o <strong>Client ID</strong> e <strong>Client Secret</strong> gerados</li>
        <li>Cole as credenciais na página de configuração e <strong>salve</strong></li>
        <li>Clique em <strong>"Testar Conexão"</strong> para validar</li>
        <li>Selecione o <strong>Merchant ID</strong> (loja) na lista que aparece</li>
        <li>Configure a <strong>URL do Webhook</strong> no portal do iFood</li>
        <li>Ative a integração e pronto!</li>
      </ol>
    </div>
  </section>

  <!-- Credenciais -->
  <section class="gc-sec" id="credentials">
    <h2><span class="gc-icon">🔑</span> Credenciais</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Client ID</strong></td><td>Identificador único do seu app no iFood. Formato UUID (ex: 3c587f8f-fb22-46a7-...)</td></tr>
          <tr><td><strong>Client Secret</strong></td><td>Chave secreta para autenticação. <strong>Nunca compartilhe.</strong> Ao editar, deixe vazio para manter o atual.</td></tr>
          <tr><td><strong>Merchant ID</strong></td><td>ID da sua loja no iFood. Após testar a conexão com sucesso, a lista de lojas disponíveis é carregada automaticamente.</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Onde obter:</strong> Acesse <em>developer.ifood.com.br</em> → Meus Aplicativos → Crie ou selecione um app → Credenciais.
      </div>
    </div>
    <div class="gc-card">
      <h3>Opções</h3>
      <table class="gc-table">
        <thead><tr><th>Toggle</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Integração Ativa</strong></td><td>Liga/desliga a integração. Desativada = não recebe novos pedidos do iFood.</td></tr>
          <tr><td><strong>Confirmar Automaticamente</strong></td><td>Se ativado, pedidos são aceitos automaticamente sem intervenção manual.</td></tr>
        </tbody>
      </table>
      <div class="gc-warn">
        <strong>Auto-confirmar:</strong> Use com cautela. Se a loja estiver sem capacidade, os pedidos serão aceitos mesmo assim. Ideal para operações estáveis com boa previsibilidade.
      </div>
    </div>
  </section>

  <!-- Webhook -->
  <section class="gc-sec" id="webhook">
    <h2><span class="gc-icon">🔗</span> Webhook</h2>
    <div class="gc-card">
      <p>O webhook é a conexão em tempo real entre o iFood e o Multi-Menu. Configure a URL exibida na tela de configuração no portal do desenvolvedor iFood:</p>
      <div class="gc-code">/webhook/ifood</div>
      <ul style="margin-top:10px">
        <li>No portal iFood: <strong>Configurações → Webhook → URL</strong></li>
        <li>Cole a URL completa gerada pelo sistema</li>
        <li>O webhook recebe: novos pedidos, mudanças de status e cancelamentos</li>
      </ul>
    </div>
  </section>

  <!-- Pedidos -->
  <section class="gc-sec" id="orders">
    <h2><span class="gc-icon">📦</span> Pedidos iFood</h2>
    <div class="gc-card">
      <p>Pedidos recebidos do iFood passam pelos seguintes status:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Status</th><th>Ação</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><span class="gc-badge gc-badge-yellow">Novo</span></td><td>Confirmar</td><td>Pedido chegou — aceite para iniciar preparo</td></tr>
          <tr><td><span class="gc-badge gc-badge-blue">Confirmado</span></td><td>Pronto</td><td>Em preparo — marque quando estiver pronto</td></tr>
          <tr><td><span class="gc-badge gc-badge-green">Pronto</span></td><td>Despachar</td><td>Pronto para entrega — despache para o entregador</td></tr>
          <tr><td><span class="gc-badge gc-badge-green">Despachado</span></td><td>—</td><td>Em rota de entrega</td></tr>
          <tr><td><span class="gc-badge gc-badge-red">Cancelado</span></td><td>Cancelar</td><td>Selecione motivo (obrigatório pelo iFood)</td></tr>
        </tbody>
      </table>
    </div>
    <div class="gc-card">
      <h3>Detalhe do Pedido</h3>
      <p>Cada pedido mostra: itens com quantidades e preços, endereço completo, forma de pagamento, subtotais, e timeline de eventos.</p>
    </div>
  </section>

  <!-- Fluxo -->
  <section class="gc-sec" id="workflow">
    <h2><span class="gc-icon">🔄</span> Fluxo Completo</h2>
    <div class="gc-card">
      <ol>
        <li><strong>Cliente pede no iFood</strong> → pedido chega via webhook</li>
        <li><strong>Pedido aparece no painel</strong> como "Novo"</li>
        <li>Você clica <strong>"Confirmar"</strong> (ou é auto-confirmado)</li>
        <li>Prepara o pedido e clica <strong>"Pronto"</strong></li>
        <li>Entregador chega e você clica <strong>"Despachar"</strong></li>
        <li>Pedido entregue — status final</li>
      </ol>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gc-sec" id="tips">
    <h2><span class="gc-icon">💡</span> Dicas</h2>
    <div class="gc-card">
      <ul>
        <li><strong>"Testar Conexão"</strong> valida as credenciais e carrega a lista de lojas. Faça isso sempre após mudar Client ID/Secret.</li>
        <li><strong>Webhook deve ser HTTPS</strong> — o iFood não aceita URLs HTTP simples.</li>
        <li><strong>Cancelamento</strong> requer selecionar um motivo da lista do iFood — faz parte do protocolo da API.</li>
        <li><strong>Pedidos do iFood</strong> aparecem separados dos pedidos do cardápio próprio. Filtros na listagem ajudam a organizar.</li>
        <li><strong>Status da loja</strong> reflete o que aparece no app do cliente. Se está "Offline", clientes não veem sua loja.</li>
      </ul>
    </div>
  </section>

</div>
</div>
<div style="height:80px;"></div>
</div>

<script>
(function(){var secs=document.querySelectorAll('.gc-sec'),links=document.querySelectorAll('.gc-nav a[data-section]');function up(){var y=window.scrollY+150,c='';var atBottom=(window.innerHeight+window.scrollY)>=(document.documentElement.scrollHeight-80);if(atBottom&&secs.length){c=secs[secs.length-1].id}else{secs.forEach(function(s){if(s.offsetTop<=y)c=s.id})}links.forEach(function(a){a.classList.toggle('active',a.dataset.section===c)})}window.addEventListener('scroll',up);up();links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.dataset.section);if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})})})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
