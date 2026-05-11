<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/FeatureFlag.php';

class FeatureFlagService
{
    public static function getOverview(int $companyId): array
    {
        return [
            'all_flags' => FeatureFlag::all(),
            'tenant_flags' => FeatureFlag::tenantFlags($companyId),
        ];
    }

    public static function toggleTenantFeature(
        int $companyId,
        int $featureFlagId,
        bool $enabled,
        int $changedBy,
        ?string $reason = null
    ): array {
        $current = FeatureFlag::getTenantFlag($companyId, $featureFlagId);
        $oldEnabled = (int)($current['enabled'] ?? 0) === 1;

        $saved = FeatureFlag::setTenantFlag($companyId, $featureFlagId, $enabled, $changedBy);
        if (!$saved) {
            return ['success' => false, 'message' => 'Nao foi possivel atualizar o feature flag.'];
        }

        FeatureFlag::addHistory($companyId, $featureFlagId, $oldEnabled, $enabled, $changedBy, $reason);

        return [
            'success' => true,
            'message' => 'Feature flag atualizado com sucesso.',
        ];
    }
}
