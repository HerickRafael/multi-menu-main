<?php
$pageTitle = $pageTitle ?? 'Guia de Configurações';
$activeNav = $activeNav ?? 'settings';
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
.gi-tbl{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:6px}
.gi-tbl th{text-align:left;padding:6px 8px;background:var(--primary-light);color:#334155;font-weight:600}
.gi-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#475569}
.gi-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#92400e;margin-top:8px}
.gi-tip strong{color:#78350f}
.gi-fab{position:fixed;bottom:80px;right:16px;width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.18);text-decoration:none;z-index:30}
</style>

<div class="gi-wrap">
  <h1 class="gi-title">Configurações da Empresa</h1>
  <p class="gi-sub">Dados, cores, imagens e horários.</p>

  <nav class="gi-pills" id="gi-nav">
    <a href="#data">Dados</a>
    <a href="#colors">Cores</a>
    <a href="#images">Imagens</a>
    <a href="#hours">Horários</a>
    <a href="#api">API</a>
    <a href="#tips">Dicas</a>
  </nav>

  <!-- Dados -->
  <section class="gi-section" id="data">
    <h2><span class="gi-ic">📋</span> Dados da Loja</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Campo</th><th>Descrição</th></tr>
        <tr><td><strong>Nome</strong></td><td>Nome exibido no cardápio e notificações</td></tr>
        <tr><td><strong>WhatsApp</strong></td><td>Contato exibido para clientes</td></tr>
        <tr><td><strong>Endereço</strong></td><td>Endereço físico (opcional)</td></tr>
        <tr><td><strong>Pedido mínimo</strong></td><td>Valor mínimo para aceitar pedidos (0 = sem mínimo)</td></tr>
        <tr><td><strong>Tempo mín/máx</strong></td><td>Faixa de tempo de entrega exibida (informativo)</td></tr>
      </table>
    </div>
  </section>

  <!-- Cores -->
  <section class="gi-section" id="colors">
    <h2><span class="gi-ic">🎨</span> Cores do Cardápio</h2>
    <div class="gi-card">
      <p>8 cores personalizáveis do cardápio do cliente:</p>
      <table class="gi-tbl" style="margin-top:8px">
        <tr><td><strong>Cabeçalho</strong></td><td>Texto, botões e fundo do topo</td></tr>
        <tr><td><strong>Logo</strong></td><td>Cor da borda ao redor da logo</td></tr>
        <tr><td><strong>Categorias</strong></td><td>Fundo e texto dos títulos de grupos</td></tr>
        <tr><td><strong>Boas-vindas</strong></td><td>Fundo e texto da mensagem de destaque</td></tr>
      </table>
      <div class="gi-tip"><strong>Reset:</strong> Use o botão de reset para cores padrão.</div>
    </div>
  </section>

  <!-- Imagens -->
  <section class="gi-section" id="images">
    <h2><span class="gi-ic">🖼️</span> Imagens</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Imagem</th><th>Formato</th></tr>
        <tr><td><strong>Logo</strong></td><td>Quadrado, 512×512px, JPG/PNG/WebP</td></tr>
        <tr><td><strong>Banner</strong></td><td>Horizontal, 1200×400px, JPG/PNG/WebP</td></tr>
      </table>
      <div class="gi-tip"><strong>Dica:</strong> Use imagens otimizadas para carregamento rápido.</div>
    </div>
  </section>

  <!-- Horários -->
  <section class="gi-section" id="hours">
    <h2><span class="gi-ic">🕐</span> Horários</h2>
    <div class="gi-card">
      <p>Configure horários por dia com até <strong>2 turnos</strong> (almoço + jantar).</p>
      <table class="gi-tbl" style="margin-top:6px">
        <tr><td><strong>Toggle</strong></td><td>Liga/desliga o dia</td></tr>
        <tr><td><strong>Turno 1</strong></td><td>Primeiro horário (ex: 11h-14h)</td></tr>
        <tr><td><strong>Turno 2</strong></td><td>Segundo horário, opcional (ex: 18h-23h)</td></tr>
      </table>
      <div class="gi-tip"><strong>Fora do horário:</strong> Cliente vê o cardápio mas não finaliza pedidos.</div>
    </div>
  </section>

  <!-- API -->
  <section class="gi-section" id="api">
    <h2><span class="gi-ic">🔗</span> API (Evolution)</h2>
    <div class="gi-card">
      <p>Conexão com a Evolution API para WhatsApp automático.</p>
      <table class="gi-tbl" style="margin-top:6px">
        <tr><td><strong>SERVER_URL</strong></td><td>URL do servidor Evolution</td></tr>
        <tr><td><strong>API_KEY</strong></td><td>Chave de autenticação global</td></tr>
      </table>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gi-section" id="tips">
    <h2><span class="gi-ic">💡</span> Dicas</h2>
    <div class="gi-card">
      <ul>
        <li><strong>Pedido mínimo = 0</strong> aceita qualquer valor.</li>
        <li><strong>Tempo</strong> é informativo — use dados reais.</li>
        <li><strong>Cores</strong> consistentes com a marca melhoram reconhecimento.</li>
        <li><strong>Logo quadrada</strong> funciona melhor no cardápio.</li>
        <li><strong>Turno 2</strong> é opcional — deixe vazio se horário contínuo.</li>
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
