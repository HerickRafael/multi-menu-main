<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Cadastro de Produtos';
$pageDescription = 'Aprenda a cadastrar produtos, configurar personalizações e montar combos';
$pageIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>';
$breadcrumbs = [
    ['label' => 'Produtos', 'url' => base_url('admin/' . $slug . '/products')],
    ['label' => 'Guia de Cadastro']
];
$actions = [
    ['label' => 'Criar Produto', 'url' => base_url('admin/' . $slug . '/products/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
];

ob_start();
?>

<style>
/* Sidebar nav — uses system colors */
.gd-nav { position: sticky; top: 80px; }
.gd-nav a { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 10px; font-size: 13px; color: #64748b; text-decoration: none; transition: all .2s; border-left: 3px solid transparent; }
.gd-nav a:hover { color: #334155; background: #f1f5f9; }
.gd-nav a.active { color: #fff; background: var(--admin-primary-color); border-left-color: var(--admin-primary-color); font-weight: 600; }
.gd-nav a.active svg { color: #fff; }
.gd-nav .nav-group { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: 16px 12px 4px; }
.gd-cta { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--admin-primary-gradient, var(--admin-primary-color)) !important; background-color: var(--admin-primary-color) !important; border-radius: 12px; color: #fff !important; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 16px; transition: opacity .2s; }
.gd-cta:hover { opacity: .9; color: #fff !important; }
.gd-cta svg { color: #fff !important; }

/* Section / Card */
.gd-sec { scroll-margin-top: 100px; }
.gd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 24px; }
.gd-card h2 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
.gd-card h2 svg { color: var(--admin-primary-color); }
.gd-card h3 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 20px 0 8px; display: flex; align-items: center; gap: 8px; }
.gd-card p { font-size: 14px; color: #475569; line-height: 1.7; margin-bottom: 12px; }

/* Steps — uses system colors */
.gd-steps { list-style: none; padding: 0; margin: 16px 0; counter-reset: st; }
.gd-steps li { counter-increment: st; display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; line-height: 1.6; }
.gd-steps li:last-child { border-bottom: none; }
.gd-steps li::before { content: counter(st); flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary-gradient); color: #fff; font-size: 13px; font-weight: 700; margin-top: 1px; }

/* Tip / Warning — uses system colors */
.gd-tip { background: color-mix(in srgb, var(--admin-primary-color) 10%, white); border: 1px solid color-mix(in srgb, var(--admin-primary-color) 30%, white); border-radius: 12px; padding: 14px 16px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start; }
.gd-tip svg { flex-shrink: 0; color: var(--admin-primary-color); margin-top: 2px; }
.gd-tip .t { font-size: 13px; color: #334155; line-height: 1.6; }
.gd-warn { background: linear-gradient(135deg, #fef3c7, #fde68a33); border: 1px solid #fcd34d; border-radius: 12px; padding: 14px 16px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start; }
.gd-warn svg { flex-shrink: 0; color: #d97706; margin-top: 2px; }
.gd-warn .t { font-size: 13px; color: #92400e; line-height: 1.6; }

/* Annotation — points out system block */
.gd-annot { display: flex; gap: 8px; margin-top: 10px; padding: 10px 14px; background: color-mix(in srgb, var(--admin-primary-color) 8%, white); border: 1px solid color-mix(in srgb, var(--admin-primary-color) 25%, white); border-radius: 10px; font-size: 12px; color: #334155; line-height: 1.6; }
.gd-annot svg { flex-shrink: 0; color: var(--admin-primary-color); margin-top: 1px; }

/* ── Real Form Replicas ── */
/* Fieldset replica — matches real admin form */
.gd-fieldset { border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); margin: 16px 0; }
.gd-legend { display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; background: #f8fafc; padding: 6px 12px; font-size: 14px; font-weight: 500; color: #475569; margin-bottom: 14px; }
.gd-legend svg { width: 16px; height: 16px; }

/* Input replica — matches real rounded-xl inputs */
.gd-input { width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; padding: 8px 12px; font-size: 14px; color: #0f172a; box-sizing: border-box; }
.gd-input:disabled { background: #f8fafc; color: #94a3b8; }
.gd-input::placeholder { color: #94a3b8; }
.gd-select { width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; padding: 8px 12px; font-size: 14px; color: #1e293b; }
.gd-field { margin-bottom: 12px; }
.gd-field:last-child { margin-bottom: 0; }
.gd-label { display: block; font-size: 14px; color: #475569; margin-bottom: 4px; }
.gd-help { font-size: 12px; color: #94a3b8; margin-top: 4px; }
.gd-row { display: grid; gap: 12px; }
.gd-row-2 { grid-template-columns: 1fr 1fr; }

/* Prefix input replica — matches real admin */
.gd-prefix-input { display: flex; border-radius: 12px; border: 1px solid #cbd5e1; overflow: hidden; background: #fff; }
.gd-prefix-input span { padding: 8px 12px; background: #f8fafc; color: #64748b; font-weight: 500; font-size: 14px; border-right: 1px solid #e2e8f0; white-space: nowrap; }
.gd-prefix-input input { border: none; padding: 8px 12px; font-size: 14px; flex: 1; outline: none; width: 100%; background: transparent; }

/* Image upload replica */
.gd-upload { border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; padding: 40px 20px; text-align: center; color: #94a3b8; }
.gd-upload svg { margin: 0 auto 8px; }

/* Toggle replica — matches real admin toggle */
.gd-toggle-row { display: flex; align-items: center; gap: 12px; }
.gd-toggle-track { width: 44px; height: 24px; border-radius: 12px; position: relative; flex-shrink: 0; }
.gd-toggle-track.on { background: var(--admin-primary-color); }
.gd-toggle-track.off { background: #cbd5e1; }
.gd-toggle-thumb { position: absolute; left: 2px; top: 2px; width: 20px; height: 20px; background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.gd-toggle-track.on .gd-toggle-thumb { left: auto; right: 2px; }
.gd-toggle-text { font-size: 14px; color: #374151; }

/* Tag */
.gd-tag { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 6px; }
.gd-tag-r { background: #fee2e2; color: #dc2626; }
.gd-tag-o { background: #f0fdf4; color: #16a34a; }
.gd-tag-a { background: #f0f9ff; color: #0284c7; }

/* Group card replica — matches real admin .group-card */
.gd-group { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 12px; }
.gd-group-head { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.gd-group-head input { flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
.gd-group-body { padding: 14px; }
.gd-group-item { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px; background: #f9fafb; border-radius: 8px; margin-bottom: 8px; }
.gd-group-item:last-child { margin-bottom: 0; }
.gd-gi-field { flex: 1; }
.gd-gi-field label { display: block; font-size: 11px; color: #6b7280; margin-bottom: 4px; }
.gd-gi-field select, .gd-gi-field input { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; text-align: center; box-sizing: border-box; }
.gd-group-foot { padding: 12px; border-top: 1px solid #e2e8f0; }
.gd-btn-add { width: 100%; padding: 12px; border: 2px dashed #cbd5e1; background: #fff; border-radius: 12px; color: #6b7280; font-size: 13px; font-weight: 500; text-align: center; }
.gd-btn-rm { width: 32px; height: 32px; border: none; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; }
.gd-btn-def { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background: #fff; color: #6b7280; }
.gd-btn-def.active { background: var(--admin-primary-color); border-color: var(--admin-primary-color); color: #fff; }

/* Choice/Pool settings — matches real */
.gd-choice-set { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px; margin: 8px 0; }
.gd-pool-set { background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px; margin: 8px 0; }

/* Promo section — matches real promo yellow gradient */
.gd-promo-fieldset { border-color: #fbbf24; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
.gd-promo-fieldset .gd-legend { background: rgba(255,255,255,.6); color: #92400e; }
.gd-promo-fieldset .gd-label { color: #78350f; }

/* Compare table */
.gd-cmp { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; margin: 16px 0; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
.gd-cmp th { background: #f8fafc; padding: 10px 14px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
.gd-cmp td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.gd-cmp tbody tr:last-child td { border-bottom: none; }

/* Mode cards */
.gd-mode { border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 16px; position: relative; overflow: hidden; }
.gd-mbadge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 10px; }
.gd-mode .mt { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.gd-mode .md { font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.6; }

/* Flow */
.gd-flow { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 16px 0; }
.gd-flow .fs { padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: #475569; }
.gd-flow .fa { color: #cbd5e1; font-size: 16px; }
.gd-flow .fs.active { background: var(--admin-primary-gradient, var(--admin-primary-color)); color: #fff; }
</style>

<div class="mx-auto max-w-7xl p-4">
<?php include __DIR__ . '/../components/page-header.php'; ?>

<div style="display: grid; grid-template-columns: 240px 1fr; gap: 32px; align-items: start;">

<!-- Sidebar -->
<nav class="gd-nav" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:16px 10px;">
    <div class="nav-group">Início</div>
    <a href="#overview" class="active" data-section="overview">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        Visão Geral
    </a>
    <a href="#form" data-section="form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
        Formulário
    </a>
    <div class="nav-group">Personalização</div>
    <a href="#modes" data-section="modes">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg>
        Modos de Seleção
    </a>
    <a href="#mode-extra" data-section="mode-extra">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Extra/Quantidade
    </a>
    <a href="#mode-choice" data-section="mode-choice">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="9"/></svg>
        Escolha
    </a>
    <a href="#mode-pool" data-section="mode-pool">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
        Montagem (Pool)
    </a>
    <div class="nav-group">Avançado</div>
    <a href="#combos" data-section="combos">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v3"/></svg>
        Combos
    </a>
    <a href="#pricing" data-section="pricing">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Modos de Preço
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="gd-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Criar Produto
    </a>
</nav>

<!-- Main -->
<div>

<!-- =============== OVERVIEW =============== -->
<section id="overview" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            Visão Geral
        </h2>
        <p>O Multi Menu tem dois tipos de produto. Veja abaixo como eles aparecem no formulário real do sistema:</p>

        <!-- RÉPLICA REAL: Type selector como fieldset do desktop form -->
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 12h10M12 7v10" stroke-linecap="round"/></svg>
                Tipo & Preço
            </div>
            <div class="gd-field">
                <span class="gd-label">Tipo</span>
                <select class="gd-select" disabled>
                    <option selected>Simples</option>
                    <option>Combo</option>
                </select>
                <p class="gd-help">Combos usam Grupos. Simples têm Personalização.</p>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Este é o <b>bloco real</b> "Tipo & Preço" do formulário. O select dropdown define se é <b>Simples</b> ou <b>Combo</b>.</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 20px 0;">
            <div style="background: color-mix(in srgb, var(--admin-primary-color) 10%, white); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🍔</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Produto Simples</div>
                <div style="font-size: 13px; color: #475569; line-height: 1.5;">Item individual com <b>personalização</b> de ingredientes.</div>
                <div style="margin-top: 10px; font-size: 12px; color: var(--admin-primary-color); font-weight: 600;">Ex: Hambúrguer, Açaí, Bebida</div>
            </div>
            <div style="background: linear-gradient(135deg, #fef3c7, #fde68a44); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">📦</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Combo</div>
                <div style="font-size: 13px; color: #475569; line-height: 1.5;">Agrupa produtos simples em <b>grupos de opções</b>.</div>
                <div style="margin-top: 10px; font-size: 12px; color: #d97706; font-weight: 600;">Ex: Burger + Batata + Bebida</div>
            </div>
        </div>

        <div class="gd-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Dica:</b> Cadastre ingredientes → produtos simples → combos. Combos usam produtos já existentes.</span>
        </div>
    </div>
</section>

<!-- =============== FORMULÁRIO =============== -->
<section id="form" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
            Formulário — Bloco a Bloco
        </h2>
        <p>Cada seção do formulário de criação exatamente como aparece no sistema. Todos os campos usam os estilos reais da aplicação.</p>

        <div class="gd-flow">
            <span class="fs active">1. Dados Básicos</span><span class="fa">→</span>
            <span class="fs">2. Tipo & Preço</span><span class="fa">→</span>
            <span class="fs">3. Descrição</span><span class="fa">→</span>
            <span class="fs">4. Imagem</span><span class="fa">→</span>
            <span class="fs">5. Personalização</span><span class="fa">→</span>
            <span class="fs">6. Publicar</span>
        </div>

        <!-- ─── BLOCO 1: Dados Básicos ─── -->
        <h3>① Dados Básicos</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h14M5 12h10M5 17h6" stroke-linecap="round"/></svg>
                Dados básicos
            </div>
            <div class="gd-field">
                <span class="gd-label">Categoria<span class="gd-tag gd-tag-o">Opcional</span></span>
                <select class="gd-select" disabled>
                    <option>— sem categoria —</option>
                    <option selected>Lanches</option>
                    <option>Bebidas</option>
                </select>
                <p class="gd-help">Usado para agrupar o cardápio.</p>
            </div>
            <div class="gd-row gd-row-2" style="margin-top:12px;">
                <div class="gd-field">
                    <span class="gd-label">Nome<span class="gd-tag gd-tag-r">Obrigatório</span></span>
                    <input type="text" class="gd-input" value="X-Burger Especial" disabled>
                </div>
                <div class="gd-field">
                    <span class="gd-label">SKU<span class="gd-tag gd-tag-a">Automático</span></span>
                    <input type="text" class="gd-input" value="001" disabled style="background:#f8fafc;">
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Este é o bloco real "Dados Básicos".</b> Categoria agrupa no cardápio. Nome é obrigatório. SKU é gerado automaticamente e não editável.</span>
        </div>

        <!-- ─── BLOCO 2: Tipo & Preço ─── -->
        <h3>② Tipo & Preço</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 12h10M12 7v10" stroke-linecap="round"/></svg>
                Tipo & Preço
            </div>
            <div class="gd-row gd-row-2">
                <div class="gd-field">
                    <span class="gd-label">Tipo<span class="gd-tag gd-tag-r">Obrigatório</span></span>
                    <select class="gd-select" disabled>
                        <option selected>Simples</option>
                        <option>Combo</option>
                    </select>
                    <p class="gd-help">Simples = personalização. Combo = grupos.</p>
                </div>
                <div class="gd-field">
                    <span class="gd-label">Modo de preço</span>
                    <select class="gd-select" disabled>
                        <option selected>Fixo (preço base)</option>
                        <option>Somar itens do grupo</option>
                    </select>
                    <p class="gd-help">Em "Somar", total = base + deltas.</p>
                </div>
            </div>
            <div class="gd-row gd-row-2" style="margin-top:12px;">
                <div class="gd-field">
                    <span class="gd-label">Preço base (R$)<span class="gd-tag gd-tag-r">Obrigatório</span></span>
                    <div class="gd-prefix-input">
                        <span>R$</span>
                        <input type="text" value="29,90" disabled style="font-weight:600;">
                    </div>
                </div>
                <div class="gd-field">
                    <span class="gd-label">Ordem de exibição<span class="gd-tag gd-tag-o">Opcional</span></span>
                    <input type="number" class="gd-input" value="0" disabled>
                    <p class="gd-help">Menor = aparece primeiro.</p>
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Bloco real "Tipo & Preço".</b> Define tipo, modo de preço e valor base. O campo R$ usa prefixo igual ao sistema real.</span>
        </div>

        <!-- ─── BLOCO 3: Promoção ─── -->
        <h3>③ Promoção</h3>
        <div class="gd-fieldset gd-promo-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Preço promocional (opcional)
            </div>
            <div class="gd-field">
                <span class="gd-label">Preço promocional</span>
                <div class="gd-prefix-input">
                    <span>R$</span>
                    <input type="text" placeholder="Ex: 24,90" disabled>
                </div>
                <p class="gd-help">Deixe vazio se não houver promoção ativa.</p>
            </div>
            <div style="background:rgba(255,255,255,.5);border:1px solid #fde68a;border-radius:8px;padding:10px;margin-top:10px;">
                <div style="font-size:12px;color:#92400e;line-height:1.6"><b>Modo Somar?</b> Este campo vira "Desconto (%)" com aplicação no total calculado.</div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Bloco real de Promoção com fundo amarelo.</b> Modo <b>Fixo → preço em R$</b>. Modo <b>Somar → % de desconto</b>. Seção muda conforme o modo selecionado.</span>
        </div>

        <!-- ─── BLOCO 4: Descrição ─── -->
        <h3>④ Descrição</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h14M5 12h14M5 17h10" stroke-linecap="round"/></svg>
                Descrição
            </div>
            <div class="gd-field">
                <span class="gd-label">Conteúdo exibido na página do produto</span>
                <textarea class="gd-input" rows="3" disabled placeholder="Ex.: Pão artesanal, burger 180g, queijo prato e molho especial." style="resize:none;"></textarea>
                <p class="gd-help">Use para destacar ingredientes, diferenciais ou modo de preparo.</p>
            </div>
        </div>

        <!-- ─── BLOCO 5: Imagem ─── -->
        <h3>⑤ Imagem</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Imagem
            </div>
            <div class="gd-row gd-row-2" style="align-items:start;">
                <div class="gd-field">
                    <span class="gd-label">Upload (jpg/png/webp)</span>
                    <div style="display:inline-flex;align-items:center;gap:8px;border:1px solid #cbd5e1;background:#f8fafc;border-radius:12px;padding:8px 12px;font-size:14px;color:#475569;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                        Selecionar arquivo
                    </div>
                    <p class="gd-help">Recomendado: 1000×750px (4:3). Máx. 5 MB.</p>
                </div>
                <div style="text-align:center;">
                    <span style="font-size:12px;color:#94a3b8;">Pré-visualização</span>
                    <div style="width:80px;height:80px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:4px auto 0;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Bloco real de Imagem.</b> Botão para upload + preview à direita. Boa foto = +30% vendas!</span>
        </div>

        <!-- ─── BLOCO 6: Publicação ─── -->
        <h3>⑥ Publicação (Toggle)</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Status
            </div>
            <div class="gd-toggle-row">
                <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                <span class="gd-toggle-text">Produto ativo no cardápio</span>
            </div>
            <p class="gd-help" style="margin-top:8px;">Quando ativo, aparece no cardápio público. Desative para rascunho.</p>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Toggle real do sistema.</b> Colorido = ativo. Cinza = desativado. Pode criar como rascunho e ativar depois.</span>
        </div>
    </div>
</section>

<!-- =============== MODOS DE SELEÇÃO =============== -->
<section id="modes" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg>
            Modos de Seleção (Personalização)
        </h2>
        <p>Cada <b>grupo de personalização</b> usa 1 de 3 modos. Um produto pode misturar modos.</p>

        <table class="gd-cmp">
            <thead><tr><th>Modo</th><th>Comportamento</th><th>Limite</th><th>Cobrança</th><th>Ideal</th></tr></thead>
            <tbody>
                <tr><td><span style="color:#2563eb;font-weight:600;">Extra/Qty</span></td><td>Ajusta quantidade por item</td><td>Por item (min/max)</td><td>Cobra acima do padrão</td><td>Burgers, Pizzas</td></tr>
                <tr><td><span style="color:#d97706;font-weight:600;">Escolha</span></td><td>Radio ou checkbox</td><td>Por grupo (seleções)</td><td>Cobra acima do padrão</td><td>Queijos, Molhos</td></tr>
                <tr><td><span style="color:#059669;font-weight:600;">Montagem</span></td><td>Pool compartilhado</td><td>Por grupo (total)</td><td>Grátis até limite, after: $</td><td>Açaí, Poke</td></tr>
            </tbody>
        </table>

        <!-- Réplica do modo select REAL -->
        <div class="gd-fieldset" style="margin-top:16px;">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg>
                Personalização
            </div>
            <div class="gd-toggle-row" style="margin-bottom:12px;">
                <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                <span class="gd-toggle-text">Permitir personalização</span>
            </div>
            <div class="gd-group">
                <div class="gd-group-head">
                    <input type="text" value="Ingredientes do Burger" disabled>
                    <span class="gd-btn-rm">✕</span>
                </div>
                <div class="gd-group-body">
                    <div style="margin-bottom:10px;">
                        <select class="gd-select" disabled style="border-radius:8px;"><option selected>Adicionar livremente</option><option>Escolher ingrediente</option><option>Montagem (açaí, poke...)</option></select>
                    </div>
                    <div class="gd-group-item">
                        <div class="gd-gi-field" style="flex:2"><label>Ingrediente</label><select disabled><option>Queijo Cheddar</option></select></div>
                        <div class="gd-gi-field"><label>Min</label><input value="0" disabled></div>
                        <div class="gd-gi-field"><label>Max</label><input value="5" disabled></div>
                        <div class="gd-gi-field"><label>Padrão</label><div class="gd-btn-def active" style="width:100%;text-align:center;">Sim</div></div>
                        <div class="gd-gi-field"><label>Qty</label><input value="1" disabled></div>
                    </div>
                    <div class="gd-group-item">
                        <div class="gd-gi-field" style="flex:2"><label>Ingrediente</label><select disabled><option>Bacon</option></select></div>
                        <div class="gd-gi-field"><label>Min</label><input value="0" disabled></div>
                        <div class="gd-gi-field"><label>Max</label><input value="5" disabled></div>
                        <div class="gd-gi-field"><label>Padrão</label><div class="gd-btn-def" style="width:100%;text-align:center;">Não</div></div>
                        <div class="gd-gi-field"><label>Qty</label><input value="0" disabled></div>
                    </div>
                </div>
                <div class="gd-group-foot"><div class="gd-btn-add">+ Ingrediente</div></div>
            </div>
            <div class="gd-btn-add" style="margin-top:8px;">+ Adicionar Grupo</div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Réplica do bloco real.</b> Ative toggle → crie grupo → selecione modo → adicione ingredientes com Min/Max/Padrão/Qty.</span>
        </div>
    </div>
</section>

<!-- =============== EXTRA =============== -->
<section id="mode-extra" class="gd-sec">
    <div class="gd-mode" style="border-left:4px solid #3b82f6;">
        <span class="gd-mbadge" style="background:#dbeafe;color:#2563eb;">Extra / Quantidade</span>
        <div class="mt">Adicionar Ingredientes Livremente</div>
        <div class="md">O cliente controla a quantidade de cada ingrediente com <b>+ / −</b>. Cobra acima da quantidade padrão.</div>

        <h3>🔧 Como Configurar</h3>
        <ol class="gd-steps">
            <li><div>Crie grupo (ex: <b>"Ingredientes do Burger"</b>)</div></li>
            <li><div>Selecione modo <b>"Adicionar livremente"</b></div></li>
            <li><div>Adicione ingredientes. Defina <b>Min, Max, Padrão, Qty</b></div></li>
        </ol>

        <h3>💲 Fórmula de Cobrança</h3>
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:16px;margin:14px 0;">
            <code style="background:#dbeafe;padding:3px 10px;border-radius:6px;font-size:12px;">Extra = (Qty pedida − Qty padrão) × Preço</code>
            <div style="font-size:13px;color:#1e3a5f;line-height:1.7;margin-top:10px;">
                <b>Ex:</b> Cheddar padrão=1. Cliente pede 3. → (3 − 1) × R$ 3,00 = <b style="color:#2563eb">R$ 6,00 extra</b>
            </div>
        </div>

        <div class="gd-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Min=1, Max=1</b> → fixo (ex: Pão). <b>Min=0</b> → o cliente decide se adiciona.</span>
        </div>
    </div>
</section>

<!-- =============== ESCOLHA =============== -->
<section id="mode-choice" class="gd-sec">
    <div class="gd-mode" style="border-left:4px solid #f59e0b;">
        <span class="gd-mbadge" style="background:#fef3c7;color:#d97706;">Escolha Única / Múltipla</span>
        <div class="mt">Selecionar entre Opções</div>
        <div class="md">Radio (1 seleção) ou checkbox (múltipla). Definido pelo Min/Max do <b>grupo</b>.</div>

        <h3>⚙️ Config no sistema real:</h3>
        <div class="gd-choice-set">
            <div style="display:flex;gap:12px;">
                <div class="gd-gi-field"><label>Mín seleções</label><input value="1" disabled></div>
                <div class="gd-gi-field"><label>Máx seleções</label><input value="1" disabled></div>
            </div>
        </div>
        <div class="gd-annot" style="margin-bottom:16px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Painel verde real.</b> Min=1/Max=1 → radio obrigatório. Min=0/Max=3 → checkbox até 3.</span>
        </div>

        <h3>🔧 Como Configurar</h3>
        <ol class="gd-steps">
            <li><div>Crie grupo (ex: <b>"Tipo de Queijo"</b>)</div></li>
            <li><div>Selecione modo <b>"Escolher ingrediente"</b></div></li>
            <li><div>Defina Min/Max: <b>1/1</b> → única · <b>0/3</b> → até 3</div></li>
            <li><div>Adicione opções e marque o <b>padrão</b></div></li>
        </ol>

        <div class="gd-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Min=0</b> → opcional. <b>Min=1</b> → obrigatório. <b>Max=1</b> → radio. <b>Max>1</b> → checkbox.</span>
        </div>
    </div>
</section>

<!-- =============== POOL =============== -->
<section id="mode-pool" class="gd-sec">
    <div class="gd-mode" style="border-left:4px solid #10b981;">
        <span class="gd-mbadge" style="background:#d1fae5;color:#059669;">Montagem / Pool</span>
        <div class="mt">Monte Seu Produto</div>
        <div class="md">Ideal para açaí, poke, frozen. Primeiros toppings <b>grátis</b>, extras são cobrados.</div>

        <h3>⚙️ Config no sistema real:</h3>
        <div class="gd-pool-set">
            <div style="display:flex;gap:12px;">
                <div class="gd-gi-field"><label>Total mínimo</label><input value="0" disabled></div>
                <div class="gd-gi-field"><label>Total máximo</label><input value="5" disabled></div>
            </div>
        </div>
        <div class="gd-annot" style="margin-bottom:16px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Painel roxo real.</b> Max = itens grátis. Ex: Max=5 → 5 toppings grátis.</span>
        </div>

        <h3>🔧 Como Configurar</h3>
        <ol class="gd-steps">
            <li><div>Crie grupo (ex: <b>"Toppings do Açaí"</b>)</div></li>
            <li><div>Selecione <b>"Montagem (açaí, poke...)"</b></div></li>
            <li><div><b>Max = itens grátis</b>. Ex: Max=5 → 5 grátis</div></li>
            <li><div>Adicione ingredientes. Preço de venda cobrado nos extras</div></li>
        </ol>

        <h3>💲 Cobrança</h3>
        <div style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:12px;padding:16px;margin:14px 0;">
            <code style="background:#bbf7d0;padding:3px 10px;border-radius:6px;font-size:12px;">Extra = Σ preços além do pool</code>
            <div style="font-size:13px;color:#064e3b;line-height:1.7;margin-top:10px;">
                Pool Max = <b>5</b>. Cliente escolhe 7. → 5 grátis + 2 pagos (Nutella R$ 4 + Paçoca R$ 2) = <b style="color:#059669">R$ 6,00 extra</b>
            </div>
        </div>

        <div class="gd-warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span class="t"><b>Atenção:</b> O preço cobrado vem do <b>preço de venda do ingrediente</b> no cadastro de Ingredientes.</span>
        </div>
    </div>
</section>

<!-- =============== COMBOS =============== -->
<section id="combos" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v3"/></svg>
            Combos
        </h2>
        <p>Combos agrupam produtos simples em "etapas". Cada etapa é um grupo de opções.</p>

        <h3>🔧 Como Criar</h3>
        <ol class="gd-steps">
            <li><div>Selecione tipo <b>"Combo"</b> no formulário</div></li>
            <li><div>Ative <b>"Usar grupos de opções"</b></div></li>
            <li><div>Clique <b>"+ Grupo"</b> para criar etapa (ex: "Burger", "Bebida")</div></li>
            <li><div>Dentro, clique <b>"+ Item"</b> e busque produto simples</div></li>
            <li><div>Configure <b>preço, padrão, min/max</b> por grupo</div></li>
        </ol>

        <!-- Réplica REAL do combo group -->
        <h3>No formulário real:</h3>
        <div class="gd-fieldset">
            <div class="gd-legend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg>
                Grupos do Combo
            </div>
            <div class="gd-toggle-row" style="margin-bottom:12px;">
                <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                <span class="gd-toggle-text">Usar grupos de opções</span>
            </div>

            <div class="gd-group">
                <div class="gd-group-head"><input type="text" value="Escolha o Burger" disabled><span class="gd-btn-rm">✕</span></div>
                <div class="gd-group-body">
                    <div style="display:flex;gap:8px;margin-bottom:10px;">
                        <div class="gd-gi-field"><label>Mín</label><input value="1" disabled></div>
                        <div class="gd-gi-field"><label>Máx</label><input value="1" disabled></div>
                    </div>
                    <div class="gd-group-item">
                        <div class="gd-gi-field" style="flex:2"><label>Produto</label><select disabled><option>Woll Smash</option></select></div>
                        <div class="gd-gi-field"><label>Qtd</label><input value="1" disabled></div>
                        <div class="gd-gi-field"><label>Preço</label><input value="0.00" disabled></div>
                        <div class="gd-gi-field"><label>Padrão</label><div class="gd-btn-def active" style="width:100%;text-align:center;">Padrão</div></div>
                    </div>
                    <div class="gd-group-item">
                        <div class="gd-gi-field" style="flex:2"><label>Produto</label><select disabled><option>Double Cheese</option></select></div>
                        <div class="gd-gi-field"><label>Qtd</label><input value="1" disabled></div>
                        <div class="gd-gi-field"><label>Preço</label><input value="5.00" disabled></div>
                        <div class="gd-gi-field"><label>Padrão</label><div class="gd-btn-def" style="width:100%;text-align:center;">Não</div></div>
                    </div>
                </div>
                <div class="gd-group-foot"><div class="gd-btn-add">+ Produto</div></div>
            </div>

            <div class="gd-group">
                <div class="gd-group-head"><input type="text" value="Bebida" disabled><span class="gd-btn-rm">✕</span></div>
                <div class="gd-group-body">
                    <div style="display:flex;gap:8px;margin-bottom:10px;">
                        <div class="gd-gi-field"><label>Mín</label><input value="1" disabled></div>
                        <div class="gd-gi-field"><label>Máx</label><input value="1" disabled></div>
                    </div>
                    <div class="gd-group-item">
                        <div class="gd-gi-field" style="flex:2"><label>Produto</label><select disabled><option>Refrigerante Lata</option></select></div>
                        <div class="gd-gi-field"><label>Qtd</label><input value="1" disabled></div>
                        <div class="gd-gi-field"><label>Preço</label><input value="0.00" disabled></div>
                        <div class="gd-gi-field"><label>Padrão</label><div class="gd-btn-def active" style="width:100%;text-align:center;">Padrão</div></div>
                    </div>
                </div>
                <div class="gd-group-foot"><div class="gd-btn-add">+ Produto</div></div>
            </div>

            <div class="gd-btn-add" style="margin-top:8px;">+ Adicionar Grupo</div>
        </div>
        <div class="gd-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Réplica dos group cards reais.</b> Cada grupo = 1 etapa. Marque item mais barato como "Padrão". Upgraes geram delta de preço.</span>
        </div>

        <div class="gd-warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span class="t"><b>Importante:</b> Produtos do combo precisam ser <b>simples e ativos</b>. Cadastre-os antes.</span>
        </div>
    </div>
</section>

<!-- =============== PREÇOS =============== -->
<section id="pricing" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Modos de Preço
        </h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 20px 0;">
            <div style="border: 2px solid var(--admin-primary-color); border-radius: 14px; padding: 20px;">
                <div style="font-size: 15px; font-weight: 700; color: var(--admin-primary-color); margin-bottom: 4px;">💎 Modo Fixo</div>
                <div style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 12px;">Preço definido manualmente.<br><code style="background:#f1f5f9;padding:3px 8px;border-radius:6px;font-size:12px;">Total = Base + Σ Deltas</code></div>
                <div style="font-size: 12px; color: #64748b;"><b>Promoção:</b> preço em R$<br><b>Ex:</b> R$ 29,90 → Promo R$ 24,90</div>
            </div>
            <div style="border: 2px solid #f59e0b; border-radius: 14px; padding: 20px;">
                <div style="font-size: 15px; font-weight: 700; color: #d97706; margin-bottom: 4px;">📊 Modo Somar</div>
                <div style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 12px;">Total = soma dos itens.<br><code style="background:#f1f5f9;padding:3px 8px;border-radius:6px;font-size:12px;">Total = Σ Preços itens</code></div>
                <div style="font-size: 12px; color: #64748b;"><b>Promoção:</b> % de desconto<br><b>Ex:</b> Soma R$ 40 → 20% = R$ 32</div>
            </div>
        </div>

        <div class="gd-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Fixo</b> = mais comum (preço tabelado). <b>Somar</b> = "monte o seu" (preço varia).</span>
        </div>
    </div>
</section>

<!-- =============== DICAS =============== -->
<section id="tips" class="gd-sec">
    <div class="gd-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Dicas & Boas Práticas
        </h2>

        <div style="display: grid; gap: 12px;">
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Ingredientes primeiro</div><div style="font-size:13px;color:#15803d;">Cadastre ingredientes com custo e preço de venda antes de criar personalização.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Boas fotos</div><div style="font-size:13px;color:#15803d;">Produtos com foto vendem <b>até 30% mais</b>. Use 4:3 com boa iluminação.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Teste como cliente</div><div style="font-size:13px;color:#15803d;">Após cadastrar, acesse o cardápio público e teste a personalização.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Use templates</div><div style="font-size:13px;color:#15803d;">Crie um Template de personalização e copie para produtos similares. <b>10 produtos em 1 minuto.</b></div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Preços no combo</div><div style="font-size:13px;color:#c2410c;"><b>Fixo:</b> base inclui padrão, upgrades geram delta. <b>Somar:</b> cada item tem preço individual.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Ocultar ingredientes</div><div style="font-size:13px;color:#c2410c;">Se ingrediente é <b>padrão</b> de um produto → produto inteiro oculto. Se é topping de montagem → só ele some.</div></div>
            </div>
        </div>
    </div>
</section>

</div><!-- end main -->
</div><!-- end grid -->
</div><!-- end container -->

<script>
(function(){
    var secs=document.querySelectorAll('.gd-sec'),links=document.querySelectorAll('.gd-nav a[data-section]');
    function up(){var y=window.scrollY+150,c='';secs.forEach(function(s){if(s.offsetTop<=y)c=s.id});links.forEach(function(a){a.classList.toggle('active',a.dataset.section===c)})}
    window.addEventListener('scroll',up);up();
    links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.dataset.section);if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
