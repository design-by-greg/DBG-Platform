<?php

namespace DBGPlatform\Database\Repositories;

class ProductionAssignmentRepository
{
    public function forOperation(int $operationId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_production_assignments WHERE operation_id = %d ORDER BY id ASC", $operationId), ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_production_assignments', [
            'operation_id' => absint($data['operation_id'] ?? 0),
            'resource_type' => sanitize_key($data['resource_type'] ?? 'user'),
            'resource_id' => absint($data['resource_id'] ?? 0),
            'assigned_at' => $data['assigned_at'] ?? current_time('mysql'),
            'accepted_at' => $data['accepted_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'created_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForOperation(int $operationId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_production_assignments', ['operation_id' => $operationId]);
    }
}
