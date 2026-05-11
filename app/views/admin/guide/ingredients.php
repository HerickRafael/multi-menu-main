<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Cadastro de Ingredientes';
$pageDescription = 'Aprenda a cadastrar ingredientes, definir custos, preços de venda e unidades';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>';
$breadcrumbs = [
    ['label' => 'Ingredientes', 'url' => base_url('admin/' . $slug . '/ingredients')],
    ['label' => 'Guia de Cadastro']
];
$actions = [
    ['label' => 'Criar Ingrediente', 'url' => base_url('admin/' . $slug . '/ingredients/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];

ob_start();
?>

<style>
/* Sidebar nav */
.gi-nav { position: sticky; top: 80px; }
.gi-nav a { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 10px; font-size: 13px; color: #64748b; text-decoration: none; transition: all .2s; border-left: 3px solid transparent; }
.gi-nav a:hover { color: #334155; background: #f1f5f9; }
.gi-nav a.active { color: #fff; background: var(--admin-primary-color); border-left-color: var(--admin-primary-color); font-weight: 600; }
.gi-nav a.active svg { color: #fff; }
.gi-nav .nav-group { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: 16px 12px 4px; }
.gi-cta { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--admin-primary-gradient, var(--admin-primary-color)) !important; background-color: var(--admin-primary-color) !important; border-radius: 12px; color: #fff !important; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 16px; transition: opacity .2s; }
.gi-cta:hover { opacity: .9; color: #fff !important; }
.gi-cta svg { color: #fff !important; }

/* Section / Card */
.gi-sec { scroll-margin-top: 100px; }
.gi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 24px; }
.gi-card h2 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
.gi-card h2 svg { color: var(--admin-primary-color); }
.gi-card h3 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 20px 0 8px; display: flex; align-items: center; gap: 8px; }
.gi-card p { font-size: 14px; color: #475569; line-height: 1.7; margin-bottom: 12px; }

/* Steps */
.gi-steps { list-style: none; padding: 0; margin: 16px 0; counter-reset: st; }
.gi-steps li { counter-increment: st; display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; line-height: 1.6; }
.gi-steps li:last-child { border-bottom: none; }
.gi-steps li::before { content: counter(st); flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary-gradient, var(--admin-primary-color)); color: #fff; font-size: 13px; font-weight: 700; margin-top: 1px; }

/* Tip / Warning */
.gi-tip { background: color-mix(in srgb, var(--admin-primary-color) 10%, white); border: 1px solid color-mix(in srgb, var(--admin-primary-color) 30%, white); border-radius: 12px; padding: 14px 16px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start; }
.gi-tip svg { flex-shrink: 0; color: var(--admin-primary-color); margin-top: 2px; }
.gi-tip .t { font-size: 13px; color: #334155; line-height: 1.6; }
.gi-warn { background: linear-gradient(135deg, #fef3c7, #fde68a33); border: 1px solid #fcd34d; border-radius: 12px; padding: 14px 16px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start; }
.gi-warn svg { flex-shrink: 0; color: #d97706; margin-top: 2px; }
.gi-warn .t { font-size: 13px; color: #92400e; line-height: 1.6; }

/* Annotation */
.gi-annot { display: flex; gap: 8px; margin-top: 10px; padding: 10px 14px; background: color-mix(in srgb, var(--admin-primary-color) 8%, white); border: 1px solid color-mix(in srgb, var(--admin-primary-color) 25%, white); border-radius: 10px; font-size: 12px; color: #334155; line-height: 1.6; }
.gi-annot svg { flex-shrink: 0; color: var(--admin-primary-color); margin-top: 1px; }

/* Real Form Replicas — Fieldset */
.gi-fieldset { border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); margin: 16px 0; }
.gi-legend { display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; background: #f8fafc; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #475569; margin-bottom: 14px; }
.gi-legend svg { width: 16px; height: 16px; }

/* Input replicas */
.gi-input { width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; padding: 8px 12px; font-size: 14px; color: #0f172a; box-sizing: border-box; }
.gi-input:disabled { background: #f8fafc; color: #94a3b8; }
.gi-input::placeholder { color: #94a3b8; }
.gi-select { width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; padding: 8px 12px; font-size: 14px; color: #1e293b; }
.gi-field { margin-bottom: 12px; }
.gi-field:last-child { margin-bottom: 0; }
.gi-label { display: block; font-size: 14px; color: #475569; margin-bottom: 4px; }
.gi-help { font-size: 12px; color: #94a3b8; margin-top: 4px; }
.gi-row { display: grid; gap: 12px; }
.gi-row-2 { grid-template-columns: 1fr 1fr; }

/* Prefix input */
.gi-prefix-input { display: flex; border-radius: 12px; border: 1px solid #cbd5e1; overflow: hidden; background: #fff; }
.gi-prefix-input span { padding: 8px 12px; background: #f8fafc; color: #64748b; font-weight: 500; font-size: 14px; border-right: 1px solid #e2e8f0; white-space: nowrap; }
.gi-prefix-input input { border: none; padding: 8px 12px; font-size: 14px; flex: 1; outline: none; width: 100%; background: transparent; }

/* Image upload */
.gi-upload { border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; padding: 40px 20px; text-align: center; color: #94a3b8; }

/* Tag */
.gi-tag { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 6px; }
.gi-tag-r { background: #fee2e2; color: #dc2626; }
.gi-tag-o { background: #f0fdf4; color: #16a34a; }

/* Margin preview */
.gi-margin { border-radius: 12px; padding: 16px; text-align: center; margin: 16px 0; }
.gi-margin-good { background: #f0fdf4; border: 1.5px solid #86efac; }
.gi-margin-warn { background: #fef9c3; border: 1.5px solid #fde047; }
.gi-margin-bad { background: #fef2f2; border: 1.5px solid #fca5a5; }

/* Compare table */
.gi-cmp { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; margin: 16px 0; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
.gi-cmp th { background: #f8fafc; padding: 10px 14px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
.gi-cmp td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.gi-cmp tbody tr:last-child td { border-bottom: none; }

/* Flow */
.gi-flow { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 16px 0; }
.gi-flow .fs { padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: #475569; }
.gi-flow .fa { color: #cbd5e1; font-size: 16px; }
.gi-flow .fs.active { background: var(--admin-primary-gradient, var(--admin-primary-color)); color: #fff; }
</style>

<div class="mx-auto max-w-7xl p-4">
<?php include __DIR__ . '/../components/page-header.php'; ?>

<div style="display: grid; grid-template-columns: 240px 1fr; gap: 32px; align-items: start;">

<!-- Sidebar -->
<nav class="gi-nav" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:16px 10px;">
    <div class="nav-group">Guia</div>
    <a href="#overview" class="active" data-section="overview">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        Visão Geral
    </a>
    <a href="#form" data-section="form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
        Formulário
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#pricing" data-section="pricing">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Custo vs Venda
    </a>
    <a href="#units" data-section="units">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h8"/></svg>
        Unidades
    </a>
    <a href="#usage" data-section="usage">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg>
        Uso em Produtos
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="gi-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Criar Ingrediente
    </a>
</nav>

<!-- Main -->
<div>

<!-- =============== OVERVIEW =============== -->
<section id="overview" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            O que é um Ingrediente?
        </h2>
        <p>Ingredientes são os <b>blocos fundamentais</b> do seu cardápio. Cada ingrediente que você cadastra pode ser adicionado como personalização em produtos simples ou como topping em montagens (pool).</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin: 20px 0;">
            <div style="background: color-mix(in srgb, var(--admin-primary-color) 10%, white); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🧀</div>
                <div style="font-size: 14px; font-weight: 700; color: #1e293b;">Personalização</div>
                <div style="font-size: 12px; color: #475569; margin-top: 4px;">+/− no burger, pizza</div>
            </div>
            <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d044); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🍓</div>
                <div style="font-size: 14px; font-weight: 700; color: #1e293b;">Topping (Pool)</div>
                <div style="font-size: 12px; color: #475569; margin-top: 4px;">Monte seu açaí, poke</div>
            </div>
            <div style="background: linear-gradient(135deg, #fef3c7, #fde68a44); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🥗</div>
                <div style="font-size: 14px; font-weight: 700; color: #1e293b;">Escolha</div>
                <div style="font-size: 12px; color: #475569; margin-top: 4px;">Tipo de queijo, molho</div>
            </div>
        </div>

        <div class="gi-flow">
            <span class="fs active">1. Ingrediente</span><span class="fa">→</span>
            <span class="fs">2. Produto Simples</span><span class="fa">→</span>
            <span class="fs">3. Personalização</span><span class="fa">→</span>
            <span class="fs">4. Combo</span>
        </div>

        <div class="gi-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Ingredientes primeiro!</b> Cadastre todos os ingredientes antes de criar produtos. Produtos usam ingredientes na personalização.</span>
        </div>
    </div>
</section>

<!-- =============== FORMULÁRIO =============== -->
<section id="form" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
            Formulário — Bloco a Bloco
        </h2>
        <p>Veja cada campo do formulário exatamente como aparece no sistema.</p>

        <div class="gi-flow">
            <span class="fs active">1. Dados</span><span class="fa">→</span>
            <span class="fs">2. Custo & Venda</span><span class="fa">→</span>
            <span class="fs">3. Unidade</span><span class="fa">→</span>
            <span class="fs">4. Imagem</span><span class="fa">→</span>
            <span class="fs">5. Salvar</span>
        </div>

        <!-- ─── BLOCO 1: Dados do Ingrediente ─── -->
        <h3>① Dados do Ingrediente</h3>
        <div class="gi-fieldset">
            <div class="gi-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 8h12M6 12h8M6 16h4" stroke-linecap="round"/></svg>
                Dados do ingrediente
            </div>
            <div class="gi-field">
                <span class="gi-label">Nome<span class="gi-tag gi-tag-r">Obrigatório</span></span>
                <input type="text" class="gi-input" value="Queijo Cheddar" disabled>
                <p class="gi-help">Nome exibido ao cliente na personalização.</p>
            </div>
            <div class="gi-field">
                <span class="gi-label">Nomenclatura interna<span class="gi-tag gi-tag-o">Opcional</span></span>
                <input type="text" class="gi-input" value="Cheddar Polenghi 150g" disabled placeholder="Ex.: Big Fries, Porção G, 180g...">
                <p class="gi-help">Complemento visível apenas no painel admin. Ex.: "Batata frita (Big Fries)"</p>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Bloco real "Dados do ingrediente".</b> O <b>Nome</b> aparece para o cliente. A <b>Nomenclatura interna</b> é só para você organizar (tipo, marca, tamanho).</span>
        </div>

        <!-- ─── BLOCO 2: Custo & Preço de Venda ─── -->
        <h3>② Custo & Preço de Venda</h3>
        <div class="gi-fieldset">
            <div class="gi-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round"/></svg>
                Preços
            </div>
            <div class="gi-row gi-row-2">
                <div class="gi-field">
                    <span class="gi-label">Custo<span class="gi-tag gi-tag-r">Obrigatório</span></span>
                    <div class="gi-prefix-input">
                        <span>R$</span>
                        <input type="text" value="2,50" disabled>
                    </div>
                    <p class="gi-help">Quanto VOCÊ paga por unidade.</p>
                </div>
                <div class="gi-field">
                    <span class="gi-label">Valor de venda<span class="gi-tag gi-tag-r">Obrigatório</span></span>
                    <div class="gi-prefix-input">
                        <span>R$</span>
                        <input type="text" value="5,00" disabled style="font-weight:600;">
                    </div>
                    <p class="gi-help">Quanto o CLIENTE paga como extra.</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Custo</b> = seu gasto. <b>Venda</b> = preço cobrado do cliente como extra. A <b>margem</b> é calculada automaticamente: (venda − custo) ÷ venda.</span>
        </div>

        <!-- Margin preview -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 16px 0;">
            <div class="gi-margin gi-margin-good">
                <div style="font-size: 24px; font-weight: 800; color: #16a34a;">50%</div>
                <div style="font-size: 12px; color: #15803d; margin-top: 2px;">Margem saudável ✅</div>
                <div style="font-size: 11px; color: #166534; margin-top: 6px;">Custo R$ 2,50 → Venda R$ 5,00</div>
            </div>
            <div class="gi-margin gi-margin-warn">
                <div style="font-size: 24px; font-weight: 800; color: #ca8a04;">33%</div>
                <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Margem aceitável ⚠️</div>
                <div style="font-size: 11px; color: #854d0e; margin-top: 6px;">Custo R$ 2,00 → Venda R$ 3,00</div>
            </div>
            <div class="gi-margin gi-margin-bad">
                <div style="font-size: 24px; font-weight: 800; color: #dc2626;">15%</div>
                <div style="font-size: 12px; color: #b91c1c; margin-top: 2px;">Margem baixa ❌</div>
                <div style="font-size: 11px; color: #991b1b; margin-top: 6px;">Custo R$ 8,50 → Venda R$ 10,00</div>
            </div>
        </div>

        <!-- ─── BLOCO 3: Unidade de Medida ─── -->
        <h3>③ Unidade de Medida</h3>
        <div class="gi-fieldset">
            <div class="gi-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h18M3 12h12M3 18h8" stroke-linecap="round"/></svg>
                Unidade
            </div>
            <div class="gi-row gi-row-2">
                <div class="gi-field">
                    <span class="gi-label">Unidade de medida<span class="gi-tag gi-tag-r">Obrigatório</span></span>
                    <select class="gi-select" disabled>
                        <option>Selecione</option>
                        <option>Unidade (un)</option>
                        <option>Quilo (kg)</option>
                        <option selected>Grama (g)</option>
                        <option>Miligrama (mg)</option>
                        <option>Litro (L)</option>
                        <option>Mililitro (mL)</option>
                        <option>Peça (pc)</option>
                        <option>Outra unidade…</option>
                    </select>
                    <p class="gi-help">Define como o ingrediente é medido.</p>
                </div>
                <div class="gi-field">
                    <span class="gi-label">Valor por <b>grama</b><span class="gi-tag gi-tag-r">Obrigatório</span></span>
                    <input type="text" class="gi-input" value="30" disabled>
                    <p class="gi-help">Ex.: "30" = cada unidade tem 30g.</p>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>A label "Valor por __"</b> muda conforme a unidade selecionada. Ex: Grama → "Valor por grama". Se escolher "Outra unidade", aparece um campo extra para digitar.</span>
        </div>

        <!-- ─── BLOCO 4: Imagem ─── -->
        <h3>④ Imagem</h3>
        <div class="gi-fieldset">
            <div class="gi-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Imagem (opcional)
            </div>
            <div class="gi-row gi-row-2" style="align-items:start;">
                <div class="gi-field">
                    <span class="gi-label">Upload (jpg/png/webp)</span>
                    <div style="display:inline-flex;align-items:center;gap:8px;border:1px solid #cbd5e1;background:#f8fafc;border-radius:12px;padding:8px 12px;font-size:14px;color:#475569;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                        Selecionar arquivo
                    </div>
                    <p class="gi-help">Recomendado: 800×800px quadrado. Máx. 5 MB.</p>
                </div>
                <div style="text-align:center;">
                    <span style="font-size:12px;color:#94a3b8;">Pré-visualização</span>
                    <div style="width:80px;height:80px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:4px auto 0;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="gi-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Imagem opcional.</b> Aparece na personalização do produto. Formato quadrado funciona melhor.</span>
        </div>
    </div>
</section>

<!-- =============== CUSTO VS VENDA =============== -->
<section id="pricing" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Custo vs Preço de Venda
        </h2>
        <p>Entenda como esses dois valores impactam a personalização dos seus produtos.</p>

        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:20px;margin:14px 0;">
            <code style="background:#dbeafe;padding:4px 12px;border-radius:6px;font-size:13px;font-weight:600;">Margem (%) = (Venda − Custo) ÷ Venda × 100</code>
            <div style="font-size:14px;color:#1e3a5f;line-height:1.7;margin-top:12px;">
                <b>Exemplo:</b> Cheddar custa R$ 2,50, vende a R$ 5,00.<br>
                Margem = (5,00 − 2,50) ÷ 5,00 = <b style="color:#2563eb">50%</b> ✅
            </div>
        </div>

        <h3>💰 Quando o cliente é cobrado?</h3>
        <table class="gi-cmp">
            <thead><tr><th>Modo</th><th>Cobrança</th><th>Exemplo</th></tr></thead>
            <tbody>
                <tr><td><b>Extra/Qty</b></td><td>Cobra <b>venda</b> × extras acima do padrão</td><td>Padrão=1, pede 3 → 2 × R$ 5 = R$ 10</td></tr>
                <tr><td><b>Escolha</b></td><td>Cobra <b>venda</b> da opção selecionada</td><td>Cheddar R$ 5, Gorgonzola R$ 8</td></tr>
                <tr><td><b>Montagem</b></td><td>Grátis até o pool, extras cobram <b>venda</b></td><td>Pool=5, pede 7 → 2 extras</td></tr>
            </tbody>
        </table>

        <div class="gi-warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span class="t"><b>Atenção:</b> Se o preço de venda for R$ 0,00, o ingrediente será <b>grátis</b> como extra. Ótimo para itens padrão (pão, alface).</span>
        </div>

        <div class="gi-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>O custo é interno</b> — usado só em relatórios e análise de margem. O cliente nunca vê o custo.</span>
        </div>
    </div>
</section>

<!-- =============== UNIDADES =============== -->
<section id="units" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h8"/></svg>
            Unidades de Medida
        </h2>
        <p>Cada ingrediente tem uma unidade que define <b>como</b> ele é medido e cobrado.</p>

        <table class="gi-cmp">
            <thead><tr><th>Unidade</th><th>Sigla</th><th>Exemplo de uso</th></tr></thead>
            <tbody>
                <tr><td>Unidade</td><td><code>un</code></td><td>Fatia de queijo, ovo, hambúrguer</td></tr>
                <tr><td>Grama</td><td><code>g</code></td><td>Cheddar 30g, Bacon 20g</td></tr>
                <tr><td>Quilo</td><td><code>kg</code></td><td>Carne moída, frango</td></tr>
                <tr><td>Mililitro</td><td><code>ml</code></td><td>Caldas, molhos especiais</td></tr>
                <tr><td>Litro</td><td><code>L</code></td><td>Leite condensado, creme</td></tr>
                <tr><td>Peça</td><td><code>pc</code></td><td>Fruta inteira, pão</td></tr>
                <tr><td>Personalizada</td><td>qualquer</td><td>Fatia, colher, porção, bola</td></tr>
            </tbody>
        </table>

        <h3>🔢 O que é "Valor por unidade"?</h3>
        <p>Define <b>quanto</b> do ingrediente cada "1 unidade" equivale. Exemplos:</p>
        <div style="display:grid;grid-template-columns: repeat(3,1fr);gap:12px;margin:14px 0;">
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;text-align:center;">
                <div style="font-size:13px;font-weight:700;color:#0369a1;">🧀 Cheddar</div>
                <div style="font-size:11px;color:#0c4a6e;margin-top:4px;">Unidade: <b>g</b></div>
                <div style="font-size:11px;color:#0c4a6e;">Valor: <b>30</b></div>
                <div style="font-size:10px;color:#64748b;margin-top:6px;">= cada fatia = 30g</div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;text-align:center;">
                <div style="font-size:13px;font-weight:700;color:#0369a1;">🥩 Blend</div>
                <div style="font-size:11px;color:#0c4a6e;margin-top:4px;">Unidade: <b>g</b></div>
                <div style="font-size:11px;color:#0c4a6e;">Valor: <b>90</b></div>
                <div style="font-size:10px;color:#64748b;margin-top:6px;">= cada disco = 90g</div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;text-align:center;">
                <div style="font-size:13px;font-weight:700;color:#0369a1;">🥛 Leite Ninho</div>
                <div style="font-size:11px;color:#0c4a6e;margin-top:4px;">Unidade: <b>colher</b> (custom)</div>
                <div style="font-size:11px;color:#0c4a6e;">Valor: <b>1</b></div>
                <div style="font-size:10px;color:#64748b;margin-top:6px;">= cada porção = 1 colher</div>
            </div>
        </div>

        <div class="gi-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Não encontrou a unidade?</b> Escolha "Outra unidade" e digite: <b>fatia</b>, <b>colher</b>, <b>bola</b>, <b>porção</b>…</span>
        </div>
    </div>
</section>

<!-- =============== USO EM PRODUTOS =============== -->
<section id="usage" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg>
            Uso em Produtos
        </h2>
        <p>Depois de cadastrar ingredientes, eles ficam disponíveis na aba <b>Personalização</b> do formulário de produto.</p>

        <ol class="gi-steps">
            <li><div>Vá em <b>Produtos → Criar Produto</b></div></li>
            <li><div>Ative toggle <b>"Permitir personalização"</b></div></li>
            <li><div>Crie um grupo (ex: "Ingredientes do Burger")</div></li>
            <li><div>Selecione o modo (Extra, Escolha ou Montagem)</div></li>
            <li><div>Adicione ingredientes ao grupo com <b>Min/Max/Padrão</b></div></li>
        </ol>

        <h3>⚙️ Parâmetros por ingrediente no produto</h3>
        <table class="gi-cmp">
            <thead><tr><th>Campo</th><th>O que faz</th><th>Exemplo</th></tr></thead>
            <tbody>
                <tr><td><b>Min</b></td><td>Quantidade mínima permitida</td><td>0 = opcional, 1 = fixo</td></tr>
                <tr><td><b>Max</b></td><td>Quantidade máxima</td><td>5 = pode pedir até 5</td></tr>
                <tr><td><b>Padrão</b></td><td>Marcado = já vem incluso</td><td>Cheddar: padrão=Sim</td></tr>
                <tr><td><b>Qty</b></td><td>Quantidade padrão</td><td>1 = cliente recebe 1 de graça</td></tr>
            </tbody>
        </table>

        <div class="gi-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t">Veja o <a href="<?= e(base_url('admin/' . $slug . '/guide/products')) ?>#modes" style="color:var(--admin-primary-color);font-weight:600;text-decoration:underline;">Guia de Produtos — Personalização</a> para detalhes completos dos 3 modos.</span>
        </div>
    </div>
</section>

<!-- =============== DICAS =============== -->
<section id="tips" class="gi-sec">
    <div class="gi-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Dicas & Boas Práticas
        </h2>

        <div style="display: grid; gap: 12px;">
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Nomes claros</div><div style="font-size:13px;color:#15803d;">Use nomes que o cliente entende: "Cheddar", "Bacon", "Molho Especial". Detalhes técnicos vão no nome interno.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Preço de venda correto</div><div style="font-size:13px;color:#15803d;">Esse é o preço que aparece quando o cliente adiciona extra. <b>R$ 0 = gratuito</b> (ideal para itens inclusos como alface, tomate).</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Custo realista</div><div style="font-size:13px;color:#15803d;">O custo não aparece pro cliente. É usado para <b>relatórios de margem</b> e análise financeira.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Reutilize ingredientes</div><div style="font-size:13px;color:#15803d;">Cheddar cadastrado 1 vez pode ser usado em <b>todos</b> os produtos: burger, hot-dog, batata. Não duplique!</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Visibilidade</div><div style="font-size:13px;color:#c2410c;">Se o ingrediente é <b>padrão de um produto</b> e você oculta/desativa, o <b>produto inteiro</b> fica oculto. Se é topping de montagem, só o ingrediente some.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Nomes duplicados</div><div style="font-size:13px;color:#c2410c;">O sistema <b>não permite</b> dois ingredientes com o mesmo nome na mesma empresa. Use o nome interno para diferenciar variações.</div></div>
            </div>
        </div>
    </div>
</section>

</div><!-- end main -->
</div><!-- end grid -->
<div style="height:80px;"></div>
</div><!-- end container -->

<script>
(function(){
    var secs=document.querySelectorAll('.gi-sec'),links=document.querySelectorAll('.gi-nav a[data-section]');
    function up(){
        var y=window.scrollY+150,c='';
        var atBottom=(window.innerHeight+window.scrollY)>=(document.documentElement.scrollHeight-80);
        if(atBottom&&secs.length){c=secs[secs.length-1].id}else{secs.forEach(function(s){if(s.offsetTop<=y)c=s.id})}
        links.forEach(function(a){a.classList.toggle('active',a.dataset.section===c)});
    }
    window.addEventListener('scroll',up);up();
    links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.dataset.section);if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
