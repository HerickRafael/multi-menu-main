<?php
/**
 * View: Analytics / Vendas
 * Design moderno e funcional com todos os dados integrados
 * Cores dinâmicas baseadas na configuração do sistema
 */

$title = 'Vendas - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$currentPeriod = $period ?? '30';

// Obter cor primária do sistema
$primaryColor = admin_theme_primary_color($company);

// Função para converter hex para RGB
function hexToRgbAnalytics($hex) {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

$primaryRgb = hexToRgbAnalytics($primaryColor);

// Dados do funil de conversão (dados reais)
$funnel = $conversionFunnel ?? [];
$fTotalCustomers = (int)($funnel['total_customers'] ?? 0);
$fUniqueBuyers = (int)($funnel['unique_buyers'] ?? 0);
$fTotalOrders = (int)($funnel['total_orders'] ?? 0);
$fCompletedOrders = (int)($funnel['completed_orders'] ?? 0);
$fCanceledOrders = (int)($funnel['canceled_orders'] ?? 0);
$fReturning = (int)($funnel['returning_customers'] ?? 0);
$fFirstTimers = (int)($funnel['first_timers'] ?? 0);
$fCompletionRate = (float)($funnel['completion_rate'] ?? 0);
$fRecurrenceRate = (float)($funnel['recurrence_rate'] ?? 0);
$fConversionRate = (float)($funnel['conversion_rate'] ?? 0);
$fAvgTicket = (float)($funnel['avg_ticket'] ?? 0);
$fAvgItems = (float)($funnel['avg_items'] ?? 0);
$fRevenue = (float)($funnel['revenue'] ?? 0);

ob_start();
?>

<style>
.metric-card {
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}
.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
}
.chart-container { position: relative; height: 320px; }
.progress-bar-animated { animation: progressLoad 1s ease-out forwards; }
@keyframes progressLoad { from { width: 0; } }
.comparison-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}
.comparison-badge.positive { background: #ECFDF5; color: #059669; }
.comparison-badge.negative { background: #FEF2F2; color: #DC2626; }
.comparison-badge.neutral { background: #F8FAFC; color: #64748B; }
.period-btn {
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: white;
    border: 1px solid #e2e8f0;
    transition: all 0.15s;
    cursor: pointer;
    text-decoration: none;
}
.period-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
.period-btn.active {
    background: var(--admin-primary-gradient, var(--admin-primary-color));
    color: white;
    border-color: transparent;
    box-shadow: 0 2px 8px var(--admin-primary-soft);
}
.tab-btn {
    position: relative;
    padding: 12px 20px;
    color: #64748b;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
    cursor: pointer;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
}
.tab-btn:hover { color: #475569; background: #f8fafc; }
.tab-btn.active { color: var(--admin-primary-color); font-weight: 600; border-bottom-color: var(--admin-primary-color); }
.funnel-step {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-radius: 12px;
    background: #f8fafc;
    transition: all 0.2s;
}
.funnel-step:hover { background: #f1f5f9; }
.funnel-bar {
    height: 8px;
    border-radius: 4px;
    background: var(--admin-primary-gradient, var(--admin-primary-color));
    transition: width 0.8s ease-out;
}
.top-product-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    transition: background 0.15s;
}
.top-product-row:hover { background: #f8fafc; }
.rank-badge {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}
.rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
.rank-2 { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); color: white; }
.rank-3 { background: linear-gradient(135deg, #cd7c2a 0%, #b45309 100%); color: white; }
.rank-default { background: #f1f5f9; color: #64748b; }
.recent-order-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
}
.recent-order-row:last-child { border-bottom: none; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; }
.status-dot.pending { background: #f59e0b; }
.status-dot.paid { background: #3b82f6; }
.status-dot.completed { background: #10b981; }
.status-dot.canceled { background: #ef4444; }
</style>

<div class="mx-auto max-w-7xl p-4">

<?php
$pageTitle = 'Analytics';
$pageDescription = 'Análise de desempenho e métricas de vendas';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$breadcrumbs = [['label' => 'Analytics']];
$actions = [
    ['label' => 'Financeiro', 'url' => base_url('admin/' . $slug . '/financial/dashboard'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
    ['label' => 'Pedidos', 'url' => base_url('admin/' . $slug . '/orders'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'primary' => true]
];
include __DIR__ . '/../components/page-header.php';
?>

<!-- FILTRO DE PERÍODO -->
<div class="mb-6 flex flex-wrap items-center gap-3">
  <span class="text-sm font-medium text-slate-600">Período:</span>
  <div class="flex flex-wrap gap-2">
    <a href="?period=today" class="period-btn <?= $currentPeriod === 'today' ? 'active' : '' ?>">Hoje</a>
    <a href="?period=7" class="period-btn <?= $currentPeriod === '7' ? 'active' : '' ?>">7 dias</a>
    <a href="?period=30" class="period-btn <?= $currentPeriod === '30' ? 'active' : '' ?>">30 dias</a>
    <a href="?period=month" class="period-btn <?= $currentPeriod === 'month' ? 'active' : '' ?>">Este mês</a>
    <a href="?period=90" class="period-btn <?= $currentPeriod === '90' ? 'active' : '' ?>">90 dias</a>
    <a href="?period=year" class="period-btn <?= $currentPeriod === 'year' ? 'active' : '' ?>">Este ano</a>
  </div>
</div>

<!-- MÉTRICAS PRINCIPAIS -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  
  <!-- Faturamento -->
  <div class="metric-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <?php if ($summary['revenue_change'] != 0): ?>
        <span class="comparison-badge <?= $summary['revenue_change'] > 0 ? 'positive' : 'negative' ?>">
          <?= $summary['revenue_change'] > 0 ? '↑' : '↓' ?> <?= abs(number_format($summary['revenue_change'], 1)) ?>%
        </span>
      <?php else: ?>
        <span class="comparison-badge neutral">—</span>
      <?php endif; ?>
    </div>
    <p class="mb-1 text-sm font-medium text-slate-500">Faturamento</p>
    <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($summary['total_revenue'], 2, ',', '.') ?></p>
    <p class="mt-1 text-xs text-slate-400">vs. período anterior</p>
  </div>

  <!-- Pedidos -->
  <div class="metric-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <?php if ($summary['orders_change'] != 0): ?>
        <span class="comparison-badge <?= $summary['orders_change'] > 0 ? 'positive' : 'negative' ?>">
          <?= $summary['orders_change'] > 0 ? '↑' : '↓' ?> <?= abs($summary['orders_change']) ?>%
        </span>
      <?php else: ?>
        <span class="comparison-badge neutral">—</span>
      <?php endif; ?>
    </div>
    <p class="mb-1 text-sm font-medium text-slate-500">Pedidos</p>
    <p class="text-2xl font-bold text-slate-900"><?= $summary['total_orders'] ?></p>
    <p class="mt-1 text-xs text-slate-400"><?= $summary['cancelled_orders'] ?> cancelados</p>
  </div>

  <!-- Ticket Médio -->
  <div class="metric-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <?php if ($summary['ticket_change'] != 0): ?>
        <span class="comparison-badge <?= $summary['ticket_change'] > 0 ? 'positive' : 'negative' ?>">
          <?= $summary['ticket_change'] > 0 ? '↑' : '↓' ?> <?= abs(number_format($summary['ticket_change'], 1)) ?>%
        </span>
      <?php else: ?>
        <span class="comparison-badge neutral">—</span>
      <?php endif; ?>
    </div>
    <p class="mb-1 text-sm font-medium text-slate-500">Ticket Médio</p>
    <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($summary['avg_ticket'], 2, ',', '.') ?></p>
    <p class="mt-1 text-xs text-slate-400">por pedido</p>
  </div>

  <!-- Clientes -->
  <div class="metric-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <?php if ($summary['customers_change'] != 0): ?>
        <span class="comparison-badge <?= $summary['customers_change'] > 0 ? 'positive' : 'negative' ?>">
          <?= $summary['customers_change'] > 0 ? '↑' : '↓' ?> <?= abs($summary['customers_change']) ?>%
        </span>
      <?php else: ?>
        <span class="comparison-badge neutral">—</span>
      <?php endif; ?>
    </div>
    <p class="mb-1 text-sm font-medium text-slate-500">Clientes</p>
    <p class="text-2xl font-bold text-slate-900"><?= $summary['new_customers'] ?></p>
    <p class="mt-1 text-xs text-slate-400">clientes únicos</p>
  </div>
</div>

<!-- VENDAS DE HOJE -->
<div class="mb-6 rounded-2xl border p-5 shadow-sm" style="border-color: var(--admin-primary-soft); background: linear-gradient(to right, rgba(80, 0, 117, 0.09), rgba(80, 0, 117, 0));">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white shadow-sm">
        <svg class="h-6 w-6" style="color: var(--admin-primary-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>
        </svg>
      </span>
      <div>
        <p class="text-sm font-medium" style="color: var(--admin-primary-color);">Vendas de Hoje</p>
        <p class="text-xs text-slate-500"><?= date('d/m/Y') ?> até <?= date('H:i') ?></p>
      </div>
    </div>
    <div class="flex flex-wrap gap-8">
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900"><?= $todaySales['orders'] ?></p>
        <p class="text-xs text-slate-500">pedidos</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($todaySales['revenue'], 2, ',', '.') ?></p>
        <p class="text-xs text-slate-500">faturado</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($todaySales['avg_ticket'], 2, ',', '.') ?></p>
        <p class="text-xs text-slate-500">ticket médio</p>
      </div>
    </div>
  </div>
</div>

<!-- GRÁFICO PRINCIPAL -->
<div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-6 pt-4">
    <div class="flex flex-wrap gap-1">
      <button class="tab-btn active" onclick="changeMainChart('revenue', this)">Faturamento</button>
      <button class="tab-btn" onclick="changeMainChart('orders', this)">Pedidos</button>
      <button class="tab-btn" onclick="changeMainChart('ticket', this)">Ticket Médio</button>
    </div>
  </div>
  <div class="p-6">
    <div class="chart-container"><canvas id="mainChart"></canvas></div>
  </div>
</div>

<!-- GRID: PRODUTOS + PEDIDOS RECENTES -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
  <!-- Top Produtos -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
        <svg class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        Produtos Mais Vendidos
      </h3>
      <a href="<?= base_url('admin/' . $slug . '/products') ?>" class="text-sm font-medium text-purple-600 hover:text-purple-700">Ver todos →</a>
    </div>
    <div class="space-y-1">
      <?php if (empty($topProducts)): ?>
        <p class="py-8 text-center text-sm text-slate-400">Nenhum produto vendido no período</p>
      <?php else: ?>
        <?php foreach (array_slice($topProducts, 0, 6) as $i => $product): ?>
          <div class="top-product-row">
            <span class="rank-badge <?= $i < 3 ? 'rank-' . ($i + 1) : 'rank-default' ?>"><?= $i + 1 ?></span>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-slate-800"><?= htmlspecialchars($product['product_name']) ?></p>
              <p class="text-xs text-slate-500"><?= $product['order_count'] ?> pedidos</p>
            </div>
            <div class="text-right">
              <p class="font-semibold text-slate-900"><?= $product['total_quantity'] ?>x</p>
              <p class="text-xs text-slate-500">R$ <?= number_format($product['total_revenue'], 2, ',', '.') ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pedidos Recentes -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
        <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Pedidos Recentes
      </h3>
      <a href="<?= base_url('admin/' . $slug . '/orders') ?>" class="text-sm font-medium text-purple-600 hover:text-purple-700">Ver todos →</a>
    </div>
    <div>
      <?php if (empty($recentOrders)): ?>
        <p class="py-8 text-center text-sm text-slate-400">Nenhum pedido encontrado</p>
      <?php else: ?>
        <?php foreach (array_slice($recentOrders, 0, 6) as $order): 
          $status = $order['status'] ?? 'pending';
          $orderNum = $order['order_number'] ?? $order['id'] ?? 0;
        ?>
          <a href="<?= base_url('admin/' . $slug . '/orders/show?id=' . $order['id']) ?>" class="recent-order-row group">
            <div class="flex items-center gap-3">
              <span class="status-dot <?= $status ?>"></span>
              <div>
                <p class="font-medium text-slate-800 group-hover:text-purple-600">#<?= $orderNum ?></p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars($order['customer_name'] ?? 'Cliente') ?></p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-semibold text-slate-900">R$ <?= number_format($order['total'], 2, ',', '.') ?></p>
              <p class="text-xs text-slate-500"><?= date('d/m H:i', strtotime($order['created_at'])) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- GRID: HORÁRIOS + DIAS -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
  <!-- Horários de Pico -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h3 class="text-lg font-semibold text-slate-900">Horários de Pico</h3>
    </div>
    <?php if (!empty($salesByHour)): $bestHour = reset($salesByHour); ?>
      <div class="mb-4 rounded-xl bg-gradient-to-r from-purple-50 to-violet-50 p-4">
        <p class="text-sm font-medium text-purple-600">🔥 Melhor horário</p>
        <p class="text-xl font-bold text-slate-900"><?= sprintf('%02d:00 - %02d:00', $bestHour['hour'], ($bestHour['hour'] + 1) % 24) ?></p>
        <p class="text-sm text-slate-600"><?= $bestHour['orders'] ?> pedidos • R$ <?= number_format($bestHour['revenue'] ?? 0, 2, ',', '.') ?></p>
      </div>
      <div class="space-y-2">
        <?php $maxOrders = max(array_column($salesByHour, 'orders'));
        foreach (array_slice($salesByHour, 0, 5) as $hour): $pct = $maxOrders > 0 ? ($hour['orders'] / $maxOrders) * 100 : 0; ?>
        <div class="flex items-center gap-3">
          <span class="w-16 text-sm font-medium text-slate-600"><?= sprintf('%02d:00', $hour['hour']) ?></span>
          <div class="h-6 flex-1 overflow-hidden rounded-lg bg-slate-100">
            <div class="funnel-bar h-full progress-bar-animated" style="width: <?= $pct ?>%"></div>
          </div>
          <span class="w-12 text-right text-sm font-semibold text-slate-700"><?= $hour['orders'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="py-8 text-center text-sm text-slate-400">Sem dados de horários</p>
    <?php endif; ?>
  </div>

  <!-- Dias da Semana -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h3 class="text-lg font-semibold text-slate-900">Dias da Semana</h3>
    </div>
    <?php if (!empty($salesByWeekday)): 
      usort($salesByWeekday, fn($a, $b) => $b['orders'] <=> $a['orders']);
      $bestDay = reset($salesByWeekday);
      usort($salesByWeekday, fn($a, $b) => $a['weekday'] <=> $b['weekday']);
    ?>
      <div class="mb-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 p-4">
        <p class="text-sm font-medium text-emerald-600">📈 Melhor dia</p>
        <p class="text-xl font-bold text-slate-900"><?= $bestDay['weekday_name'] ?></p>
        <p class="text-sm text-slate-600"><?= $bestDay['orders'] ?> pedidos • R$ <?= number_format($bestDay['revenue'] ?? 0, 2, ',', '.') ?></p>
      </div>
      <div class="flex h-48 items-stretch justify-between gap-2">
        <?php $maxDayOrders = max(array_column($salesByWeekday, 'orders'));
        foreach ($salesByWeekday as $day): 
          $heightPct = $maxDayOrders > 0 ? ($day['orders'] / $maxDayOrders) * 100 : 5;
          $isBest = $day['orders'] == $bestDay['orders'];
        ?>
        <div class="flex flex-1 flex-col items-center gap-1">
          <span class="text-xs font-bold <?= $isBest ? 'text-purple-600' : 'text-slate-600' ?>"><?= $day['orders'] ?></span>
          <div class="flex w-full flex-1 items-end">
            <div class="w-full rounded-t-lg transition-all duration-500 <?= $isBest ? 'admin-gradient-bg' : 'bg-slate-200' ?>" style="height: <?= max($heightPct, 5) ?>%"></div>
          </div>
          <span class="text-xs font-medium <?= $isBest ? 'text-purple-600' : 'text-slate-500' ?>"><?= mb_substr($day['weekday_name'], 0, 3) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="py-8 text-center text-sm text-slate-400">Sem dados de dias</p>
    <?php endif; ?>
  </div>
</div>

<!-- FORMAS DE PAGAMENTO -->
<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="mb-6 flex items-center gap-2">
    <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h3 class="text-lg font-semibold text-slate-900">Formas de Pagamento</h3>
  </div>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($paymentMethods as $method): ?>
    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
      <div class="mb-3 flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
          </svg>
        </span>
        <div>
          <p class="font-semibold text-slate-900"><?= $method['payment_method'] ?></p>
          <p class="text-xs text-slate-500"><?= $method['percentage'] ?>% do total</p>
        </div>
      </div>
      <div class="flex items-baseline justify-between">
        <span class="text-2xl font-bold text-slate-900"><?= $method['orders'] ?></span>
        <span class="text-sm text-slate-500">pedidos</span>
      </div>
      <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full admin-gradient-bg progress-bar-animated" style="width: <?= $method['percentage'] ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- FUNIL DE CONVERSÃO (DADOS REAIS) -->
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-2">
      <svg class="h-5 w-5" style="color:var(--admin-primary-color)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h3 class="text-lg font-semibold text-slate-900">Funil de Conversão</h3>
    </div>
    <div class="flex items-center gap-2">
      <span class="rounded-full px-3 py-1 text-sm font-semibold" style="background:rgba(<?= $primaryRgb['r'] ?>,<?= $primaryRgb['g'] ?>,<?= $primaryRgb['b'] ?>,0.1);color:var(--admin-primary-color)"><?= $fConversionRate ?>% conversão</span>
      <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700"><?= $fCompletionRate ?>% conclusão</span>
    </div>
  </div>

  <!-- Funil visual -->
  <div class="space-y-3">
    <?php
    // Steps do funil com dados reais
    $maxValue = max($fTotalCustomers, 1);
    $funnelSteps = [
        [
            'label' => 'Clientes cadastrados',
            'value' => $fTotalCustomers,
            'detail' => $funnel['customers_registered_period'] > 0 ? '+' . $funnel['customers_registered_period'] . ' no período' : 'base total',
            'pct' => 100,
            'color' => 'var(--admin-primary-color)',
            'bgColor' => "rgba({$primaryRgb['r']},{$primaryRgb['g']},{$primaryRgb['b']},0.12)",
            'textColor' => 'var(--admin-primary-color)',
            'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'
        ],
        [
            'label' => 'Clientes que compraram',
            'value' => $fUniqueBuyers,
            'detail' => $fFirstTimers > 0 ? $fFirstTimers . ' novos · ' . $fReturning . ' recorrentes' : '',
            'pct' => $fTotalCustomers > 0 ? round(($fUniqueBuyers / $fTotalCustomers) * 100, 1) : 0,
            'color' => '#3b82f6',
            'bgColor' => 'rgba(59,130,246,0.12)',
            'textColor' => '#3b82f6',
            'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>'
        ],
        [
            'label' => 'Pedidos realizados',
            'value' => $fTotalOrders,
            'detail' => $fAvgItems > 0 ? 'Média ' . number_format($fAvgItems, 1, ',', '.') . ' itens/pedido' : '',
            'pct' => $fTotalCustomers > 0 ? round(($fTotalOrders / $fTotalCustomers) * 100, 1) : 0,
            'color' => '#f59e0b',
            'bgColor' => 'rgba(245,158,11,0.12)',
            'textColor' => '#f59e0b',
            'icon' => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'
        ],
        [
            'label' => 'Pedidos concluídos',
            'value' => $fCompletedOrders,
            'detail' => $fCanceledOrders > 0 ? $fCanceledOrders . ' cancelado' . ($fCanceledOrders > 1 ? 's' : '') : 'nenhum cancelamento',
            'pct' => $fTotalOrders > 0 ? round(($fCompletedOrders / $fTotalOrders) * 100, 1) : 0,
            'color' => '#10b981',
            'bgColor' => 'rgba(16,185,129,0.12)',
            'textColor' => '#10b981',
            'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
        ],
    ];
    ?>

    <?php foreach ($funnelSteps as $i => $step): ?>
    <div class="funnel-step" <?= $i === count($funnelSteps) - 1 ? 'style="background:' . $step['bgColor'] . '"' : '' ?>>
      <div class="flex h-12 w-12 items-center justify-center rounded-xl" style="background:<?= $step['bgColor'] ?>;color:<?= $step['textColor'] ?>">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $step['icon'] ?></svg>
      </div>
      <div class="min-w-0 flex-1">
        <div class="mb-1 flex items-center justify-between">
          <div>
            <span class="font-medium text-slate-900"><?= $step['label'] ?></span>
            <?php if ($step['detail']): ?>
              <span class="ml-2 text-xs text-slate-400"><?= $step['detail'] ?></span>
            <?php endif; ?>
          </div>
          <span class="text-sm font-semibold text-slate-600"><?= number_format($step['value']) ?></span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
          <div class="h-full rounded-full progress-bar-animated" style="width: <?= max($step['pct'], 2) ?>%; background: <?= $step['color'] ?>"></div>
        </div>
      </div>
      <span class="w-16 text-right text-sm font-bold" style="color:<?= $step['textColor'] ?>"><?= $step['pct'] ?>%</span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- KPIs complementares -->
  <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
      <div class="text-xs font-medium text-slate-500">Taxa de Recompra</div>
      <div class="mt-1 text-lg font-bold" style="color:var(--admin-primary-color)"><?= $fRecurrenceRate ?>%</div>
      <div class="text-xs text-slate-400"><?= $fReturning ?> de <?= $fUniqueBuyers ?> clientes</div>
    </div>
    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
      <div class="text-xs font-medium text-slate-500">Ticket Médio</div>
      <div class="mt-1 text-lg font-bold text-slate-900">R$ <?= number_format($fAvgTicket, 2, ',', '.') ?></div>
      <div class="text-xs text-slate-400"><?= number_format($fAvgItems, 1, ',', '.') ?> itens/pedido</div>
    </div>
    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
      <div class="text-xs font-medium text-slate-500">Faturamento</div>
      <div class="mt-1 text-lg font-bold text-emerald-600">R$ <?= number_format($fRevenue, 2, ',', '.') ?></div>
      <div class="text-xs text-slate-400"><?= $fCompletedOrders ?> pedidos concluídos</div>
    </div>
    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
      <div class="text-xs font-medium text-slate-500">Cancelamentos</div>
      <div class="mt-1 text-lg font-bold <?= $fCanceledOrders > 0 ? 'text-red-500' : 'text-emerald-600' ?>"><?= $funnel['cancel_rate'] ?? 0 ?>%</div>
      <div class="text-xs text-slate-400"><?= $fCanceledOrders ?> de <?= $fTotalOrders ?> pedidos</div>
    </div>
  </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Cor primária do sistema
const primaryColor = '<?= $primaryColor ?>';
const primaryRgb = { r: <?= $primaryRgb['r'] ?>, g: <?= $primaryRgb['g'] ?>, b: <?= $primaryRgb['b'] ?> };

const salesByDayData = <?= json_encode($salesByDay ?: []) ?>;
const labels = salesByDayData.map(d => {
    const date = new Date(d.date + 'T00:00:00');
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
});
const ordersData = salesByDayData.map(d => parseInt(d.orders || 0));
const revenueData = salesByDayData.map(d => parseFloat(d.revenue || 0));
const ticketData = salesByDayData.map(d => {
    const orders = parseInt(d.orders || 0);
    return orders > 0 ? parseFloat(d.revenue || 0) / orders : 0;
});

const ctx = document.getElementById('mainChart').getContext('2d');
const gradientFill = ctx.createLinearGradient(0, 0, 0, 300);
gradientFill.addColorStop(0, `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0.2)`);
gradientFill.addColorStop(1, `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0)`);

let mainChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Faturamento',
            data: revenueData,
            borderColor: primaryColor,
            backgroundColor: gradientFill,
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointBackgroundColor: primaryColor,
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'white',
                titleColor: '#0f172a',
                bodyColor: '#475569',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        const value = context.parsed.y;
                        if (context.dataset.label === 'Faturamento' || context.dataset.label === 'Ticket Médio') {
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        }
                        return value + ' pedidos';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: { 
                    color: '#94a3b8',
                    callback: function(value) {
                        if (this.chart.data.datasets[0].label === 'Faturamento' || this.chart.data.datasets[0].label === 'Ticket Médio') {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                        return value;
                    }
                }
            },
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});

function changeMainChart(type, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    let newData, label;
    switch(type) {
        case 'orders': newData = ordersData; label = 'Pedidos'; break;
        case 'revenue': newData = revenueData; label = 'Faturamento'; break;
        case 'ticket': newData = ticketData; label = 'Ticket Médio'; break;
    }
    mainChart.data.datasets[0].data = newData;
    mainChart.data.datasets[0].label = label;
    mainChart.update('active');
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
