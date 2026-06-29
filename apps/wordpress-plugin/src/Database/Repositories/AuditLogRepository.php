<?php

namespace DBGPlatform\Database\Repositories;

class AuditLogRepository
{
    public function all(int $limit = 100): array
    {
        return $this->search([], $limit);
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_audit_logs';
        $limit = max(1, min(500, absint($limit)));

        $where = [];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = 'action = %s';
            $params[] = sanitize_key($filters['action']);
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $params[] = sanitize_key($filters['entity_type']);
        }

        if (!empty($filters['actor_id'])) {
            $where[] = 'actor_id = %d';
            $params[] = absint($filters['actor_id']);
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = %d';
            $params[] = absint($filters['entity_id']);
        }

        $sql = "SELECT * FROM {$table}";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC LIMIT %d';
        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
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
