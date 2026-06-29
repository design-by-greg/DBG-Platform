<?php

namespace DBGPlatform\Database\Repositories;

class MediaFolderRepository
{
    public function all(array $filters = []): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_folders';
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

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_key($filters['status']);
        }

        $sql = "SELECT * FROM {$table}";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY name ASC';

        return !empty($params)
            ? ($wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [])
            : ($wpdb->get_results($sql, ARRAY_A) ?: []);
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_folders';
        $name = sanitize_text_field($data['name'] ?? 'Folder');
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0),
            'parent_id' => absint($data['parent_id'] ?? 0),
            'name' => $name,
            'slug' => sanitize_title($name),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function archive(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_folders';

        return false !== $wpdb->update($table, [
            'status' => 'archived',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}
