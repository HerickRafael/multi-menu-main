<?php
require_once __DIR__ . '/../components/icons.php';
$activeProductNav = 'guide-crosssell';
?>

<!-- Nav Pills -->
<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#how" class="gi-pill">Como Funciona</a>
        <a href="#form" class="gi-pill">Formulário</a>
        <a href="#examples" class="gi-pill">Exemplos</a>
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
.gi-annot{display:flex;gap:6px;margin-top:8px;padding:8px 10px;background:var(--primary-light);border:1px solid var(--primary);border-radius:8px;font-size:11px;color:var(--primary-dark);line-height:1.5}
.gi-annot svg{flex-shrink:0;margin-top:1px}
.gi-tip{background:var(--primary-light);border:1px solid var(--primary);border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:var(--primary-dark);line-height:1.6}
.gi-tip svg{flex-shrink:0;color:var(--primary);margin-top:1px}
.gi-warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:#92400e;line-height:1.6}
.gi-warn svg{flex-shrink:0;color:#d97706;margin-top:1px}
.gi-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:9px;font-weight:700;vertical-align:middle;margin-left:4px}
.gi-tag-req{background:#fee2e2;color:#dc2626}
.gi-chk-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:8px}
.gi-chk-row input[type=checkbox]{width:18px;height:18px;accent-color:var(--primary)}
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
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
            O que é Cross-Sell?
        </h2>
        <p>Cross-Sell sugere <b>categorias complementares</b> quando o cliente navega pelo cardápio. Ex: vendo Hambúrgueres, aparecer "Que tal uma bebida?"</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:14px 0;">
            <div style="background:var(--primary-light);border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🍔</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Disparadora</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">Cliente está vendo</div>
            </div>
            <div style="background:#d1fae533;border:1px solid #bbf7d0;border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🥤</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Recomendada</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">Aparece como sugestão</div>
            </div>
        </div>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Aumenta o ticket médio!</b> Clientes tendem a adicionar mais itens.</span>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section id="how" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg></span>
            Como Funciona
        </h2>
        <ol class="gi-steps">
            <li><div>Escolha a <b>categoria disparadora</b></div></li>
            <li><div>Marque as <b>categorias para recomendar</b></div></li>
            <li><div>Escreva um <b>título persuasivo</b> para cada</div></li>
            <li><div>A sugestão aparece <b>automaticamente</b></div></li>
        </ol>

        <!-- Visual example -->
        <div style="background:#f9fafb;border:2px dashed #e5e7eb;border-radius:12px;padding:16px;margin-top:12px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <span style="background:var(--primary);color:#fff;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;">🍔 Hambúrgueres</span>
                <span style="font-size:11px;color:#9ca3af;">→ dispara</span>
            </div>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:6px;">
                <div style="font-size:11px;color:#6b7280;">🥤 Bebidas</div>
                <div style="font-size:10px;color:var(--primary);margin-top:2px;">"Que tal uma bebida?"</div>
            </div>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px;">
                <div style="font-size:11px;color:#6b7280;">🍟 Acompanhamentos</div>
                <div style="font-size:10px;color:var(--primary);margin-top:2px;">"Batata frita?"</div>
            </div>
        </div>
    </div>
</section>

<!-- FORMULÁRIO -->
<section id="form" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg></span>
            Formulário
        </h2>

        <h3>① Categoria Disparadora</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                Disparadora
            </div>
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Quando o cliente ver <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <select class="gi-form-select" disabled>
                        <option selected>🍔 Hambúrgueres</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Categoria que <b>ativa</b> a recomendação quando acessada.</span>
        </div>

        <h3>② Categorias Recomendadas</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                Recomendar
            </div>
            <div class="gi-form-body">
                <div class="gi-chk-row" style="border-color:var(--primary);background:var(--primary-light);">
                    <input type="checkbox" checked disabled>
                    <span style="font-size:13px;color:#374151;font-weight:600;">🥤 Bebidas</span>
                </div>
                <div class="gi-form-group" style="padding-left:12px;">
                    <span class="gi-form-label">Título da seção</span>
                    <input type="text" class="gi-form-input" value="Que tal uma bebida?" disabled>
                </div>
                <div class="gi-chk-row">
                    <input type="checkbox" disabled>
                    <span style="font-size:13px;color:#374151;">🍕 Pizzas</span>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Ao marcar, aparece campo de <b>título</b> — texto que o cliente vê.</span>
        </div>
    </div>
</section>

<!-- EXEMPLOS -->
<section id="examples" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707"/></svg></span>
            Exemplos
        </h2>
        <div style="display:grid;gap:10px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px;">🍔 Hamburgueria</div>
                <div style="font-size:11px;color:#15803d;line-height:1.5;">Hambúrgueres → Bebidas + Acompanhamentos</div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0369a1;margin-bottom:4px;">🍕 Pizzaria</div>
                <div style="font-size:11px;color:#075985;line-height:1.5;">Pizzas → Bebidas + Bordas especiais</div>
            </div>
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#7c3aed;margin-bottom:4px;">🍣 Japonesa</div>
                <div style="font-size:11px;color:#5b21b6;line-height:1.5;">Sushis → Bebidas + Entradas</div>
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
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Títulos persuasivos</div><div class="g-desc">"Que tal uma bebida?" funciona melhor que "Bebidas".</div></div>
            </div>
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Combinações naturais</div><div class="g-desc">Hambúrguer → Bebida é natural. Recomende o que faz sentido.</div></div>
            </div>
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Desative sem excluir</div><div class="g-desc">Use o toggle de status para pausar regras temporariamente.</div></div>
            </div>
            <div class="gi-gc gi-gc-warn">
                <span class="g-icon">⚠️</span>
                <div><div class="g-title">Não exagere</div><div class="g-desc">2-3 recomendações por categoria. Muitas confundem o cliente.</div></div>
            </div>
            <div class="gi-gc gi-gc-warn">
                <span class="g-icon">⚠️</span>
                <div><div class="g-title">Evite circular</div><div class="g-desc">Se A recomenda B, B não precisa recomendar A de volta.</div></div>
            </div>
        </div>
    </div>
</section>

</div>

<!-- FAB -->
<a href="/cross-sell" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
</a>

<script>
(function(){var secs=document.querySelectorAll('.gi-sec'),pills=document.querySelectorAll('.gi-pill');function up(){var y=window.scrollY+140,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});pills.forEach(function(p){p.classList.toggle('active',p.getAttribute('href')==='#'+c)})}window.addEventListener('scroll',up);up();pills.forEach(function(p){p.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.getAttribute('href').substring(1));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){e.target.querySelector('.gi-card')&&e.target.querySelector('.gi-card').classList.toggle('visible',e.isIntersecting)})},{threshold:.3});secs.forEach(function(s){obs.observe(s)})})();
</script>
