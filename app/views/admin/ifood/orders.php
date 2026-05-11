<?php
/**
 * iFood Orders List Page
 */
$slug = $activeSlug ?? $company['slug'] ?? '';

$statusLabels = [
    'PLACED' => ['label' => 'Novo', 'color' => 'warning'],
    'CONFIRMED' => ['label' => 'Confirmado', 'color' => 'info'],
    'READY_TO_PICKUP' => ['label' => 'Pronto', 'color' => 'primary'],
    'DISPATCHED' => ['label' => 'Em Entrega', 'color' => 'purple'],
    'CONCLUDED' => ['label' => 'Concluído', 'color' => 'success'],
    'CANCELLED' => ['label' => 'Cancelado', 'color' => 'danger'],
];
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-bag text-danger me-2"></i>
                            Pedidos iFood
                        </h5>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="pollEvents()">
                            <i class="fas fa-sync me-1"></i>Atualizar
                        </button>
                        <a href="/admin/<?= $slug ?>/ifood/config" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-cog me-1"></i>Configurações
                        </a>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card-body pb-0">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/admin/<?= $slug ?>/ifood/orders" 
                           class="btn btn-sm <?= !$currentStatus ? 'btn-primary' : 'btn-outline-primary' ?>">
                            Todos
                        </a>
                        <?php foreach ($statusLabels as $status => $info): ?>
                            <a href="/admin/<?= $slug ?>/ifood/orders?status=<?= $status ?>"
                               class="btn btn-sm <?= $currentStatus === $status ? "btn-{$info['color']}" : "btn-outline-{$info['color']}" ?>">
                                <?= $info['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card-body px-0 pt-0 pb-2">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum pedido encontrado</p>
                            <button type="button" class="btn btn-primary" onclick="pollEvents()">
                                <i class="fas fa-sync me-2"></i>Buscar Pedidos
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pedido</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Cliente</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Valor</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tipo</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <?php $statusInfo = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'color' => 'secondary']; ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">
                                                            <i class="fas fa-utensils text-danger me-2"></i>
                                                            #<?= htmlspecialchars($order['ifood_display_id'] ?? substr($order['ifood_order_id'], 0, 8)) ?>
                                                        </h6>
                                                        <?php if ($order['local_order_id']): ?>
                                                            <p class="text-xs text-secondary mb-0">
                                                                Local: #<?= $order['local_order_id'] ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-sm font-weight-bold mb-0"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge bg-<?= $statusInfo['color'] ?>">
                                                    <?= $statusInfo['label'] ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-sm font-weight-bold">
                                                    R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php if ($order['order_type'] === 'DELIVERY'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-motorcycle me-1"></i>Entrega
                                                    </span>
                                                <?php elseif ($order['order_type'] === 'TAKEOUT'): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-shopping-bag me-1"></i>Retirada
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= $order['order_type'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?= date('d/m H:i', strtotime($order['created_at'])) ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="dropdown">
                                                    <button class="btn btn-link text-secondary mb-0" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="/admin/<?= $slug ?>/ifood/orders/<?= $order['id'] ?>">
                                                                <i class="fas fa-eye me-2"></i>Ver Detalhes
                                                            </a>
                                                        </li>
                                                        <?php if ($order['status'] === 'PLACED'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="confirmOrder(<?= $order['id'] ?>)">
                                                                    <i class="fas fa-check me-2 text-success"></i>Confirmar
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($order['status'] === 'CONFIRMED'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="readyOrder(<?= $order['id'] ?>)">
                                                                    <i class="fas fa-box me-2 text-primary"></i>Marcar Pronto
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($order['status'] === 'READY_TO_PICKUP' && $order['delivered_by'] === 'MERCHANT'): ?>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="dispatchOrder(<?= $order['id'] ?>)">
                                                                    <i class="fas fa-motorcycle me-2 text-info"></i>Despachar
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (in_array($order['status'], ['PLACED', 'CONFIRMED'])): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#" onclick="cancelOrder(<?= $order['id'] ?>)">
                                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($order['local_order_id']): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="/admin/<?= $slug ?>/orders/show?id=<?= $order['local_order_id'] ?>">
                                                                    <i class="fas fa-external-link-alt me-2"></i>Ver no Sistema
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const slug = '<?= $slug ?>';

function pollEvents() {
    Swal.fire({
        title: 'Buscando pedidos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`/admin/${slug}/ifood/poll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.processed > 0) {
            Swal.fire({
                icon: 'success',
                title: 'Novos pedidos!',
                text: `${data.processed} evento(s) processado(s)`,
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Nenhum pedido novo',
            });
        }
    })
    .catch(error => {
        Swal.fire({ icon: 'error', title: 'Erro', text: error.message });
    });
}

function confirmOrder(id) {
    Swal.fire({
        title: 'Confirmar pedido?',
        text: 'O pedido será marcado como em preparo no iFood.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiAction(`/admin/${slug}/ifood/orders/${id}/confirm`, 'Pedido confirmado!');
        }
    });
}

function readyOrder(id) {
    Swal.fire({
        title: 'Marcar como pronto?',
        text: 'Isso notificará o entregador.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, pronto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiAction(`/admin/${slug}/ifood/orders/${id}/ready`, 'Pedido marcado como pronto!');
        }
    });
}

function dispatchOrder(id) {
    Swal.fire({
        title: 'Despachar pedido?',
        text: 'Marcar que o pedido saiu para entrega.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, despachar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiAction(`/admin/${slug}/ifood/orders/${id}/dispatch`, 'Pedido despachado!');
        }
    });
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
                            Swal.fire({ icon: 'success', title: data.message }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.error });
                        }
                    });
                }
            });
        } else {
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
                            Swal.fire({ icon: 'success', title: data.message }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.error });
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

function apiAction(url, successMessage) {
    Swal.fire({
        title: 'Processando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: successMessage }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.error });
        }
    })
    .catch(error => {
        Swal.fire({ icon: 'error', title: 'Erro', text: error.message });
    });
}
</script>
