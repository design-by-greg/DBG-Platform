<?php

namespace DBGPlatform\Database\Repositories;

class ProductionOperationRepository
{
    public function forJob(int $jobId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_production_operations WHERE job_id = %d ORDER BY sort_order ASC, id ASC", $jobId), ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_production_operations', [
            'job_id' => absint($data['job_id'] ?? 0),
            'operation_type' => sanitize_key($data['operation_type'] ?? 'generic'),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'todo'),
            'sort_order' => absint($data['sort_order'] ?? 0),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForJob(int $jobId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_production_operations', ['job_id' => $jobId]);
    }
}
