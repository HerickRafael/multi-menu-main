<?php
$pageTitle = $pageTitle ?? 'Guia de Pedido Manual';
$activeNav = $activeNav ?? 'orders';
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
.gi-fab{position:fixed;bottom:80px;right:16px;width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.18);text-decoration:none;z-index:30}
</style>

<div class="gi-wrap">
  <h1 class="gi-title">Pedido Manual</h1>
  <p class="gi-sub">Crie pedidos por telefone ou presenciais.</p>

  <nav class="gi-pills" id="gi-nav">
    <a href="#overview">Visão Geral</a>
    <a href="#customer">Cliente</a>
    <a href="#address">Endereço</a>
    <a href="#products">Produtos</a>
    <a href="#payment">Pagamento</a>
    <a href="#tips">Dicas</a>
  </nav>

  <!-- Visão Geral -->
  <section class="gi-section" id="overview">
    <h2><span class="gi-ic">📋</span> Visão Geral</h2>
    <div class="gi-card">
      <p>Registre pedidos de telefone, WhatsApp ou balcão. O fluxo:</p>
      <ol>
        <li><strong>Cliente</strong> — WhatsApp + nome</li>
        <li><strong>Tipo</strong> — Entrega ou Retirada</li>
        <li><strong>Endereço</strong> — cidade, bairro (taxa automática)</li>
        <li><strong>Produtos</strong> — selecione com personalizações</li>
        <li><strong>Pagamento</strong> — forma de pagamento</li>
      </ol>
      <div class="gi-tip"><strong>Nota:</strong> Pedidos manuais entram no sistema igual aos do cardápio — contam nas estatísticas e geram notificações.</div>
    </div>
  </section>

  <!-- Cliente -->
  <section class="gi-section" id="customer">
    <h2><span class="gi-ic">👤</span> Dados do Cliente</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Campo</th><th>Descrição</th></tr>
        <tr><td><strong>WhatsApp *</strong></td><td>Número do cliente (obrigatório)</td></tr>
        <tr><td><strong>Nome *</strong></td><td>Nome do cliente (obrigatório)</td></tr>
      </table>
    </div>
  </section>

  <!-- Endereço -->
  <section class="gi-section" id="address">
    <h2><span class="gi-ic">📍</span> Endereço</h2>
    <div class="gi-card">
      <p>Selecione <strong>Entrega</strong> ou <strong>Retirada</strong>. Se entrega:</p>
      <table class="gi-tbl" style="margin-top:6px">
        <tr><td><strong>Cidade</strong></td><td>Das cadastradas em Taxas de Entrega</td></tr>
        <tr><td><strong>Bairro</strong></td><td>Taxa de entrega calculada automaticamente</td></tr>
        <tr><td><strong>Rua / Número</strong></td><td>Endereço completo</td></tr>
        <tr><td><strong>Referência</strong></td><td>Ponto de referência (opcional)</td></tr>
      </table>
      <div class="gi-tip"><strong>Taxa automática:</strong> Ao escolher o bairro, a taxa é preenchida. Pode editar manualmente.</div>
    </div>
  </section>

  <!-- Produtos -->
  <section class="gi-section" id="products">
    <h2><span class="gi-ic">🛒</span> Produtos</h2>
    <div class="gi-card">
      <ul>
        <li>Navegue pelas <strong>abas de categoria</strong></li>
        <li>Use <strong>+/−</strong> para ajustar quantidade</li>
        <li>Produtos com adicionais abrem <strong>modal de personalização</strong></li>
        <li>Preços atualizam em tempo real</li>
      </ul>
    </div>
  </section>

  <!-- Pagamento -->
  <section class="gi-section" id="payment">
    <h2><span class="gi-ic">💳</span> Pagamento</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Opção</th><th>Detalhes</th></tr>
        <tr><td><strong>PIX</strong></td><td>Registra pagamento via PIX</td></tr>
        <tr><td><strong>Dinheiro</strong></td><td>Informe valor para cálculo de troco</td></tr>
        <tr><td><strong>Crédito/Débito</strong></td><td>Selecione a bandeira do cartão</td></tr>
        <tr><td><strong>Voucher</strong></td><td>Vale alimentação/refeição</td></tr>
      </table>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gi-section" id="tips">
    <h2><span class="gi-ic">💡</span> Dicas</h2>
    <div class="gi-card">
      <ul>
        <li><strong>Taxa de entrega</strong> pode ser editada (ex: frete grátis).</li>
        <li><strong>Sem bandeiras?</strong> Cadastre em Configurações → Pagamentos.</li>
        <li><strong>Observações</strong> — use para pedidos especiais.</li>
        <li><strong>Retirada</strong> — endereço não é necessário.</li>
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
