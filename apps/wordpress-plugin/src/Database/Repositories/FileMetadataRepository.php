<?php

namespace DBGPlatform\Database\Repositories;

class FileMetadataRepository
{
    public function allForFile(int $fileId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_metadata';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$table} WHERE file_record_id = %d ORDER BY meta_key ASC", $fileId), ARRAY_A) ?: [];
        $metadata = [];
        foreach ($rows as $row) {
            $metadata[$row['meta_key']] = maybe_unserialize($row['meta_value']);
        }
        return $metadata;
    }

    public function set(int $fileId, string $key, $value): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_metadata';
        $key = sanitize_key($key);
        $now = current_time('mysql');

        if ($key === '') {
            return false;
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE file_record_id = %d AND meta_key = %s", $fileId, $key));
        $payload = [
            'file_record_id' => $fileId,
            'meta_key' => $key,
            'meta_value' => maybe_serialize($value),
            'updated_at' => $now,
        ];

        if ($existing) {
            return false !== $wpdb->update($table, $payload, ['id' => absint($existing)]);
        }

        $payload['created_at'] = $now;
        return false !== $wpdb->insert($table, $payload);
    }

    public function sync(int $fileId, array $metadata): int
    {
        $count = 0;
        foreach ($metadata as $key => $value) {
            $count += $this->set($fileId, (string) $key, $value) ? 1 : 0;
        }
        return $count;
    }

    public function delete(int $fileId, string $key): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_metadata';
        return false !== $wpdb->delete($table, ['file_record_id' => $fileId, 'meta_key' => sanitize_key($key)]);
    }
}
