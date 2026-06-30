<?php

namespace DBGPlatform\Database\Repositories;

class MediaTagRepository
{
    public function all(string $status = 'active'): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_tags';
        $status = sanitize_key($status);

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE status = %s ORDER BY name ASC", $status),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_tags';
        $name = sanitize_text_field($data['name'] ?? 'Tag');
        $now = current_time('mysql');

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s", sanitize_title($name)));
        if ($existing) {
            return (int) $existing;
        }

        $wpdb->insert($table, [
            'name' => $name,
            'slug' => sanitize_title($name),
            'color' => sanitize_hex_color($data['color'] ?? '') ?: null,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function assignToFile(int $fileId, array $tagIds): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_tag_map';
        $count = 0;

        foreach ($this->cleanIds($tagIds) as $tagId) {
            $inserted = $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$table} (file_record_id, tag_id, created_at) VALUES (%d, %d, %s)",
                $fileId,
                $tagId,
                current_time('mysql')
            ));
            $count += $inserted ? 1 : 0;
        }

        return $count;
    }

    public function syncFileTags(int $fileId, array $tagIds): int
    {
        global $wpdb;
        $map = $wpdb->prefix . 'dbg_file_tag_map';
        $wpdb->delete($map, ['file_record_id' => $fileId]);
        return $this->assignToFile($fileId, $tagIds);
    }

    public function tagsForFile(int $fileId): array
    {
        global $wpdb;
        $tags = $wpdb->prefix . 'dbg_media_tags';
        $map = $wpdb->prefix . 'dbg_file_tag_map';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$tags} t INNER JOIN {$map} m ON m.tag_id = t.id WHERE m.file_record_id = %d AND t.status = 'active' ORDER BY t.name ASC",
            $fileId
        ), ARRAY_A) ?: [];
    }

    public function archive(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_media_tags';

        return false !== $wpdb->update($table, [
            'status' => 'archived',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    private function cleanIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }
}
