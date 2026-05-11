<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Desconto Fidelidade';
$pageDescription = 'Configure descontos progressivos e cadastro completo';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>';
$breadcrumbs = [
    ['label' => 'Desconto Fidelidade', 'url' => base_url('admin/' . $slug . '/loyalty-discount')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Gerenciar Descontos', 'url' => base_url('admin/' . $slug . '/loyalty-discount'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>', 'primary' => true]
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
    <a href="#embedded" data-section="embedded">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Taxa Embutida
    </a>
    <a href="#signup" data-section="signup">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Cadastro Completo
    </a>
    <div class="nav-group">Mais</div>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/loyalty-discount')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
        Gerenciar Descontos
    </a>
</nav>

<!-- Main -->
<div>

<!-- VISÃO GERAL -->
<section id="overview" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></span>
        Desconto Fidelidade
    </h2>
    <p>Ferramentas para <b>reter e recompensar clientes</b>: cupons de fidelidade, taxa embutida no preço e desconto por cadastro completo.</p>

    <div class="gc-tabs-demo">
        <span class="inactive">🎟️ Cupons</span>
        <span class="active">💰 Taxa Embutida</span>
        <span class="inactive">📋 Cadastro Completo</span>
    </div>

    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>A aba <b>Cupons</b> redireciona para a tela de cupons. As abas <b>Taxa Embutida</b> e <b>Cadastro Completo</b> têm configurações próprias.</span>
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
    <table class="gc-cmp">
        <thead><tr><th>Aba</th><th>Função</th></tr></thead>
        <tbody>
            <tr><td><b>Cupons</b></td><td>Dashboard de cupons de fidelidade (stats + listagem)</td></tr>
            <tr><td><b>Taxa Embutida</b></td><td>Embutir parte do frete no preço dos produtos</td></tr>
            <tr><td><b>Cadastro Completo</b></td><td>Desconto automático para clientes com perfil preenchido</td></tr>
        </tbody>
    </table>
</div>
</section>

<!-- TAXA EMBUTIDA -->
<section id="embedded" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
        Taxa Embutida
    </h2>
    <p>Adiciona um valor fixo ao preço de <b>cada produto</b> para compensar a taxa de entrega. O cliente vê produto um pouco mais caro, mas paga menos (ou nada) de frete.</p>

    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Taxa Embutida</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Valor a embutir por produto (R$)</span>
                <input type="text" class="gc-fi" value="1.50" disabled>
                <p class="gc-fh">Cada produto fica R$ 1,50 mais caro. Use para oferecer "frete grátis" sem perder margem.</p>
            </div>
        </div>
    </div>

    <h3>Como funciona na prática</h3>
    <table class="gc-cmp">
        <thead><tr><th>Cenário</th><th>Sem embutir</th><th>Embutindo R$ 1,50</th></tr></thead>
        <tbody>
            <tr><td>Hambúrguer</td><td>R$ 25,00</td><td>R$ 26,50</td></tr>
            <tr><td>Pedido c/ 3 itens</td><td>R$ 75,00 + R$ 8 frete</td><td>R$ 79,50 + frete grátis</td></tr>
        </tbody>
    </table>

    <div class="gc-warn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span>Valor alto demais pode deixar seus produtos caros demais comparado à concorrência. Recomendado: <b>R$ 0,50 a R$ 2,00</b>.</span>
    </div>
</div>
</section>

<!-- CADASTRO COMPLETO -->
<section id="signup" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>
        Cadastro Completo
    </h2>
    <p>Recompense clientes que preenchem CPF e data de nascimento com um <b>desconto permanente</b> em todos os pedidos.</p>

    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Configuração</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Ativar desconto por cadastro completo</span>
                <div style="display:flex;align-items:center;gap:8px;"><div style="width:36px;height:20px;border-radius:10px;background:var(--admin-primary-color,#6366f1);position:relative;"><div style="width:16px;height:16px;border-radius:50%;background:#fff;position:absolute;top:2px;right:2px;"></div></div> <span style="font-size:13px;color:#64748b;">Ativado</span></div>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Porcentagem de desconto (%)</span>
                <input type="text" class="gc-fi" value="5.00" disabled>
                <p class="gc-fh">5% de desconto no total do pedido para clientes com cadastro completo</p>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Mensagem de boas-vindas <span class="gc-tag gc-tag-opt">Opcional</span></span>
                <input type="text" class="gc-fi" value="Obrigado por completar seu cadastro!" disabled>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Prefixo do cupom</span>
                <input type="text" class="gc-fi" value="WOLL" disabled>
                <p class="gc-fh">Cupom gerado automaticamente: <b>WOLL-ABC123</b>. Máx 10 caracteres.</p>
            </div>
        </div>
    </div>

    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>O sistema gera um <b>cupom único</b> por cliente. O desconto se aplica automaticamente quando o cliente loga com cadastro completo.</span>
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
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Combine estratégias</div><div class="g-desc">Taxa embutida + desconto cadastro = "frete grátis + 5% off" sem perder margem.</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">5% é o ideal</div><div class="g-desc">Baixo o suficiente para não impactar, alto o suficiente para motivar.</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Prefixo personalizado</div><div class="g-desc">Use o nome da loja no prefixo (ex: PIZZA, BURGER) para identidade.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Taxa embutida altera preços</div><div class="g-desc">Todos os produtos ficam mais caros. Avise sua equipe.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Desconto é permanente</div><div class="g-desc">Uma vez ativado, o cliente sempre recebe o desconto. Planeje a margem.</div></div></div>
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
