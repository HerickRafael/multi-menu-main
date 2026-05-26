#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * iFood Shipping Orders — Status Polling Cron
 *
 * Sincroniza o estado dos shipping orders ativos via GET
 * /shipping/v1.0/orders/{ifood_shipping_id}.
 *
 * Estratégia de seleção:
 *   - Procura rows com `next_poll_at <= NOW()` e status NÃO terminal.
 *   - Status terminais (DELIVERED, CANCELLED, REJECTED, FAILED) já têm
 *     next_poll_at=NULL e nunca são pegos.
 *   - Após cada poll, recalcula next_poll_at conforme a etapa:
 *       SUBMITTED        → 30s (estado volátil; iFood aceita ou rejeita rápido)
 *       ACCEPTED         → 60s (aguardando driver)
 *       CONFIRMED        → 90s (driver atribuído)
 *       PICKED_UP        → 120s (em rota)
 *       *terminal*       → NULL (para de polar)
 *
 * Cap por execução (CAP env, default 100) — protege contra explosão de
 * pedidos antigos esquecidos.
 *
 * Cron sugerido (a cada 1 min):
 *   * * * * * php /path/to/scripts/ifood_shipping_poll_cron.php >> /var/log/ifood_shipping_poll.log 2>&1
 *
 * Uso manual:
 *   php scripts/ifood_shipping_poll_cron.php [company_id]
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/IFood/IFoodEndpoints.php';
require_once __DIR__ . '/../app/services/IFood/IFoodApiLogger.php';
require_once __DIR__ . '/../app/services/IFood/IFoodClient.php';
require_once __DIR__ . '/../app/services/IFood/IFoodResponse.php';
require_once __DIR__ . '/../app/services/IFoodService.php';

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodClient;
use App\Services\IFood\IFoodEndpoints;

$cap = max(1, (int) (getenv('CAP') ?: '100'));
$onlyCompanyId = isset($argv[1]) ? (int) $argv[1] : 0;

$db = db();
$logger = new IFoodApiLogger($db);
$ts = static fn(): string => '[' . date('Y-m-d H:i:s') . ']';

echo $ts() . " Iniciando shipping poll (cap={$cap})\n";

// Mapeia status retornado pelo iFood para nosso enum.
// O iFood usa códigos como ACCEPTED, CONFIRMED, DRIVER_ASSIGNED, PICKED_UP,
// COMPLETED, CANCELLED... Mapeamos o mais robustamente que dá; tudo o que
// não bater fica como o status atual (no-op).
$mapStatus = static function (string $remote): ?string {
    $remote = strtoupper(trim($remote));
    $map = [
        'ACCEPTED'        => 'ACCEPTED',
        'CONFIRMED'       => 'CONFIRMED',
        'DRIVER_ASSIGNED' => 'CONFIRMED',
        'DISPATCHED'      => 'CONFIRMED',
        'PICKED_UP'       => 'PICKED_UP',
        'GOING_TO_DESTINATION' => 'PICKED_UP',
        'COMPLETED'       => 'DELIVERED',
        'DELIVERED'       => 'DELIVERED',
        'CONCLUDED'       => 'DELIVERED',
        'CANCELLED'       => 'CANCELLED',
        'CANCELED'        => 'CANCELLED',
        'REJECTED'        => 'REJECTED',
    ];
    return $map[$remote] ?? null;
};

$nextPollFor = static function (string $status): ?int {
    return match ($status) {
        'SUBMITTED' => 30,
        'ACCEPTED'  => 60,
        'CONFIRMED' => 90,
        'PICKED_UP' => 120,
        default     => null, // terminal — para de polar
    };
};

$tsColumnFor = static function (string $status): ?string {
    return match ($status) {
        'ACCEPTED'  => 'accepted_at',
        'PICKED_UP' => 'picked_up_at',
        'DELIVERED' => 'delivered_at',
        'CANCELLED' => 'cancelled_at',
        default     => null,
    };
};

// Seleciona companies para polar.
$sqlComp = 'SELECT i.company_id, i.environment, c.name AS company_name
             FROM ifood_integrations i
             INNER JOIN companies c ON c.id = i.company_id
            WHERE i.is_active = 1';
$bind = [];
if ($onlyCompanyId > 0) {
    $sqlComp .= ' AND i.company_id = ?';
    $bind[] = $onlyCompanyId;
}
$stmtC = $db->prepare($sqlComp);
$stmtC->execute($bind);
$companies = $stmtC->fetchAll(PDO::FETCH_ASSOC);

if (empty($companies)) {
    echo $ts() . " Nenhuma integração ativa.\n";
    exit(0);
}

$totalChecked = 0;
$totalUpdated = 0;
$totalErrors = 0;

