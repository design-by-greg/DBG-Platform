<?php

namespace DBGPlatform\Database\Repositories;

class ProjectRepository
{
    public function all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_projects';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_projects';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_projects';
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_projects';

        return false !== $wpdb->update($table, [
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_projects';

        return false !== $wpdb->update($table, [
            'status' => 'archived',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}
