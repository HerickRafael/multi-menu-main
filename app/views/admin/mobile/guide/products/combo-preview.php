<!-- ═══ COMBOS ═══ -->
<section id="combos" class="gd-sec">
    <div class="gd-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v3"/></svg></span>
            Combos
        </h2>
        <p>Combos agrupam produtos simples em "etapas". Cada etapa é um grupo de opções.</p>

        <h3>🔧 Como Criar</h3>
        <ol class="gd-steps">
            <li><div>Selecione tipo <b>"Combo"</b> no formulário</div></li>
            <li><div>Ative <b>"Usar grupos de opções"</b></div></li>
            <li><div>Crie grupos e adicione produtos simples</div></li>
            <li><div>Configure preço, padrão e min/max por grupo</div></li>
        </ol>

        <!-- RÉPLICA REAL: Combo group cards -->
        <h3>No formulário real:</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                Grupos do Combo
            </div>
            <div class="gd-form-body">
                <div class="gd-toggle-row">
                    <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                    <span class="gd-toggle-text">Usar grupos de opções</span>
                </div>

                <!-- Grupo 1 -->
                <div class="gd-group-card">
                    <div class="gd-group-header">
                        <input type="text" value="Escolha o Burger" disabled>
                        <span class="gd-btn-remove">✕</span>
                    </div>
                    <div class="gd-group-items">
                        <div style="display:flex;gap:8px;margin-bottom:8px">
                            <div class="gd-item-field"><label>Mín</label><input value="1" disabled></div>
                            <div class="gd-item-field"><label>Máx</label><input value="1" disabled></div>
                        </div>
                        <div class="gd-group-item">
                            <div class="gd-item-field" style="flex:2"><label>Produto</label><select disabled><option>Woll Smash</option></select></div>
                            <div class="gd-item-field"><label>Qtd</label><input value="1" disabled></div>
                            <div class="gd-item-field"><label>Preço</label><input value="0.00" disabled></div>
                            <div class="gd-btn-default active" style="align-self:flex-end">Padrão</div>
                        </div>
                        <div class="gd-group-item">
                            <div class="gd-item-field" style="flex:2"><label>Produto</label><select disabled><option>Double Cheese</option></select></div>
                            <div class="gd-item-field"><label>Qtd</label><input value="1" disabled></div>
                            <div class="gd-item-field"><label>Preço</label><input value="5.00" disabled></div>
                            <div class="gd-btn-default" style="align-self:flex-end">Não</div>
                        </div>
                    </div>
                    <div class="gd-group-footer"><div class="gd-btn-add">+ Produto</div></div>
                </div>

                <!-- Grupo 2 -->
                <div class="gd-group-card">
                    <div class="gd-group-header">
                        <input type="text" value="Bebida" disabled>
                        <span class="gd-btn-remove">✕</span>
                    </div>
                    <div class="gd-group-items">
                        <div style="display:flex;gap:8px;margin-bottom:8px">
                            <div class="gd-item-field"><label>Mín</label><input value="1" disabled></div>
                            <div class="gd-item-field"><label>Máx</label><input value="1" disabled></div>
                        </div>
                        <div class="gd-group-item">
                            <div class="gd-item-field" style="flex:2"><label>Produto</label><select disabled><option>Refrigerante Lata</option></select></div>
                            <div class="gd-item-field"><label>Qtd</label><input value="1" disabled></div>
                            <div class="gd-item-field"><label>Preço</label><input value="0.00" disabled></div>
                            <div class="gd-btn-default active" style="align-self:flex-end">Padrão</div>
                        </div>
                    </div>
                    <div class="gd-group-footer"><div class="gd-btn-add">+ Produto</div></div>
                </div>

                <div class="gd-btn-add" style="margin-top:8px">+ Adicionar Grupo</div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Cada grupo = 1 etapa. Marque o item mais barato como "Padrão". Upgrades geram delta de preço.</span>
        </div>

        <div class="gd-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span>Produtos do combo precisam ser <b>simples e ativos</b>. Cadastre-os antes.</span>
        </div>
    </div>
</section>
