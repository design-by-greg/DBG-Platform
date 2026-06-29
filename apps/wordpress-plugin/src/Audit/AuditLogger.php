<?php

namespace DBGPlatform\Audit;

use DBGPlatform\Database\Repositories\AuditLogRepository;

class AuditLogger
{
    private AuditLogRepository $logs;

    public function __construct()
    {
        $this->logs = new AuditLogRepository();
    }

    public function record(string $action, string $entityType, ?int $entityId = null, array $payload = []): int
    {
        return $this->logs->record($action, $entityType, $entityId, $payload);
    }
}
