<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Taxas de Entrega';
$pageDescription = 'Configure taxas por cidade e bairro, ajustes em lote e taxa noturna';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>';
$breadcrumbs = [
    ['label' => 'Taxas de Entrega', 'url' => base_url('admin/' . $slug . '/delivery-fees')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Gerenciar Taxas', 'url' => base_url('admin/' . $slug . '/delivery-fees'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>', 'primary' => true]
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
.gc-steps{list-style:none;padding:0;margin:14px 0;counter-reset:gs}
.gc-steps li{counter-increment:gs;display:flex;gap:14px;padding:14px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;line-height:1.65}
.gc-steps li:last-child{border-bottom:none}
.gc-steps li::before{content:counter(gs);flex-shrink:0;width:28px;height:28px;border-radius:50%;background:var(--admin-primary-color,#6366f1);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-top:2px}
.gc-info{background:color-mix(in srgb,var(--admin-primary-color,#6366f1) 8%,white);border:1px solid color-mix(in srgb,var(--admin-primary-color,#6366f1) 25%,white);border-radius:12px;padding:14px 16px;margin:14px 0;display:flex;gap:10px;font-size:13px;color:#334155;line-height:1.6}
.gc-info svg{flex-shrink:0;color:var(--admin-primary-color,#6366f1);margin-top:2px}
.gc-warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:14px 16px;margin:14px 0;display:flex;gap:10px;font-size:13px;color:#92400e;line-height:1.6}
.gc-warn svg{flex-shrink:0;color:#d97706;margin-top:2px}
.gc-form{border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;margin:16px 0}
.gc-form-h{display:flex;align-items:center;gap:10px;font-weight:600;color:#1e293b;font-size:14px;padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.gc-form-h svg{width:18px;height:18px;color:var(--admin-primary-color,#6366f1);flex-shrink:0}
.gc-form-b{padding:18px}
.gc-fg{margin-bottom:16px}
.gc-fg:last-child{margin-bottom:0}
.gc-fl{display:block;font-weight:500;color:#374151;margin-bottom:6px;font-size:13px}
.gc-fi{width:100%;padding:10px 14px;border:1.5px solid #d1d5db;border-radius:10px;font-size:14px;background:#fff;color:#64748b;box-sizing:border-box}
.gc-fh{font-size:12px;color:#64748b;margin-top:5px;line-height:1.4}
.gc-tag{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;vertical-align:middle;margin-left:6px}
.gc-tag-req{background:#fee2e2;color:#dc2626}
.gc-tag-opt{background:#f0fdf4;color:#16a34a}
.gc-cmp{width:100%;border-collapse:separate;border-spacing:0;border-radius:12px;overflow:hidden;border:1.5px solid #e2e8f0;margin:14px 0;font-size:13px}
.gc-cmp th{background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#475569}
.gc-cmp td{padding:10px 14px;border-top:1px solid #f1f5f9;color:#334155}
.gc-gc{display:flex;gap:12px;padding:14px 16px;border-radius:12px;margin-bottom:10px}
.gc-gc-good{background:#f0fdf4;border:1px solid #bbf7d0}
.gc-gc-warn{background:#fff7ed;border:1px solid #fed7aa}
.gc-gc .g-icon{font-size:18px;flex-shrink:0}
.gc-gc .g-title{font-size:13px;font-weight:700;margin-bottom:2px}
.gc-gc-good .g-title{color:#166534}
.gc-gc-warn .g-title{color:#9a3412}
.gc-gc .g-desc{font-size:12px;line-height:1.55}
.gc-gc-good .g-desc{color:#15803d}
.gc-gc-warn .g-desc{color:#c2410c}
.gc-tabs-demo{display:flex;gap:6px;margin:14px 0}
.gc-tabs-demo span{padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:default}
.gc-tabs-demo .active{background:var(--admin-primary-color,#6366f1);color:#fff}
.gc-tabs-demo .inactive{background:#f1f5f9;color:#64748b}
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
    <a href="#tabs" data-section="tabs">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        Abas
    </a>
    <a href="#ajustes" data-section="ajustes">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/></svg>
        Ajustes Rápidos
    </a>
    <a href="#hierarchy" data-section="hierarchy">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M8 10h.01M16 10h.01M12 10h.01M8 14h.01M16 14h.01M12 14h.01"/></svg>
        Cidades e Bairros
    </a>
    <div class="nav-group">Mais</div>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/delivery-fees')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Gerenciar Taxas
    </a>
</nav>

<!-- Main -->
<div>

<!-- VISÃO GERAL -->
<section id="overview" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
        Taxas de Entrega
    </h2>
    <p>Configure <b>quanto cobrar de frete</b> por bairro, aplique ajustes em lote e defina regras especiais como <b>frete grátis</b> e <b>taxa noturna</b>.</p>

    <ol class="gc-steps">
        <li><div>Cadastre as <b>cidades</b> que você atende</div></li>
        <li><div>Adicione os <b>bairros</b> com o valor da taxa de cada um</div></li>
        <li><div>Use <b>Ajustes Rápidos</b> para alterar tudo de uma vez</div></li>
    </ol>

    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>A hierarquia é <b>Cidade → Bairro → Taxa</b>. Cada bairro pertence a uma cidade e tem sua própria taxa.</span>
    </div>
</div>
</section>

<!-- ABAS -->
<section id="tabs" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg></span>
        Abas
    </h2>
    <p>A tela é dividida em <b>3 abas</b>:</p>

    <div class="gc-tabs-demo">
        <span class="active">⚡ Ajustes Rápidos</span>
        <span class="inactive">🏙️ Cidades</span>
        <span class="inactive">🏘️ Bairros</span>
    </div>

    <table class="gc-cmp">
        <thead><tr><th>Aba</th><th>Função</th></tr></thead>
        <tbody>
            <tr><td><b>Ajustes Rápidos</b></td><td>Alterar taxas em lote, taxa noturna, frete grátis</td></tr>
            <tr><td><b>Cidades</b></td><td>Cadastrar/editar as cidades atendidas</td></tr>
            <tr><td><b>Bairros</b></td><td>Definir bairros e suas taxas individuais</td></tr>
        </tbody>
    </table>
</div>
</section>

<!-- AJUSTES RÁPIDOS -->
<section id="ajustes" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 20V10M6 20V4M18 20v-6"/></svg></span>
        Ajustes Rápidos
    </h2>

    <h3>① Ajuste em Lote</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10M6 20V4M18 20v-6"/></svg> Ajuste em Lote</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Valor do ajuste (R$)</span>
                <input type="text" class="gc-fi" value="2.00" disabled>
                <p class="gc-fh"><b>Positivo</b> = aumenta todas as taxas • <b>Negativo</b> = diminui todas</p>
            </div>
        </div>
    </div>
    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Ex.: valor <b>+2.00</b> → todos os bairros sobem R$ 2. Valor <b>-1.50</b> → todos baixam R$ 1,50.</span>
    </div>

    <h3>② Taxa Após 18h</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Taxa Noturna</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Adicional (R$)</span>
                <input type="text" class="gc-fi" value="3.00" disabled>
                <p class="gc-fh">Somado automaticamente a pedidos após as 18h</p>
            </div>
        </div>
    </div>

    <h3>③ Taxa Gratuita</h3>
    <p>Toggle que <b>zera todas as taxas</b>. Útil para promoções rápidas.</p>
    <div class="gc-warn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span><b>Exclusão mútua:</b> Taxa Gratuita e Frete Grátis Promocional não funcionam juntos. Ativar um desativa o outro.</span>
    </div>

    <h3>④ Frete Grátis Promocional</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 01-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/></svg> Frete Grátis Promocional</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Valor mínimo do pedido (R$)</span>
                <input type="text" class="gc-fi" value="50.00" disabled>
                <p class="gc-fh">Pedidos acima desse valor têm frete grátis. 0 = desativado.</p>
            </div>
        </div>
    </div>
</div>
</section>

<!-- CIDADES / BAIRROS -->
<section id="hierarchy" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M13 21V3l6 4v14"/></svg></span>
        Cidades e Bairros
    </h2>

    <h3>🏙️ Cidades</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18"/></svg> Nova Cidade</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Nome da cidade <span class="gc-tag gc-tag-req">Obrigatório</span></span>
                <input type="text" class="gc-fi" value="São Paulo" disabled>
            </div>
        </div>
    </div>

    <h3>🏘️ Bairros</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> Novo Bairro</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Cidade <span class="gc-tag gc-tag-req">Obrigatório</span></span>
                <select class="gc-fi" disabled><option>São Paulo</option></select>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Bairro <span class="gc-tag gc-tag-req">Obrigatório</span></span>
                <input type="text" class="gc-fi" value="Vila Mariana" disabled>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Taxa de entrega (R$) <span class="gc-tag gc-tag-req">Obrigatório</span></span>
                <input type="text" class="gc-fi" value="8.00" disabled>
            </div>
        </div>
    </div>

    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Cada bairro pertence a uma cidade. <b>Cadastre a cidade primeiro</b>, depois adicione seus bairros.</span>
    </div>
</div>
</section>

<!-- DICAS -->
<section id="tips" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
        Dicas
    </h2>
    <div style="display:grid;gap:10px;">
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Cubra todos os bairros</div><div class="g-desc">Bairro sem taxa = cliente não consegue finalizar. Cadastre todos.</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Use ajuste em lote</div><div class="g-desc">Combustível subiu? Ajuste +R$ 1 em tudo de uma vez.</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Frete grátis = mais pedidos</div><div class="g-desc">Use o valor mínimo promocional para incentivar ticket médio maior.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Taxa noturna é automática</div><div class="g-desc">Após 18h o adicional é cobrado sem aviso extra. Informe no WhatsApp/site.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Grátis vs Promocional</div><div class="g-desc">São exclusivos — ativar "Taxa Gratuita" desativa "Frete Grátis Promocional" e vice-versa.</div></div></div>
    </div>
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
