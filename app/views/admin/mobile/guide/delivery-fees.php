<?php
require_once __DIR__ . '/../components/icons.php';
?>

<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#ajustes" class="gi-pill">Ajustes</a>
        <a href="#hierarchy" class="gi-pill">Cidades/Bairros</a>
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
.gi-card h3{font-size:14px;font-weight:700;color:#1f2937;margin:18px 0 8px;display:flex;align-items:center;gap:6px}
.gi-card p{font-size:13px;color:#4b5563;line-height:1.7;margin-bottom:10px}
.gi-steps{list-style:none;padding:0;margin:12px 0;counter-reset:gs}
.gi-steps li{counter-increment:gs;display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;line-height:1.6}
.gi-steps li:last-child{border-bottom:none}
.gi-steps li::before{content:counter(gs);flex-shrink:0;width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-top:2px}
.gi-form-block{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin:14px 0}
.gi-form-header{display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;padding:12px 16px;border-bottom:1px solid #e5e7eb}
.gi-form-header svg{width:18px;height:18px;color:var(--primary);flex-shrink:0}
.gi-form-body{padding:16px}
.gi-form-group{margin-bottom:14px}
.gi-form-group:last-child{margin-bottom:0}
.gi-form-label{display:block;font-weight:500;color:#374151;margin-bottom:6px;font-size:13px}
.gi-form-input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;color:#6b7280;box-sizing:border-box;font-family:inherit}
.gi-form-input:disabled{background:#f9fafb;color:#9ca3af}
.gi-form-select{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;color:#374151;box-sizing:border-box}
.gi-form-help{font-size:11px;color:#6b7280;margin-top:4px;line-height:1.4}
.gi-tip{background:var(--primary-light);border:1px solid var(--primary);border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:var(--primary-dark);line-height:1.6}
.gi-tip svg{flex-shrink:0;color:var(--primary);margin-top:1px}
.gi-warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:#92400e;line-height:1.6}
.gi-warn svg{flex-shrink:0;color:#d97706;margin-top:1px}
.gi-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:9px;font-weight:700;vertical-align:middle;margin-left:4px}
.gi-tag-req{background:#fee2e2;color:#dc2626}
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
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            Taxas de Entrega
        </h2>
        <p>Configure <b>quanto cobrar de frete</b> por bairro, ajuste em lote e defina regras especiais.</p>

        <ol class="gi-steps">
            <li><div>Cadastre <b>cidades</b> que você atende</div></li>
            <li><div>Adicione <b>bairros</b> com o valor de cada taxa</div></li>
            <li><div>Use <b>Ajustes Rápidos</b> para alterar tudo de uma vez</div></li>
        </ol>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Hierarquia: <b>Cidade → Bairro → Taxa</b>. Cadastre a cidade primeiro.</span>
        </div>
    </div>
</section>

<!-- AJUSTES -->
<section id="ajustes" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 20V10M6 20V4M18 20v-6"/></svg></span>
            Ajustes Rápidos
        </h2>

        <h3>① Ajuste em Lote</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Valor do ajuste (R$)</span>
                    <input type="text" class="gi-form-input" value="2.00" disabled>
                    <p class="gi-form-help"><b>Positivo</b> = aumenta todas • <b>Negativo</b> = diminui todas</p>
                </div>
            </div>
        </div>

        <h3>② Taxa Após 18h</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Adicional (R$)</span>
                    <input type="text" class="gi-form-input" value="3.00" disabled>
                    <p class="gi-form-help">Somado automaticamente após as 18h</p>
                </div>
            </div>
        </div>

        <h3>③ Taxa Gratuita</h3>
        <p>Toggle que <b>zera todas as taxas</b>. Útil para promoções rápidas.</p>

        <h3>④ Frete Grátis Promocional</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Valor mínimo do pedido (R$)</span>
                    <input type="text" class="gi-form-input" value="50.00" disabled>
                    <p class="gi-form-help">Pedidos acima = frete grátis. 0 = desativado.</p>
                </div>
            </div>
        </div>

        <div class="gi-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span><b>Exclusivo:</b> Taxa Gratuita e Frete Grátis Promocional não funcionam juntos.</span>
        </div>
    </div>
</section>

<!-- CIDADES / BAIRROS -->
<section id="hierarchy" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M13 21V3l6 4v14"/></svg></span>
            Cidades e Bairros
        </h2>

        <h3>🏙️ Cidade</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Nome da cidade <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <input type="text" class="gi-form-input" value="São Paulo" disabled>
                </div>
            </div>
        </div>

        <h3>🏘️ Bairro</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Cidade <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <select class="gi-form-select" disabled><option>São Paulo</option></select>
                </div>
                <div class="gi-form-group">
                    <span class="gi-form-label">Bairro <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <input type="text" class="gi-form-input" value="Vila Mariana" disabled>
                </div>
                <div class="gi-form-group">
                    <span class="gi-form-label">Taxa (R$) <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <input type="text" class="gi-form-input" value="8.00" disabled>
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
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Cubra todos os bairros</div><div class="g-desc">Sem taxa = cliente não fecha pedido.</div></div></div>
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Ajuste em lote</div><div class="g-desc">Combustível subiu? +R$ 1 em tudo de uma vez.</div></div></div>
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Frete grátis = + pedidos</div><div class="g-desc">Use valor mínimo para aumentar ticket médio.</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Taxa noturna é automática</div><div class="g-desc">Cobrada após 18h sem alerta. Informe o cliente.</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Grátis vs Promocional</div><div class="g-desc">São exclusivos. Ativar um desativa o outro.</div></div></div>
        </div>
    </div>
</section>

</div>

<a href="/settings/delivery" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
</a>

<script>
(function(){var secs=document.querySelectorAll('.gi-sec'),pills=document.querySelectorAll('.gi-pill');function up(){var y=window.scrollY+140,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});pills.forEach(function(p){p.classList.toggle('active',p.getAttribute('href')==='#'+c)})}window.addEventListener('scroll',up);up();pills.forEach(function(p){p.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.getAttribute('href').substring(1));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){e.target.querySelector('.gi-card')&&e.target.querySelector('.gi-card').classList.toggle('visible',e.isIntersecting)})},{threshold:.3});secs.forEach(function(s){obs.observe(s)})})();
</script>
