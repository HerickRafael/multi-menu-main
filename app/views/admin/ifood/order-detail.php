<?php
/**
 * iFood Order Detail Page
 */
$slug = $activeSlug ?? $company['slug'] ?? '';

$statusLabels = [
    'PLACED' => ['label' => 'Novo Pedido', 'color' => 'warning', 'icon' => 'bell'],
    'CONFIRMED' => ['label' => 'Confirmado', 'color' => 'info', 'icon' => 'check'],
    'READY_TO_PICKUP' => ['label' => 'Pronto para Retirada', 'color' => 'primary', 'icon' => 'box'],
    'DISPATCHED' => ['label' => 'Em Entrega', 'color' => 'purple', 'icon' => 'motorcycle'],
    'CONCLUDED' => ['label' => 'Concluído', 'color' => 'success', 'icon' => 'check-double'],
    'CANCELLED' => ['label' => 'Cancelado', 'color' => 'danger', 'icon' => 'times'],
];

$statusInfo = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'color' => 'secondary', 'icon' => 'question'];
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-dark">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="icon icon-lg icon-shape bg-danger shadow text-center border-radius-xl me-3">
                                    <i class="fas fa-utensils text-white opacity-10"></i>
                                </div>
                                <div>
                                    <h4 class="text-white mb-0">
                                        Pedido iFood #<?= htmlspecialchars($order['ifood_display_id'] ?? substr($order['ifood_order_id'], 0, 8)) ?>
                                    </h4>
                                    <p class="text-white text-sm mb-0 opacity-8">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                        
                                        <?php if ($order['order_timing'] === 'SCHEDULED'): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="fas fa-calendar me-1"></i>Agendado
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-<?= $statusInfo['color'] ?> badge-lg p-2">
                                <i class="fas fa-<?= $statusInfo['icon'] ?> me-1"></i>
                                <?= $statusInfo['label'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Order Info -->
        <div class="col-lg-8">
            <!-- Items -->
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6><i class="fas fa-shopping-cart me-2"></i>Itens do Pedido</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Item</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Qtd</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <?php if (!empty($item['imageUrl'])): ?>
                                                    <div class="me-3">
                                                        <img src="<?= htmlspecialchars($item['imageUrl']) ?>" 
                                                             class="avatar avatar-sm rounded-circle">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($item['name']) ?></h6>
                                                    <?php if (!empty($item['observations'])): ?>
                                                        <p class="text-xs text-warning mb-0">
                                                            <i class="fas fa-sticky-note me-1"></i>
                                                            <?= htmlspecialchars($item['observations']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['options'])): ?>
                                                        <div class="mt-1">
                                                            <?php foreach ($item['options'] as $opt): ?>
                                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                                    <?= htmlspecialchars(($opt['groupName'] ?? '') . ': ' . ($opt['name'] ?? '')) ?>
                                                                    <?php if (($opt['quantity'] ?? 1) > 1): ?>
                                                                        x<?= $opt['quantity'] ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-sm font-weight-bold">
                                                <?= $item['quantity'] ?? 1 ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-end">
                                            <span class="text-secondary text-sm font-weight-bold">
                                                R$ <?= number_format($item['totalPrice'] ?? 0, 2, ',', '.') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Totals -->
                    <hr>
                    <div class="row">
                        <div class="col-6 col-md-8"></div>
                        <div class="col-6 col-md-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-sm">Subtotal:</span>
                                <span class="text-sm font-weight-bold">R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></span>
                            </div>
                            <?php if ($order['delivery_fee'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-sm">Taxa de entrega:</span>
                                    <span class="text-sm font-weight-bold">R$ <?= number_format($order['delivery_fee'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['benefits_total'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span class="text-sm">Descontos:</span>
                                    <span class="text-sm font-weight-bold">- R$ <?= number_format($order['benefits_total'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['additional_fees'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-sm">Taxas adicionais:</span>
                                    <span class="text-sm font-weight-bold">R$ <?= number_format($order['additional_fees'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="h6 mb-0">Total:</span>
                                <span class="h6 mb-0 text-primary">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment -->
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6><i class="fas fa-credit-card me-2"></i>Pagamento</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($order['payments']['methods'])): ?>
                        <?php foreach ($order['payments']['methods'] as $method): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon icon-shape bg-gradient-<?= $method['type'] === 'ONLINE' ? 'success' : 'warning' ?> shadow text-center border-radius-md me-3">
                                    <i class="fas fa-<?= $method['type'] === 'ONLINE' ? 'check' : 'money-bill' ?> text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        <?= $method['type'] === 'ONLINE' ? 'Pago Online' : 'Pagar na Entrega' ?>
                                    </h6>
                                    <p class="text-sm text-muted mb-0">
                                        <?= htmlspecialchars($method['method'] ?? '') ?>
                                        <?php if (!empty($method['card']['brand'])): ?>
                                            - <?= htmlspecialchars($method['card']['brand']) ?>
                                        <?php endif; ?>
                                        - R$ <?= number_format($method['value'] ?? 0, 2, ',', '.') ?>
                                    </p>
                                    <?php if (!empty($method['cash']['changeFor'])): ?>
                                        <p class="text-sm text-warning mb-0">
                                            <i class="fas fa-coins me-1"></i>
                                            Troco para: R$ <?= number_format($method['cash']['changeFor'], 2, ',', '.') ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Informações de pagamento não disponíveis</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6><i class="fas fa-cogs me-2"></i>Ações</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($order['status'] === 'PLACED'): ?>
                            <button class="btn btn-success" onclick="confirmOrder(<?= $order['id'] ?>)">
                                <i class="fas fa-check me-2"></i>Confirmar Pedido
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'CONFIRMED'): ?>
                            <button class="btn btn-primary" onclick="readyOrder(<?= $order['id'] ?>)">
                                <i class="fas fa-box me-2"></i>Marcar como Pronto
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'READY_TO_PICKUP' && $order['delivered_by'] === 'MERCHANT'): ?>
                            <button class="btn btn-info" onclick="dispatchOrder(<?= $order['id'] ?>)">
                                <i class="fas fa-motorcycle me-2"></i>Despachar para Entrega
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['PLACED', 'CONFIRMED'])): ?>
                            <button class="btn btn-outline-danger" onclick="cancelOrder(<?= $order['id'] ?>)">
                                <i class="fas fa-times me-2"></i>Cancelar Pedido
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($order['local_order_id']): ?>
                            <a href="/admin/<?= $slug ?>/orders/show?id=<?= $order['local_order_id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-external-link-alt me-2"></i>Ver Pedido Local
                            </a>
                        <?php endif; ?>
                        
                        <a href="/admin/<?= $slug ?>/ifood/orders" class="btn btn-outline-dark">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Customer -->
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6><i class="fas fa-user me-2"></i>Cliente</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></strong>
                    </p>
                    <?php if ($order['customer_phone']): ?>
                        <p class="text-sm mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>">
                                <?= htmlspecialchars($order['customer_phone']) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delivery Address -->
            <?php if ($order['order_type'] === 'DELIVERY' && !empty($order['delivery_address'])): ?>
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Endereço de Entrega</h6>
                    </div>
                    <div class="card-body">
                        <?php $addr = $order['delivery_address']; ?>
                        <p class="text-sm mb-1">
                            <?= htmlspecialchars($addr['streetName'] ?? '') ?>, 
                            <?= htmlspecialchars($addr['streetNumber'] ?? '') ?>
                        </p>
                        <?php if (!empty($addr['complement'])): ?>
                            <p class="text-sm mb-1"><?= htmlspecialchars($addr['complement']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm mb-1">
                            <?= htmlspecialchars($addr['neighborhood'] ?? '') ?> - 
                            <?= htmlspecialchars($addr['city'] ?? '') ?>/<?= htmlspecialchars($addr['state'] ?? '') ?>
                        </p>
                        <?php if (!empty($addr['postalCode'])): ?>
                            <p class="text-sm mb-1">CEP: <?= htmlspecialchars($addr['postalCode']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($addr['reference'])): ?>
                            <p class="text-sm text-muted mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= htmlspecialchars($addr['reference']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($addr['coordinates'])): ?>
                            <a href="https://www.google.com/maps?q=<?= $addr['coordinates']['latitude'] ?>,<?= $addr['coordinates']['longitude'] ?>" 
                               target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-map me-1"></i>Ver no Mapa
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Delivery Info -->
            <?php if ($order['delivered_by']): ?>
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <h6><i class="fas fa-truck me-2"></i>Entrega</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <span class="badge bg-<?= $order['delivered_by'] === 'IFOOD' ? 'danger' : 'info' ?>">
                                <?= $order['delivered_by'] === 'IFOOD' ? 'Logística iFood' : 'Entrega Própria' ?>
                            </span>
                        </p>
                        <?php if ($order['pickup_code']): ?>
                            <p class="text-sm mb-0">
                                <strong>Código de Retirada:</strong> 
                                <span class="badge bg-dark"><?= htmlspecialchars($order['pickup_code']) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Timeline -->
            <div class="card">
                <div class="card-header pb-0">
                    <h6><i class="fas fa-history me-2"></i>Histórico</h6>
                </div>
                <div class="card-body">
                    <div class="timeline timeline-one-side">
                        <div class="timeline-block mb-3">
                            <span class="timeline-step bg-warning">
                                <i class="fas fa-bell text-white"></i>
                            </span>
                            <div class="timeline-content">
                                <h6 class="text-dark text-sm font-weight-bold mb-0">Pedido Recebido</h6>
                                <p class="text-secondary text-xs mt-1 mb-0">
                                    <?= $order['ifood_created_at'] ? date('d/m H:i', strtotime($order['ifood_created_at'])) : '-' ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($order['confirmed_at']): ?>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step bg-info">
                                    <i class="fas fa-check text-white"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Confirmado</h6>
                                    <p class="text-secondary text-xs mt-1 mb-0">
                                        <?= date('d/m H:i', strtotime($order['confirmed_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['ready_at']): ?>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step bg-primary">
                                    <i class="fas fa-box text-white"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Pronto</h6>
                                    <p class="text-secondary text-xs mt-1 mb-0">
                                        <?= date('d/m H:i', strtotime($order['ready_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['dispatched_at']): ?>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step bg-purple">
                                    <i class="fas fa-motorcycle text-white"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Despachado</h6>
                                    <p class="text-secondary text-xs mt-1 mb-0">
                                        <?= date('d/m H:i', strtotime($order['dispatched_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['concluded_at']): ?>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step bg-success">
                                    <i class="fas fa-check-double text-white"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Concluído</h6>
                                    <p class="text-secondary text-xs mt-1 mb-0">
                                        <?= date('d/m H:i', strtotime($order['concluded_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['cancelled_at']): ?>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step bg-danger">
                                    <i class="fas fa-times text-white"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Cancelado</h6>
                                    <p class="text-secondary text-xs mt-1 mb-0">
                                        <?= date('d/m H:i', strtotime($order['cancelled_at'])) ?>
                                    </p>
                                    <?php if ($order['cancellation_reason']): ?>
                                        <p class="text-xs text-danger mb-0">
                                            <?= htmlspecialchars($order['cancellation_reason']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const slug = '<?= $slug ?>';

function confirmOrder(id) {
    Swal.fire({
        title: 'Confirmar pedido?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiAction(`/admin/${slug}/ifood/orders/${id}/confirm`);
        }
    });
}

function readyOrder(id) {
    Swal.fire({
        title: 'Marcar como pronto?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não'
    }).then((result) => {
        if (result.isConfirmed) {
            apiAction(`/admin/${slug}/ifood/orders/${id}/ready`);
        }
    });
}

function dispatchOrder(id) {
    apiAction(`/admin/${slug}/ifood/orders/${id}/dispatch`);
}

function cancelOrder(id) {
    Swal.fire({ title: 'Carregando motivos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch(`/admin/${slug}/ifood/orders/${id}/cancel-reasons`)
    .then(r => r.json())
    .then(data => {
        Swal.close();
        const reasons = data.reasons || [];
        
        if (reasons.length > 0) {
            const inputOptions = {};
            reasons.forEach(r => { inputOptions[r.cancelCodeId || r.code || r.cancellationCode] = r.description || r.cancelCodeId || r.code; });
            
            Swal.fire({
                title: 'Cancelar pedido?',
                input: 'select',
                inputOptions: inputOptions,
                inputPlaceholder: 'Selecione o motivo',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Cancelar Pedido',
                cancelButtonText: 'Voltar',
                inputValidator: (value) => {
                    if (!value) return 'Selecione um motivo de cancelamento';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/${slug}/ifood/orders/${id}/cancel`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ reason_code: result.value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Cancelado!', '', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Erro', data.error, 'error');
                        }
                    });
                }
            });
        } else {
            // Fallback: free text if no reasons available
            Swal.fire({
                title: 'Cancelar pedido?',
                input: 'text',
                inputPlaceholder: 'Motivo do cancelamento',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Cancelar Pedido',
                cancelButtonText: 'Voltar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/${slug}/ifood/orders/${id}/cancel`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ reason_code: result.value || 'OUTROS' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Cancelado!', '', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Erro', data.error, 'error');
                        }
                    });
                }
            });
        }
    })
    .catch(() => {
        Swal.fire('Erro', 'Não foi possível carregar motivos de cancelamento', 'error');
    });
}

function apiAction(url) {
    Swal.fire({ title: 'Processando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' } })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Sucesso!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Erro', data.error, 'error');
        }
    });
}
</script>
