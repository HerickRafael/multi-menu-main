<?php

declare(strict_types=1);

class OrderListService
{
    /**
     * Lista pedidos com paginacao e filtros no contexto admin.
     *
     * @return array{orders: array, pagination: array, filters: array}
     */
    public static function listForCompany(PDO $db, int $companyId, array $filters): array
    {
        $status = $filters['status'] ?? null;
        $status = $status === '' ? null : $status;

        $source = $filters['source'] ?? null;
        $source = $source === '' ? null : $source;

        $excludeSource = $filters['exclude_source'] ?? null;
        $excludeSource = $excludeSource === '' ? null : $excludeSource;

        $search = $filters['search'] ?? null;
        $search = $search === '' ? null : $search;

        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = (int)($filters['per_page'] ?? 10);
        $allowedPerPage = [10, 25, 50, 100, 200, 500];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $offset = ($page - 1) * $perPage;

        $totalOrders = Order::countByCompany($db, $companyId, $status, $search, $source, $excludeSource);
        $totalPages = max(1, (int)ceil($totalOrders / $perPage));
        $orders = Order::listByCompany($db, $companyId, $status, $perPage, $offset, $search, $source, $excludeSource);

        return [
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalOrders,
                'totalPages' => $totalPages,
            ],
            'filters' => [
                'status' => $status,
                'source' => $source,
                'search' => $search,
            ],
        ];
    }

    /**
     * Lista pedidos e contagens para o contexto mobile.
     *
     * @return array{orders: array, totalOrders: int, statusCounts: array, totalPages: int}
     */
    public static function listForMobile(PDO $db, int $companyId, ?string $status, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        if ($status === null || $status === 'all') {
            $orders = Order::listByCompany($db, $companyId, null, $limit, $offset);
            $totalOrders = Order::countByCompany($db, $companyId);
        } else {
            $orders = Order::listByCompany($db, $companyId, $status, $limit, $offset);
            $totalOrders = Order::countByCompanyAndStatus($db, $companyId, $status);
        }

        $statusCounts = [
            'all' => Order::countByCompany($db, $companyId),
            'pending' => Order::countByCompanyAndStatus($db, $companyId, 'pending'),
            'confirmed' => Order::countByCompanyAndStatus($db, $companyId, 'confirmed'),
            'preparing' => Order::countByCompanyAndStatus($db, $companyId, 'preparing'),
            'ready' => Order::countByCompanyAndStatus($db, $companyId, 'ready'),
            'delivered' => Order::countByCompanyAndStatus($db, $companyId, 'delivered'),
            'completed' => Order::countByCompanyAndStatus($db, $companyId, 'completed'),
        ];

        $totalPages = (int)ceil($totalOrders / $limit);

        return [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'statusCounts' => $statusCounts,
            'totalPages' => $totalPages,
        ];
    }
}
