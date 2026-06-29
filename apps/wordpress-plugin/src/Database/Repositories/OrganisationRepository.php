<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationRepository
{
    public function all(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisations';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisations';
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'type' => sanitize_text_field($data['type'] ?? 'company'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }
}
