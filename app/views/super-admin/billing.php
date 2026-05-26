<?php
declare(strict_types=1);

/** @var array $companies */
/** @var array|null $selectedCompany */
/** @var int $selectedCompanyId */
/** @var array $plans */
/** @var array|null $selectedPlan */
/** @var string $selectedPlanCode */
/** @var array $subscriptions */
/** @var array|null $currentSubscription */
/** @var array $invoices */
/** @var array $usageLimits */
/** @var array $statusSummary */
/** @var string $subscriptionStatusFilter */
/** @var array $allowedStatuses */

include __DIR__ . '/layout.php';
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Billing SaaS</h1>
    <p class="sub">Assinaturas, faturas e limites por tenant.</p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Ativas</div><div class="stat-value"><?= (int)($statusSummary['active'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Trialing</div><div class="stat-value"><?= (int)($statusSummary['trialing'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Past Due</div><div class="stat-value"><?= (int)($statusSummary['past_due'] ?? 0) ?></div></div>
  <div class="stat-card"><div class="stat-label">Canceled</div><div class="stat-value"><?= (int)($statusSummary['canceled'] ?? 0) ?></div></div>
</div>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <form method="get" action="<?= htmlspecialchars(base_url('superadmin/billing'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label>Loja</label>
      <select name="company_id" style="min-width:260px;">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $id = (int)($company['id'] ?? 0); ?>
          <option value="<?= $id ?>" <?= $selectedCompanyId === $id ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($company['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars((string)($company['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Status assinatura</label>
      <select name="subscription_status" style="min-width:180px;">
        <option value="">Todos</option>
        <?php foreach ($allowedStatuses as $status): ?>
          <option value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>" <?= $subscriptionStatusFilter === $status ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn secondary sm" type="submit">Filtrar</button>
  </form>
</div>

<div class="card" style="margin-bottom:1rem;">
  <div class="section-title" style="margin-top:0;">Planos</div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;margin-bottom:1rem;">
    <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/plans/save'), ENT_QUOTES, 'UTF-8') ?>" class="card" style="padding:1rem;">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <h2 style="margin-bottom:.65rem;"><?= !empty($selectedPlan) ? 'Editar plano' : 'Novo plano' ?></h2>
      <div class="row">
        <label>Código</label>
        <input type="text" name="code" value="<?= htmlspecialchars((string)($selectedPlan['code'] ?? $selectedPlanCode ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="row">
        <label>Nome</label>
        <input type="text" name="name" value="<?= htmlspecialchars((string)($selectedPlan['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="row">
        <label>Descrição</label>
        <textarea name="description"><?= htmlspecialchars((string)($selectedPlan['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;max-width:480px;">
        <div>
          <label>Mensal</label>
          <input type="number" step="0.01" min="0" name="price_monthly" value="<?= htmlspecialchars((string)($selectedPlan['price_monthly'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div>
          <label>Anual</label>
          <input type="number" step="0.01" min="0" name="price_yearly" value="<?= htmlspecialchars((string)($selectedPlan['price_yearly'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
      </div>
      <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;max-width:480px;">
        <div>
          <label>Moeda</label>
          <input type="text" name="currency" maxlength="3" value="<?= htmlspecialchars((string)($selectedPlan['currency'] ?? 'BRL'), ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div style="display:flex;align-items:flex-end;padding-bottom:.2rem;">
          <label class="chk" style="margin-bottom:0;">
            <input type="checkbox" name="is_active" value="1" <?= empty($selectedPlan) || !empty($selectedPlan['is_active']) ? 'checked' : '' ?>>
            Ativo
          </label>
        </div>
      </div>
      <div class="row">
        <label>Limites JSON</label>
        <textarea name="limits_json" placeholder='{"orders":{"hard_limit":1000,"soft_limit":800}}'><?= htmlspecialchars(is_array($selectedPlan['limits_json'] ?? null) ? json_encode($selectedPlan['limits_json'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string)($selectedPlan['limits_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <div class="hint">Use um JSON com recursos e limites por recurso.</div>
      </div>
      <button class="btn secondary sm" type="submit">Salvar plano</button>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>Código</th>
        <th>Nome</th>
        <th>Mensal</th>
        <th>Anual</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($plans)): ?>
        <tr><td colspan="6" style="text-align:center;padding:1.25rem;color:#64748b;">Nenhum plano cadastrado.</td></tr>
      <?php else: ?>
        <?php foreach ($plans as $plan): ?>
          <tr>
            <td><?= htmlspecialchars((string)($plan['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($plan['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($plan['currency'] ?? 'BRL'), ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($plan['price_monthly'] ?? 0), 2, ',', '.') ?></td>
            <td><?= htmlspecialchars((string)($plan['currency'] ?? 'BRL'), ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($plan['price_yearly'] ?? 0), 2, ',', '.') ?></td>
            <td>
              <?php if (!empty($plan['is_active'])): ?>
                <span class="badge on">Ativo</span>
              <?php else: ?>
                <span class="badge off">Inativo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="actions">
                <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/billing?plan_code=' . urlencode((string)($plan['code'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/plans/' . rawurlencode((string)($plan['code'] ?? '')) . '/toggle'), ENT_QUOTES, 'UTF-8') ?>">
                  <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                  <button class="btn secondary sm" type="submit"><?= !empty($plan['is_active']) ? 'Desativar' : 'Ativar' ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($selectedCompany): ?>
  <div class="card" style="margin-bottom:1rem;">
    <h2 style="margin-bottom:.75rem;">Acoes para <?= htmlspecialchars((string)($selectedCompany['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">
      <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/subscriptions/create'), ENT_QUOTES, 'UTF-8') ?>" class="card" style="padding:1rem;">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <input type="hidden" name="company_id" value="<?= (int)$selectedCompanyId ?>">
        <h2 style="margin-bottom:.65rem;">Nova assinatura</h2>
        <div class="row">
          <label>Plano</label>
          <select name="plan_id" required>
            <?php foreach ($plans as $plan): ?>
              <option value="<?= (int)($plan['id'] ?? 0) ?>">
                <?= htmlspecialchars((string)($plan['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                (<?= htmlspecialchars((string)($plan['currency'] ?? 'BRL'), ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($plan['price_monthly'] ?? 0), 2, ',', '.') ?>/mes)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <label>Status</label>
          <select name="status" required>
            <option value="active">active</option>
            <option value="trialing">trialing</option>
            <option value="past_due">past_due</option>
            <option value="incomplete">incomplete</option>
            <option value="paused">paused</option>
            <option value="canceled">canceled</option>
          </select>
        </div>
        <div class="row">
          <label>Periodo (meses)</label>
          <input type="number" min="1" max="24" name="billing_months" value="1" required>
        </div>
        <div class="row">
          <label>Trial (dias)</label>
          <input type="number" min="0" max="365" name="trial_days" value="0">
        </div>
        <button class="btn secondary sm" type="submit">Criar assinatura</button>
      </form>

      <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/invoices/create-draft'), ENT_QUOTES, 'UTF-8') ?>" class="card" style="padding:1rem;">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <input type="hidden" name="company_id" value="<?= (int)$selectedCompanyId ?>">
        <h2 style="margin-bottom:.65rem;">Nova fatura manual</h2>
        <div class="row">
          <label>Valor total</label>
          <input type="number" step="0.01" min="0.01" name="amount_total" required>
        </div>
        <div class="row">
          <label>Moeda</label>
          <input type="text" name="currency" value="BRL" maxlength="3" required>
        </div>
        <div class="row">
          <label>Vencimento</label>
          <input type="date" name="due_date">
        </div>
        <button class="btn secondary sm" type="submit">Criar fatura</button>
      </form>
    </div>

    <?php if (!empty($currentSubscription)): ?>
      <div class="section-title">Assinatura Atual</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.7rem;">
        <div class="stat-card"><div class="stat-label">ID</div><div class="stat-value" style="font-size:1.1rem;">#<?= (int)($currentSubscription['id'] ?? 0) ?></div></div>
        <div class="stat-card"><div class="stat-label">Plano</div><div class="stat-value" style="font-size:1.1rem;"><?= htmlspecialchars((string)($currentSubscription['plan_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="stat-card"><div class="stat-label">Status</div><div class="stat-value" style="font-size:1.1rem;"><?= htmlspecialchars((string)($currentSubscription['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="stat-card"><div class="stat-label">Periodo fim</div><div class="stat-value" style="font-size:1.1rem;"><?= htmlspecialchars((string)($currentSubscription['current_period_end'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
      </div>

      <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/subscriptions/' . (int)($currentSubscription['id'] ?? 0) . '/status'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;margin-top:.9rem;">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <div>
          <label>Novo status</label>
          <select name="status" required>
            <option value="active">active</option>
            <option value="trialing">trialing</option>
            <option value="past_due">past_due</option>
            <option value="paused">paused</option>
            <option value="incomplete">incomplete</option>
            <option value="canceled">canceled</option>
          </select>
        </div>
        <button class="btn secondary sm" type="submit">Atualizar status</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card" style="margin-bottom:1rem;">
  <div class="section-title" style="margin-top:0;">Assinaturas Recentes</div>
  <div class="table-wrap">
    <table>
      <thead>
      <tr>
        <th>ID</th>
        <th>Loja</th>
        <th>Plano</th>
        <th>Status</th>
        <th>Periodo fim</th>
      </tr>
      </thead>
      <tbody>
      <?php if (empty($subscriptions)): ?>
        <tr><td colspan="5" style="text-align:center;padding:2rem;color:#64748b;">Sem assinaturas para os filtros informados.</td></tr>
      <?php else: ?>
        <?php foreach ($subscriptions as $row): ?>
          <tr>
            <td>#<?= (int)($row['id'] ?? 0) ?></td>
            <td>
              <?= htmlspecialchars((string)($row['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
              <span style="color:#64748b;font-size:.75rem;"><?= htmlspecialchars((string)($row['company_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><?= htmlspecialchars((string)($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['current_period_end'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($selectedCompany): ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start;">
    <div class="card">
      <div class="section-title" style="margin-top:0;">Faturas da Loja</div>
      <div class="table-wrap">
        <table>
          <thead>
          <tr>
            <th>ID</th>
            <th>Numero</th>
            <th>Status</th>
            <th>Total</th>
            <th>Acoes</th>
          </tr>
          </thead>
          <tbody>
          <?php if (empty($invoices)): ?>
            <tr><td colspan="5" style="text-align:center;padding:1.2rem;color:#64748b;">Sem faturas para a loja.</td></tr>
          <?php else: ?>
            <?php foreach ($invoices as $invoice): ?>
              <tr>
                <td>#<?= (int)($invoice['id'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($invoice['currency'] ?? 'BRL'), ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($invoice['amount_total'] ?? 0), 2, ',', '.') ?></td>
                <td>
                  <?php if ((string)($invoice['status'] ?? '') !== 'paid'): ?>
                    <form method="post" action="<?= htmlspecialchars(base_url('superadmin/billing/invoices/' . (int)($invoice['id'] ?? 0) . '/mark-paid'), ENT_QUOTES, 'UTF-8') ?>">
                      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                      <button class="btn secondary sm" type="submit">Marcar paga</button>
                    </form>
                  <?php else: ?>
                    <span class="badge on">Paga</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="section-title" style="margin-top:0;">Usage Limits</div>
      <div class="table-wrap">
        <table>
          <thead>
          <tr>
            <th>Recurso</th>
            <th>Uso</th>
            <th>Soft</th>
            <th>Hard</th>
            <th>Reset</th>
          </tr>
          </thead>
          <tbody>
          <?php if (empty($usageLimits)): ?>
            <tr><td colspan="5" style="text-align:center;padding:1.2rem;color:#64748b;">Sem limites configurados para a loja.</td></tr>
          <?php else: ?>
            <?php foreach ($usageLimits as $limit): ?>
              <tr>
                <td><?= htmlspecialchars((string)($limit['resource_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)($limit['current_usage'] ?? 0) ?></td>
                <td><?= (int)($limit['soft_limit'] ?? 0) ?></td>
                <td><?= (int)($limit['hard_limit'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($limit['reset_period'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/layout_end.php';
