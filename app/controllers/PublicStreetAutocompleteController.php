<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/AddressAutocompleteService.php';

/**
 * Autocomplete de ruas - Enterprise Edition
 * 
 * Arquitetura multi-camada:
 *   Redis cache → MySQL FULLTEXT → MySQL LIKE → Overpass API fallback
 * 
 * Endpoints:
 *   GET  /{slug}/street-autocomplete?q=...&city=...&neighborhood=...
 *   POST /{slug}/street-autocomplete/popularity  (incrementa score)
 *   POST /{slug}/street-autocomplete/learn        (aprende rua nova)
 */
class PublicStreetAutocompleteController extends Controller
{
    /**
     * GET /{slug}/street-autocomplete?q=...&city=...&neighborhood=...
     */
    public function search(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            echo json_encode(['results' => []]);
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $city = trim($_GET['city'] ?? '');
        $neighborhood = trim($_GET['neighborhood'] ?? '');

        if ($query === '' || mb_strlen($query) < 2 || $city === '') {
            echo json_encode(['results' => []]);
            return;
        }

        $companyId = (int)($company['id'] ?? 0);
        $service = new AddressAutocompleteService(db(), $companyId);
        
        $result = $service->search($query, $city, $neighborhood);
        
        // Format for frontend - street + validated flag
        $output = [];
        foreach ($result['results'] as $item) {
            $entry = [
                'street' => $item['street'],
                'display' => $item['street'],
                'validated' => true, // only active entries reach here
            ];
            
            if (isset($item['id']) && $item['id'] > 0) {
                $entry['id'] = $item['id'];
            }
            
            if (!empty($item['neighborhood'])) {
                $entry['neighborhood'] = $item['neighborhood'];
            }
            
            $output[] = $entry;
        }

        echo json_encode([
            'results' => $output,
            'source' => $result['source'] ?? 'unknown',
            'timing_ms' => $result['timing_ms'] ?? 0,
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * POST /{slug}/street-autocomplete/popularity
     * Body: { "street_id": 123 }
     */
    public function popularity(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $streetId = (int)($input['street_id'] ?? 0);
        
        if ($streetId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_street_id']);
            return;
        }
        
        $companyId = (int)($company['id'] ?? 0);
        $service = new AddressAutocompleteService(db(), $companyId);
        $service->incrementPopularity($streetId);
        
        echo json_encode(['ok' => true]);
    }
    
    /**
     * POST /{slug}/street-autocomplete/learn
     * Body: { "city": "...", "neighborhood": "...", "street": "..." }
     */
    public function learn(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $city = trim($input['city'] ?? '');
        $neighborhood = trim($input['neighborhood'] ?? '');
        $street = trim($input['street'] ?? '');
        
        if ($city === '' || $street === '') {
            http_response_code(400);
            echo json_encode(['error' => 'missing_fields']);
            return;
        }
        
        // Sanitize: limit lengths
        $city = mb_substr($city, 0, 120);
        $neighborhood = mb_substr($neighborhood, 0, 120);
        $street = mb_substr($street, 0, 255);
        
        $companyId = (int)($company['id'] ?? 0);
        $service = new AddressAutocompleteService(db(), $companyId);
        $service->learnStreet($city, $neighborhood, $street);
        
        echo json_encode(['ok' => true]);
    }
}
