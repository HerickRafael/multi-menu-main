<?php
require_once __DIR__ . '/../components/icons.php';
?>

<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#glossary" class="gi-pill">Glossário</a>
        <a href="#settings" class="gi-pill">Config</a>
        <a href="#tips" class="gi-pill">Dicas</a>
    </div>
</div>

<style>
.gi-nav-wrap{position:sticky;top:56px;z-index:50;background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 0 10px 16px;margin:0 -16px}
.gi-nav{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-right:16px;scrollbar-width:none}
.gi-nav::-webkit-scrollbar{display:none}
.gi-pill{flex-shrink:0;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#6b7280;text-decoration:none;border:1.5px solid #e5e7eb;transition:all .2s;white-space:nowrap}
.gi-pill.active{background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 2px 8px var(--primary-light)}
.gi-sec{scroll-margin-top:120px;padding:0 16px;margin-bottom:20px}
.gi-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;position:relative;overflow:hidden}
.gi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--primary);border-radius:16px 16px 0 0;opacity:0;transition:opacity .25s}
.gi-card.visible::before{opacity:1}
.gi-card h2{font-size:17px;font-weight:800;color:#1f2937;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.gi-card h2 .ic{width:34px;height:34px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.gi-card h3{font-size:14px;font-weight:700;color:#1f2937;margin:18px 0 8px}
.gi-card p{font-size:13px;color:#4b5563;line-height:1.7;margin-bottom:10px}
.gi-form-block{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin:14px 0}
.gi-form-body{padding:16px}
.gi-form-group{margin-bottom:14px}
.gi-form-group:last-child{margin-bottom:0}
.gi-form-label{display:block;font-weight:500;color:#374151;margin-bottom:6px;font-size:13px}
.gi-form-input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;color:#6b7280;box-sizing:border-box;font-family:inherit}
.gi-form-input:disabled{background:#f9fafb;color:#9ca3af}
.gi-form-help{font-size:11px;color:#6b7280;margin-top:4px;line-height:1.4}
.gi-tip{background:var(--primary-light);border:1px solid var(--primary);border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:var(--primary-dark);line-height:1.6}
.gi-tip svg{flex-shrink:0;color:var(--primary);margin-top:1px}
.gi-warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:#92400e;line-height:1.6}
.gi-warn svg{flex-shrink:0;color:#d97706;margin-top:1px}
.gi-cmp{width:100%;border-collapse:separate;border-spacing:0;border-radius:10px;overflow:hidden;border:1.5px solid #e5e7eb;margin:12px 0;font-size:11px}
.gi-cmp th{background:#f3f4f6;padding:8px;text-align:left;font-weight:700;color:#4b5563}
.gi-cmp td{padding:7px 8px;border-top:1px solid #f3f4f6;color:#374151}
.gi-gc{display:flex;gap:10px;padding:12px;border-radius:12px;margin-bottom:8px}
.gi-gc-good{background:#f0fdf4;border:1px solid #bbf7d0}
.gi-gc-warn{background:#fff7ed;border:1px solid #fed7aa}
.gi-gc .g-icon{font-size:18px;flex-shrink:0}
.gi-gc .g-title{font-size:12px;font-weight:700;margin-bottom:2px}
.gi-gc-good .g-title{color:#166534}
.gi-gc-warn .g-title{color:#9a3412}
.gi-gc .g-desc{font-size:11px;line-height:1.55}
.gi-gc-good .g-desc{color:#15803d}
.gi-gc-warn .g-desc{color:#c2410c}
</style>

<div class="products-list" style="padding:0 0 100px;">

<!-- VISÃO GERAL -->
<section id="overview" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
            Financeiro
        </h2>
        <p>Acompanhe <b>faturamento, custos, lucro e margem</b>. Configure impostos e taxas para números precisos.</p>

        <table class="gi-cmp">
            <thead><tr><th>Tela</th><th>Função</th></tr></thead>
            <tbody>
                <tr><td><b>Dashboard</b></td><td>Faturamento, lucro, DRE, gráficos</td></tr>
                <tr><td><b>Mensal</b></td><td>Detalhe mês a mês</td></tr>
                <tr><td><b>Anual</b></td><td>Comparativo 12 meses</td></tr>
                <tr><td><b>Config</b></td><td>Impostos, taxas, metas</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- GLOSSÁRIO -->
<section id="glossary" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></span>
            Glossário
        </h2>

        <table class="gi-cmp">
            <thead><tr><th>Termo</th><th>Significado</th></tr></thead>
            <tbody>
                <tr><td><b>CMV</b></td><td>Custo de Mercadoria Vendida (ingredientes vendidos)</td></tr>
                <tr><td><b>DRE</b></td><td>Demonstrativo de Resultados: Receita − CMV − Despesas = Lucro</td></tr>
                <tr><td><b>ROI</b></td><td>Retorno sobre Investimento: (Lucro ÷ Despesas) × 100</td></tr>
                <tr><td><b>Margem</b></td><td>(Lucro ÷ Receita) × 100. Ideal: 20-35%</td></tr>
                <tr><td><b>Ticket Médio</b></td><td>Faturamento ÷ nº pedidos</td></tr>
            </tbody>
        </table>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Margem saudável: <b>20-35%</b>. Abaixo de 15% = atenção urgente.</span>
        </div>
    </div>
</section>

<!-- CONFIGURAÇÕES -->
<section id="settings" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33"/></svg></span>
            Configurações
        </h2>

        <h3>📊 Impostos</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Taxa de Imposto (%)</span>
                    <input type="text" class="gi-form-input" value="8.00" disabled>
                    <p class="gi-form-help">Simples ~6-8% | Presumido ~11-16%</p>
                </div>
            </div>
        </div>

        <h3>🏪 Taxas de Canais</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Taxa iFood (%)</span>
                    <input type="text" class="gi-form-input" value="12.00" disabled>
                    <p class="gi-form-help">Básico ~12% | Entrega ~27%</p>
                </div>
            </div>
        </div>

        <h3>💰 Custos e Metas</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Mão de Obra/h (R$)</span>
                    <input type="text" class="gi-form-input" value="15.00" disabled>
                    <p class="gi-form-help">Salário ÷ horas + encargos (~70%)</p>
                </div>
                <div class="gi-form-group">
                    <span class="gi-form-label">Margem Alvo (%)</span>
                    <input type="text" class="gi-form-input" value="30.00" disabled>
                    <p class="gi-form-help">Food service: 20-35%</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DICAS -->
<section id="tips" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            Dicas
        </h2>
        <div style="display:grid;gap:8px;">
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Custos nos ingredientes</div><div class="g-desc">Sem custo, CMV = 0 e margem fica irreal.</div></div></div>
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Configure impostos</div><div class="g-desc">Pergunte ao contador a alíquota real.</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Margem < 15%</div><div class="g-desc">Revise preços e custos. Operação em risco.</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Recalcule</div><div class="g-desc">Mudou ingredientes? Use "Atualizar Custos".</div></div></div>
        </div>
    </div>
</section>

</div>

<a href="/financial/settings" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33"/></svg>
</a>

<script>
(function(){var secs=document.querySelectorAll('.gi-sec'),pills=document.querySelectorAll('.gi-pill');function up(){var y=window.scrollY+140,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});pills.forEach(function(p){p.classList.toggle('active',p.getAttribute('href')==='#'+c)})}window.addEventListener('scroll',up);up();pills.forEach(function(p){p.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.getAttribute('href').substring(1));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){e.target.querySelector('.gi-card')&&e.target.querySelector('.gi-card').classList.toggle('visible',e.isIntersecting)})},{threshold:.3});secs.forEach(function(s){obs.observe(s)})})();
</script>
