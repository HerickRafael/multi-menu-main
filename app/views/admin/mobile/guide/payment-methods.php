<?php
require_once __DIR__ . '/../components/icons.php';
?>

<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#types" class="gi-pill">Tipos</a>
        <a href="#form" class="gi-pill">Formulário</a>
        <a href="#pix" class="gi-pill">Pix</a>
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
.gi-tag-opt{background:#f0fdf4;color:#16a34a}
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
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
            Métodos de Pagamento
        </h2>
        <p>Configure quais <b>formas de pagamento</b> seus clientes podem usar no checkout.</p>

        <ol class="gi-steps">
            <li><div>Escolha o <b>tipo</b> (Crédito, Débito, Pix...)</div></li>
            <li><div>Defina <b>nome</b> e opcionalmente um <b>ícone</b></div></li>
            <li><div>Ative/desative conforme necessário</div></li>
        </ol>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Pix e Dinheiro</b> têm nomes e ícones automáticos — basta selecionar o tipo.</span>
        </div>
    </div>
</section>

<!-- TIPOS -->
<section id="types" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
            Tipos
        </h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:12px 0;">
            <div style="border:1.5px solid #3b82f6;background:#eff6ff;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">💳</div><div style="font-size:11px;font-weight:700;color:#1d4ed8;">Crédito</div>
            </div>
            <div style="border:1.5px solid #10b981;background:#ecfdf5;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">💳</div><div style="font-size:11px;font-weight:700;color:#059669;">Débito</div>
            </div>
            <div style="border:1.5px solid #8b5cf6;background:#faf5ff;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">📱</div><div style="font-size:11px;font-weight:700;color:#7c3aed;">Pix</div>
            </div>
            <div style="border:1.5px solid #f59e0b;background:#fffbeb;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">💵</div><div style="font-size:11px;font-weight:700;color:#d97706;">Dinheiro</div>
            </div>
            <div style="border:1.5px solid #ec4899;background:#fdf2f8;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">🎫</div><div style="font-size:11px;font-weight:700;color:#db2777;">Vale</div>
            </div>
            <div style="border:1.5px solid #6b7280;background:#f9fafb;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;">📋</div><div style="font-size:11px;font-weight:700;color:#4b5563;">Outros</div>
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

        <h3>① Tipo</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Tipo <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <select class="gi-form-select" disabled>
                        <option selected>Crédito</option>
                    </select>
                    <p class="gi-form-help">Determina campos extras (Pix, Dinheiro = automático)</p>
                </div>
            </div>
        </div>

        <h3>② Nome</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Nome da bandeira <span class="gi-tag gi-tag-req">Obrigatório*</span></span>
                    <input type="text" class="gi-form-input" value="Visa" disabled>
                    <p class="gi-form-help">*Oculto para Pix e Dinheiro (automático)</p>
                </div>
            </div>
        </div>

        <h3>③ Ícone</h3>
        <div class="gi-form-block">
            <div class="gi-form-body" style="text-align:center;">
                <div style="border:2px dashed #d1d5db;border-radius:10px;padding:16px;background:#f9fafb;">
                    <div style="font-size:12px;color:#6b7280;">Selecione da biblioteca ou envie SVG/PNG</div>
                    <div style="display:flex;gap:6px;justify-content:center;margin-top:8px;">
                        <div style="width:32px;height:32px;border:2px solid var(--primary);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;background:#fff;">💳</div>
                        <div style="width:32px;height:32px;border:1px solid #e5e7eb;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;background:#fff;">💳</div>
                    </div>
                </div>
            </div>
        </div>

        <h3>④ Instruções</h3>
        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Instruções <span class="gi-tag gi-tag-opt">Opcional</span></span>
                    <input type="text" class="gi-form-input" placeholder="Recado exibido ao cliente" disabled>
                    <p class="gi-form-help">Ex: "Envie o comprovante pelo WhatsApp"</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PIX -->
<section id="pix" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
            Pix
        </h2>
        <p>Ao selecionar tipo <b>Pix</b>, campos extras aparecem:</p>

        <div class="gi-form-block">
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Chave Pix</span>
                    <input type="text" class="gi-form-input" value="11999999999" disabled>
                </div>
                <div class="gi-form-group">
                    <span class="gi-form-label">Nome do Titular</span>
                    <input type="text" class="gi-form-input" value="João da Silva" disabled>
                </div>
            </div>
        </div>

        <h3>Tipos de chave</h3>
        <table class="gi-cmp">
            <thead><tr><th>Tipo</th><th>Exemplo</th></tr></thead>
            <tbody>
                <tr><td><b>CPF</b></td><td>12345678901</td></tr>
                <tr><td><b>CNPJ</b></td><td>12345678000199</td></tr>
                <tr><td><b>E-mail</b></td><td>loja@email.com</td></tr>
                <tr><td><b>Telefone</b></td><td>+5511999999999</td></tr>
                <tr><td><b>Aleatória</b></td><td>a1b2c3d4-e5f6...</td></tr>
            </tbody>
        </table>

        <div class="gi-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span>Chave errada = cliente não consegue pagar. <b>Teste antes</b> de ativar!</span>
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
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Mínimo 3 métodos</div><div class="g-desc">Pix + Cartão + Dinheiro. Menos opções = mais desistência.</div></div></div>
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Ícones ajudam</div><div class="g-desc">Métodos com bandeira visual são mais confiáveis pro cliente.</div></div></div>
            <div class="gi-gc gi-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Instruções claras</div><div class="g-desc">"Envie comprovante Pix pelo WhatsApp" ou "Tenha troco pronto".</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Desative, não exclua</div><div class="g-desc">Use toggle para pausar. Excluir perde histórico de pedidos.</div></div></div>
            <div class="gi-gc gi-gc-warn"><span class="g-icon">⚠️</span><div><div class="g-title">Verifique chave Pix</div><div class="g-desc">Chave errada = cliente não paga. Teste antes de ativar.</div></div></div>
        </div>
    </div>
</section>

</div>

<a href="/settings/payments" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
</a>

<script>
(function(){var secs=document.querySelectorAll('.gi-sec'),pills=document.querySelectorAll('.gi-pill');function up(){var y=window.scrollY+140,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});pills.forEach(function(p){p.classList.toggle('active',p.getAttribute('href')==='#'+c)})}window.addEventListener('scroll',up);up();pills.forEach(function(p){p.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.getAttribute('href').substring(1));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){e.target.querySelector('.gi-card')&&e.target.querySelector('.gi-card').classList.toggle('visible',e.isIntersecting)})},{threshold:.3});secs.forEach(function(s){obs.observe(s)})})();
</script>
