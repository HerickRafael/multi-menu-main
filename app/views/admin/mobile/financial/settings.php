<?php
/**
 * Financial Settings - Mobile
 */
$activeNav = 'settings';
$s = $settings ?? [];
$showSuccess = !empty($success);
$showError = !empty($error);

ob_start();
?>

<style>
.fs-page { padding: 1rem; padding-bottom: 6rem; }
.fs-back { display: inline-flex; align-items: center; gap: 0.375rem; color: var(--text-secondary, #64748b); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.75rem; }
.fs-title { font-size: 1.125rem; font-weight: 700; color: var(--text-primary, #1e293b); margin-bottom: 0.25rem; }
.fs-subtitle { font-size: 0.8125rem; color: var(--text-secondary, #64748b); margin-bottom: 1rem; }

/* Alert */
.fs-alert { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border-radius: 0.75rem; margin-bottom: 0.75rem; font-size: 0.8125rem; }
.fs-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.fs-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Section */
.fs-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; margin-bottom: 0.75rem; }
.fs-section-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem; font-weight: 600; color: var(--text-primary, #1e293b); margin-bottom: 0.75rem; }
.fs-section-title svg { width: 1.125rem; height: 1.125rem; color: var(--text-secondary, #64748b); }
.fs-section-desc { font-size: 0.75rem; color: var(--text-secondary, #64748b); margin-bottom: 0.625rem; }

/* Field */
.fs-field { margin-bottom: 0.875rem; }
.fs-label { display: block; font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); margin-bottom: 0.375rem; }
.fs-input { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; background: #fff; }
.fs-input:focus { outline: none; border-color: var(--primary, #4361ee); }
.fs-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.fs-hint { font-size: 0.6875rem; color: var(--text-secondary, #64748b); margin-top: 0.25rem; }

.fs-input-prefix { position: relative; }
.fs-input-prefix span { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.875rem; color: var(--text-secondary, #64748b); }
.fs-input-prefix input { padding-left: 2.25rem; }

/* Actions */
.fs-actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; margin-bottom: 1rem; }
.fs-btn { flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-size: 0.9375rem; font-weight: 500; border: none; cursor: pointer; text-align: center; text-decoration: none; }
.fs-btn-cancel { background: #f1f5f9; color: var(--text-primary, #1e293b); display: flex; align-items: center; justify-content: center; }
.fs-btn-save { background: var(--primary, #4361ee); color: #fff; }

/* Recalculate section */
.fs-recalc { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 0.875rem; }
.fs-recalc-text { font-size: 0.8125rem; font-weight: 500; color: var(--text-primary, #1e293b); }
.fs-recalc-desc { font-size: 0.6875rem; color: var(--text-secondary, #64748b); }
.fs-recalc-btn { padding: 0.5rem 0.875rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.625rem; font-size: 0.8125rem; cursor: pointer; color: var(--text-primary, #1e293b); }

/* Toast */
.fs-toast { display: none; position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
</style>

<div class="fs-page">
    <a href="/financial" class="fs-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="12" x2="5" y2="12" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 19 5 12 12 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Financeiro
    </a>

    <h1 class="fs-title">Configurações Financeiras</h1>
    <p class="fs-subtitle">Configure taxas e custos padrão</p>

    <?php if ($showSuccess): ?>
    <div class="fs-alert fs-alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Configurações salvas com sucesso!
    </div>
    <?php endif; ?>

    <?php if ($showError): ?>
    <div class="fs-alert fs-alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Erro ao salvar configurações.
    </div>
    <?php endif; ?>

    <form action="/financial/settings" method="POST">
        <!-- Impostos -->
        <div class="fs-section">
            <div class="fs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="5" x2="5" y2="19" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                Impostos
            </div>
            <div class="fs-field">
                <label class="fs-label">Taxa de Imposto Padrão (%) <a href="/guide/financial#config" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                <input type="number" name="default_tax_percentage" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['default_tax_percentage'] ?? '0') ?>">
                <div class="fs-hint">ICMS, ISS, etc.</div>
            </div>
        </div>

        <!-- Taxas de Canais -->
        <div class="fs-section">
            <div class="fs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="19" y1="5" x2="5" y2="19" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                Taxas de Canais de Venda
            </div>
            <div class="fs-row">
                <div class="fs-field">
                    <label class="fs-label">Taxa iFood (%) <a href="/guide/financial#config" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                    <input type="number" name="ifood_fee_percentage" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['ifood_fee_percentage'] ?? '0') ?>">
                </div>
                <div class="fs-field">
                    <label class="fs-label">Taxa Rappi (%)</label>
                    <input type="number" name="rappi_fee_percentage" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['rappi_fee_percentage'] ?? '0') ?>">
                </div>
            </div>
            <div class="fs-row">
                <div class="fs-field">
                    <label class="fs-label">Taxa UberEats (%)</label>
                    <input type="number" name="ubereats_fee_percentage" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['ubereats_fee_percentage'] ?? '0') ?>">
                    <div class="fs-hint">UberEats e similares</div>
                </div>
                <div class="fs-field">
                    <label class="fs-label">Taxa Delivery Próprio (%)</label>
                    <input type="number" name="own_delivery_fee_percentage" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['own_delivery_fee_percentage'] ?? '0') ?>">
                    <div class="fs-hint">Entrega própria</div>
                </div>
            </div>
        </div>

        <!-- Custos e Metas -->
        <div class="fs-section">
            <div class="fs-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Custos e Metas
            </div>
            <div class="fs-row">
                <div class="fs-field">
                    <label class="fs-label">Custo Mão de Obra/h (R$) <a href="/guide/financial#config" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                    <div class="fs-input-prefix">
                        <span>R$</span>
                        <input type="number" name="hourly_labor_cost" class="fs-input" style="padding-left:2.25rem;" step="0.01" min="0" value="<?= htmlspecialchars($s['hourly_labor_cost'] ?? '0') ?>">
                    </div>
                </div>
                <div class="fs-field">
                    <label class="fs-label">Margem de Lucro (%) <a href="/guide/financial#config" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;border:1.5px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:10px;font-weight:700;text-decoration:none;margin-left:auto;line-height:1;" title="Ajuda">?</a></label>
                    <input type="number" name="target_profit_margin" class="fs-input" step="0.01" min="0" max="100" value="<?= htmlspecialchars($s['target_profit_margin'] ?? '30') ?>">
                </div>
            </div>
            <div class="fs-row">
                <div class="fs-field">
                    <label class="fs-label">Meta Faturamento (R$)</label>
                    <div class="fs-input-prefix">
                        <span>R$</span>
                        <input type="number" name="monthly_revenue_goal" class="fs-input" style="padding-left:2.25rem;" step="0.01" min="0" value="<?= htmlspecialchars($s['monthly_revenue_goal'] ?? '0') ?>">
                    </div>
                </div>
                <div class="fs-field">
                    <label class="fs-label">Meta Lucro (R$)</label>
                    <div class="fs-input-prefix">
                        <span>R$</span>
                        <input type="number" name="monthly_profit_goal" class="fs-input" style="padding-left:2.25rem;" step="0.01" min="0" value="<?= htmlspecialchars($s['monthly_profit_goal'] ?? '0') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="fs-actions">
            <a href="/financial" class="fs-btn fs-btn-cancel">Cancelar</a>
            <button type="submit" class="fs-btn fs-btn-save">Salvar</button>
        </div>
    </form>

    <!-- Recalculate -->
    <div class="fs-recalc">
        <div>
            <div class="fs-recalc-text">Atualizar Custos</div>
            <div class="fs-recalc-desc">Recalcula custos de todos os produtos</div>
        </div>
        <button type="button" class="fs-recalc-btn" onclick="recalculate()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:inline; vertical-align:middle; margin-right:0.25rem;">
            Atualizar
        </button>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fs-toast"></div>

<script>
function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.display = 'block';
    t.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    t.style.color = type === 'success' ? '#166534' : '#991b1b';
    setTimeout(function() { t.style.display = 'none'; }, 3000);
}

function recalculate() {
    if (!confirm('Recalcular custos de TODOS os produtos ativos?')) return;

    fetch('/financial/recalculate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast('Erro: ' + (data.message || 'desconhecido'), 'error');
        }
    })
    .catch(function(err) {
        showToast('Erro: ' + err.message, 'error');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
