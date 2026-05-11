<?php
require_once __DIR__ . '/../components/icons.php';
$activeCouponTab = 'guide';
?>

<!-- Nav Pills -->
<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#form" class="gi-pill">Formulário</a>
        <a href="#types" class="gi-pill">Genérico vs Individual</a>
        <a href="#limits" class="gi-pill">Limites</a>
        <a href="#strategies" class="gi-pill">Estratégias</a>
        <a href="#tips" class="gi-pill">Dicas</a>
    </div>
</div>

<style>
/* ── Nav ── */
.gi-nav-wrap{position:sticky;top:56px;z-index:50;background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 0 10px 16px;margin:0 -16px}
.gi-nav{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-right:16px;scrollbar-width:none}
.gi-nav::-webkit-scrollbar{display:none}
.gi-pill{flex-shrink:0;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#6b7280;text-decoration:none;border:1.5px solid #e5e7eb;transition:all .2s;white-space:nowrap}
.gi-pill.active{background:var(--primary);color:#fff;border-color:var(--primary);box-shadow:0 2px 8px var(--primary-light)}

/* ── Section ── */
.gi-sec{scroll-margin-top:120px;padding:0 16px;margin-bottom:20px}
.gi-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;position:relative;overflow:hidden}
.gi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--primary);border-radius:16px 16px 0 0;opacity:0;transition:opacity .25s}
.gi-card.visible::before{opacity:1}
.gi-card h2{font-size:17px;font-weight:800;color:#1f2937;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.gi-card h2 .ic{width:34px;height:34px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.gi-card h3{font-size:14px;font-weight:700;color:#1f2937;margin:18px 0 8px;display:flex;align-items:center;gap:6px}
.gi-card p{font-size:13px;color:#4b5563;line-height:1.7;margin-bottom:10px}

/* ── Steps ── */
.gi-steps{list-style:none;padding:0;margin:12px 0;counter-reset:gs}
.gi-steps li{counter-increment:gs;display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;line-height:1.6}
.gi-steps li:last-child{border-bottom:none}
.gi-steps li::before{content:counter(gs);flex-shrink:0;width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-top:2px}

/* ── Form blocks ── */
.gi-form-block{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin:14px 0}
.gi-form-header{display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;padding:12px 16px;border-bottom:1px solid #e5e7eb}
.gi-form-header svg{width:18px;height:18px;color:var(--primary);flex-shrink:0}
.gi-form-body{padding:16px}
.gi-form-group{margin-bottom:14px}
.gi-form-group:last-child{margin-bottom:0}
.gi-form-label{display:block;font-weight:500;color:#374151;margin-bottom:6px;font-size:13px}
.gi-form-input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;color:#6b7280;box-sizing:border-box;font-family:inherit}
.gi-form-input:disabled{background:#f9fafb;color:#9ca3af}
.gi-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.gi-form-help{font-size:11px;color:#6b7280;margin-top:4px;line-height:1.4}

/* ── Annotations ── */
.gi-annot{display:flex;gap:6px;margin-top:8px;padding:8px 10px;background:var(--primary-light);border:1px solid var(--primary);border-radius:8px;font-size:11px;color:var(--primary-dark);line-height:1.5}
.gi-annot svg{flex-shrink:0;margin-top:1px}

/* ── Tip/Warn ── */
.gi-tip{background:var(--primary-light);border:1px solid var(--primary);border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:var(--primary-dark);line-height:1.6}
.gi-tip svg{flex-shrink:0;color:var(--primary);margin-top:1px}
.gi-warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:12px;margin:12px 0;display:flex;gap:8px;font-size:12px;color:#92400e;line-height:1.6}
.gi-warn svg{flex-shrink:0;color:#d97706;margin-top:1px}

/* ── Compare table ── */
.gi-cmp{width:100%;border-collapse:separate;border-spacing:0;border-radius:10px;overflow:hidden;border:1.5px solid #e5e7eb;margin:12px 0;font-size:11px}
.gi-cmp th{background:#f3f4f6;padding:8px;text-align:left;font-weight:700;color:#4b5563}
.gi-cmp td{padding:7px 8px;border-top:1px solid #f3f4f6;color:#374151}

/* ── Tag ── */
.gi-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:9px;font-weight:700;vertical-align:middle;margin-left:4px}
.gi-tag-req{background:#fee2e2;color:#dc2626}
.gi-tag-opt{background:#f0fdf4;color:#16a34a}

/* ── Toggle ── */
.gi-toggle-row{display:flex;align-items:center;gap:12px;padding:8px 0}
.gi-toggle-track{width:40px;height:22px;border-radius:11px;position:relative;flex-shrink:0;background:#cbd5e1}
.gi-toggle-track.on{background:var(--primary)}
.gi-toggle-thumb{position:absolute;left:2px;top:2px;width:18px;height:18px;background:#fff;border-radius:9px;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gi-toggle-track.on .gi-toggle-thumb{transform:translateX(18px)}

/* ── Good/Caution ── */
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

<!-- ═══ VISÃO GERAL ═══ -->
<section id="overview" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
            O que são Cupons?
        </h2>
        <p>Cupons são <b>códigos de desconto</b> que o cliente digita no carrinho para obter desconto em porcentagem sobre o valor total do pedido.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:14px 0;">
            <div style="background:var(--primary-light);border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🎫</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Genérico</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">Qualquer cliente</div>
            </div>
            <div style="background:#d1fae533;border:1px solid #bbf7d0;border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">👤</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Individual</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">1 cliente só</div>
            </div>
        </div>

        <ol class="gi-steps">
            <li><div>Crie o cupom com código, desconto % e limite</div></li>
            <li><div>Divulgue o código (WhatsApp, rede social)</div></li>
            <li><div>Cliente digita no carrinho antes de finalizar</div></li>
            <li><div>Desconto aplicado automaticamente</div></li>
        </ol>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Código automático:</b> deixe em branco e o sistema gera um código aleatório.</span>
        </div>
    </div>
</section>

<!-- ═══ FORMULÁRIO ═══ -->
<section id="form" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg></span>
            Formulário
        </h2>
        <p>Cada campo do formulário de criação:</p>

        <!-- Código -->
        <h3>① Código do Cupom</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21.41 11.58l-9-9A2 2 0 0011 2H4a2 2 0 00-2 2v7c0 .55.22 1.05.59 1.42l9 9A2 2 0 0013 22a2 2 0 001.41-.59l7-7A2 2 0 0022 13a2 2 0 00-.59-1.42z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
                Código
            </div>
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Código do Cupom <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                    <input type="text" class="gi-form-input" value="PROMO10" disabled style="text-transform:uppercase;font-weight:600;letter-spacing:1px;">
                    <p class="gi-form-help">Letras, números e hífens. Ex: PROMO10, BF2024</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Convertido para <b>maiúsculas</b> automaticamente. Deixe em branco para gerar código aleatório.</span>
        </div>

        <!-- Telefone -->
        <h3>② Telefone do Cliente</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                Telefone
            </div>
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <span class="gi-form-label">Telefone do Cliente <span class="gi-tag gi-tag-opt">Opcional</span></span>
                    <input type="text" class="gi-form-input" placeholder="Ex: 11999999999" disabled>
                    <p class="gi-form-help">Em branco = genérico. Preenchido = individual.</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Sem telefone</b> = qualquer pessoa usa. <b>Com telefone</b> = só esse cliente.</span>
        </div>

        <!-- Desconto + Limite -->
        <h3>③ Desconto & Limite</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                Valores
            </div>
            <div class="gi-form-body">
                <div class="gi-form-row">
                    <div class="gi-form-group">
                        <span class="gi-form-label">Desconto (%) <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                        <input type="number" class="gi-form-input" value="10" disabled>
                        <p class="gi-form-help">Entre 1% e 100%</p>
                    </div>
                    <div class="gi-form-group">
                        <span class="gi-form-label">Limite de Usos <span class="gi-tag gi-tag-req">Obrigatório</span></span>
                        <input type="number" class="gi-form-input" value="50" disabled>
                        <p class="gi-form-help">Total de vezes</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Desconto:</b> % sobre o total. <b>Limite:</b> atingido → cupom desativado automaticamente.</span>
        </div>

        <!-- Toggle -->
        <h3>④ Uso por Cliente</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
                Múltiplos Usos
            </div>
            <div class="gi-form-body">
                <div class="gi-toggle-row">
                    <div class="gi-toggle-track on"><div class="gi-toggle-thumb"></div></div>
                    <span style="font-size:13px;color:#374151;">Mesmo cliente pode usar múltiplas vezes</span>
                </div>
                <p class="gi-form-help">Se desativado, cada cliente só usa uma vez.</p>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>ON:</b> 1 cliente pode usar várias vezes. <b>OFF:</b> 1 uso por cliente.</span>
        </div>
    </div>
</section>

<!-- ═══ GENÉRICO VS INDIVIDUAL ═══ -->
<section id="types" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg></span>
            Genérico vs Individual
        </h2>
        <p>A diferença está no campo <b>Telefone</b>:</p>

        <table class="gi-cmp">
            <thead><tr><th></th><th>🎫 Genérico</th><th>👤 Individual</th></tr></thead>
            <tbody>
                <tr><td><b>Telefone</b></td><td>Em branco</td><td>Preenchido</td></tr>
                <tr><td><b>Quem usa</b></td><td>Qualquer um</td><td>Só 1 cliente</td></tr>
                <tr><td><b>Uso</b></td><td>Promoção geral</td><td>Compensação</td></tr>
                <tr><td><b>Risco</b></td><td>Pode vazar</td><td>Seguro</td></tr>
            </tbody>
        </table>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Marketing em massa:</b> genérico. <b>Problema com cliente:</b> individual com telefone.</span>
        </div>
    </div>
</section>

<!-- ═══ LIMITES ═══ -->
<section id="limits" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
            Limites de Uso
        </h2>
        <p>Dois controles juntos:</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0;">
            <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:6px;">🔢 Limite Total</div>
                <div style="font-size:11px;color:#1e3a5f;line-height:1.5;">
                    Total de usos somando todos os clientes.<br>
                    <b>Ex:</b> 50 → esgota após 50 usos
                </div>
            </div>
            <div style="background:#faf5ff;border:1.5px solid #e9d5ff;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#7c3aed;margin-bottom:6px;">👤 Por Cliente</div>
                <div style="font-size:11px;color:#4c1d95;line-height:1.5;">
                    Se o mesmo pode reusar.<br>
                    <b>ON:</b> usa várias vezes<br>
                    <b>OFF:</b> 1x por cliente
                </div>
            </div>
        </div>

        <h3>📊 Combinações</h3>
        <table class="gi-cmp">
            <thead><tr><th>Cenário</th><th>Limite</th><th>Multi</th></tr></thead>
            <tbody>
                <tr><td>Promoção</td><td>100</td><td>OFF</td></tr>
                <tr><td>VIP</td><td>10</td><td>ON</td></tr>
                <tr><td>Flash sale</td><td>20</td><td>OFF</td></tr>
                <tr><td>Compensação</td><td>1</td><td>OFF</td></tr>
            </tbody>
        </table>

        <div class="gi-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span>Limite atingido → cupom fica <b>"Esgotado"</b> automaticamente. Edite para aumentar.</span>
        </div>
    </div>
</section>

<!-- ═══ ESTRATÉGIAS ═══ -->
<section id="strategies" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707"/></svg></span>
            Estratégias
        </h2>

        <div style="display:grid;gap:10px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px;">🚀 Primeira Compra</div>
                <div style="font-size:11px;color:#15803d;line-height:1.5;">
                    <code style="background:#dcfce7;padding:1px 4px;border-radius:3px;font-size:10px;">PRIMEIRACOMPRA10</code> · 10% · Limite: 500 · Multi: OFF
                </div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0369a1;margin-bottom:4px;">🔥 Black Friday</div>
                <div style="font-size:11px;color:#075985;line-height:1.5;">
                    <code style="background:#dbeafe;padding:1px 4px;border-radius:3px;font-size:10px;">BF2024</code> · 15-20% · Limite: 100 · Multi: OFF
                </div>
            </div>
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#7c3aed;margin-bottom:4px;">💜 VIP / Fidelidade</div>
                <div style="font-size:11px;color:#5b21b6;line-height:1.5;">
                    Auto-gerado · 10% · Limite: 5 · Multi: ON · Telefone: do cliente
                </div>
            </div>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#9a3412;margin-bottom:4px;">🔧 Compensação</div>
                <div style="font-size:11px;color:#c2410c;line-height:1.5;">
                    Auto-gerado · 15-30% · Limite: 1 · Multi: OFF · Telefone: do cliente
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ DICAS ═══ -->
<section id="tips" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            Dicas
        </h2>
        <div style="display:grid;gap:8px;">
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Códigos memoráveis</div><div class="g-desc">Use curtos e fáceis: PROMO10, FRETE, VIP20.</div></div>
            </div>
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Limite com sentido</div><div class="g-desc">Alto (500+) para campanhas. Baixo (10-50) para urgência.</div></div>
            </div>
            <div class="gi-gc gi-gc-good">
                <span class="g-icon">✅</span>
                <div><div class="g-title">Compensação = Individual</div><div class="g-desc">Sempre com telefone. Nunca divulgue publicamente.</div></div>
            </div>
            <div class="gi-gc gi-gc-warn">
                <span class="g-icon">⚠️</span>
                <div><div class="g-title">Cuidado com 100%</div><div class="g-desc">Desconto 100% = grátis. Use com limite=1 e telefone.</div></div>
            </div>
            <div class="gi-gc gi-gc-warn">
                <span class="g-icon">⚠️</span>
                <div><div class="g-title">Genérico pode vazar</div><div class="g-desc">Código em rede social = qualquer um usa. Limite baixo!</div></div>
            </div>
        </div>
    </div>
</section>

</div><!-- end products-list -->

<!-- FAB -->
<a href="/coupons/create" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
</a>

<script>
(function(){
    var secs=document.querySelectorAll('.gi-sec'),pills=document.querySelectorAll('.gi-pill');
    function up(){var y=window.scrollY+140,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});pills.forEach(function(p){p.classList.toggle('active',p.getAttribute('href')==='#'+c)})}
    window.addEventListener('scroll',up);up();
    pills.forEach(function(p){p.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.getAttribute('href').substring(1));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});
    var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){e.target.querySelector('.gi-card')&&e.target.querySelector('.gi-card').classList.toggle('visible',e.isIntersecting)})},{threshold:.3});
    secs.forEach(function(s){obs.observe(s)});
})();
</script>
