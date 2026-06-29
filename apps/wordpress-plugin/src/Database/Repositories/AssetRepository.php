<?php

namespace DBGPlatform\Database\Repositories;

class AssetRepository
{
    public function all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => isset($data['project_id']) ? absint($data['project_id']) : null,
            'type' => sanitize_text_field($data['type'] ?? 'document'),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';

        return false !== $wpdb->update($table, [
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0),
            'type' => sanitize_text_field($data['type'] ?? 'document'),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';

        return false !== $wpdb->update($table, [
            'status' => 'archived',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }
}
