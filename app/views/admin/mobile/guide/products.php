<?php
require_once __DIR__ . '/../components/icons.php';
$activeProductNav = 'guide';
?>
<link rel="stylesheet" href="<?= base_url('assets/css/products-guide.css') ?>">

<!-- Nav Pills -->
<div class="gd-nav-wrap">
    <div class="gd-nav">
        <a href="#overview" class="gd-pill active">Visão Geral</a>
        <a href="#form" class="gd-pill">Formulário</a>
        <a href="#modes" class="gd-pill">Personalização</a>
        <a href="#combos" class="gd-pill">Combos</a>
        <a href="#pricing" class="gd-pill">Preços</a>
        <a href="#tips" class="gd-pill">Dicas</a>
    </div>
</div>

<div class="products-list" style="padding:0 0 100px;">

    <!-- ═══ VISÃO GERAL ═══ -->
    <section id="overview" class="gd-sec">
        <div class="gd-card">
            <h2>
                <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
                Visão Geral
            </h2>
            <p>O sistema tem dois tipos de produto. Veja como aparecem no formulário real:</p>

            <!-- RÉPLICA REAL: Type Cards do form -->
            <div class="gd-form-block">
                <div class="gd-form-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                    Tipo do Produto
                </div>
                <div class="gd-form-body">
                    <div class="gd-type-cards">
                        <div class="gd-type-card active">
                            <svg class="tc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;width:28px;height:28px;color:var(--primary)"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <div class="tc-title">Simples</div>
                            <div class="tc-desc">Produto único</div>
                        </div>
                        <div class="gd-type-card">
                            <svg class="tc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;width:28px;height:28px;color:#6b7280"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <div class="tc-title">Combo</div>
                            <div class="tc-desc">Múltiplos itens</div>
                        </div>
                    </div>
                    <p class="gd-form-help"><b>Simples:</b> Produto com personalização de ingredientes. <b>Combo:</b> Monte kits com outros produtos.</p>
                </div>
            </div>
            <div class="gd-annot">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <span>Este é o bloco <b>real</b> do sistema. Toque em "Simples" ou "Combo" para definir o tipo.</span>
            </div>

            <div class="gd-tip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
                <span><strong>Ordem:</strong> Cadastre Ingredientes → Produtos Simples → Combos. Combos usam produtos já existentes.</span>
            </div>
        </div>
    </section>

    <!-- ═══ FORMULÁRIO BLOCO A BLOCO ═══ -->
    <?php include __DIR__ . '/products/form-preview.php'; ?>

    <!-- ═══ PERSONALIZAÇÃO ═══ -->
    <?php include __DIR__ . '/products/customization-modes.php'; ?>

    <!-- ═══ COMBOS ═══ -->
    <?php include __DIR__ . '/products/combo-preview.php'; ?>

    <!-- ═══ PREÇOS ═══ -->
    <section id="pricing" class="gd-sec">
        <div class="gd-card">
            <h2>
                <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
                Modos de Preço
            </h2>

            <div style="border:2px solid var(--primary);border-radius:14px;padding:16px;margin-bottom:12px">
                <div style="font-size:14px;font-weight:800;color:var(--primary);margin-bottom:4px">💎 Fixo</div>
                <div style="font-size:12px;color:#4b5563;line-height:1.6;margin-bottom:8px">Preço definido. Upgrades geram delta.</div>
                <code style="background:#f3f4f6;padding:3px 8px;border-radius:6px;font-size:11px;display:inline-block;margin-bottom:6px">Total = Base + Σ Deltas</code>
                <div style="font-size:12px;color:#4b5563"><b>Promoção:</b> preço em R$</div>
                <div style="background:#f9fafb;border-radius:8px;padding:8px;margin-top:6px;font-size:11px;color:#374151"><b>Ex:</b> R$ 29,90 → Promo R$ 24,90</div>
            </div>

            <div style="border:2px solid #f59e0b;border-radius:14px;padding:16px;margin-bottom:12px">
                <div style="font-size:14px;font-weight:800;color:#d97706;margin-bottom:4px">📊 Somar</div>
                <div style="font-size:12px;color:#4b5563;line-height:1.6;margin-bottom:8px">Total = soma dos preços dos itens.</div>
                <code style="background:#f3f4f6;padding:3px 8px;border-radius:6px;font-size:11px;display:inline-block;margin-bottom:6px">Total = Σ Preços itens</code>
                <div style="font-size:12px;color:#4b5563"><b>Promoção:</b> % de desconto</div>
                <div style="background:#fef3c720;border-radius:8px;padding:8px;margin-top:6px;font-size:11px;color:#374151"><b>Ex:</b> Soma R$ 40 → 20% desc = R$ 32</div>
            </div>

            <div class="gd-tip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
                <span><b>Fixo</b> = mais comum (preço tabelado). <b>Somar</b> = "monte o seu" (preço varia).</span>
            </div>
        </div>
    </section>

    <!-- ═══ DICAS ═══ -->
    <section id="tips" class="gd-sec">
        <div class="gd-card">
            <h2>
                <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></span>
                Dicas & Boas Práticas
            </h2>

            <div class="gd-gc gd-gc-good"><span class="g-icon">✅</span><div><div class="g-title">Ingredientes primeiro</div><div class="g-desc">Cadastre ingredientes com custo e preço de venda antes de criar personalização.</div></div></div>
            <div class="gd-gc gd-gc-good"><span class="g-icon">📸</span><div><div class="g-title">Boas fotos</div><div class="g-desc">Produtos com foto vendem <b>30% mais</b>. Use 4:3, boa luz, fundo limpo.</div></div></div>
            <div class="gd-gc gd-gc-good"><span class="g-icon">🧪</span><div><div class="g-title">Teste como cliente</div><div class="g-desc">Após cadastrar, teste personalização no cardápio público.</div></div></div>
            <div class="gd-gc gd-gc-good"><span class="g-icon">📋</span><div><div class="g-title">Use templates</div><div class="g-desc">Produtos similares? Crie Template e copie. <b>10 produtos em 1 minuto.</b></div></div></div>
            <div class="gd-gc gd-gc-warn"><span class="g-icon">💰</span><div><div class="g-title">Preços no combo</div><div class="g-desc"><b>Fixo:</b> base inclui padrão, upgrades geram delta. <b>Somar:</b> cada item tem preço.</div></div></div>
            <div class="gd-gc gd-gc-warn"><span class="g-icon">👁️</span><div><div class="g-title">Ocultar ingredientes</div><div class="g-desc">Se é <b>padrão</b> do produto → produto inteiro oculto. Se é topping → só ele.</div></div></div>
        </div>
    </section>

</div>

<!-- FAB Criar Produto -->
<a href="<?= base_url('admin/' . rawurlencode($companySlug ?? '') . '/products/create') ?>" class="fab" style="bottom:80px">
    <?= productIcon('plus', 24, '1.5') ?>
</a>

<script src="<?= base_url('assets/js/products-guide.js') ?>"></script>
