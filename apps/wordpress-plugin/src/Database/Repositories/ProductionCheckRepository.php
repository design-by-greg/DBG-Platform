<?php

namespace DBGPlatform\Database\Repositories;

class ProductionCheckRepository
{
    public function listForOperation(int $operationId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_production_checklists';
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' WHERE operation_id = %d', $operationId), ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_production_checklists', [
            'operation_id' => absint($data['operation_id'] ?? 0),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'is_completed' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function setCompleted(int $id): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_production_checklists', [
            'is_completed' => 1,
            'completed_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}
