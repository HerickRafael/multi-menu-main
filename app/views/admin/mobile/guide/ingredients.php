<?php
require_once __DIR__ . '/../components/icons.php';
$activeIngredientNav = 'guide';
?>

<!-- Nav Pills -->
<div class="gi-nav-wrap">
    <div class="gi-nav">
        <a href="#overview" class="gi-pill active">Visão Geral</a>
        <a href="#form" class="gi-pill">Formulário</a>
        <a href="#pricing" class="gi-pill">Custo vs Venda</a>
        <a href="#units" class="gi-pill">Unidades</a>
        <a href="#usage" class="gi-pill">Uso</a>
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

/* ── Real Form Blocks ── */
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

/* Image upload */
.gi-upload{width:100%;aspect-ratio:1/1;max-width:120px;border:2px dashed #d1d5db;border-radius:12px;background:#f9fafb;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#9ca3af;margin:0 auto}
.gi-upload svg{width:36px;height:36px}

/* Price prefix */
.gi-prefix{position:relative;display:flex;align-items:center}
.gi-prefix .pf{position:absolute;left:14px;color:#6b7280;font-weight:500;font-size:14px;pointer-events:none;z-index:1}
.gi-prefix .gi-form-input{padding-left:38px}

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

/* ── Tag ── */
.gi-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:9px;font-weight:700;vertical-align:middle;margin-left:4px}
.gi-tag-req{background:#fee2e2;color:#dc2626}
.gi-tag-opt{background:#f0fdf4;color:#16a34a}

/* Margin preview */
.gi-margin{border-radius:12px;padding:14px;text-align:center;margin-bottom:8px}
.gi-margin-good{background:#f0fdf4;border:1.5px solid #86efac}
.gi-margin-warn{background:#fef9c3;border:1.5px solid #fde047}
.gi-margin-bad{background:#fef2f2;border:1.5px solid #fca5a5}
</style>

<div class="products-list" style="padding:0 0 100px;">

<!-- ═══ VISÃO GERAL ═══ -->
<section id="overview" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
            O que é um Ingrediente?
        </h2>
        <p>Ingredientes são os <b>blocos fundamentais</b> do seu cardápio. Cadastre ingredientes para usar como personalização nos produtos.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin:14px 0;">
            <div style="background:var(--primary-light);border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🧀</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Extra</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">+/− qty</div>
            </div>
            <div style="background:#d1fae533;border:1px solid #bbf7d0;border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🍓</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Topping</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">Monte seu</div>
            </div>
            <div style="background:#fef3c733;border:1px solid #fde68a;border-radius:12px;padding:14px 8px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🥗</div>
                <div style="font-size:11px;font-weight:700;color:#1f2937;">Escolha</div>
                <div style="font-size:10px;color:#6b7280;margin-top:2px;">Tipo molho</div>
            </div>
        </div>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><strong>Cadastre ingredientes primeiro!</strong> Depois crie produtos e adicione ingredientes na personalização.</span>
        </div>
    </div>
</section>

<!-- ═══ FORMULÁRIO ═══ -->
<section id="form" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg></span>
            Formulário — Bloco a Bloco
        </h2>
        <p>Cada card do formulário exatamente como aparece no sistema:</p>

        <!-- ─── BLOCO 1: IMAGEM ─── -->
        <h3>① Imagem</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Imagem
            </div>
            <div class="gi-form-body" style="text-align:center;">
                <div class="gi-upload">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span style="font-size:12px;font-weight:500">Adicionar foto</span>
                </div>
                <p class="gi-form-help" style="margin-top:8px;">Recomendado: 800×800px quadrado. Máx. 5MB.</p>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Imagem <b>opcional</b>. Aparece na personalização do produto. JPG, PNG ou WebP.</span>
        </div>

        <!-- ─── BLOCO 2: NOME ─── -->
        <h3>② Nome</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round"/></svg>
                Nome *
            </div>
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <input type="text" class="gi-form-input" value="Queijo Cheddar" disabled>
                    <p class="gi-form-help">Nome exibido ao cliente na personalização.</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Nome</b> é obrigatório e aparece pro cliente. Use nomes claros: "Cheddar", "Bacon", "Molho Especial".</span>
        </div>

        <!-- ─── BLOCO 3: NOME INTERNO ─── -->
        <h3>③ Nome Interno</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 8h12M6 12h8M6 16h4" stroke-linecap="round"/></svg>
                Nome Interno
            </div>
            <div class="gi-form-body">
                <div class="gi-form-group">
                    <input type="text" class="gi-form-input" value="Cheddar Polenghi 150g" disabled placeholder="Nome para uso interno (opcional)">
                    <p class="gi-form-help">Usado apenas internamente — marca, tamanho, etc.</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Opcional.</b> Complemento visível só no painel admin. Perfeito para diferenciar variações (marca, peso).</span>
        </div>

        <!-- ─── BLOCO 4: CUSTO + VENDA ─── -->
        <h3>④ Custo & Preço de Venda</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Preços
            </div>
            <div class="gi-form-body">
                <div class="gi-form-row">
                    <div class="gi-form-group">
                        <label class="gi-form-label">Custo<span class="gi-tag gi-tag-req">Obrig.</span></label>
                        <div class="gi-prefix">
                            <span class="pf">R$</span>
                            <input type="text" class="gi-form-input" value="2,50" disabled>
                        </div>
                        <p class="gi-form-help">Seu custo</p>
                    </div>
                    <div class="gi-form-group">
                        <label class="gi-form-label">Venda<span class="gi-tag gi-tag-req">Obrig.</span></label>
                        <div class="gi-prefix">
                            <span class="pf">R$</span>
                            <input type="text" class="gi-form-input" value="5,00" disabled style="font-weight:600">
                        </div>
                        <p class="gi-form-help">Preço pro cliente</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Custo</b> = seu gasto (interno). <b>Venda</b> = preço cobrado do cliente como extra. Margem = (venda−custo)÷venda.</span>
        </div>

        <!-- ─── BLOCO 5: UNIDADE + VALOR ─── -->
        <h3>⑤ Unidade & Valor Unitário</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Unidade
            </div>
            <div class="gi-form-body">
                <div class="gi-form-row">
                    <div class="gi-form-group">
                        <label class="gi-form-label">Unidade<span class="gi-tag gi-tag-req">Obrig.</span></label>
                        <select class="gi-form-input" disabled>
                            <option selected>Gramas (g)</option>
                        </select>
                    </div>
                    <div class="gi-form-group">
                        <label class="gi-form-label">Valor Unitário<span class="gi-tag gi-tag-req">Obrig.</span></label>
                        <input type="text" class="gi-form-input" value="30" disabled>
                        <p class="gi-form-help">Qtd por unidade (ex: 30g)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Opções: g, kg, ml, L, un, fatia, colher, personalizada. <b>"Valor"</b> = quanto tem em cada 1 (ex: 30 = 30g por fatia).</span>
        </div>

        <!-- ─── BLOCO 6: MARGEM (preview auto) ─── -->
        <h3>⑥ Preview de Margem</h3>
        <div class="gi-form-block">
            <div class="gi-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Margem (automático)
            </div>
            <div class="gi-form-body">
                <div style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-radius:10px;padding:14px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:13px;font-weight:600;color:#065f46;display:flex;align-items:center;gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                        Margem
                    </span>
                    <span style="font-size:22px;font-weight:800;color:#059669;">50%</span>
                </div>
                <p class="gi-form-help" style="margin-top:8px;">Calculado automaticamente: (5,00 − 2,50) ÷ 5,00 = 50%</p>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Muda de cor: <b style="color:#16a34a">verde</b> (≥40%), <b style="color:#ca8a04">amarelo</b> (20-39%), <b style="color:#dc2626">vermelho</b> (&lt;20%). Atualiza em tempo real.</span>
        </div>
    </div>
</section>

<!-- ═══ CUSTO VS VENDA ═══ -->
<section id="pricing" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
            Custo vs Preço de Venda
        </h2>
        <p>Entenda como esses valores impactam a personalização dos seus produtos.</p>

        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px;margin:10px 0;">
            <code style="background:#dbeafe;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;">Margem = (Venda − Custo) ÷ Venda × 100</code>
            <div style="font-size:12px;color:#1e3a5f;line-height:1.6;margin-top:8px;">
                Cheddar: R$ 2,50 → R$ 5,00 = <b style="color:#2563eb">50%</b> ✅
            </div>
        </div>

        <div style="display:grid;gap:8px;margin:14px 0;">
            <div class="gi-margin gi-margin-good">
                <div style="font-size:20px;font-weight:800;color:#16a34a;">≥40%</div>
                <div style="font-size:11px;color:#15803d;">Saudável ✅</div>
            </div>
            <div class="gi-margin gi-margin-warn">
                <div style="font-size:20px;font-weight:800;color:#ca8a04;">20-39%</div>
                <div style="font-size:11px;color:#a16207;">Aceitável ⚠️</div>
            </div>
            <div class="gi-margin gi-margin-bad">
                <div style="font-size:20px;font-weight:800;color:#dc2626;">&lt;20%</div>
                <div style="font-size:11px;color:#b91c1c;">Baixa ❌</div>
            </div>
        </div>

        <h3>💰 Quando o cliente paga?</h3>
        <table class="gi-cmp">
            <thead><tr><th>Modo</th><th>Cobrança</th></tr></thead>
            <tbody>
                <tr><td><b>Extra</b></td><td>Cobra <b>venda</b> por extras acima do padrão</td></tr>
                <tr><td><b>Escolha</b></td><td>Cobra <b>venda</b> da opção selecionada</td></tr>
                <tr><td><b>Montagem</b></td><td>Grátis até o pool, extras cobram <b>venda</b></td></tr>
            </tbody>
        </table>

        <div class="gi-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span>Venda R$ 0 = ingrediente <b>grátis</b> como extra. Bom para itens inclusos (alface, pão).</span>
        </div>
    </div>
</section>

<!-- ═══ UNIDADES ═══ -->
<section id="units" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h8"/></svg></span>
            Unidades de Medida
        </h2>
        <p>Define <b>como</b> o ingrediente é medido e cobrado.</p>

        <table class="gi-cmp">
            <thead><tr><th>Unidade</th><th>Exemplo</th></tr></thead>
            <tbody>
                <tr><td><b>un</b></td><td>Fatia, ovo, hambúrguer</td></tr>
                <tr><td><b>g</b></td><td>Cheddar 30g, Bacon 20g</td></tr>
                <tr><td><b>kg</b></td><td>Carne moída, frango</td></tr>
                <tr><td><b>ml</b></td><td>Caldas, molhos</td></tr>
                <tr><td><b>L</b></td><td>Leite condensado</td></tr>
                <tr><td><b>pc</b></td><td>Fruta, pão</td></tr>
                <tr><td><b>custom</b></td><td>Fatia, colher, bola…</td></tr>
            </tbody>
        </table>

        <h3>🔢 "Valor por unidade"</h3>
        <p>Quanto do ingrediente cada "1 porção" tem:</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0;">
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:10px;text-align:center;">
                <div style="font-size:12px;font-weight:700;color:#0369a1;">🧀 Cheddar</div>
                <div style="font-size:10px;color:#0c4a6e;margin-top:4px;"><b>g</b> · Valor: <b>30</b></div>
                <div style="font-size:9px;color:#64748b;margin-top:2px;">= cada fatia = 30g</div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:10px;text-align:center;">
                <div style="font-size:12px;font-weight:700;color:#0369a1;">🥩 Blend</div>
                <div style="font-size:10px;color:#0c4a6e;margin-top:4px;"><b>g</b> · Valor: <b>90</b></div>
                <div style="font-size:9px;color:#64748b;margin-top:2px;">= cada disco = 90g</div>
            </div>
        </div>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span>Não encontrou? Escolha <b>"Personalizada"</b> e digite: fatia, colher, bola, porção…</span>
        </div>
    </div>
</section>

<!-- ═══ USO EM PRODUTOS ═══ -->
<section id="usage" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg></span>
            Uso em Produtos
        </h2>
        <p>Ingredientes cadastrados ficam disponíveis na aba <b>Personalização</b> dos produtos.</p>

        <ol class="gi-steps">
            <li><div>Vá em <b>Produtos → Criar</b></div></li>
            <li><div>Ative <b>"Personalização"</b></div></li>
            <li><div>Crie um grupo (ex: "Ingredientes")</div></li>
            <li><div>Escolha o modo (Extra, Escolha, Montagem)</div></li>
            <li><div>Adicione ingredientes com <b>Min/Max/Padrão</b></div></li>
        </ol>

        <table class="gi-cmp">
            <thead><tr><th>Campo</th><th>O que faz</th></tr></thead>
            <tbody>
                <tr><td><b>Min</b></td><td>Qty mínima (0=opcional)</td></tr>
                <tr><td><b>Max</b></td><td>Qty máxima permitida</td></tr>
                <tr><td><b>Padrão</b></td><td>Já vem incluso no produto</td></tr>
            </tbody>
        </table>

        <div class="gi-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span>Veja o <b>Guia de Produtos → Personalização</b> para detalhes dos 3 modos.</span>
        </div>
    </div>
</section>

<!-- ═══ DICAS ═══ -->
<section id="tips" class="gi-sec">
    <div class="gi-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></span>
            Dicas & Boas Práticas
        </h2>

        <div class="gi-gc gi-gc-good">
            <span class="g-icon">✅</span>
            <div><div class="g-title">Nomes claros</div><div class="g-desc">Use nomes que o cliente entende. Detalhes técnicos vão no nome interno.</div></div>
        </div>
        <div class="gi-gc gi-gc-good">
            <span class="g-icon">✅</span>
            <div><div class="g-title">Preço correto</div><div class="g-desc">Venda = preço do extra. R$ 0 = gratuito (itens inclusos).</div></div>
        </div>
        <div class="gi-gc gi-gc-good">
            <span class="g-icon">✅</span>
            <div><div class="g-title">Reutilize</div><div class="g-desc">Cheddar 1x pode ser usado em TODOS os produtos. Não duplique!</div></div>
        </div>
        <div class="gi-gc gi-gc-good">
            <span class="g-icon">✅</span>
            <div><div class="g-title">Custo realista</div><div class="g-desc">Cliente nunca vê. Usado só para relatórios de margem.</div></div>
        </div>
        <div class="gi-gc gi-gc-warn">
            <span class="g-icon">⚠️</span>
            <div><div class="g-title">Visibilidade</div><div class="g-desc">Desativar ingrediente padrão de produto pode ocultar o produto inteiro.</div></div>
        </div>
        <div class="gi-gc gi-gc-warn">
            <span class="g-icon">⚠️</span>
            <div><div class="g-title">Nomes duplicados</div><div class="g-desc">Sistema não permite nomes iguais. Use nome interno para variações.</div></div>
        </div>
    </div>
</section>

</div><!-- end products-list -->

<!-- FAB -->
<a href="/ingredients/create" class="gi-fab" style="position:fixed;bottom:80px;right:16px;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none;z-index:100;">
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
