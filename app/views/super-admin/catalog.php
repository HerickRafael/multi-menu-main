<?php
declare(strict_types=1);
/** @var array $companies */
/** @var array|null $selectedCompany */
/** @var int $selectedCompanyId */
/** @var int $statsTotalProducts */
/** @var int $statsActiveProducts */
/** @var int $statsInactiveProducts */
/** @var int $statsNoImageProducts */
/** @var int $statsTotalCategories */
/** @var array $rows */
/** @var string $superAdminName */
$hideTopbar = false;
include __DIR__ . '/layout.php';
?>

<div class="toolbar" style="margin-bottom:1rem;">
  <div class="toolbar-left">
    <h1>Cardápios e Produtos</h1>
    <p class="sub">Visão por loja com diagnóstico rápido de catálogo.</p>
  </div>
  <div class="toolbar-right">
    <form method="get" action="<?= htmlspecialchars(base_url('superadmin/catalog'), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;gap:.4rem;align-items:center;">
      <select name="company_id" style="max-width:260px;">
        <option value="0">Todas as lojas</option>
        <?php foreach ($companies as $company): ?>
          <?php $cid = (int)($company['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $selectedCompanyId === $cid ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($company['name'] ?? 'Loja') . ' (' . (string)($company['slug'] ?? '-') . ')', ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn secondary sm">Filtrar</button>
      <?php if ($selectedCompanyId > 0): ?>
        <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin/catalog'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($selectedCompany): ?>
  <div class="flash ok">
    Contexto da loja: <strong><?= htmlspecialchars((string)($selectedCompany['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
    · <a href="<?= htmlspecialchars(base_url((string)($selectedCompany['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">abrir cardápio público</a>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Produtos</div>
    <div class="stat-value"><?= (int)$statsTotalProducts ?></div>
    <div class="stat-sub">total no escopo</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Categorias</div>
    <div class="stat-value"><?= (int)$statsTotalCategories ?></div>
    <div class="stat-sub">organização do cardápio</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ativos</div>
    <div class="stat-value" style="color:#166534"><?= (int)$statsActiveProducts ?></div>
    <div class="stat-sub">disponíveis para venda</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Sem imagem</div>
    <div class="stat-value" style="color:#b45309"><?= (int)$statsNoImageProducts ?></div>
    <div class="stat-sub">itens para revisão</div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Loja</th>
          <th>Produto</th>
          <th>Categoria</th>
          <th>Preço</th>
          <th>Status</th>
          <th>Imagem</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:#64748b">Nenhum produto encontrado para o filtro atual.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars((string)($r['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.78rem;color:#64748b">/<?= htmlspecialchars((string)($r['company_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td style="font-weight:600"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['category_name'] ?? 'Sem categoria'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>R$ <?= number_format((float)($r['price'] ?? 0), 2, ',', '.') ?></td>
            <td>
              <?php if (!empty($r['active'])): ?>
                <span class="badge on">Ativo</span>
              <?php else: ?>
                <span class="badge off">Inativo</span>
              <?php endif; ?>
            </td>
            <td><?= !empty($r['image']) ? 'OK' : 'Pendente' ?></td>
            <td>
              <a class="btn secondary sm" href="<?= htmlspecialchars(base_url('superadmin?company_id=' . (int)($r['company_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">Ver loja</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
