<?php

namespace DBGPlatform\Database\Repositories;

class FileRecordRepository
{
    public function all(int $limit = 100): array
    {
        return $this->search([], $limit);
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $limit = max(1, min(500, absint($limit)));

        $where = [];
        $params = [];

        if (!empty($filters['organisation_id'])) {
            $where[] = 'organisation_id = %d';
            $params[] = absint($filters['organisation_id']);
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'project_id = %d';
            $params[] = absint($filters['project_id']);
        }

        if (!empty($filters['asset_id'])) {
            $where[] = 'asset_id = %d';
            $params[] = absint($filters['asset_id']);
        }

        if (!empty($filters['mime_type'])) {
            $where[] = 'mime_type LIKE %s';
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['mime_type'])) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_key($filters['status']);
        }

        if (!empty($filters['search'])) {
            $where[] = '(original_name LIKE %s OR filename LIKE %s)';
            $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT * FROM {$table}";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC LIMIT %d';
        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'asset_id' => absint($data['asset_id'] ?? 0),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0),
            'original_name' => sanitize_file_name($data['original_name'] ?? ''),
            'filename' => sanitize_file_name($data['filename'] ?? ''),
            'mime_type' => sanitize_text_field($data['mime_type'] ?? ''),
            'size' => absint($data['size'] ?? 0),
            'path' => sanitize_text_field($data['path'] ?? ''),
            'url' => esc_url_raw($data['url'] ?? ''),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function archive(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';

        return false !== $wpdb->update($table, [
            'status' => 'archived',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}
