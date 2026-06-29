<?php

namespace DBGPlatform\Database\Repositories;

class FileVersionRepository
{
    public function allForFile(int $fileRecordId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_versions';

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE file_record_id = %d ORDER BY version_number DESC", $fileRecordId),
            ARRAY_A
        ) ?: [];
    }

    public function nextVersionNumber(int $fileRecordId): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_versions';

        $max = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_number) FROM {$table} WHERE file_record_id = %d",
            $fileRecordId
        ));

        return $max + 1;
    }

    public function create(int $fileRecordId, array $data, string $note = ''): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_versions';

        $wpdb->insert($table, [
            'file_record_id' => $fileRecordId,
            'version_number' => $this->nextVersionNumber($fileRecordId),
            'original_name' => sanitize_file_name($data['original_name'] ?? ''),
            'filename' => sanitize_file_name($data['filename'] ?? ''),
            'mime_type' => sanitize_text_field($data['mime_type'] ?? ''),
            'size' => absint($data['size'] ?? 0),
            'path' => sanitize_text_field($data['path'] ?? ''),
            'url' => esc_url_raw($data['url'] ?? ''),
            'note' => sanitize_textarea_field($note),
            'created_by' => get_current_user_id() ?: null,
            'created_at' => current_time('mysql'),
        ]);

        return (int) $wpdb->insert_id;
    }
}
