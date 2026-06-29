<?php

namespace DBGPlatform\Database\Repositories;

class AuditLogRepository
{
    public function all(int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_audit_logs';
        $limit = max(1, min(500, $limit));
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A) ?: [];
    }

    public function record(string $action, string $entityType, ?int $entityId = null, array $payload = []): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_audit_logs';

        $wpdb->insert($table, [
            'actor_id' => get_current_user_id() ?: null,
            'action' => sanitize_key($action),
            'entity_type' => sanitize_key($entityType),
            'entity_id' => $entityId,
            'payload' => wp_json_encode($payload),
            'created_at' => current_time('mysql'),
        ]);

        return (int) $wpdb->insert_id;
    }
}
