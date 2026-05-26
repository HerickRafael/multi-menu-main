<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/CustomizationTemplate.php';
require_once __DIR__ . '/../modules/products/ProductImageService.php';

use App\Repositories\ProductRepository;

class ProductService
{
    private static function repo(): ProductRepository
    {
        return new ProductRepository(db());
    }

    public static function listForAdmin(int $companyId): array
    {
        return self::repo()->listByCompany($companyId);
    }

    public static function listForMobile(int $companyId, string $search = '', ?int $categoryId = null, string $status = 'all'): array
    {
        return self::repo()->listByFiltersForMobile($companyId, $search, $categoryId, $status);
    }

    public static function productStats(int $companyId): array
    {
        return self::repo()->statsByCompany($companyId);
    }

    public static function buildFormData(int $companyId, ?int $productId = null): array
    {
        $product = $productId ? Product::find($productId, false, $companyId) : null;
        if (!$product) {
            $product = [
                'id' => null,
                'name' => '',
                'description' => '',
                'price' => 0.0,
                'promo_price' => null,
                'promo_start_at' => null,
                'promo_end_at' => null,
                'sku' => Product::nextSkuForCompany($companyId),
                'sort_order' => 0,
                'active' => 1,
                'category_id' => null,
                'image' => null,
                'type' => 'simple',
                'price_mode' => 'fixed',
            ];
        }

        return [
            'product' => $product,
            'categories' => Category::allByCompany($companyId),
            'ingredients' => Ingredient::allForCompany($companyId),
            'simpleProducts' => Product::simpleProductsForCompany($companyId, false),
            'customization' => [
                'enabled' => !empty($product['allow_customize']),
                'groups' => !empty($product['id']) ? ProductCustomization::loadForAdmin((int)$product['id']) : [],
            ],
            'groups' => !empty($product['id']) ? Product::getComboGroupsWithItems((int)$product['id']) : [],
            'custTemplates' => CustomizationTemplate::listWithItemsForCompany($companyId),
        ];
    }

    public static function findForCompany(int $companyId, int $productId): ?array
    {
        return self::repo()->findByCompanyAndId($companyId, $productId);
    }

    public static function save(int $companyId, array $payload, ?array $file = null, ?int $productId = null, ?string &$error = null): ?int
    {
        $error = null;
        $name = trim((string)($payload['name'] ?? ''));
        $price = (float)str_replace(',', '.', (string)($payload['price'] ?? '0'));
        if ($name === '' || $price <= 0) {
            $error = 'Nome e preço são obrigatórios';
            return null;
        }

        $existing = $productId ? self::findForCompany($companyId, $productId) : null;
        if ($productId && !$existing) {
            $error = 'Produto não encontrado';
            return null;
        }

        $upload = ProductImageService::upload($file);
        if (!empty($upload['error'])) {
            $error = (string)$upload['error'];
            return null;
        }

        $type = (($payload['type'] ?? 'simple') === 'combo') ? 'combo' : 'simple';
        $priceMode = (($payload['price_mode'] ?? 'fixed') === 'sum') ? 'sum' : 'fixed';
        $promoInput = $priceMode === 'sum' ? ($payload['promo_percentage'] ?? null) : ($payload['promo_price'] ?? null);
        $promoPrice = self::sanitizePromoPrice($promoInput, $price, $priceMode);
        $custPayload = $payload['customization'] ?? [];
        $custData = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], $companyId);
        $useGroups = $type === 'combo' && (!empty($payload['use_groups']) || !empty($payload['groups']));
        $groupsPayload = $useGroups && !empty($payload['groups']) && is_array($payload['groups'])
            ? Product::sanitizeComboGroupsPayload($payload['groups'], $companyId)
            : [];

        $data = [
            'company_id' => $companyId,
            'category_id' => ($payload['category_id'] ?? '') !== '' ? (int)$payload['category_id'] : null,
            'name' => $name,
            'description' => trim((string)($payload['description'] ?? '')),
            'price' => $price,
            'promo_price' => $promoPrice,
            'promo_start_at' => !empty($payload['promo_start_at']) ? (string)$payload['promo_start_at'] : null,
            'promo_end_at' => !empty($payload['promo_end_at']) ? (string)$payload['promo_end_at'] : null,
            'sku' => $existing['sku'] ?? Product::nextSkuForCompany($companyId),
            'image' => $upload['path'] ?? ($existing['image'] ?? null),
            'active' => !empty($payload['active']) ? 1 : 0,
            'sort_order' => (int)($payload['sort_order'] ?? 0),
            'allow_customize' => $type === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
            'type' => $type,
            'price_mode' => $priceMode,
        ];

        $oldActive = $existing !== null ? (int) ($existing['active'] ?? 0) : null;

        if ($productId === null) {
            $productId = self::repo()->create($data);
        } else {
            self::repo()->update($productId, $data);
        }

        ProductCustomization::save($productId, $custData);
        Product::saveComboGroupsAndItems($productId, $type === 'combo' ? $groupsPayload : []);

        if ($productId !== null && $oldActive !== null && $oldActive !== (int) $data['active']) {
            self::dispatchIFoodStockSync($companyId, (int) $productId);
        }

        return $productId;
    }

    public static function delete(int $companyId, int $productId): bool
    {
        $product = self::findForCompany($companyId, $productId);
        if (!$product) {
            return false;
        }

        self::repo()->delete($productId);
        return true;
    }

    public static function toggle(int $companyId, int $productId): ?int
    {
        $newStatus = self::repo()->toggleStatus($companyId, $productId);
        if ($newStatus === null) {
            return null;
        }

        require_once __DIR__ . '/../services/ProductCache.php';
        $cache = ProductCache::instance();
        $cache->invalidateProduct($productId);
        $cache->invalidateCombosContainingProduct($productId);

        self::dispatchIFoodStockSync($companyId, $productId);

        return $newStatus;
    }

    public static function searchSimpleProducts(int $companyId, string $search, int $limit = 20): array
    {
        return self::repo()->searchSimpleByCompany($companyId, $search, $limit);
    }

    private static function sanitizePromoPrice($input, float $basePrice, string $priceMode = 'fixed'): ?float
    {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            $input = reset($input);
        }

        $raw = str_replace(' ', '', trim((string)$input));
        if ($raw === '') {
            return null;
        }

        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);

        if ($priceMode === 'sum') {
            if (substr($raw, -1) === '%') {
                $raw = trim(substr($raw, 0, -1));
            }
            if (!is_numeric($raw)) {
                return null;
            }
            $promo = (float)$raw;
            return ($promo > 0 && $promo < 100) ? $promo : null;
        }

        if (!is_numeric($raw)) {
            return null;
        }

        $promo = (float)$raw;
        if ($promo <= 0 || $promo >= $basePrice) {
            return null;
        }

        return $promo;
    }

    /**
     * Best-effort: enfileira sincronização do status do produto com o catálogo do iFood.
     * Nunca lança — qualquer erro é logado e ignorado (sync de catálogo não pode
     * derrubar a edição do produto).
     */
    private static function dispatchIFoodStockSync(int $companyId, int $productId): void
    {
        try {
            require_once __DIR__ . '/IFood/StockSyncDispatcher.php';
            \App\Services\IFood\StockSyncDispatcher::syncProduct($companyId, $productId);
        } catch (\Throwable $e) {
            error_log('[ProductService] dispatchIFoodStockSync falhou: ' . $e->getMessage());
        }
    }
}