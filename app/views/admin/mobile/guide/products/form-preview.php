<!-- ═══ FORMULÁRIO BLOCO A BLOCO ═══ -->
<section id="form" class="gd-sec">
    <div class="gd-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg></span>
            Formulário — Bloco a Bloco
        </h2>
        <p>Cada seção do formulário de criação de produto, exatamente como aparece no sistema:</p>

        <!-- ─── BLOCO 1: IMAGEM ─── -->
        <h3>① Imagem do Produto</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Imagem
            </div>
            <div class="gd-form-body">
                <div class="gd-upload">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14m-7-7h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span style="font-size:14px;font-weight:500">Toque para adicionar foto</span>
                </div>
                <p class="gd-form-help">Recomendado: 1000×750px (4:3). Máx. 5MB.</p>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Toque na área tracejada para enviar a foto. JPG, PNG ou WebP. Produtos com foto vendem <b>30% mais</b>.</span>
        </div>

        <!-- ─── BLOCO 2: DADOS BÁSICOS ─── -->
        <h3>② Dados Básicos</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke-linecap="round"/></svg>
                Dados Básicos
            </div>
            <div class="gd-form-body">
                <div class="gd-form-group">
                    <label class="gd-form-label">Nome do Produto *<span class="gd-tag gd-tag-req">Obrig.</span></label>
                    <input type="text" class="gd-form-input" placeholder="Ex: X-Burger Especial" disabled>
                </div>
                <div class="gd-form-row">
                    <div class="gd-form-group">
                        <label class="gd-form-label">SKU<span class="gd-tag gd-tag-auto">Auto</span></label>
                        <input type="text" class="gd-form-input" value="001" disabled style="background:#f9fafb">
                    </div>
                    <div class="gd-form-group">
                        <label class="gd-form-label">Categoria<span class="gd-tag gd-tag-opt">Opc.</span></label>
                        <select class="gd-form-input" disabled><option>Lanches</option></select>
                    </div>
                </div>
                <div class="gd-form-group">
                    <label class="gd-form-label">Descrição<span class="gd-tag gd-tag-opt">Opc.</span></label>
                    <textarea class="gd-form-input" rows="2" disabled placeholder="Descreva o produto..." style="resize:none"></textarea>
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Nome</b> é obrigatório. <b>SKU</b> gera automático (não edita). <b>Categoria</b> agrupa no cardápio. <b>Descrição</b> aparece na página do produto.</span>
        </div>

        <!-- ─── BLOCO 3: TIPO DO PRODUTO ─── -->
        <h3>③ Tipo do Produto</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                Tipo do Produto
            </div>
            <div class="gd-form-body">
                <div class="gd-type-cards">
                    <div class="gd-type-card active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;width:28px;height:28px;color:var(--primary)"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div class="tc-title">Simples</div>
                        <div class="tc-desc">Produto único</div>
                    </div>
                    <div class="gd-type-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;width:28px;height:28px;color:#6b7280"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div class="tc-title">Combo</div>
                        <div class="tc-desc">Múltiplos itens</div>
                    </div>
                </div>
                <p class="gd-form-help"><b>Simples:</b> Produto com personalização. <b>Combo:</b> Agrupa outros produtos.</p>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Toque no card para selecionar. <b>Simples</b> libera personalização. <b>Combo</b> libera grupos de opções.</span>
        </div>

        <!-- ─── BLOCO 4: PREÇO ─── -->
        <h3>④ Preço</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                Preço
            </div>
            <div class="gd-form-body">
                <div class="gd-form-row">
                    <div class="gd-form-group">
                        <label class="gd-form-label">Preço Base (R$) *<span class="gd-tag gd-tag-req">Obrig.</span></label>
                        <div class="gd-prefix">
                            <span class="pf">R$</span>
                            <input type="text" class="gd-form-input" value="29,90" disabled style="font-weight:600">
                        </div>
                    </div>
                    <div class="gd-form-group">
                        <label class="gd-form-label">Ordem<span class="gd-tag gd-tag-opt">Opc.</span></label>
                        <input type="number" class="gd-form-input" value="0" disabled>
                    </div>
                </div>
                <div class="gd-form-group">
                    <label class="gd-form-label">Modo de Preço</label>
                    <select class="gd-form-input" disabled>
                        <option>Fixo (preço base)</option>
                        <option>Somar itens do grupo</option>
                    </select>
                    <p class="gd-form-help">Em "Somar", total = preço base + deltas dos itens selecionados.</p>
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Preço Base</b> é o valor principal. <b>Modo Fixo</b> = preço direto. <b>Modo Somar</b> = soma dos itens (combos "monte o seu").</span>
        </div>

        <!-- ─── BLOCO 5: PROMOÇÃO ─── -->
        <h3>⑤ Promoção</h3>
        <div class="gd-form-block gd-promo">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                Promoção
            </div>
            <div class="gd-form-body">
                <div class="gd-form-group">
                    <label class="gd-form-label">Preço Promocional</label>
                    <div class="gd-prefix">
                        <span class="pf">R$</span>
                        <input type="text" class="gd-form-input" placeholder="0,00" disabled>
                    </div>
                    <p class="gd-form-help">Aparece no modo <b>Fixo</b>. Deixe vazio se não tem promoção.</p>
                </div>
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px;margin-top:8px">
                    <div style="font-size:11px;color:#9a3412;line-height:1.6"><b>Modo Somar?</b> Ao invés de preço, aparece campo de <b>Desconto (%)</b>. Ex: 20% = 20% off no total.</div>
                </div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>A seção Promoção tem fundo amarelo no sistema real. <b>Fixo → preço em R$</b>. <b>Somar → % de desconto</b>.</span>
        </div>

        <!-- ─── BLOCO 6: PUBLICAÇÃO ─── -->
        <h3>⑥ Publicação</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Status
            </div>
            <div class="gd-form-body">
                <div class="gd-toggle-row">
                    <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                    <span class="gd-toggle-text">Produto ativo no cardápio</span>
                </div>
                <p class="gd-form-help">Quando ativo, o produto aparece no cardápio público. Desative para rascunho.</p>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>O toggle colorido = ativo. Cinza = desativado. Pode criar como rascunho e ativar depois.</span>
        </div>
    </div>
</section>
