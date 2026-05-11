<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/models/Company.php';
require_once __DIR__ . '/../app/models/EvolutionInstance.php';
require_once __DIR__ . '/../app/controllers/AdminEvolutionController.php';

$slug = $argv[1] ?? 'wollburger';
$company = Company::findBySlug($slug);
if (!$company) {
    echo "Company with slug '{$slug}' not found\n";
    exit(1);
}

$controller = new AdminEvolutionController();
$ref = new ReflectionClass($controller);
$m = $ref->getMethod('evolutionApiRequest');
$m->setAccessible(true);

$candidates = [
    '/instance/fetchInstances',
    '/instance/getAll',
    '/instances',
    '/api/instances',
    '/api/v2/instances',
    '/instance/list',
];

$all = null; $lastRes = null;
foreach ($candidates as $p) {
    echo "Trying: {$p}\n";
    $res = $m->invoke($controller, $company, $p, 'GET', null);
    $lastRes = $res;
    if (!isset($res['error']) && isset($res['data'])) {
        $data = $res['data'];
        if (isset($data['instances']) && is_array($data['instances'])) $all = $data['instances'];
        elseif (isset($data['data']) && is_array($data['data'])) $all = $data['data'];
        elseif (is_array($data)) $all = $data;
        if (is_array($all)) break;
    }
}

if (!is_array($all)) {
    echo "Failed to list instances: " . ($lastRes['error'] ?? 'no response') . "\n";
    exit(2);
}

echo "Found " . count($all) . " remote instances\n";

$existing = EvolutionInstance::allForCompany((int)$company['id']);
$existingIds = array_column($existing, 'instance_identifier');

$imported = 0; $skipped = 0;
foreach ($all as $item) {
    if (!is_array($item)) continue;
    $instance_identifier = $item['instance_identifier'] ?? ($item['id'] ?? ($item['instanceName'] ?? null));
    if (!$instance_identifier) continue;
    if (in_array($instance_identifier, $existingIds, true)) { $skipped++; continue; }

    $number = $item['number'] ?? $item['phone'] ?? null;
    $qr = $item['qr_code'] ?? $item['qr'] ?? null;
    $label = $item['label'] ?? $item['name'] ?? $number;
    $status = $item['status'] ?? $item['state'] ?? 'pending';

    EvolutionInstance::create((int)$company['id'], [
        'label' => $label,
        'number' => $number,
        'instance_identifier' => $instance_identifier,
        'qr_code' => $qr,
        'status' => $status,
    ]);
    $imported++;
}

echo "Imported={$imported}, Skipped={$skipped}\n";
exit(0);
