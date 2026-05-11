<!-- ═══ PERSONALIZAÇÃO ═══ -->
<section id="modes" class="gd-sec">
    <div class="gd-card">
        <h2>
            <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg></span>
            Personalização
        </h2>
        <p>Produtos simples podem ter grupos de personalização. Cada grupo usa 1 de 3 modos:</p>

        <table class="gd-cmp">
            <thead><tr><th>Modo</th><th>Limite</th><th>Ideal</th></tr></thead>
            <tbody>
                <tr><td><span class="gd-badge" style="background:#dbeafe;color:#2563eb">EXTRA</span></td><td>Por item</td><td>Burger, Pizza</td></tr>
                <tr><td><span class="gd-badge" style="background:#fef3c7;color:#d97706">ESCOLHA</span></td><td>Por grupo</td><td>Queijo, Molho</td></tr>
                <tr><td><span class="gd-badge" style="background:#d1fae5;color:#059669">MONTAGEM</span></td><td>Pool total</td><td>Açaí, Poke</td></tr>
            </tbody>
        </table>

        <!-- RÉPLICA REAL: Personalização toggle + group card -->
        <h3>No formulário real:</h3>
        <div class="gd-form-block">
            <div class="gd-form-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round"/></svg>
                Personalização
            </div>
            <div class="gd-form-body">
                <div class="gd-toggle-row">
                    <div class="gd-toggle-track on"><div class="gd-toggle-thumb"></div></div>
                    <span class="gd-toggle-text">Permitir personalização</span>
                </div>

                <div class="gd-group-card">
                    <div class="gd-group-header">
                        <input type="text" value="Ingredientes do Burger" disabled>
                        <span class="gd-btn-remove">✕</span>
                    </div>
                    <div class="gd-group-items">
                        <div class="gd-mode-row">
                            <select disabled>
                                <option selected>Adicionar livremente</option>
                                <option>Escolher ingrediente</option>
                                <option>Montagem (açaí, poke...)</option>
                            </select>
                        </div>
                        <div class="gd-group-item">
                            <div class="gd-item-field" style="flex:2"><label>Ingrediente</label><select disabled><option>Queijo Cheddar</option></select></div>
                            <div class="gd-item-field"><label>Min</label><input value="0" disabled></div>
                            <div class="gd-item-field"><label>Max</label><input value="5" disabled></div>
                        </div>
                        <div class="gd-group-item">
                            <div class="gd-item-field" style="flex:2"><label>Ingrediente</label><select disabled><option>Bacon</option></select></div>
                            <div class="gd-item-field"><label>Min</label><input value="0" disabled></div>
                            <div class="gd-item-field"><label>Max</label><input value="5" disabled></div>
                        </div>
                    </div>
                    <div class="gd-group-footer">
                        <div class="gd-btn-add">+ Ingrediente</div>
                    </div>
                </div>
                <div class="gd-btn-add" style="margin-top:8px">+ Adicionar Grupo</div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Ative o toggle → crie grupos → selecione o modo → adicione ingredientes com Min/Max. Cada modo muda como o cliente interage.</span>
        </div>
    </div>

    <!-- ─── EXTRA ─── -->
    <div class="gd-mode gd-mode-extra">
        <span class="gd-badge" style="background:#dbeafe;color:#2563eb;margin-bottom:10px">EXTRA / QUANTIDADE</span>
        <h2>Adicionar Livremente</h2>
        <p>O cliente controla cada ingrediente individualmente com botões <b>+</b> e <b>−</b>.</p>

        <h3>📱 Visão do Cliente</h3>
        <div class="gd-form-block">
            <div class="gd-form-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4V7"/></svg> Cardápio — Personalização</div>
            <div class="gd-form-body" style="padding:12px">
                <div style="font-size:13px;font-weight:700;color:#1f2937;margin-bottom:10px">Personalize seu Burger</div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6">
                    <div><div style="font-size:13px;font-weight:600;color:#1f2937">🧀 Cheddar</div><div style="font-size:11px;color:#6b7280">+R$ 3,00 cada extra</div></div>
                    <div style="display:flex;align-items:center;border:1.5px solid #d1d5db;border-radius:8px;overflow:hidden"><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">−</span><span style="min-width:28px;text-align:center;font-size:14px;font-weight:700;color:#1f2937">1</span><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">+</span></div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6">
                    <div><div style="font-size:13px;font-weight:600;color:#1f2937">🥩 Blend 90g</div><div style="font-size:11px;color:#6b7280">+R$ 8,00 cada extra</div></div>
                    <div style="display:flex;align-items:center;border:1.5px solid #d1d5db;border-radius:8px;overflow:hidden"><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">−</span><span style="min-width:28px;text-align:center;font-size:14px;font-weight:700;color:#1f2937">1</span><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">+</span></div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0">
                    <div><div style="font-size:13px;font-weight:600;color:#1f2937">🥓 Bacon</div><div style="font-size:11px;color:#6b7280">+R$ 4,00</div></div>
                    <div style="display:flex;align-items:center;border:1.5px solid #d1d5db;border-radius:8px;overflow:hidden"><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">−</span><span style="min-width:28px;text-align:center;font-size:14px;font-weight:700;color:#9ca3af">0</span><span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f9fafb;color:#6b7280;font-weight:600">+</span></div>
                </div>
            </div>
        </div>

        <h3>💲 Cobrança</h3>
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:14px;margin:10px 0">
            <div style="font-size:12px;color:#1e3a5f;line-height:1.7">
                <code style="background:#dbeafe;padding:2px 8px;border-radius:5px;font-size:11px">Extra = (Qty pedida − Qty padrão) × Preço</code><br><br>
                <b>Ex:</b> Cheddar padrão=1. Cliente pede 3.<br>(3 − 1) × R$ 3 = <b style="color:#2563eb">R$ 6,00 extra</b>
            </div>
        </div>

        <div class="gd-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Min=1, Max=1</b> → fixo (ex: Pão). <b>Min=0</b> → opcional. O cliente decide se adiciona.</span>
        </div>
    </div>

    <!-- ─── ESCOLHA ─── -->
    <div class="gd-mode gd-mode-choice">
        <span class="gd-badge" style="background:#fef3c7;color:#d97706;margin-bottom:10px">ESCOLHA</span>
        <h2>Selecionar entre Opções</h2>
        <p>Escolha <b>única</b> (radio) ou <b>múltipla</b> (checkbox), definido pelo limite do grupo.</p>

        <h3>⚙️ Config no sistema real:</h3>
        <div class="gd-choice-settings">
            <div style="display:flex;gap:8px">
                <div class="gd-item-field"><label>Mín seleções</label><input value="1" disabled></div>
                <div class="gd-item-field"><label>Máx seleções</label><input value="1" disabled></div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Painel verde aparece quando modo = "Escolha". <b>Min=1/Max=1</b> → radio. <b>Max&gt;1</b> → checkbox.</span>
        </div>

        <h3>📱 Radio (Única)</h3>
        <div class="gd-form-block">
            <div class="gd-form-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><circle cx="12" cy="12" r="10"/></svg> Queijo (obrigatório)</div>
            <div class="gd-form-body" style="padding:12px">
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6">
                    <span style="width:18px;height:18px;border-radius:50%;border:2px solid var(--primary);background:var(--primary);display:flex;align-items:center;justify-content:center"><span style="width:6px;height:6px;border-radius:50%;background:#fff"></span></span>
                    <span style="flex:1;font-size:13px;font-weight:600;color:#1f2937">Cheddar</span>
                    <span style="font-size:12px;color:#6b7280">R$ 3</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6">
                    <span style="width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db"></span>
                    <span style="flex:1;font-size:13px;color:#4b5563">Mussarela</span>
                    <span style="font-size:12px;color:#6b7280">R$ 2,50</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0">
                    <span style="width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db"></span>
                    <span style="flex:1;font-size:13px;color:#4b5563">Gorgonzola</span>
                    <span style="font-size:12px;color:#6b7280">R$ 5</span>
                </label>
            </div>
        </div>

        <h3>📱 Checkbox (Múltipla)</h3>
        <div class="gd-form-block">
            <div class="gd-form-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><circle cx="12" cy="12" r="10"/></svg> Molhos (até 2)</div>
            <div class="gd-form-body" style="padding:12px">
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6">
                    <span style="width:18px;height:18px;border-radius:4px;background:var(--primary);display:flex;align-items:center;justify-content:center"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
                    <span style="flex:1;font-size:13px;font-weight:600;color:#1f2937">Maionese</span>
                    <span style="font-size:11px;color:#16a34a;font-weight:600">Grátis</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6">
                    <span style="width:18px;height:18px;border-radius:4px;background:var(--primary);display:flex;align-items:center;justify-content:center"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
                    <span style="flex:1;font-size:13px;font-weight:600;color:#1f2937">Ketchup</span>
                    <span style="font-size:11px;color:#16a34a;font-weight:600">Grátis</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:8px 0">
                    <span style="width:18px;height:18px;border-radius:4px;border:2px solid #d1d5db"></span>
                    <span style="flex:1;font-size:13px;color:#4b5563">Barbecue</span>
                    <span style="font-size:12px;color:#6b7280">+R$ 1</span>
                </label>
            </div>
        </div>

        <div class="gd-tip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span><b>Min=0</b> → opcional. <b>Min=1</b> → obrigatório. <b>Max=1</b> → radio. <b>Max&gt;1</b> → checkbox.</span>
        </div>
    </div>

    <!-- ─── MONTAGEM / POOL ─── -->
    <div class="gd-mode gd-mode-pool">
        <span class="gd-badge" style="background:#d1fae5;color:#059669;margin-bottom:10px">MONTAGEM / POOL</span>
        <h2>Monte Seu Produto</h2>
        <p>Ideal para açaí, poke, frozen. Os primeiros toppings são <b>grátis</b>!</p>

        <h3>⚙️ Config no sistema real:</h3>
        <div class="gd-pool-settings">
            <div style="display:flex;gap:8px">
                <div class="gd-item-field"><label>Total mínimo</label><input value="0" disabled></div>
                <div class="gd-item-field"><label>Total máximo</label><input value="5" disabled></div>
            </div>
        </div>
        <div class="gd-annot">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Painel roxo aparece quando modo = "Montagem". <b>Max = itens grátis</b>. Ex: Max=5 → 5 toppings grátis.</span>
        </div>

        <h3>📱 Visão do Cliente</h3>
        <div class="gd-form-block">
            <div class="gd-form-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><circle cx="12" cy="12" r="10"/></svg> Monte seu Açaí</div>
            <div class="gd-form-body" style="padding:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <div style="font-size:13px;font-weight:700;color:#1f2937">Toppings</div>
                    <div style="padding:3px 10px;border-radius:16px;background:#d1fae5;color:#059669;font-size:10px;font-weight:700">3 de 5 grátis</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px">
                    <div style="padding:8px;border:2px solid #10b981;border-radius:10px;background:#f0fdf4;text-align:center"><span style="font-size:16px">🍓</span><div style="font-size:10px;font-weight:600;color:#166534">Morango</div></div>
                    <div style="padding:8px;border:2px solid #10b981;border-radius:10px;background:#f0fdf4;text-align:center"><span style="font-size:16px">🍌</span><div style="font-size:10px;font-weight:600;color:#166534">Banana</div></div>
                    <div style="padding:8px;border:2px solid #10b981;border-radius:10px;background:#f0fdf4;text-align:center"><span style="font-size:16px">🥣</span><div style="font-size:10px;font-weight:600;color:#166534">Granola</div></div>
                    <div style="padding:8px;border:2px solid #e5e7eb;border-radius:10px;text-align:center"><span style="font-size:16px">🍫</span><div style="font-size:10px;font-weight:600;color:#374151">Nutella</div></div>
                    <div style="padding:8px;border:2px solid #e5e7eb;border-radius:10px;text-align:center"><span style="font-size:16px">🥜</span><div style="font-size:10px;font-weight:600;color:#374151">Paçoca</div></div>
                    <div style="padding:8px;border:2px solid #e5e7eb;border-radius:10px;text-align:center"><span style="font-size:16px">🥛</span><div style="font-size:10px;font-weight:600;color:#374151">L. Ninho</div></div>
                </div>
            </div>
        </div>

        <h3>💲 Cobrança</h3>
        <div style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:12px;padding:14px;margin:10px 0">
            <div style="font-size:12px;color:#064e3b;line-height:1.7">
                <code style="background:#bbf7d0;padding:2px 8px;border-radius:5px;font-size:10px">Extra = Σ preços além do pool</code><br><br>
                <b>Pool = 5.</b> Cliente escolhe 7. 5 grátis + 2 pagos (Nutella R$ 4 + Paçoca R$ 2) = <b style="color:#059669">R$ 6,00 extra</b>
            </div>
        </div>

        <div class="gd-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span>O preço cobrado vem do <b>preço de venda</b> no cadastro de <b>Ingredientes</b>.</span>
        </div>
    </div>
</section>
