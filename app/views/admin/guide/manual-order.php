<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Pedido Manual';
$pageDescription = 'Como criar pedidos manuais com cliente, endereço e pagamento';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
$breadcrumbs = [
    ['label' => 'Pedido Manual', 'url' => base_url('admin/' . $slug . '/orders/create')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Novo Pedido', 'url' => base_url('admin/' . $slug . '/orders/create'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', 'primary' => true]
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
    <a href="#customer" data-section="customer">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Cliente
    </a>
    <a href="#address" data-section="address">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Endereço
    </a>
    <a href="#products" data-section="products">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        Produtos
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#summary" data-section="summary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        Resumo
    </a>
    <a href="#payment" data-section="payment">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
        Pagamento
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Criar Pedido
    </a>
</nav>

<!-- Main -->
<div>

<!-- Visão Geral -->
  <section class="gc-sec" id="overview">
    <h2><span class="gc-icon">📋</span> Visão Geral</h2>
    <div class="gc-card">
      <p>O pedido manual permite registrar pedidos recebidos por telefone, WhatsApp ou presencialmente. O fluxo segue estas etapas:</p>
      <ol>
        <li><strong>Dados do cliente</strong> — identificação e contato</li>
        <li><strong>Endereço</strong> — local de entrega (com taxa automática)</li>
        <li><strong>Produtos</strong> — seleção por categoria com personalização</li>
        <li><strong>Resumo</strong> — subtotal, taxa de entrega, desconto</li>
        <li><strong>Pagamento</strong> — forma e detalhes</li>
      </ol>
      <div class="gc-tip">
        <strong>Dica:</strong> Pedidos manuais entram no sistema igual aos pedidos do cardápio online — aparecem no painel de pedidos, geram notificações e contam nas estatísticas.
      </div>
    </div>
  </section>

  <!-- Cliente -->
  <section class="gc-sec" id="customer">
    <h2><span class="gc-icon">👤</span> Dados do Cliente</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Cliente cadastrado</strong></td><td>Seleção opcional. Se o cliente já comprou antes, selecione para preencher automaticamente nome, WhatsApp e endereço.</td></tr>
          <tr><td><strong>WhatsApp *</strong></td><td>Número de contato. Obrigatório. Usado para enviar confirmação e acompanhamento.</td></tr>
          <tr><td><strong>Nome completo *</strong></td><td>Nome do cliente para o pedido. Obrigatório.</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Auto-preenchimento:</strong> Ao selecionar um cliente cadastrado, todos os campos são preenchidos automaticamente. Você pode editar antes de salvar.
      </div>
    </div>
  </section>

  <!-- Endereço -->
  <section class="gc-sec" id="address">
    <h2><span class="gc-icon">📍</span> Endereço de Entrega</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Cidade *</strong></td><td>Selecione a cidade cadastrada. As opções vêm das cidades configuradas em Taxas de Entrega.</td></tr>
          <tr><td><strong>Bairro *</strong></td><td>Selecione o bairro. A <strong>taxa de entrega é calculada automaticamente</strong> baseada no bairro escolhido.</td></tr>
          <tr><td><strong>Rua / Avenida *</strong></td><td>Nome da rua</td></tr>
          <tr><td><strong>Número *</strong></td><td>Número do endereço</td></tr>
          <tr><td><strong>Complemento</strong></td><td>Apto, bloco, referência (opcional)</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Taxa automática:</strong> Ao selecionar o bairro, a taxa de entrega é preenchida automaticamente conforme configurado em Configurações → Taxas de Entrega. Você pode editá-la manualmente se necessário.
      </div>
    </div>
  </section>

  <!-- Produtos -->
  <section class="gc-sec" id="products">
    <h2><span class="gc-icon">🛒</span> Produtos</h2>
    <div class="gc-card">
      <p>Navegue pelas <strong>abas de categoria</strong> para encontrar os produtos. Para cada produto:</p>
      <ul>
        <li>Use <strong>+</strong> e <strong>−</strong> para ajustar a quantidade</li>
        <li>Produtos com personalização (adicionais) abrem um <strong>modal de customização</strong></li>
        <li>No modal, selecione os ingredientes/adicionais desejados</li>
        <li>O preço é atualizado em tempo real no resumo</li>
      </ul>
      <div class="gc-tip">
        <strong>Personalização:</strong> Se o produto tem grupos de personalização, o ícone de customização aparece. Clique para abrir o modal e selecionar adicionais — idêntico ao que o cliente vê no cardápio.
      </div>
    </div>
  </section>

  <!-- Resumo -->
  <section class="gc-sec" id="summary">
    <h2><span class="gc-icon">📊</span> Resumo do Pedido</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Subtotal</strong></td><td>Soma dos produtos (calculado automaticamente)</td></tr>
          <tr><td><strong>Taxa de entrega</strong></td><td>Preenchida automaticamente pelo bairro. Pode ser editada manualmente.</td></tr>
          <tr><td><strong>Desconto</strong></td><td>Valor de desconto a aplicar no pedido (manual). Útil para cupons verbais ou cortesias.</td></tr>
          <tr><td><strong>Total</strong></td><td>Subtotal + Taxa − Desconto (calculado automaticamente)</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Pagamento -->
  <section class="gc-sec" id="payment">
    <h2><span class="gc-icon">💳</span> Pagamento</h2>
    <div class="gc-card">
      <p>Selecione a forma de pagamento do cliente:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Opção</th><th>Detalhes</th></tr></thead>
        <tbody>
          <tr><td><strong>PIX</strong></td><td>Pagamento via PIX. Registra a opção.</td></tr>
          <tr><td><strong>Dinheiro</strong></td><td>Expande campo de <strong>troco</strong>: informe quanto o cliente vai pagar para calcular o troco.</td></tr>
          <tr><td><strong>Cartão de crédito</strong></td><td>Expande grid de <strong>bandeiras</strong> cadastradas. Selecione a bandeira usada.</td></tr>
          <tr><td><strong>Cartão de débito</strong></td><td>Expande grid de bandeiras. Selecione a bandeira.</td></tr>
          <tr><td><strong>Vale Alimentação/Refeição</strong></td><td>Vouchers corporativos (Sodexo, VR, etc.)</td></tr>
          <tr><td><strong>Outros</strong></td><td>Métodos customizados configurados em Pagamentos.</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Troco:</strong> Ao selecionar "Dinheiro", informe o valor que o cliente vai pagar. O sistema calcula o troco automaticamente. Se não precisa de troco, deixe em branco.
      </div>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gc-sec" id="tips">
    <h2><span class="gc-icon">💡</span> Dicas</h2>
    <div class="gc-card">
      <ul>
        <li><strong>Selecione cliente cadastrado</strong> para economizar tempo — preenche tudo automaticamente.</li>
        <li><strong>Taxa de entrega</strong> pode ser editada após seleção do bairro (ex: frete grátis em promoção).</li>
        <li><strong>Desconto manual</strong> é útil para cupons informados por telefone/WhatsApp.</li>
        <li><strong>Sem bandeiras?</strong> Cadastre métodos de pagamento em Configurações → Pagamentos.</li>
        <li><strong>Observações</strong> — use o campo de notas para registrar pedidos especiais do cliente.</li>
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
