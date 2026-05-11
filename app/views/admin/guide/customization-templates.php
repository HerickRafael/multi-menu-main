<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Templates de Personalização';
$pageDescription = 'Modos de seleção, ingredientes e sincronização';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>';
$breadcrumbs = [
    ['label' => 'Templates', 'url' => base_url('admin/' . $slug . '/customization-templates')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Gerenciar Templates', 'url' => base_url('admin/' . $slug . '/customization-templates'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>', 'primary' => true]
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
.gc-badge{display:inline-block;padding:2px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.gc-badge-green{background:#dcfce7;color:#166534}
.gc-badge-blue{background:#dbeafe;color:#1e40af}
.gc-badge-purple{background:#f3e8ff;color:#6b21a8}
.gc-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#92400e;margin-top:10px}
.gc-tip strong{color:#78350f}
.gc-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#991b1b;margin-top:10px}
.gc-warn strong{color:#7f1d1d}
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
    <a href="#modes" data-section="modes">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
        Modos
    </a>
    <a href="#form" data-section="form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
        Formulário
    </a>
    <a href="#ingredients" data-section="ingredients">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        Ingredientes
    </a>
    <div class="nav-group">Mais</div>
    <a href="#sync" data-section="sync">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        Sincronização
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/customization-templates')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
        Gerenciar Templates
    </a>
</nav>

<!-- Main -->
<div>

<!-- Visão Geral -->
  <section class="gc-sec" id="overview">
    <h2><span class="gc-icon">📋</span> Visão Geral</h2>
    <div class="gc-card">
      <p>Grupos de personalização são <strong>templates reutilizáveis</strong> que definem conjuntos de ingredientes adicionais/opcionais. Em vez de configurar ingredientes produto a produto, você cria um grupo e o aplica em vários produtos.</p>
      <table class="gc-table" style="margin-top:14px">
        <thead><tr><th>Conceito</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Grupo</strong></td><td>Conjunto nomeado de ingredientes (ex: "Adicionais", "Molhos")</td></tr>
          <tr><td><strong>Modo</strong></td><td>Define como o cliente seleciona itens (Extra, Escolha ou Montagem)</td></tr>
          <tr><td><strong>Sincronização</strong></td><td>Alterações no grupo são replicadas automaticamente nos produtos vinculados</td></tr>
          <tr><td><strong>Ativo/Inativo</strong></td><td>Grupos inativos não aparecem como opção para copiar em novos produtos</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Modos de Seleção -->
  <section class="gc-sec" id="modes">
    <h2><span class="gc-icon">🔀</span> Modos de Seleção</h2>

    <div class="gc-card">
      <h3><span class="gc-badge gc-badge-green">Extra</span> Adicionar ingredientes livremente</h3>
      <p>O cliente pode adicionar quantos ingredientes quiser, cada um com sua quantidade individual. Ideal para adicionais simples onde não há limite de escolha.</p>
      <ul>
        <li>Cada ingrediente tem <strong>Mín</strong> e <strong>Máx</strong> individual</li>
        <li>Não há limite global de seleções</li>
        <li>Exemplo: "Adicionais do Hambúrguer" — bacon, queijo extra, ovo, etc.</li>
      </ul>
    </div>

    <div class="gc-card">
      <h3><span class="gc-badge gc-badge-blue">Escolha</span> Escolher ingrediente</h3>
      <p>O cliente escolhe entre as opções com um <strong>limite global</strong> de seleções (mínimo e máximo).</p>
      <ul>
        <li><strong>Seleções mínimas</strong> — quantas opções o cliente deve marcar no mínimo (0 = opcional)</li>
        <li><strong>Seleções máximas</strong> — limite de opções que pode escolher</li>
        <li>Exemplo: "Escolha o molho" — mín 1, máx 2 (obrigatório, escolhe até 2)</li>
      </ul>
    </div>

    <div class="gc-card">
      <h3><span class="gc-badge gc-badge-purple">Montagem</span> Montagem (açaí, poke...)</h3>
      <p>O cliente monta o prato distribuindo um <strong>total de itens</strong> entre as opções. Funciona como um "pool" de unidades.</p>
      <ul>
        <li><strong>Total mínimo</strong> — mínimo de itens no total (ex: 3 frutas)</li>
        <li><strong>Total máximo</strong> — máximo de itens somando tudo (ex: 5 frutas)</li>
        <li>Cada ingrediente pode ter Mín/Máx individual dentro do total</li>
        <li>Exemplo: "Monte seu açaí" — total máx 4, escolha entre morango (máx 2), banana (máx 2), granola (máx 1)...</li>
      </ul>
      <div class="gc-tip">
        <strong>Nota:</strong> O modo Montagem está disponível apenas no desktop. No mobile o formulário oferece apenas Extra e Escolha.
      </div>
    </div>
  </section>

  <!-- Formulário -->
  <section class="gc-sec" id="form">
    <h2><span class="gc-icon">📝</span> Formulário</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Nome do grupo</strong></td><td>Nome visível para o cliente (ex: "Adicionais", "Molhos", "Frutas")</td></tr>
          <tr><td><strong>Grupo ativo</strong></td><td>Se desligado, o grupo não aparece como opção para copiar em novos produtos</td></tr>
          <tr><td><strong>Ocultar ingredientes repetidos</strong></td><td>Se o mesmo ingrediente já existir em outro grupo do produto, ele fica invisível neste grupo para o cliente</td></tr>
          <tr><td><strong>Modo de seleção</strong></td><td>Define a lógica de escolha: Extra, Escolha ou Montagem</td></tr>
          <tr><td><strong>Seleções mín/máx</strong></td><td>Limites globais (modo Escolha) ou totais (modo Montagem)</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Ingredientes -->
  <section class="gc-sec" id="ingredients">
    <h2><span class="gc-icon">🧂</span> Ingredientes</h2>
    <div class="gc-card">
      <p>Cada ingrediente adicionado ao grupo possui configurações individuais:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Ingrediente</strong></td><td>Digite para buscar ingredientes já cadastrados (typeahead)</td></tr>
          <tr><td><strong>Mín</strong></td><td>Quantidade mínima deste ingrediente (0 = opcional)</td></tr>
          <tr><td><strong>Máx</strong></td><td>Quantidade máxima permitida deste ingrediente</td></tr>
          <tr><td><strong>Padrão</strong></td><td>Se ativado, o ingrediente já vem selecionado quando o cliente abre a personalização</td></tr>
          <tr><td><strong>Qtd padrão</strong></td><td>Quantidade pré-selecionada quando "Padrão" está ativado</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Arrastar para reordenar:</strong> Use o ícone de arrastar (⠿) para mudar a ordem dos ingredientes. A ordem definida aqui é a exibida para o cliente.
      </div>
    </div>
  </section>

  <!-- Sincronização -->
  <section class="gc-sec" id="sync">
    <h2><span class="gc-icon">🔄</span> Sincronização</h2>
    <div class="gc-card">
      <h3>Sincronizar alterações com os produtos</h3>
      <p>Quando você edita um grupo que já está vinculado a produtos:</p>
      <ul>
        <li>A lista de <strong>produtos vinculados</strong> aparece no formulário de edição</li>
        <li>Com "Sincronizar" ativado (padrão), ao salvar o grupo todas as alterações são <strong>aplicadas automaticamente</strong> nos produtos vinculados</li>
        <li>Desative se quiser editar o grupo sem alterar os produtos existentes</li>
      </ul>
      <div class="gc-warn">
        <strong>Cuidado:</strong> A sincronização substitui os ingredientes do grupo nos produtos. Se um produto tinha ingredientes editados manualmente nesse grupo, eles serão sobrepostos.
      </div>
    </div>
    <div class="gc-card">
      <h3>Fluxo de uso recomendado</h3>
      <ul>
        <li><strong>1.</strong> Crie o grupo com os ingredientes desejados</li>
        <li><strong>2.</strong> Ao criar/editar um produto, use "Copiar de grupo" para aplicar</li>
        <li><strong>3.</strong> Para atualizar todos os produtos de uma vez, edite o grupo com sincronização ativada</li>
      </ul>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gc-sec" id="tips">
    <h2><span class="gc-icon">💡</span> Dicas</h2>
    <div class="gc-card">
      <ul>
        <li><strong>Nomeie de forma clara</strong> — O nome do grupo aparece para o cliente. "Adicionais", "Escolha o molho", "Monte seu açaí" são bons exemplos.</li>
        <li><strong>Use "Ocultar repetidos"</strong> para evitar duplicação quando o mesmo ingrediente está em múltiplos grupos de um produto.</li>
        <li><strong>Modo Escolha com mín=1</strong> torna a seleção obrigatória — o cliente não consegue pedir sem escolher.</li>
        <li><strong>Ingrediente padrão</strong> é útil para itens que "vêm no produto" e o cliente pode remover (ex: cebola no hambúrguer).</li>
        <li><strong>Crie grupos genéricos</strong> (ex: "Adicionais Hambúrguer") e reutilize em vários produtos para economizar tempo.</li>
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