foreach ($companies as $comp) {
    $companyId = (int) $comp['company_id'];
    $envCompany = IFoodEndpoints::normalizeEnvironment((string) $comp['environment']);

    $stmt = $db->prepare(
        "SELECT id, external_reference, environment, ifood_shipping_id, status
           FROM ifood_shipping_orders
          WHERE company_id = :cid
            AND ifood_shipping_id IS NOT NULL
            AND status NOT IN ('DELIVERED', 'CANCELLED', 'REJECTED', 'FAILED', 'PENDING')
            AND (next_poll_at IS NULL OR next_poll_at <= NOW())
          ORDER BY next_poll_at ASC
          LIMIT :cap"
    );
    $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    $stmt->bindValue(':cap', $cap, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        continue;
    }

    echo $ts() . " Company {$companyId} ({$comp['company_name']}): " . count($rows) . " pedido(s) a polar\n";

    $service = new \IFoodService($db, $companyId);
    $token = $service->getAccessToken();
    if ($token === null || $token === '') {
        echo $ts() . "   ↳ sem token, pulando company {$companyId}\n";
        continue;
    }

    foreach ($rows as $row) {
        $totalChecked++;
        $rowId = (int) $row['id'];
        $env = IFoodEndpoints::normalizeEnvironment((string) $row['environment']);
        $shippingId = (string) $row['ifood_shipping_id'];

        $client = new IFoodClient(
            companyId: $companyId,
            environment: $env,
            accessToken: $token,
            logger: $logger,
            jobId: null,
            maxAttempts: 2,
            timeoutSeconds: 15
        );

        $response = $client->get(
            IFoodEndpoints::shippingOrder($env, $shippingId),
            IFoodEndpoints::MODULE_SHIPPING
        );

        if (!$response->ok) {
            $totalErrors++;
            // 404 = order desapareceu do iFood → marca FAILED para parar de polar.
            if ($response->status === 404) {
                $db->prepare(
                    "UPDATE ifood_shipping_orders
                        SET status = 'FAILED',
                            last_error = 'iFood retornou 404 no polling',
                            last_response_status = 404,
                            next_poll_at = NULL
                      WHERE id = :id"
                )->execute([':id' => $rowId]);
                continue;
            }
            // Outros erros: bumpa retries e reagenda em 2 min.
            $db->prepare(
                "UPDATE ifood_shipping_orders
                    SET retries = retries + 1,
                        last_error = :err,
                        last_response_status = :hs,
                        next_poll_at = DATE_ADD(NOW(), INTERVAL 120 SECOND)
                  WHERE id = :id"
            )->execute([
                ':err' => mb_substr((string) ($response->error ?? 'poll error'), 0, 500),
                ':hs'  => $response->status,
                ':id'  => $rowId,
            ]);
            continue;
        }

        $remoteStatusRaw = '';
        if (is_array($response->body)) {
            $remoteStatusRaw = (string) ($response->body['status']
                ?? $response->body['fullCode']
                ?? $response->body['state']
                ?? '');
        }
        $mapped = $remoteStatusRaw !== '' ? $mapStatus($remoteStatusRaw) : null;

        // Se o iFood não devolveu status reconhecível, mantém o atual e reagenda.
        if ($mapped === null) {
            $db->prepare(
                "UPDATE ifood_shipping_orders
                    SET next_poll_at = DATE_ADD(NOW(), INTERVAL 60 SECOND),
                        response_payload = :resp
                  WHERE id = :id"
            )->execute([
                ':resp' => json_encode($response->body, JSON_UNESCAPED_UNICODE),
                ':id'   => $rowId,
            ]);
            continue;
        }

        // Houve mudança de status?
        $currentLocal = (string) $row['status'];
        if ($mapped === $currentLocal) {
            // Mesmo status — só atualiza next_poll_at.
            $nextSeconds = $nextPollFor($mapped);
            $db->prepare(
                "UPDATE ifood_shipping_orders
                    SET next_poll_at = " . ($nextSeconds === null ? "NULL" : "DATE_ADD(NOW(), INTERVAL {$nextSeconds} SECOND)") . ",
                        response_payload = :resp
                  WHERE id = :id"
            )->execute([
                ':resp' => json_encode($response->body, JSON_UNESCAPED_UNICODE),
                ':id'   => $rowId,
            ]);
            continue;
        }

        // Status mudou — atualiza e popula timestamp da etapa correspondente.
        $tsCol = $tsColumnFor($mapped);
        $nextSeconds = $nextPollFor($mapped);
        $sets = ["status = :st", "response_payload = :resp"];
        $sets[] = $nextSeconds === null
            ? "next_poll_at = NULL"
            : "next_poll_at = DATE_ADD(NOW(), INTERVAL {$nextSeconds} SECOND)";
        if ($tsCol !== null) {
            $sets[] = "{$tsCol} = COALESCE({$tsCol}, NOW())";
        }

        $db->prepare(
            "UPDATE ifood_shipping_orders SET " . implode(', ', $sets) . " WHERE id = :id"
        )->execute([
            ':st'   => $mapped,
            ':resp' => json_encode($response->body, JSON_UNESCAPED_UNICODE),
            ':id'   => $rowId,
        ]);

        $totalUpdated++;
        echo $ts() . "   ↳ {$row['external_reference']}: {$currentLocal} → {$mapped}\n";
    }
}

echo $ts() . " Concluído. checked={$totalChecked} updated={$totalUpdated} errors={$totalErrors}\n";
