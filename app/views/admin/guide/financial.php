<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia Financeiro';
$pageDescription = 'Faturamento, custos, margem e configurações financeiras';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>';
$breadcrumbs = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $slug . '/financial/settings')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Configurações', 'url' => base_url('admin/' . $slug . '/financial/settings'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>', 'primary' => true]
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
    <a href="#glossary" data-section="glossary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
        Glossário
    </a>
    <a href="#settings" data-section="settings">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Configurações
    </a>
    <a href="#dashboard" data-section="dashboard">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        Dashboard
    </a>
    <div class="nav-group">Mais</div>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/financial/settings')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Ir para Configurações
    </a>
</nav>

<!-- Main -->
<div>

<!-- VISÃO GERAL -->
<section id="overview" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
        Financeiro
    </h2>
    <p>Acompanhe <b>faturamento, custos, lucro e margem</b> do seu negócio. Configure impostos, taxas de marketplace e metas para ter uma visão clara da saúde financeira.</p>

    <table class="gc-cmp">
        <thead><tr><th>Tela</th><th>Função</th></tr></thead>
        <tbody>
            <tr><td><b>Dashboard</b></td><td>Visão geral: faturamento, lucro, DRE, gráficos</td></tr>
            <tr><td><b>Mensal</b></td><td>Detalhamento mês a mês</td></tr>
            <tr><td><b>Anual</b></td><td>Comparativo dos 12 meses</td></tr>
            <tr><td><b>Produtos</b></td><td>Margem e lucratividade por produto</td></tr>
            <tr><td><b>Configurações</b></td><td>Impostos, taxas, custos e metas</td></tr>
        </tbody>
    </table>
</div>
</section>

<!-- GLOSSÁRIO -->
<section id="glossary" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></span>
        Glossário
    </h2>
    <p>Termos financeiros usados no sistema:</p>

    <table class="gc-cmp">
        <thead><tr><th>Termo</th><th>Significado</th></tr></thead>
        <tbody>
            <tr><td><b>CMV</b></td><td><b>Custo de Mercadoria Vendida</b> — soma do custo dos ingredientes de tudo que foi vendido</td></tr>
            <tr><td><b>DRE</b></td><td><b>Demonstrativo de Resultados</b> — resumo: Receita − CMV − Despesas = Lucro</td></tr>
            <tr><td><b>ROI</b></td><td><b>Retorno sobre Investimento</b> — quanto lucrou em relação ao que gastou (%)</td></tr>
            <tr><td><b>Margem</b></td><td>Percentual de lucro sobre a receita. Ex: receita R$ 100, lucro R$ 30 = margem 30%</td></tr>
            <tr><td><b>Ticket Médio</b></td><td>Valor médio por pedido (faturamento ÷ nº de pedidos)</td></tr>
            <tr><td><b>Saúde Financeira</b></td><td>Score calculado com base na margem, lucro e despesas do mês</td></tr>
        </tbody>
    </table>

    <div class="gc-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Food service saudável: margem entre <b>20% e 35%</b>. Abaixo de 15% = atenção urgente.</span>
    </div>
</div>
</section>

<!-- CONFIGURAÇÕES -->
<section id="settings" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 010 4h-.09c-.658.003-1.25.396-1.51 1z"/></svg></span>
        Configurações
    </h2>

    <h3>📊 Impostos</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Impostos</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Taxa de Imposto Padrão (%)</span>
                <input type="text" class="gc-fi" value="8.00" disabled>
                <p class="gc-fh">ICMS, ISS, etc. Varia por regime: <b>Simples ~6-8%</b>, Lucro Presumido ~11-16%</p>
            </div>
        </div>
    </div>

    <h3>🏪 Taxas de Canais de Venda</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Canais</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Taxa iFood (%)</span>
                <input type="text" class="gc-fi" value="12.00" disabled>
                <p class="gc-fh">Plano Básico ~12% | Plano Entrega ~27%. Verifique seu contrato.</p>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Taxa Rappi / UberEats / Delivery Próprio (%)</span>
                <input type="text" class="gc-fi" value="0.00" disabled>
                <p class="gc-fh">Comissão cobrada por cada canal. 0 = canal não utilizado.</p>
            </div>
        </div>
    </div>

    <h3>💰 Custos e Metas</h3>
    <div class="gc-form">
        <div class="gc-form-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Custos e Metas</div>
        <div class="gc-form-b">
            <div class="gc-fg">
                <span class="gc-fl">Custo Mão de Obra/hora (R$)</span>
                <input type="text" class="gc-fi" value="15.00" disabled>
                <p class="gc-fh">Salário mensal ÷ horas trabalhadas + encargos (~70%). Ex: R$ 2.000 ÷ 220h × 1.7 ≈ R$ 15,45</p>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Margem de Lucro Alvo (%)</span>
                <input type="text" class="gc-fi" value="30.00" disabled>
                <p class="gc-fh">Meta de margem sobre receita bruta. Food service típico: <b>20-35%</b></p>
            </div>
            <div class="gc-fg">
                <span class="gc-fl">Meta Faturamento / Meta Lucro (R$)</span>
                <input type="text" class="gc-fi" value="50.000,00" disabled>
                <p class="gc-fh">Metas mensais para acompanhamento no dashboard</p>
            </div>
        </div>
    </div>
</div>
</section>

<!-- DASHBOARD -->
<section id="dashboard" class="gc-sec">
<div class="gc-card">
    <h2>
        <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span>
        Dashboard
    </h2>
    <p>O dashboard calcula tudo automaticamente a partir dos <b>pedidos</b> e das <b>configurações</b>.</p>

    <h3>Como é calculado</h3>
    <table class="gc-cmp">
        <thead><tr><th>Métrica</th><th>Cálculo</th></tr></thead>
        <tbody>
            <tr><td><b>Receita Bruta</b></td><td>Soma de todos os pedidos do período</td></tr>
            <tr><td><b>CMV</b></td><td>Custo dos ingredientes dos itens vendidos</td></tr>
            <tr><td><b>Impostos</b></td><td>Receita × Taxa de Imposto (%)</td></tr>
            <tr><td><b>Taxas Canal</b></td><td>Receita × Taxa do canal (iFood, etc.)</td></tr>
            <tr><td><b>Lucro Líquido</b></td><td>Receita − CMV − Impostos − Taxas − Despesas</td></tr>
            <tr><td><b>Margem</b></td><td>(Lucro ÷ Receita) × 100</td></tr>
            <tr><td><b>ROI</b></td><td>(Lucro ÷ Despesas Totais) × 100</td></tr>
        </tbody>
    </table>

    <div class="gc-warn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span>Se os custos dos ingredientes não estão cadastrados nos produtos, o CMV será zero e a margem ficará inflada.</span>
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
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Preencha custos dos ingredientes</div><div class="g-desc">Sem custo nos ingredientes, o CMV é zero e os números ficam irreais.</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Configure impostos corretamente</div><div class="g-desc">Pergunte ao contador qual sua alíquota real (Simples, Presumido, etc.).</div></div></div>
        <div class="gc-gc gc-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Defina metas realistas</div><div class="g-desc">Use meses anteriores como base. Meta alta demais desanima.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Margem abaixo de 15%</div><div class="g-desc">Revise preços, custos e despesas imediatamente. Operação em risco.</div></div></div>
        <div class="gc-gc gc-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Recalcule após mudanças</div><div class="g-desc">Mudou ingredientes ou preços? Use "Atualizar Custos" nas configurações.</div></div></div>
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
