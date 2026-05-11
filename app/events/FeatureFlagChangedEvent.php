<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseEvent.php';

class FeatureFlagChangedEvent extends BaseEvent
{
    public function __construct(int $featureFlagId, int $companyId, array $payload = [], ?int $dispatchedBy = null)
    {
        parent::__construct('feature_flag.changed', 'feature_flag', $featureFlagId, $companyId, $payload, $dispatchedBy, 'feature_flags');
    }
}
