<?php

declare(strict_types=1);

use App\Repositories\CategoryRepository;

class CategoryService
{
    private static function repo(): CategoryRepository
    {
        return new CategoryRepository(db());
    }

    public static function listForAdmin(int $companyId): array
    {
        return self::repo()->allByCompany($companyId);
    }

    public static function listForMobile(int $companyId): array
    {
        return self::repo()->listWithProductStatsByCompany($companyId);
    }

    public static function findForCompany(int $companyId, int $categoryId): ?array
    {
        return self::repo()->findByCompanyAndId($companyId, $categoryId);
    }

    public static function save(int $companyId, array $payload, ?int $categoryId = null): int
    {
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Nome é obrigatório');
        }

        $description = trim((string)($payload['description'] ?? ''));
        $image = $payload['image'] ?? null;
        $active = !empty($payload['active']) ? 1 : 0;
        $sortOrder = (int)($payload['sort_order'] ?? 0);
        if ($categoryId === null) {
            $newId = self::repo()->create($companyId, [
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'active' => $active,
                'sort_order' => $sortOrder,
            ]);
            SmartCache::forgetByPattern("categories:company:{$companyId}:*");
            return $newId;
        }

        self::repo()->update($companyId, $categoryId, [
            'name' => $name,
            'description' => $description,
            'image' => $image,
            'active' => $active,
            'sort_order' => $sortOrder,
        ]);

        SmartCache::forget("categories:id:{$categoryId}");
        SmartCache::forgetByPattern("categories:company:{$companyId}:*");

        return $categoryId;
    }

    public static function delete(int $companyId, int $categoryId): bool
    {
        if (self::repo()->countProducts($categoryId) > 0) {
            return false;
        }

        $deleted = self::repo()->delete($companyId, $categoryId);

        SmartCache::forget("categories:id:{$categoryId}");
        SmartCache::forgetByPattern("categories:company:{$companyId}:*");
        return $deleted;
    }

    public static function toggle(int $companyId, int $categoryId): ?int
    {
        $newStatus = self::repo()->toggleStatus($companyId, $categoryId);
        if ($newStatus === null) {
            return null;
        }

        SmartCache::forget("categories:id:{$categoryId}");
        SmartCache::forgetByPattern("categories:company:{$companyId}:*");
        return $newStatus;
    }

    public static function uploadImage(?array $file, string $prefix = 'category'): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $name = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/' . $name;
        $dir = dirname($dest);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file((string)$file['tmp_name'], $dest)) {
            return 'uploads/' . $name;
        }

        return null;
    }
}