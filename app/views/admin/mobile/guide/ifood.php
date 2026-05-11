<?php
$pageTitle = $pageTitle ?? 'Guia iFood';
$activeNav = $activeNav ?? 'ifood';
$showBackButton = $showBackButton ?? true;
?>

<style>
:root{--primary:var(--admin-primary-color,#4361ee);--primary-light:color-mix(in srgb,var(--primary) 12%,white);--primary-dark:color-mix(in srgb,var(--primary) 60%,black)}
.gi-wrap{padding:16px 16px 100px}
.gi-title{font-size:1.25rem;font-weight:800;color:var(--text-primary,#1e293b);margin-bottom:2px}
.gi-sub{font-size:.82rem;color:#64748b;margin-bottom:18px}
.gi-pills{display:flex;gap:6px;overflow-x:auto;padding-bottom:8px;margin-bottom:18px;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.gi-pills::-webkit-scrollbar{display:none}
.gi-pills a{flex-shrink:0;padding:6px 14px;border-radius:10px;font-size:.78rem;font-weight:600;color:#64748b;background:#f1f5f9;text-decoration:none;white-space:nowrap;transition:all .2s}
.gi-pills a.active{background:var(--primary);color:#fff}
.gi-section{margin-bottom:24px;scroll-margin-top:70px}
.gi-section h2{font-size:1rem;font-weight:700;color:var(--text-primary,#1e293b);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.gi-section h2 .gi-ic{width:24px;height:24px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;background:var(--primary-light);font-size:13px}
.gi-card{background:var(--card-bg,#fff);border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:10px}
.gi-card h3{font-size:.88rem;font-weight:700;color:#334155;margin-bottom:6px}
.gi-card p,.gi-card li{font-size:.82rem;color:#475569;line-height:1.55}
.gi-card ul{list-style:disc;padding-left:16px;margin-top:4px}
.gi-card ol{padding-left:16px;margin-top:4px}
.gi-tbl{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:6px}
.gi-tbl th{text-align:left;padding:6px 8px;background:var(--primary-light);color:#334155;font-weight:600}
.gi-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#475569}
.gi-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#92400e;margin-top:8px}
.gi-tip strong{color:#78350f}
.gi-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#991b1b;margin-top:8px}
.gi-warn strong{color:#7f1d1d}
.gi-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600}
.gi-badge-green{background:#dcfce7;color:#166534}
.gi-badge-yellow{background:#fef3c7;color:#92400e}
.gi-badge-blue{background:#dbeafe;color:#1e40af}
.gi-badge-red{background:#fee2e2;color:#991b1b}
.gi-fab{position:fixed;bottom:80px;right:16px;width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.18);text-decoration:none;z-index:30}
</style>

<div class="gi-wrap">
  <h1 class="gi-title">Integração iFood</h1>
  <p class="gi-sub">Receba e gerencie pedidos do iFood.</p>

  <nav class="gi-pills" id="gi-nav">
    <a href="#setup">Configuração</a>
    <a href="#credentials">Credenciais</a>
    <a href="#orders">Pedidos</a>
    <a href="#workflow">Fluxo</a>
    <a href="#tips">Dicas</a>
  </nav>

  <!-- Configuração -->
  <section class="gi-section" id="setup">
    <h2><span class="gi-ic">📋</span> Passo a Passo</h2>
    <div class="gi-card">
      <ol>
        <li>Acesse o <strong>Portal iFood Developer</strong></li>
        <li>Crie um app → copie Client ID e Secret</li>
        <li>Cole aqui e <strong>salve</strong></li>
        <li>Clique <strong>"Testar Conexão"</strong></li>
        <li>Selecione o <strong>Merchant ID</strong></li>
        <li>Configure o <strong>Webhook</strong> no portal iFood</li>
        <li>Ative a integração!</li>
      </ol>
    </div>
  </section>

  <!-- Credenciais -->
  <section class="gi-section" id="credentials">
    <h2><span class="gi-ic">🔑</span> Credenciais</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Campo</th><th>Descrição</th></tr>
        <tr><td><strong>Client ID</strong></td><td>UUID do app no iFood</td></tr>
        <tr><td><strong>Client Secret</strong></td><td>Chave secreta (nunca compartilhe)</td></tr>
        <tr><td><strong>Merchant ID</strong></td><td>ID da sua loja (carrega após testar)</td></tr>
      </table>
    </div>
    <div class="gi-card">
      <h3>Opções</h3>
      <table class="gi-tbl">
        <tr><td><strong>Integração Ativa</strong></td><td>Liga/desliga recebimento de pedidos</td></tr>
        <tr><td><strong>Auto-confirmar</strong></td><td>Aceita pedidos automaticamente</td></tr>
      </table>
      <div class="gi-warn"><strong>Auto-confirmar:</strong> Use com cautela — aceita mesmo sem capacidade.</div>
    </div>
  </section>

  <!-- Pedidos -->
  <section class="gi-section" id="orders">
    <h2><span class="gi-ic">📦</span> Pedidos iFood</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Status</th><th>Ação</th></tr>
        <tr><td><span class="gi-badge gi-badge-yellow">Novo</span></td><td>Confirmar pedido</td></tr>
        <tr><td><span class="gi-badge gi-badge-blue">Confirmado</span></td><td>Marcar como pronto</td></tr>
        <tr><td><span class="gi-badge gi-badge-green">Pronto</span></td><td>Despachar</td></tr>
        <tr><td><span class="gi-badge gi-badge-green">Despachado</span></td><td>Em rota de entrega</td></tr>
        <tr><td><span class="gi-badge gi-badge-red">Cancelado</span></td><td>Selecionar motivo</td></tr>
      </table>
    </div>
  </section>

  <!-- Fluxo -->
  <section class="gi-section" id="workflow">
    <h2><span class="gi-ic">🔄</span> Fluxo</h2>
    <div class="gi-card">
      <ol>
        <li>Cliente pede no iFood → chega via webhook</li>
        <li>Aparece como <strong>"Novo"</strong> no painel</li>
        <li>Confirmar → preparar → <strong>"Pronto"</strong></li>
        <li>Entregador chega → <strong>"Despachar"</strong></li>
      </ol>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gi-section" id="tips">
    <h2><span class="gi-ic">💡</span> Dicas</h2>
    <div class="gi-card">
      <ul>
        <li><strong>"Testar Conexão"</strong> valida credenciais e carrega lojas.</li>
        <li><strong>Webhook HTTPS</strong> obrigatório pelo iFood.</li>
        <li><strong>Cancelamento</strong> requer motivo da lista do iFood.</li>
        <li><strong>Loja Offline</strong> = clientes não veem no app.</li>
      </ul>
    </div>
  </section>
</div>

<a href="#" class="gi-fab" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">↑</a>

<script>
(function(){
  const nav=document.getElementById('gi-nav');if(!nav)return;
  const links=[...nav.querySelectorAll('a[href^="#"]')];
  const secs=links.map(l=>document.getElementById(l.getAttribute('href').slice(1))).filter(Boolean);
  const io=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){
      const i=secs.indexOf(e.target);if(i>-1)links.forEach((l,j)=>l.classList.toggle('active',j===i));
    }});
  },{rootMargin:'-30% 0px -60% 0px'});
  secs.forEach(s=>io.observe(s));
})();
</script>
