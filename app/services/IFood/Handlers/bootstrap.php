<?php

declare(strict_types=1);

/**
 * Carregado automaticamente por scripts/ifood_worker.php.
 *
 * Recebe duas variáveis do contexto do worker:
 *   $dispatcher : App\Services\IFood\IFoodJobDispatcher
 *
 * Cada arquivo de handler é requerido aqui (sem autoloader PSR-4 dedicado
 * para App\Services), e registrado contra um job_type.
 */

use App\Services\IFood\IFoodApiLogger;
use App\Services\IFood\IFoodJobDispatcher;
use App\Services\IFood\Handlers\ReviewsFetchHandler;
use App\Services\IFood\Handlers\StockSyncHandler;
use App\Services\IFood\Handlers\DriverRequestHandler;
use App\Services\IFood\Handlers\DriverCancelHandler;
use App\Services\IFood\Handlers\ShippingOrderCreateHandler;
use App\Services\IFood\Handlers\ShippingOrderCancelHandler;

/** @var IFoodJobDispatcher $dispatcher */
if (!isset($dispatcher) || !$dispatcher instanceof IFoodJobDispatcher) {
    throw new \RuntimeException('Handlers/bootstrap.php: $dispatcher não fornecido pelo worker.');
}

$db = db();
$apiLogger = new IFoodApiLogger($db);

require_once __DIR__ . '/ReviewsFetchHandler.php';
require_once __DIR__ . '/StockSyncHandler.php';
require_once __DIR__ . '/DriverRequestHandler.php';
require_once __DIR__ . '/DriverCancelHandler.php';
require_once __DIR__ . '/ShippingOrderCreateHandler.php';
require_once __DIR__ . '/ShippingOrderCancelHandler.php';

$dispatcher->register(
    'ifood.reviews.fetch',
    new ReviewsFetchHandler($db, $apiLogger)
);

$dispatcher->register(
    'ifood.stock.sync',
    new StockSyncHandler($db, $apiLogger)
);

$dispatcher->register(
    'ifood.driver.request',
    new DriverRequestHandler($db, $apiLogger)
);

$dispatcher->register(
    'ifood.driver.cancel',
    new DriverCancelHandler($db, $apiLogger)
);

$dispatcher->register(
    'ifood.shipping.create',
    new ShippingOrderCreateHandler($db, $apiLogger)
);

$dispatcher->register(
    'ifood.shipping.cancel',
    new ShippingOrderCancelHandler($db, $apiLogger)
);
