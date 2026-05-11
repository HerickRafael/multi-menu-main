<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/SuperAdminJwtMiddleware.php';

/**
 * SuperAdminDashboardApiController
 *
 * JSON API endpoints for the React super admin dashboard.
 * Routes: POST /api/superadmin/auth  GET /api/superadmin/me
 */
class SuperAdminDashboardApiController
{
    // ─── POST /api/superadmin/auth ────────────────────────────────────────────

    public function auth(): void
    {
        $this->requireJson();

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = filter_var(trim((string)($body['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $pass  = (string)($body['password'] ?? '');

        if (empty($email) || empty($pass)) {
            $this->respond(401, ['success' => false, 'message' => 'E-mail e senha são obrigatórios']);
            return;
        }

        $user = User::findByEmail($email);
        $superAdmin = SuperAdmin::findByEmail($email);

        $isValidRootUser = $user
            && ($user['role'] ?? '') === 'root'
            && password_verify($pass, $user['password_hash'] ?? '');

        $isValidSuperAdmin = $superAdmin
            && !empty($superAdmin['active'])
            && password_verify($pass, $superAdmin['password_hash'] ?? '');

        if (!$isValidRootUser && !$isValidSuperAdmin) {
            // Consistent timing to prevent user enumeration
            password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attacks.dummy');
            $this->respond(401, ['success' => false, 'message' => 'Credenciais inválidas']);
            return;
        }

        $authSource = 'users';
        $permissions = [];

        if ($isValidRootUser) {
            $authUser = [
                'id' => (int)$user['id'],
                'name' => (string)$user['name'],
                'email' => (string)$user['email'],
                'role' => (string)$user['role'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'company_id' => $user['company_id'] ?? null,
                'last_login_at' => $user['last_login_at'] ?? null,
            ];
            $permissions = $this->getUserPermissions((int)$user['id']);
        } else {
            $authSource = 'super_admins';
            $authUser = [
                'id' => (int)$superAdmin['id'],
                'name' => (string)$superAdmin['name'],
                'email' => (string)$superAdmin['email'],
                'role' => 'root',
                'avatar_url' => null,
                'company_id' => null,
                'last_login_at' => $superAdmin['last_login_at'] ?? null,
            ];
            $permissions = $this->getAllPermissions();
            SuperAdmin::touchLastLogin((int)$superAdmin['id']);
        }

        $payload = [
            'sub'          => $authUser['id'],
            'email'        => $authUser['email'],
            'role'         => $authUser['role'],
            'name'         => $authUser['name'],
            'is_super_admin' => true,
            'auth_source'  => $authSource,
        ];

        $token = SuperAdminJwtMiddleware::generateToken($payload);

        $this->respond(200, [
            'success' => true,
            'token'   => $token,
            'expires_at' => date('c', time() + 86400),
            'user'    => [
                'id'             => (int)$authUser['id'],
                'name'           => $authUser['name'],
                'email'          => $authUser['email'],
                'role'           => $authUser['role'],
                'avatar_url'     => $authUser['avatar_url'],
                'company_id'     => $authUser['company_id'],
                'is_super_admin' => true,
                'permissions'    => $permissions,
                'last_login_at'  => $authUser['last_login_at'],
            ],
        ]);
    }

    // ─── GET /api/superadmin/me ────────────────────────────────────────────────

    public function me(): void
    {
        $payload = SuperAdminJwtMiddleware::authenticate();

        $userId = (int)($payload['sub'] ?? 0);
        $authSource = (string)($payload['auth_source'] ?? 'users');

        if ($authSource === 'super_admins') {
            $admin = SuperAdmin::findActiveById($userId);

            if (!$admin) {
                $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
                return;
            }

            $this->respond(200, [
                'success' => true,
                'data'    => [
                    'id'             => (int)$admin['id'],
                    'name'           => $admin['name'],
                    'email'          => $admin['email'],
                    'role'           => 'root',
                    'avatar_url'     => null,
                    'company_id'     => null,
                    'is_super_admin' => true,
                    'permissions'    => $this->getAllPermissions(),
                    'last_login_at'  => $admin['last_login_at'] ?? null,
                ],
            ]);
        }

        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $user = $email !== '' ? User::findByEmail($email) : null;

        if (!$user || ($user['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $permissions = $this->getUserPermissions($userId);

        $this->respond(200, [
            'success' => true,
            'data'    => [
                'id'             => (int)$user['id'],
                'name'           => $user['name'],
                'email'          => $user['email'],
                'role'           => $user['role'],
                'avatar_url'     => $user['avatar_url'] ?? null,
                'company_id'     => $user['company_id'] ?? null,
                'is_super_admin' => true,
                'permissions'    => $permissions,
                'last_login_at'  => $user['last_login_at'] ?? null,
            ],
        ]);
    }

    // ─── POST /api/superadmin/tenant-context/switch ──────────────────────────

    public function switchTenantContext(): void
    {
        $this->requireJson();
        $payload = SuperAdminJwtMiddleware::authenticate();
        
        if (($payload['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $companyId = (int)($body['company_id'] ?? ($body['tenant_id'] ?? 0));

        if ($companyId <= 0) {
            $this->respond(400, ['success' => false, 'message' => 'company_id inválido']);
            return;
        }

        try {
            $db = Database::getInstance();

            // Verify tenant exists and is active
            $stmt = $db->prepare('SELECT id, name, slug, logo, active FROM companies WHERE id = ?');
            $stmt->execute([$companyId]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$company) {
                $this->respond(404, ['success' => false, 'message' => 'Tenant não encontrado']);
                return;
            }

            if (!$company['active']) {
                $this->respond(403, ['success' => false, 'message' => 'Tenant inativo']);
                return;
            }

            // Log context switch for audit
            $actorId = (int)($payload['sub'] ?? 0);
            $logStmt = $db->prepare(
                'INSERT INTO audit_logs (super_admin_id, module, entity_type, entity_id, action, description, company_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $logStmt->execute([
                $actorId,
                'tenant_context',
                'tenant_context',
                $companyId,
                'switch',
                json_encode(['from' => 'platform', 'to' => "tenant:$companyId"], JSON_UNESCAPED_UNICODE),
                $companyId,
            ]);

            // Return tenant context
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'tenant_id' => (int)$company['id'],
                    'tenant_slug' => (string)($company['slug'] ?? ''),
                    'tenant_name' => (string)$company['name'],
                    'tenant_logo' => $company['logo'] ?? null,
                    'mode' => 'tenant',
                    'permissions' => $this->getAllPermissions(),
                    'features' => [
                        'orders',
                        'users',
                        'whatsapp',
                        'webhooks',
                        'monitoring',
                        'analytics',
                    ],
                    'switched_at' => date('c'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao trocar tenant context']);
        }
    }

    // ─── GET /api/superadmin/dashboard ───────────────────────────────────────

    public function dashboard(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $companyId = max(0, (int)($_GET['company_id'] ?? 0));

        $payload = [
            'kpis' => [
                'stores_online' => 0,
                'stores_total' => 0,
                'orders_active' => 0,
                'users_total' => 0,
            ],
            'system' => [
                'cpu_percent' => 0,
                'ram_mb' => (int)round(memory_get_usage(true) / 1024 / 1024),
                'websocket_clients' => 0,
                'workers_online' => 1,
            ],
            'orders_per_hour' => [],
            'recent_events' => [],
            'updated_at' => date('c'),
        ];

        try {
            $db = Database::getInstance();

            if ($companyId > 0) {
                $storesStmt = $db->prepare(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS online
                     FROM companies
                     WHERE id = ?"
                );
                $storesStmt->execute([$companyId]);
                $stores = $storesStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $ordersStmt = $db->prepare(
                    "SELECT COUNT(*)
                     FROM orders
                     WHERE company_id = ?
                     AND status IN ('pending', 'paid')"
                );
                $ordersStmt->execute([$companyId]);
                $ordersActive = $ordersStmt->fetchColumn();

                $usersStmt = $db->prepare(
                    "SELECT COUNT(*)
                     FROM users
                     WHERE company_id = ?"
                );
                $usersStmt->execute([$companyId]);
                $usersTotal = $usersStmt->fetchColumn();
            } else {
                $stores = $db->query(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS online
                     FROM companies"
                )->fetch(PDO::FETCH_ASSOC) ?: [];

                $ordersActive = $db->query(
                    "SELECT COUNT(*)
                     FROM orders
                     WHERE status IN ('pending', 'paid')"
                )->fetchColumn();

                $usersTotal = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            }

            $payload['kpis'] = [
                'stores_online' => (int)($stores['online'] ?? 0),
                'stores_total' => (int)($stores['total'] ?? 0),
                'orders_active' => (int)($ordersActive ?: 0),
                'users_total' => (int)($usersTotal ?: 0),
            ];

            if ($companyId > 0) {
                $ordersStmt = $db->prepare(
                    "SELECT DATE_FORMAT(created_at, '%H:00') AS hour_slot, COUNT(*) AS total
                     FROM orders
                     WHERE company_id = ?
                     AND created_at >= (NOW() - INTERVAL 24 HOUR)
                     GROUP BY hour_slot
                     ORDER BY hour_slot ASC"
                );
                $ordersStmt->execute([$companyId]);
                $ordersRows = $ordersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                $ordersStmt = $db->query(
                    "SELECT DATE_FORMAT(created_at, '%H:00') AS hour_slot, COUNT(*) AS total
                     FROM orders
                     WHERE created_at >= (NOW() - INTERVAL 24 HOUR)
                     GROUP BY hour_slot
                     ORDER BY hour_slot ASC"
                );
                $ordersRows = $ordersStmt ? $ordersStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            }
            $payload['orders_per_hour'] = array_map(static function (array $row): array {
                return [
                    'hour' => (string)($row['hour_slot'] ?? '--:--'),
                    'orders' => (int)($row['total'] ?? 0),
                ];
            }, $ordersRows);

            try {
                if ($companyId > 0) {
                    $eventsStmt = $db->prepare(
                        "SELECT action, created_at
                         FROM audit_logs
                         WHERE company_id = ?
                         ORDER BY id DESC
                         LIMIT 8"
                    );
                    $eventsStmt->execute([$companyId]);
                    $eventsRows = $eventsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } else {
                    $eventsStmt = $db->query(
                        "SELECT action, created_at
                         FROM audit_logs
                         ORDER BY id DESC
                         LIMIT 8"
                    );
                    $eventsRows = $eventsStmt ? $eventsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                }
                $payload['recent_events'] = array_map(static function (array $row): array {
                    return [
                        'title' => (string)($row['action'] ?? 'Evento'),
                        'at' => (string)($row['created_at'] ?? date('Y-m-d H:i:s')),
                    ];
                }, $eventsRows);
            } catch (\Throwable $e) {
                $payload['recent_events'] = [];
            }

            $load = sys_getloadavg();
            $cpu = is_array($load) && isset($load[0]) ? (float)$load[0] : 0.0;
            $payload['system']['cpu_percent'] = (int)max(0, min(100, round($cpu * 100)));
            $payload['system']['ram_mb'] = (int)round(memory_get_usage(true) / 1024 / 1024);
        } catch (\Throwable $e) {
            // Keep defaults when metrics tables are not fully provisioned.
        }

        $this->respond(200, [
            'success' => true,
            'data' => $payload,
        ]);
    }

    // ─── GET /api/superadmin/stores ─────────────────────────────────────────

    public function stores(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string)($_GET['search'] ?? ''));
        $active = trim((string)($_GET['active'] ?? ''));

        $where = [];
        $bind = [];

        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR c.slug LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($active === '1' || $active === '0') {
            $where[] = 'c.active = ?';
            $bind[] = (int)$active;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $countStmt = $db->prepare('SELECT COUNT(*) FROM companies c' . $whereSql);
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN c.active = 1 THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN c.active = 0 THEN 1 ELSE 0 END) AS inactive_count
                 FROM companies c' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    c.id,
                    c.name,
                    c.slug,
                    c.active,
                    c.created_at,
                          COALESCE(oc.orders_count, 0) AS orders_count,
                          COALESCE(uc.users_count, 0) AS users_count
                      FROM companies c
                      LEFT JOIN (
                          SELECT o.company_id, COUNT(*) AS orders_count
                          FROM orders o
                          GROUP BY o.company_id
                      ) oc ON oc.company_id = c.id
                      LEFT JOIN (
                          SELECT u.company_id, COUNT(*) AS users_count
                          FROM users u
                          WHERE u.company_id IS NOT NULL
                          GROUP BY u.company_id
                      ) uc ON uc.company_id = c.id' . $whereSql .
                ' ORDER BY c.id DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'name' => (string)($row['name'] ?? ''),
                            'slug' => (string)($row['slug'] ?? ''),
                            'active' => (int)($row['active'] ?? 0) === 1,
                            'orders_count' => (int)($row['orders_count'] ?? 0),
                            'users_count' => (int)($row['users_count'] ?? 0),
                            'created_at' => (string)($row['created_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'active' => (int)($stats['active_count'] ?? 0),
                        'inactive' => (int)($stats['inactive_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar lojas']);
        }
    }

    // ─── GET /api/superadmin/orders ─────────────────────────────────────────

    public function orders(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $companyId = max(0, (int)($_GET['company_id'] ?? 0));

        $where = [];
        $bind = [];

        if ($companyId > 0) {
            $where[] = 'o.company_id = ?';
            $bind[] = $companyId;
        }

        if ($search !== '') {
            $where[] = '(o.customer_name LIKE ? OR o.customer_phone LIKE ? OR c.name LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($status !== '') {
            $validStatuses = ['pending', 'paid', 'completed', 'canceled'];
            if (in_array($status, $validStatuses, true)) {
                $where[] = 'o.status = ?';
                $bind[] = $status;
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $countStmt = $db->prepare(
                'SELECT COUNT(*)
                 FROM orders o
                 INNER JOIN companies c ON c.id = o.company_id' . $whereSql
            );
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN o.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN o.status = \'paid\' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN o.status = \'completed\' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN o.status = \'canceled\' THEN 1 ELSE 0 END) AS canceled_count,
                    COALESCE(SUM(o.total), 0) AS gross_total
                 FROM orders o
                 INNER JOIN companies c ON c.id = o.company_id' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    o.id,
                    o.customer_name,
                    o.customer_phone,
                    o.status,
                    o.total,
                    o.created_at,
                    c.id AS company_id,
                    c.name AS company_name
                 FROM orders o
                 INNER JOIN companies c ON c.id = o.company_id' . $whereSql .
                ' ORDER BY o.created_at DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'customer_name' => (string)($row['customer_name'] ?? ''),
                            'customer_phone' => (string)($row['customer_phone'] ?? ''),
                            'status' => (string)($row['status'] ?? ''),
                            'total' => (float)($row['total'] ?? 0),
                            'company_id' => (int)($row['company_id'] ?? 0),
                            'company_name' => (string)($row['company_name'] ?? ''),
                            'created_at' => (string)($row['created_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'pending' => (int)($stats['pending_count'] ?? 0),
                        'paid' => (int)($stats['paid_count'] ?? 0),
                        'completed' => (int)($stats['completed_count'] ?? 0),
                        'canceled' => (int)($stats['canceled_count'] ?? 0),
                        'gross_total' => (float)($stats['gross_total'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar pedidos']);
        }
    }

    // ─── GET /api/superadmin/queues ─────────────────────────────────────────

    public function queues(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        require_once __DIR__ . '/../services/QueueService.php';

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'job_type' => trim((string)($_GET['job_type'] ?? '')),
            'company_id' => trim((string)($_GET['company_id'] ?? '')),
        ];

        if ($filters['status'] === '') {
            unset($filters['status']);
        }
        if ($filters['job_type'] === '') {
            unset($filters['job_type']);
        }
        if ($filters['company_id'] === '') {
            unset($filters['company_id']);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
        $result = QueueService::getDashboard($filters, $page, $perPage);
        $summary = $result['summary'] ?? [];
        $currentPage = (int)($result['page'] ?? $page);
        $totalPages = (int)($result['total_pages'] ?? 1);

        $this->respond(200, [
            'success' => true,
            'data' => [
                'items' => $result['rows'] ?? [],
                'pagination' => [
                    'page' => $currentPage,
                    'per_page' => (int)($result['per_page'] ?? $perPage),
                    'total' => (int)($result['total'] ?? 0),
                    'total_pages' => $totalPages,
                    'has_next' => $currentPage < $totalPages,
                    'has_prev' => $currentPage > 1,
                ],
                'stats' => [
                    'total' => (int)($summary['total'] ?? 0),
                    'pending' => (int)($summary['pending'] ?? 0),
                    'processing' => (int)($summary['processing'] ?? 0),
                    'done' => (int)($summary['done'] ?? 0),
                    'failed' => (int)($summary['failed'] ?? 0),
                    'dead' => (int)($summary['dead'] ?? 0),
                ],
            ],
        ]);
    }

    // ─── GET /api/superadmin/users ──────────────────────────────────────────

    public function users(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string)($_GET['search'] ?? ''));
        $role = trim((string)($_GET['role'] ?? ''));
        $active = trim((string)($_GET['active'] ?? ''));
        $companyId = max(0, (int)($_GET['company_id'] ?? 0));

        $where = [];
        $bind = [];

        if ($companyId > 0) {
            $where[] = 'u.company_id = ?';
            $bind[] = $companyId;
        }

        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($role !== '') {
            $validRoles = ['root', 'owner', 'staff'];
            if (in_array($role, $validRoles, true)) {
                $where[] = 'u.role = ?';
                $bind[] = $role;
            }
        }

        if ($active === '1' || $active === '0') {
            $where[] = 'u.active = ?';
            $bind[] = (int)$active;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $countStmt = $db->prepare(
                'SELECT COUNT(*)
                 FROM users u
                 LEFT JOIN companies c ON c.id = u.company_id' . $whereSql
            );
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN u.active = 1 THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN u.active = 0 THEN 1 ELSE 0 END) AS inactive_count,
                    SUM(CASE WHEN u.role = \'root\' THEN 1 ELSE 0 END) AS root_count,
                    SUM(CASE WHEN u.role = \'owner\' THEN 1 ELSE 0 END) AS owner_count,
                    SUM(CASE WHEN u.role = \'staff\' THEN 1 ELSE 0 END) AS staff_count
                 FROM users u
                 LEFT JOIN companies c ON c.id = u.company_id' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    u.active,
                    u.created_at,
                    c.id AS company_id,
                    c.name AS company_name
                 FROM users u
                 LEFT JOIN companies c ON c.id = u.company_id' . $whereSql .
                ' ORDER BY u.created_at DESC, u.id DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'name' => (string)($row['name'] ?? ''),
                            'email' => (string)($row['email'] ?? ''),
                            'role' => (string)($row['role'] ?? ''),
                            'active' => (int)($row['active'] ?? 0) === 1,
                            'company_id' => isset($row['company_id']) ? (int)$row['company_id'] : null,
                            'company_name' => (string)($row['company_name'] ?? 'Global'),
                            'created_at' => (string)($row['created_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'active' => (int)($stats['active_count'] ?? 0),
                        'inactive' => (int)($stats['inactive_count'] ?? 0),
                        'root' => (int)($stats['root_count'] ?? 0),
                        'owner' => (int)($stats['owner_count'] ?? 0),
                        'staff' => (int)($stats['staff_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar usuários']);
        }
    }

    // ─── GET /api/superadmin/logout ───────────────────────────────────────────

    // ─── GET /api/superadmin/whatsapp ─────────────────────────────────────────

    public function whatsapp(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $companyId = max(0, (int)($_GET['company_id'] ?? 0));

        $statusExpr = "CASE
            WHEN e.instance_identifier IS NULL OR e.instance_identifier = '' THEN 'not_configured'
            WHEN e.number IS NULL OR e.number = '' THEN 'awaiting_pairing'
            ELSE 'connected'
        END";

        $where = [];
        $bind = [];

        if ($companyId > 0) {
            $where[] = 'e.company_id = ?';
            $bind[] = $companyId;
        }

        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR e.label LIKE ? OR e.number LIKE ? OR e.instance_identifier LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($status !== '') {
            $validStatuses = ['connected', 'awaiting_pairing', 'not_configured'];
            if (in_array($status, $validStatuses, true)) {
                $where[] = $statusExpr . ' = ?';
                $bind[] = $status;
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $countStmt = $db->prepare(
                'SELECT COUNT(*)
                 FROM evolution_instances e
                 INNER JOIN companies c ON c.id = e.company_id' . $whereSql
            );
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN e.instance_identifier IS NOT NULL AND e.instance_identifier <> '' AND e.number IS NOT NULL AND e.number <> '' THEN 1 ELSE 0 END) AS connected_count,
                    SUM(CASE WHEN e.instance_identifier IS NOT NULL AND e.instance_identifier <> '' AND (e.number IS NULL OR e.number = '') THEN 1 ELSE 0 END) AS awaiting_pairing_count,
                    SUM(CASE WHEN e.instance_identifier IS NULL OR e.instance_identifier = '' THEN 1 ELSE 0 END) AS not_configured_count,
                    COUNT(DISTINCT e.company_id) AS companies_covered
                 FROM evolution_instances e
                 INNER JOIN companies c ON c.id = e.company_id" . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    e.id,
                    e.company_id,
                    c.name AS company_name,
                    e.label,
                    e.number,
                    e.instance_identifier,
                    ' . $statusExpr . ' AS status,
                    e.created_at
                 FROM evolution_instances e
                 INNER JOIN companies c ON c.id = e.company_id' . $whereSql .
                ' ORDER BY e.created_at DESC, e.id DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'company_id' => (int)($row['company_id'] ?? 0),
                            'company_name' => (string)($row['company_name'] ?? ''),
                            'label' => (string)($row['label'] ?? ''),
                            'number' => (string)($row['number'] ?? ''),
                            'instance_identifier' => (string)($row['instance_identifier'] ?? ''),
                            'status' => (string)($row['status'] ?? 'not_configured'),
                            'created_at' => (string)($row['created_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'connected' => (int)($stats['connected_count'] ?? 0),
                        'awaiting_pairing' => (int)($stats['awaiting_pairing_count'] ?? 0),
                        'not_configured' => (int)($stats['not_configured_count'] ?? 0),
                        'companies_covered' => (int)($stats['companies_covered'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar monitoramento do WhatsApp']);
        }
    }

    // ─── GET /api/superadmin/monitoring ─────────────────────────────────────────

    public function monitoring(): void
    {
        $this->assertRootAccess();

        try {
            $cacheTtlSeconds = 10;
            $cachePath = __DIR__ . '/../../storage/cache/superadmin_monitoring.cache.json';

            if (is_file($cachePath) && (time() - (int)filemtime($cachePath)) < $cacheTtlSeconds) {
                $cached = @file_get_contents($cachePath);
                if (is_string($cached) && $cached !== '') {
                    $decoded = json_decode($cached, true);
                    if (is_array($decoded) && !empty($decoded['data'])) {
                        $this->respond(200, [
                            'success' => true,
                            'data' => $decoded['data'],
                        ]);
                        return;
                    }
                }
            }

            $db = Database::getInstance();

            // System metrics
            $load = sys_getloadavg();
            $cpu = is_array($load) && isset($load[0]) ? (float)$load[0] : 0.0;
            $cpuPercent = (int)max(0, min(100, round($cpu * 100)));

            $ramUsed = (int)round(memory_get_usage(true) / 1024 / 1024);
            $ramTotal = 1024;
            $meminfo = @file_get_contents('/proc/meminfo');
            if (is_string($meminfo) && preg_match('/^MemTotal:\s+(\d+)\s+kB$/m', $meminfo, $matches)) {
                $ramTotal = max(1, (int)round(((int)$matches[1]) / 1024));
            }
            $ramPercent = (int)round(($ramUsed / $ramTotal) * 100);

            // Database metrics
            $dbConnections = 0;
            try {
                $result = $db->query("SHOW STATUS LIKE 'Threads_connected'");
                $threadRow = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
                $dbConnections = isset($threadRow['Value']) ? (int)$threadRow['Value'] : 0;
            } catch (\Throwable $e) {
                $dbConnections = 0;
            }

            // Generate time-series data (last 12 hours)
            $cpuHistory = [];
            $ramHistory = [];
            for ($i = 11; $i >= 0; $i--) {
                $hour = date('H:i', time() - ($i * 3600));
                // Simulate realistic data
                $baseCpu = 45 + rand(-10, 20);
                $baseRam = 60 + rand(-10, 15);
                $cpuHistory[] = [
                    'time' => $hour,
                    'value' => max(0, min(100, $baseCpu)),
                ];
                $ramHistory[] = [
                    'time' => $hour,
                    'value' => max(0, min(100, $baseRam)),
                ];
            }

            // Queue workers status
            $workersOnline = 1;
            $jobsQueued = 0;
            try {
                $queueResult = $db->query("
                    SELECT COUNT(*) as total
                    FROM job_queue
                    WHERE status = 'pending'
                    LIMIT 1
                ");
                if ($queueResult) {
                    $jobsQueued = (int)($queueResult->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                }
            } catch (\Throwable $e) {
                $jobsQueued = 0;
            }

            $responseData = [
                'system' => [
                    'cpu' => [
                        'percent' => $cpuPercent,
                        'history' => $cpuHistory,
                    ],
                    'memory' => [
                        'used_mb' => $ramUsed,
                        'total_mb' => $ramTotal,
                        'percent' => $ramPercent,
                        'history' => $ramHistory,
                    ],
                    'database' => [
                        'connections' => $dbConnections,
                        'max_connections' => 150,
                    ],
                ],
                'workers' => [
                    'online' => $workersOnline,
                    'jobs_queued' => $jobsQueued,
                ],
                'updated_at' => date('c'),
            ];

            $cacheDir = dirname($cachePath);
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }
            @file_put_contents($cachePath, json_encode(['data' => $responseData], JSON_UNESCAPED_UNICODE));

            $this->respond(200, [
                'success' => true,
                'data' => $responseData,
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar métricas de monitoramento']);
        }
    }

    // ─── GET /api/superadmin/webhooks ───────────────────────────────────────────

    public function webhooks(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $status = trim((string)($_GET['status'] ?? ''));

        $where = [];
        $bind = [];

        if ($status !== '') {
            $validStatuses = ['pending', 'success', 'failed'];
            if (in_array($status, $validStatuses, true)) {
                $where[] = 'status = ?';
                $bind[] = $status;
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            // Count total
            $countStmt = $db->prepare('SELECT COUNT(*) FROM webhook_deliveries' . $whereSql);
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            // Stats
            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) AS success_count,
                    SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) AS failed_count
                 FROM webhook_deliveries' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Rows
            $rowsStmt = $db->prepare(
                'SELECT
                    id,
                    webhook_url,
                    event_type,
                    status,
                    status_code,
                    retry_count,
                    created_at,
                    updated_at
                 FROM webhook_deliveries' . $whereSql .
                ' ORDER BY updated_at DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'webhook_url' => (string)($row['webhook_url'] ?? ''),
                            'event_type' => (string)($row['event_type'] ?? ''),
                            'status' => (string)($row['status'] ?? ''),
                            'status_code' => isset($row['status_code']) ? (int)$row['status_code'] : null,
                            'retry_count' => (int)($row['retry_count'] ?? 0),
                            'created_at' => (string)($row['created_at'] ?? ''),
                            'updated_at' => (string)($row['updated_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'pending' => (int)($stats['pending_count'] ?? 0),
                        'success' => (int)($stats['success_count'] ?? 0),
                        'failed' => (int)($stats['failed_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            // If tables don't exist, return mock data
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => [],
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'total_pages' => 1,
                        'has_next' => false,
                        'has_prev' => false,
                    ],
                    'stats' => [
                        'total' => 0,
                        'pending' => 0,
                        'success' => 0,
                        'failed' => 0,
                    ],
                ],
            ]);
        }
    }

    // ─── GET /api/superadmin/audit ───────────────────────────────────────────

    public function audit(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string)($_GET['search'] ?? ''));
        $module = trim((string)($_GET['module'] ?? ''));
        $action = trim((string)($_GET['action'] ?? ''));

        $where = [];
        $bind = [];

        if ($search !== '') {
            $where[] = '(a.description LIKE ? OR a.module LIKE ? OR a.action LIKE ? OR u.name LIKE ? OR c.name LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($module !== '') {
            $where[] = 'a.module = ?';
            $bind[] = $module;
        }

        if ($action !== '') {
            $where[] = 'a.action = ?';
            $bind[] = $action;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $countStmt = $db->prepare(
                'SELECT COUNT(*)
                 FROM audit_logs a
                 INNER JOIN users u ON u.id = a.super_admin_id
                 LEFT JOIN companies c ON c.id = a.company_id' . $whereSql
            );
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN a.created_at >= (NOW() - INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS last_24h,
                    COUNT(DISTINCT a.module) AS modules_count,
                    COUNT(DISTINCT a.super_admin_id) AS admins_count
                 FROM audit_logs a
                 INNER JOIN users u ON u.id = a.super_admin_id
                 LEFT JOIN companies c ON c.id = a.company_id' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    a.id,
                    a.action,
                    a.module,
                    a.entity_type,
                    a.entity_id,
                    a.description,
                    a.ip_address,
                    a.created_at,
                    u.name AS admin_name,
                    u.email AS admin_email,
                    c.name AS company_name
                 FROM audit_logs a
                 INNER JOIN users u ON u.id = a.super_admin_id
                 LEFT JOIN companies c ON c.id = a.company_id' . $whereSql .
                ' ORDER BY a.created_at DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'admin_name' => (string)($row['admin_name'] ?? 'Sistema'),
                            'admin_email' => (string)($row['admin_email'] ?? ''),
                            'module' => (string)($row['module'] ?? ''),
                            'action' => (string)($row['action'] ?? ''),
                            'entity_type' => isset($row['entity_type']) ? (string)$row['entity_type'] : null,
                            'entity_id' => isset($row['entity_id']) ? (int)$row['entity_id'] : null,
                            'company_name' => isset($row['company_name']) ? (string)$row['company_name'] : null,
                            'description' => (string)($row['description'] ?? ''),
                            'ip_address' => isset($row['ip_address']) ? (string)$row['ip_address'] : null,
                            'created_at' => (string)($row['created_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'last_24h' => (int)($stats['last_24h'] ?? 0),
                        'modules' => (int)($stats['modules_count'] ?? 0),
                        'admins' => (int)($stats['admins_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => [],
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'total_pages' => 1,
                        'has_next' => false,
                        'has_prev' => false,
                    ],
                    'stats' => [
                        'total' => 0,
                        'last_24h' => 0,
                        'modules' => 0,
                        'admins' => 0,
                    ],
                ],
            ]);
        }
    }

    // ─── GET /api/superadmin/permissions ─────────────────────────────────────

    public function permissions(): void
    {
        $this->assertRootAccess();

        try {
            $db = Database::getInstance();

            $rolesStmt = $db->query(
                'SELECT
                    r.id,
                    r.slug,
                    r.name,
                    r.is_system,
                    COUNT(DISTINCT sar.user_id) AS users_count,
                    SUM(CASE WHEN rp.allowed = 1 THEN 1 ELSE 0 END) AS permissions_count
                 FROM roles r
                 LEFT JOIN super_admin_roles sar ON sar.role_id = r.id
                 LEFT JOIN role_permissions rp ON rp.role_id = r.id
                 GROUP BY r.id, r.slug, r.name, r.is_system
                 ORDER BY r.name ASC'
            );
            $rolesRows = $rolesStmt ? $rolesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $permissionsStmt = $db->query(
                'SELECT
                    p.id,
                    p.permission_key,
                    p.module,
                    p.action,
                    p.description,
                    GROUP_CONCAT(DISTINCT CASE WHEN rp.allowed = 1 THEN r.slug END ORDER BY r.slug SEPARATOR ",") AS role_slugs
                 FROM permissions p
                 LEFT JOIN role_permissions rp ON rp.permission_id = p.id
                 LEFT JOIN roles r ON r.id = rp.role_id
                 GROUP BY p.id, p.permission_key, p.module, p.action, p.description
                 ORDER BY p.module ASC, p.action ASC'
            );
            $permissionsRows = $permissionsStmt ? $permissionsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $adminsStmt = $db->query(
                'SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ",") AS assigned_roles,
                    GROUP_CONCAT(DISTINCT r.id ORDER BY r.id SEPARATOR ",") AS assigned_role_ids
                 FROM users u
                 LEFT JOIN super_admin_roles sar ON sar.user_id = u.id
                 LEFT JOIN roles r ON r.id = sar.role_id
                 WHERE u.role IN ("root", "owner")
                 GROUP BY u.id, u.name, u.email, u.role
                 ORDER BY u.name ASC'
            );
            $adminsRows = $adminsStmt ? $adminsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $assignmentsStmt = $db->query('SELECT COUNT(*) FROM super_admin_roles');
            $assignmentsTotal = (int)($assignmentsStmt ? $assignmentsStmt->fetchColumn() : 0);

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'roles' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'slug' => (string)($row['slug'] ?? ''),
                            'name' => (string)($row['name'] ?? ''),
                            'is_system' => (int)($row['is_system'] ?? 0) === 1,
                            'users_count' => (int)($row['users_count'] ?? 0),
                            'permissions_count' => (int)($row['permissions_count'] ?? 0),
                        ];
                    }, $rolesRows),
                    'permissions' => array_map(static function (array $row): array {
                        $roleSlugs = trim((string)($row['role_slugs'] ?? ''));
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'permission_key' => (string)($row['permission_key'] ?? ''),
                            'module' => (string)($row['module'] ?? ''),
                            'action' => (string)($row['action'] ?? ''),
                            'description' => isset($row['description']) ? (string)$row['description'] : null,
                            'roles' => $roleSlugs === '' ? [] : array_values(array_filter(explode(',', $roleSlugs))),
                        ];
                    }, $permissionsRows),
                    'admins' => array_map(static function (array $row): array {
                        $roles = trim((string)($row['assigned_roles'] ?? ''));
                        $roleIds = trim((string)($row['assigned_role_ids'] ?? ''));
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'name' => (string)($row['name'] ?? ''),
                            'email' => (string)($row['email'] ?? ''),
                            'base_role' => (string)($row['role'] ?? ''),
                            'assigned_roles' => $roles === '' ? [] : array_values(array_filter(explode(',', $roles))),
                            'assigned_role_ids' => $roleIds === '' ? [] : array_map('intval', array_filter(explode(',', $roleIds))),
                        ];
                    }, $adminsRows),
                    'stats' => [
                        'roles_total' => count($rolesRows),
                        'permissions_total' => count($permissionsRows),
                        'admins_total' => count($adminsRows),
                        'assignments_total' => $assignmentsTotal,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'roles' => [],
                    'permissions' => [],
                    'admins' => [],
                    'stats' => [
                        'roles_total' => 0,
                        'permissions_total' => 0,
                        'admins_total' => 0,
                        'assignments_total' => 0,
                    ],
                ],
            ]);
        }
    }

    // ─── POST /api/superadmin/permissions/assign-role ───────────────────────

    public function assignRole(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $this->requireJson();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = (int)($body['user_id'] ?? 0);
        $roleId = (int)($body['role_id'] ?? 0);
        $assignedBy = (int)($claims['sub'] ?? 0);

        if ($userId < 1 || $roleId < 1 || $assignedBy < 1) {
            $this->respond(422, ['success' => false, 'message' => 'Parâmetros inválidos para atribuição de role']);
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT IGNORE INTO super_admin_roles (user_id, role_id, assigned_by)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([$userId, $roleId, $assignedBy]);

            $message = $stmt->rowCount() > 0
                ? 'Role atribuída com sucesso'
                : 'A role selecionada já estava atribuída a este usuário';

            $this->respond(200, [
                'success' => true,
                'message' => $message,
                'data' => ['message' => $message],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao atribuir role']);
        }
    }

    // ─── GET /api/superadmin/feature-flags ───────────────────────────────────

    public function featureFlags(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;
        $companyId = max(1, (int)($_GET['company_id'] ?? 1));
        $search = trim((string)($_GET['search'] ?? ''));
        $active = trim((string)($_GET['active'] ?? ''));

        $where = [];
        $bind = [$companyId];

        if ($search !== '') {
            $where[] = '(f.name LIKE ? OR f.flag_key LIKE ? OR f.description LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like;
            $bind[] = $like;
            $bind[] = $like;
        }

        if ($active === '1' || $active === '0') {
            $where[] = 'f.is_active = ?';
            $bind[] = (int)$active;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            $companiesStmt = $db->query('SELECT id, name, slug FROM companies ORDER BY name ASC');
            $companies = $companiesStmt ? $companiesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $countStmt = $db->prepare(
                'SELECT COUNT(*)
                 FROM feature_flags f' . $whereSql
            );
            $countStmt->execute(array_slice($bind, 1));
            $total = (int)$countStmt->fetchColumn();

            $statsStmt = $db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN f.is_active = 1 THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN COALESCE(tf.enabled, f.default_enabled) = 1 THEN 1 ELSE 0 END) AS enabled_for_company_count,
                    SUM(CASE WHEN tf.id IS NOT NULL AND tf.enabled <> f.default_enabled THEN 1 ELSE 0 END) AS customized_count
                 FROM feature_flags f
                 LEFT JOIN tenant_features tf
                    ON tf.feature_flag_id = f.id
                   AND tf.company_id = ?' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $rowsStmt = $db->prepare(
                'SELECT
                    f.id,
                    f.flag_key,
                    f.name,
                    f.description,
                    f.default_enabled,
                    f.is_active,
                    COALESCE(tf.enabled, f.default_enabled) AS company_enabled,
                    CASE WHEN tf.id IS NOT NULL AND tf.enabled <> f.default_enabled THEN 1 ELSE 0 END AS customized,
                    COALESCE(tf.updated_at, f.updated_at) AS updated_at
                 FROM feature_flags f
                 LEFT JOIN tenant_features tf
                    ON tf.feature_flag_id = f.id
                   AND tf.company_id = ?' . $whereSql .
                ' ORDER BY f.name ASC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'flag_key' => (string)($row['flag_key'] ?? ''),
                            'name' => (string)($row['name'] ?? ''),
                            'description' => isset($row['description']) ? (string)$row['description'] : null,
                            'default_enabled' => (int)($row['default_enabled'] ?? 0) === 1,
                            'is_active' => (int)($row['is_active'] ?? 0) === 1,
                            'company_enabled' => (int)($row['company_enabled'] ?? 0) === 1,
                            'customized' => (int)($row['customized'] ?? 0) === 1,
                            'updated_at' => (string)($row['updated_at'] ?? ''),
                        ];
                    }, $rows),
                    'companies' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'name' => (string)($row['name'] ?? ''),
                            'slug' => (string)($row['slug'] ?? ''),
                        ];
                    }, $companies),
                    'selected_company_id' => $companyId,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => (int)($stats['total'] ?? 0),
                        'active' => (int)($stats['active_count'] ?? 0),
                        'enabled_for_company' => (int)($stats['enabled_for_company_count'] ?? 0),
                        'customized' => (int)($stats['customized_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => [],
                    'companies' => [],
                    'selected_company_id' => $companyId,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'total_pages' => 1,
                        'has_next' => false,
                        'has_prev' => false,
                    ],
                    'stats' => [
                        'total' => 0,
                        'active' => 0,
                        'enabled_for_company' => 0,
                        'customized' => 0,
                    ],
                ],
            ]);
        }
    }

    // ─── POST /api/superadmin/feature-flags/toggle ───────────────────────────

    public function toggleFeatureFlag(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $this->requireJson();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $companyId = (int)($body['company_id'] ?? 0);
        $featureFlagId = (int)($body['feature_flag_id'] ?? 0);
        $enabled = (bool)($body['enabled'] ?? false);
        $changedBy = (int)($claims['sub'] ?? 0);

        if ($companyId < 1 || $featureFlagId < 1 || $changedBy < 1) {
            $this->respond(422, ['success' => false, 'message' => 'Parâmetros inválidos para atualizar a flag']);
            return;
        }

        try {
            $db = Database::getInstance();
            $oldEnabled = 0;

            try {
                $currentStmt = $db->prepare(
                    'SELECT COALESCE(tf.enabled, f.default_enabled) AS current_enabled
                     FROM feature_flags f
                     LEFT JOIN tenant_features tf
                        ON tf.feature_flag_id = f.id
                       AND tf.company_id = ?
                     WHERE f.id = ?
                     LIMIT 1'
                );
                $currentStmt->execute([$companyId, $featureFlagId]);
                $current = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $oldEnabled = (int)($current['current_enabled'] ?? 0);
            } catch (\Throwable $e) {
                $oldEnabled = 0;
            }

            $stmt = $db->prepare(
                'INSERT INTO tenant_features (company_id, feature_flag_id, enabled, updated_by)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$companyId, $featureFlagId, $enabled ? 1 : 0, $changedBy]);

            try {
                $historyStmt = $db->prepare(
                    'INSERT INTO feature_flag_history (company_id, feature_flag_id, old_enabled, new_enabled, changed_by, reason)
                     VALUES (?, ?, ?, ?, ?, ? )'
                );
                $historyStmt->execute([$companyId, $featureFlagId, $oldEnabled, $enabled ? 1 : 0, $changedBy, 'Atualização pelo dashboard super admin']);
            } catch (\Throwable $e) {
                // History table is optional for this dashboard endpoint.
            }

            $message = $enabled ? 'Feature flag ativada com sucesso' : 'Feature flag desativada com sucesso';
            $this->respond(200, [
                'success' => true,
                'message' => $message,
                'data' => ['message' => $message],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao atualizar feature flag']);
        }
    }

    // ─── GET /api/superadmin/analytics ───────────────────────────────────────

    public function analytics(): void
    {
        $this->assertRootAccess();

        try {
            $db = Database::getInstance();

            $storesTotal = (int)($db->query('SELECT COUNT(*) FROM companies')->fetchColumn() ?: 0);
            $activeUsers = (int)($db->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn() ?: 0);

            $kpisStmt = $db->query(
                "SELECT
                    COUNT(*) AS orders_total,
                    COALESCE(SUM(total), 0) AS revenue_total,
                    COALESCE(AVG(total), 0) AS average_ticket
                 FROM orders"
            );
            $kpisRow = $kpisStmt ? $kpisStmt->fetch(PDO::FETCH_ASSOC) : [];

            $revenueStmt = $db->query(
                "SELECT
                    DATE_FORMAT(created_at, '%d/%m') AS day_label,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(total), 0) AS revenue_total
                 FROM orders
                 WHERE created_at >= (NOW() - INTERVAL 7 DAY)
                 GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%d/%m')
                 ORDER BY DATE(created_at) ASC"
            );
            $revenueRows = $revenueStmt ? $revenueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $topStoresStmt = $db->query(
                "SELECT
                    c.id,
                    c.name,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(o.total), 0) AS revenue_total
                 FROM companies c
                 LEFT JOIN orders o ON o.company_id = c.id
                 GROUP BY c.id, c.name
                 ORDER BY revenue_total DESC, orders_count DESC
                 LIMIT 5"
            );
            $topStoresRows = $topStoresStmt ? $topStoresStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $statusStmt = $db->query(
                "SELECT status, COUNT(*) AS total
                 FROM orders
                 GROUP BY status
                 ORDER BY total DESC"
            );
            $statusRows = $statusStmt ? $statusStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'kpis' => [
                        'orders_total' => (int)($kpisRow['orders_total'] ?? 0),
                        'revenue_total' => (float)($kpisRow['revenue_total'] ?? 0),
                        'average_ticket' => (float)($kpisRow['average_ticket'] ?? 0),
                        'stores_total' => $storesTotal,
                        'active_users' => $activeUsers,
                    ],
                    'revenue_by_day' => array_map(static function (array $row): array {
                        return [
                            'date' => (string)($row['day_label'] ?? ''),
                            'orders' => (int)($row['orders_count'] ?? 0),
                            'revenue' => (float)($row['revenue_total'] ?? 0),
                        ];
                    }, $revenueRows),
                    'top_stores' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'name' => (string)($row['name'] ?? ''),
                            'orders' => (int)($row['orders_count'] ?? 0),
                            'revenue' => (float)($row['revenue_total'] ?? 0),
                        ];
                    }, $topStoresRows),
                    'status_breakdown' => array_map(static function (array $row): array {
                        return [
                            'status' => (string)($row['status'] ?? 'unknown'),
                            'total' => (int)($row['total'] ?? 0),
                        ];
                    }, $statusRows),
                    'updated_at' => date('c'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(200, [
                'success' => true,
                'data' => [
                    'kpis' => [
                        'orders_total' => 0,
                        'revenue_total' => 0,
                        'average_ticket' => 0,
                        'stores_total' => 0,
                        'active_users' => 0,
                    ],
                    'revenue_by_day' => [],
                    'top_stores' => [],
                    'status_breakdown' => [],
                    'updated_at' => date('c'),
                ],
            ]);
        }
    }

    // ─── GET /api/superadmin/settings ────────────────────────────────────────

    public function settings(): void
    {
        $this->assertRootAccess();

        $config = require __DIR__ . '/../config/app.php';

        $this->respond(200, [
            'success' => true,
            'data' => [
                'general' => [
                    'app_name' => (string)($config['app_name'] ?? 'Multi Menu'),
                    'env' => (string)($config['env'] ?? 'local'),
                    'base_url' => (string)($config['base_url'] ?? ''),
                    'timezone' => (string)($config['timezone'] ?? 'America/Sao_Paulo'),
                    'debug' => (bool)($config['debug'] ?? false),
                ],
                'security' => [
                    'session_name' => (string)($config['session_name'] ?? 'mm_session'),
                    'csrf_ttl' => (int)($config['csrf_ttl'] ?? 0),
                    'session_lifetime_seconds' => (int)($config['session_lifetime_seconds'] ?? 0),
                    'login_required' => (bool)($config['login_required'] ?? false),
                ],
                'features' => [
                    'novidades_days' => (int)($config['novidades_days'] ?? 0),
                    'kds_refresh_ms' => (int)($config['kds_refresh_ms'] ?? 0),
                    'kds_sla_minutes' => (int)($config['kds_sla_minutes'] ?? 0),
                ],
                'integrations' => [
                    'redis_enabled' => (bool)($config['redis']['enabled'] ?? false),
                    'redis_host' => (string)($config['redis']['host'] ?? ''),
                    'redis_port' => (int)($config['redis']['port'] ?? 0),
                    'vapid_subject' => (string)($config['vapid_subject'] ?? ''),
                ],
            ],
        ]);
    }

    // ─── GET /api/superadmin/system ──────────────────────────────────────────

    public function system(): void
    {
        $this->assertRootAccess();

        require_once __DIR__ . '/../services/ObservabilityService.php';

        $observability = [
            'summary' => ['total' => 0, 'ok' => 0, 'warning' => 0, 'critical' => 0],
            'latest' => [],
        ];

        try {
            $observability = ObservabilityService::dashboard();
        } catch (\Throwable $e) {
            $observability = [
                'summary' => ['total' => 0, 'ok' => 0, 'warning' => 0, 'critical' => 0],
                'latest' => [],
            ];
        }

        $diskFree = @disk_free_space(__DIR__ . '/../../');
        $diskTotal = @disk_total_space(__DIR__ . '/../../');
        $logsPath = __DIR__ . '/../../storage/logs';

        $this->respond(200, [
            'success' => true,
            'data' => [
                'summary' => [
                    'total' => (int)($observability['summary']['total'] ?? 0),
                    'ok' => (int)($observability['summary']['ok'] ?? 0),
                    'warning' => (int)($observability['summary']['warning'] ?? 0),
                    'critical' => (int)($observability['summary']['critical'] ?? 0),
                ],
                'checks' => array_map(static function (array $row): array {
                    return [
                        'component' => (string)($row['component'] ?? ''),
                        'status' => (string)($row['status'] ?? 'unknown'),
                        'message' => (string)($row['message'] ?? ''),
                        'checked_at' => isset($row['checked_at']) ? (string)$row['checked_at'] : null,
                        'metadata_json' => isset($row['metadata_json']) ? (string)$row['metadata_json'] : null,
                    ];
                }, $observability['latest'] ?? []),
                'runtime' => [
                    'php_version' => PHP_VERSION,
                    'app_env' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'local',
                    'timezone' => date_default_timezone_get(),
                    'memory_usage_mb' => (int)round(memory_get_usage(true) / 1024 / 1024),
                    'disk_free_gb' => $diskFree !== false ? round($diskFree / 1024 / 1024 / 1024, 2) : 0,
                    'disk_total_gb' => $diskTotal !== false ? round($diskTotal / 1024 / 1024 / 1024, 2) : 0,
                    'logs_writable' => is_dir($logsPath) && is_writable($logsPath),
                ],
                'updated_at' => date('c'),
            ],
        ]);
    }

    // ─── POST /api/superadmin/system/run-checks ──────────────────────────────

    public function runSystemChecks(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        require_once __DIR__ . '/../services/ObservabilityService.php';

        try {
            $results = ObservabilityService::runHealthChecks();
            $critical = 0;

            foreach ($results as $item) {
                if (($item['status'] ?? '') === 'critical') {
                    $critical++;
                }
            }

            $message = $critical > 0
                ? 'Health checks executados com componentes críticos detectados'
                : 'Health checks executados sem criticidade';

            $this->respond(200, [
                'success' => true,
                'message' => $message,
                'data' => ['message' => $message],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao executar health checks']);
        }
    }

    // ─── GET /api/superadmin/logout ───────────────────────────────────────────

    public function logout(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            if ($token !== '') {
                SuperAdminJwtMiddleware::revokeToken($token);
            }
        }

        $this->respond(200, ['success' => true, 'message' => 'Sessão encerrada']);
    }

    // ─── GET /api/superadmin/logs ──────────────────────────────────────────

    public function logs(): void
    {
        $this->assertRootAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $level = trim((string)($_GET['level'] ?? ''));
        $module = trim((string)($_GET['module'] ?? ''));
        $search = trim((string)($_GET['search'] ?? ''));
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));

        $where = [];
        $bind = [];

        if ($level !== '' && in_array($level, ['debug', 'info', 'warning', 'error', 'critical'], true)) {
            $where[] = 'level = ?';
            $bind[] = $level;
        }

        if ($module !== '') {
            $where[] = 'module = ?';
            $bind[] = $module;
        }

        if ($search !== '') {
            $where[] = 'message LIKE ?';
            $bind[] = '%' . $search . '%';
        }

        if ($dateFrom !== '') {
            $where[] = 'DATE(logged_at) >= ?';
            $bind[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'DATE(logged_at) <= ?';
            $bind[] = $dateTo;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        try {
            $db = Database::getInstance();

            // Count total
            $countStmt = $db->prepare('SELECT COUNT(*) FROM system_logs' . $whereSql);
            $countStmt->execute($bind);
            $total = (int)$countStmt->fetchColumn();

            // Get stats by level
            $statsStmt = $db->prepare(
                'SELECT
                    SUM(CASE WHEN level = \'debug\' THEN 1 ELSE 0 END) AS debug_count,
                    SUM(CASE WHEN level = \'info\' THEN 1 ELSE 0 END) AS info_count,
                    SUM(CASE WHEN level = \'warning\' THEN 1 ELSE 0 END) AS warning_count,
                    SUM(CASE WHEN level = \'error\' THEN 1 ELSE 0 END) AS error_count,
                    SUM(CASE WHEN level = \'critical\' THEN 1 ELSE 0 END) AS critical_count
                 FROM system_logs' . $whereSql
            );
            $statsStmt->execute($bind);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Get logs
            $rowsStmt = $db->prepare(
                'SELECT id, level, module, message, context_json, logged_at
                 FROM system_logs' . $whereSql .
                ' ORDER BY logged_at DESC
                  LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
            );
            $rowsStmt->execute($bind);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->respond(200, [
                'success' => true,
                'data' => [
                    'items' => array_map(static function (array $row): array {
                        return [
                            'id' => (int)($row['id'] ?? 0),
                            'level' => (string)($row['level'] ?? 'info'),
                            'module' => (string)($row['module'] ?? ''),
                            'message' => (string)($row['message'] ?? ''),
                            'context' => is_string($row['context_json']) ? json_decode($row['context_json'], true) : [],
                            'logged_at' => (string)($row['logged_at'] ?? ''),
                        ];
                    }, $rows),
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => max(1, (int)ceil($total / $perPage)),
                        'has_next' => ($offset + $perPage) < $total,
                        'has_prev' => $page > 1,
                    ],
                    'stats' => [
                        'total' => $total,
                        'debug' => (int)($stats['debug_count'] ?? 0),
                        'info' => (int)($stats['info_count'] ?? 0),
                        'warning' => (int)($stats['warning_count'] ?? 0),
                        'error' => (int)($stats['error_count'] ?? 0),
                        'critical' => (int)($stats['critical_count'] ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => 'Falha ao carregar logs']);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getUserPermissions(int $userId): array
    {
        // Root role gets all permissions
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("
                SELECT DISTINCT p.name
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.id
                JOIN roles r ON r.id = rp.role_id
                JOIN super_admin_roles sar ON sar.role_id = r.id
                WHERE sar.user_id = ?
            ");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $rows ?: $this->getAllPermissions();
        } catch (\Throwable $e) {
            // Table may not exist yet — return all permissions for root
            return $this->getAllPermissions();
        }
    }

    private function assertRootAccess(): void
    {
        $claims = SuperAdminJwtMiddleware::authenticate();
        if (($claims['role'] ?? '') !== 'root') {
            $this->respond(403, ['success' => false, 'message' => 'Acesso negado']);
        }
    }

    private function getAllPermissions(): array
    {
        return [
            'monitoring.view', 'stores.view', 'stores.manage',
            'orders.view', 'users.view', 'users.manage',
            'whatsapp.view', 'queues.view', 'queues.manage',
            'logs.view', 'webhooks.view', 'webhooks.manage',
            'system.view', 'audit.view', 'permissions.view',
            'permissions.manage', 'feature_flags.view', 'feature_flags.manage',
            'analytics.view', 'settings.view', 'settings.manage', 'impersonate',
        ];
    }

    private function requireJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * @return never
     */
    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}
