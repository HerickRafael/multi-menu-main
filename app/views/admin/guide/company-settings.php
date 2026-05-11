<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Configurações da Empresa';
$pageDescription = 'Dados gerais, API, cores, imagens e horários do seu comércio';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33"/></svg>';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => base_url('admin/' . $slug . '/settings')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Configurações', 'url' => base_url('admin/' . $slug . '/settings'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>', 'primary' => true]
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
.gc-color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:10px}
.gc-color-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:.82rem;color:#475569}
.gc-color-item strong{display:block;margin-bottom:2px;color:#334155}
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
    <a href="#data" data-section="data">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Dados
    </a>
    <a href="#texts" data-section="texts">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
        Textos
    </a>
    <a href="#colors" data-section="colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.688H16c3.314 0 6-2.686 6-6 0-5.523-4.477-10-10-10z"/></svg>
        Cores
    </a>
    <a href="#images" data-section="images">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        Imagens
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#hours" data-section="hours">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Horários
    </a>
    <a href="#api" data-section="api">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        API
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Ir para Configurações
    </a>
</nav>

<!-- Main -->
<div>

<!-- Visão Geral -->
  <section class="gc-sec" id="overview">
    <h2><span class="gc-icon">⚙️</span> Visão Geral</h2>
    <div class="gc-card">
      <p>As configurações são organizadas em <strong>5 abas</strong>:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Aba</th><th>O que configura</th></tr></thead>
        <tbody>
          <tr><td><strong>Dados</strong></td><td>Nome, WhatsApp, endereço, pedido mínimo, tempo de entrega</td></tr>
          <tr><td><strong>API</strong></td><td>Conexão com Evolution API (WhatsApp automático)</td></tr>
          <tr><td><strong>Cores</strong></td><td>Personalização visual do cardápio do cliente</td></tr>
          <tr><td><strong>Imagens</strong></td><td>Logo e banner exibidos no cardápio</td></tr>
          <tr><td><strong>Horários</strong></td><td>Dias e horários de funcionamento (2 turnos por dia)</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Dados -->
  <section class="gc-sec" id="data">
    <h2><span class="gc-icon">📋</span> Dados da Loja</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Nome do comércio</strong></td><td>Nome exibido no cardápio, recibos e notificações</td></tr>
          <tr><td><strong>WhatsApp</strong></td><td>Número de contato exibido para clientes (formato: DDD + número)</td></tr>
          <tr><td><strong>Endereço</strong></td><td>Endereço físico exibido no cardápio (opcional)</td></tr>
          <tr><td><strong>Pedido mínimo (R$)</strong></td><td>Valor mínimo para aceitar pedidos. Se o cliente não atingir, não consegue finalizar. Use 0 para sem mínimo.</td></tr>
          <tr><td><strong>Tempo médio (de/até)</strong></td><td>Faixa de tempo de entrega exibida ao cliente (ex: 40-60 min). Informativo — não bloqueia pedidos.</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Textos por Dia -->
  <section class="gc-sec" id="texts">
    <h2><span class="gc-icon">💬</span> Textos por Dia da Semana</h2>
    <div class="gc-card">
      <p>Configure uma <strong>mensagem de boas-vindas</strong> diferente para cada dia da semana. O texto aparece no topo do cardápio do cliente.</p>
      <ul>
        <li>Cada dia tem um <strong>toggle</strong> para ativar/desativar a mensagem</li>
        <li>Com o toggle desligado, nenhum texto aparece naquele dia</li>
        <li>Use o botão <strong>"Aplicar texto único"</strong> para copiar a mesma mensagem para todos os dias</li>
      </ul>
      <div class="gc-tip">
        <strong>Dica de uso:</strong> "Terça-feira de promoção! 🔥 Todos os combos com 20% OFF" ou "Sexta de rodízio! Peça agora" — personalize por dia para aumentar engajamento.
      </div>
    </div>
  </section>

  <!-- Cores -->
  <section class="gc-sec" id="colors">
    <h2><span class="gc-icon">🎨</span> Cores do Cardápio</h2>
    <div class="gc-card">
      <p>Personalize as cores do cardápio que o cliente vê. Cada cor pode ser definida via <strong>seletor visual</strong> ou <strong>código hex</strong>.</p>
      <div class="gc-color-grid">
        <div class="gc-color-item"><strong>Texto do cabeçalho</strong>Cor do nome da loja no topo</div>
        <div class="gc-color-item"><strong>Botões do cabeçalho</strong>Ícones de busca, carrinho, etc.</div>
        <div class="gc-color-item"><strong>Fundo do cabeçalho</strong>Background do topo do cardápio</div>
        <div class="gc-color-item"><strong>Borda da logo</strong>Contorno ao redor da logo</div>
        <div class="gc-color-item"><strong>Fundo título grupos</strong>Background do título das categorias</div>
        <div class="gc-color-item"><strong>Texto título grupos</strong>Cor do texto das categorias</div>
        <div class="gc-color-item"><strong>Fundo boas-vindas</strong>Background da mensagem de texto</div>
        <div class="gc-color-item"><strong>Texto boas-vindas</strong>Cor da mensagem de texto</div>
      </div>
      <div class="gc-tip" style="margin-top:14px">
        <strong>Reset:</strong> Use o botão de reset para voltar todas as cores ao padrão do sistema.
      </div>
    </div>
  </section>

  <!-- Imagens -->
  <section class="gc-sec" id="images">
    <h2><span class="gc-icon">🖼️</span> Imagens</h2>
    <div class="gc-card">
      <table class="gc-table">
        <thead><tr><th>Imagem</th><th>Recomendações</th></tr></thead>
        <tbody>
          <tr><td><strong>Logo (quadrado)</strong></td><td>Formato quadrado, JPG/PNG/WebP. Aparece no topo do cardápio e em notificações. Recomendado: 512×512px.</td></tr>
          <tr><td><strong>Banner (largura)</strong></td><td>Formato horizontal, JPG/PNG/WebP. Exibido como destaque acima dos produtos. Recomendado: 1200×400px.</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Dica:</strong> Use imagens otimizadas (comprimidas) para carregamento rápido. Evite imagens muito grandes que podem deixar o cardápio lento.
      </div>
    </div>
  </section>

  <!-- Horários -->
  <section class="gc-sec" id="hours">
    <h2><span class="gc-icon">🕐</span> Horários de Funcionamento</h2>
    <div class="gc-card">
      <p>Configure os horários de cada dia da semana. O sistema suporta <strong>2 turnos por dia</strong> (ex: almoço e jantar).</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Toggle aberto</strong></td><td>Liga/desliga o dia. Dia desligado = loja fechada nesse dia.</td></tr>
          <tr><td><strong>Turno 1 (Início/Término)</strong></td><td>Primeiro horário de funcionamento (ex: 11:00 – 14:00)</td></tr>
          <tr><td><strong>Turno 2 (Início/Término)</strong></td><td>Segundo horário, opcional (ex: 18:00 – 23:00). Deixe vazio se não usa.</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Fora do horário:</strong> Quando a loja está fechada, o cliente ainda pode ver o cardápio mas não consegue finalizar pedidos.
      </div>
    </div>
  </section>

  <!-- API -->
  <section class="gc-sec" id="api">
    <h2><span class="gc-icon">🔗</span> API (Evolution)</h2>
    <div class="gc-card">
      <p>Conecte sua instância da <strong>Evolution API</strong> para enviar mensagens automáticas via WhatsApp.</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>SERVER_URL</strong></td><td>URL do servidor Evolution API (ex: https://api.seudominio.com)</td></tr>
          <tr><td><strong>AUTHENTICATION_API_KEY</strong></td><td>Chave de autenticação global da Evolution API</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Onde encontrar:</strong> Essas credenciais são geradas na instalação da Evolution API. Consulte quem configurou o servidor.
      </div>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gc-sec" id="tips">
    <h2><span class="gc-icon">💡</span> Dicas</h2>
    <div class="gc-card">
      <ul>
        <li><strong>Pedido mínimo = 0</strong> aceita qualquer valor. Ideal para delivery de itens baratos.</li>
        <li><strong>Tempo de entrega</strong> é informativo — use dados reais para não frustrar clientes.</li>
        <li><strong>Cores consistentes</strong> com a identidade visual da marca melhoram o reconhecimento.</li>
        <li><strong>Logo quadrada</strong> funciona melhor no cardápio. Recorte se necessário.</li>
        <li><strong>Segundo turno</strong> é opcional — deixe vazio se funciona em horário contínuo.</li>
        <li><strong>Textos por dia</strong> aumentam vendas quando usados com promoções específicas.</li>
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
