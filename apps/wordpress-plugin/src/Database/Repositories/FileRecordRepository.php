<?php

namespace DBGPlatform\Database\Repositories;

class FileRecordRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'name' => 'original_name',
        'type' => 'mime_type',
        'size' => 'size',
        'favorite' => 'is_favorite',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function all(int $limit = 100): array { return $this->search([], $limit); }
    public function search(array $filters = [], int $limit = 100): array { return $this->paginated($filters, 1, $limit)['items']; }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->whereSql($filters);
        $orderSql = $this->orderSql($filters);
        $countSql = "SELECT COUNT(*) FROM {$table}" . $whereSql;
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params)) : (int) $wpdb->get_var($countSql);
        $sql = "SELECT * FROM {$table}" . $whereSql . $orderSql . ' LIMIT %d OFFSET %d';
        $queryParams = array_merge($params, [$perPage, $offset]);

        return [
            'items' => $wpdb->get_results($wpdb->prepare($sql, $queryParams), ARRAY_A) ?: [],
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))],
            'sort' => $this->normaliseSort($filters),
        ];
    }

    public function duplicateGroups(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $groups = $wpdb->get_results("SELECT file_hash, COUNT(*) as duplicate_count FROM {$table} WHERE file_hash IS NOT NULL AND file_hash != '' AND status != 'archived' GROUP BY file_hash HAVING COUNT(*) > 1 ORDER BY duplicate_count DESC", ARRAY_A) ?: [];

        foreach ($groups as &$group) {
            $group['files'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE file_hash = %s AND status != 'archived' ORDER BY id DESC", $group['file_hash']), ARRAY_A) ?: [];
        }

        return $groups;
    }

    public function duplicatesForHash(string $hash): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        $hash = sanitize_text_field($hash);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE file_hash = %s AND status != 'archived' ORDER BY id DESC", $hash), ARRAY_A) ?: [];
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        $metadataTable = $wpdb->prefix . 'dbg_file_metadata';

        if (!empty($filters['organisation_id'])) { $where[] = 'organisation_id = %d'; $params[] = absint($filters['organisation_id']); }
        if (!empty($filters['project_id'])) { $where[] = 'project_id = %d'; $params[] = absint($filters['project_id']); }
        if (!empty($filters['folder_id'])) { $where[] = 'folder_id = %d'; $params[] = absint($filters['folder_id']); }
        if (!empty($filters['asset_id'])) { $where[] = 'asset_id = %d'; $params[] = absint($filters['asset_id']); }
        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') { $where[] = 'is_favorite = %d'; $params[] = absint($filters['is_favorite']); }
        if (!empty($filters['file_hash'])) { $where[] = 'file_hash = %s'; $params[] = sanitize_text_field($filters['file_hash']); }
        if (!empty($filters['only_duplicates'])) { $where[] = "file_hash IN (SELECT file_hash FROM {$wpdb->prefix}dbg_file_records WHERE file_hash IS NOT NULL AND file_hash != '' GROUP BY file_hash HAVING COUNT(*) > 1)"; }
        if (!empty($filters['meta_key'])) { $where[] = "id IN (SELECT file_record_id FROM {$metadataTable} WHERE meta_key = %s)"; $params[] = sanitize_key($filters['meta_key']); }
        if (!empty($filters['meta_value'])) { $where[] = "id IN (SELECT file_record_id FROM {$metadataTable} WHERE meta_value LIKE %s)"; $params[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['meta_value'])) . '%'; }
        if (!empty($filters['meta_key']) && !empty($filters['meta_value'])) {
            array_pop($where); array_pop($where); array_pop($params); array_pop($params);
            $where[] = "id IN (SELECT file_record_id FROM {$metadataTable} WHERE meta_key = %s AND meta_value LIKE %s)";
            $params[] = sanitize_key($filters['meta_key']);
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['meta_value'])) . '%';
        }
        if (!empty($filters['tag_id'])) { $where[] = "id IN (SELECT file_record_id FROM {$wpdb->prefix}dbg_file_tag_map WHERE tag_id = %d)"; $params[] = absint($filters['tag_id']); }
        if (!empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $tagIds = array_values(array_unique(array_filter(array_map('absint', $filters['tag_ids']))));
            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
                $where[] = "id IN (SELECT file_record_id FROM {$wpdb->prefix}dbg_file_tag_map WHERE tag_id IN ({$placeholders}))";
                foreach ($tagIds as $tagId) { $params[] = $tagId; }
            }
        }
        if (!empty($filters['mime_type'])) { $where[] = 'mime_type LIKE %s'; $params[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['mime_type'])) . '%'; }
        if (!empty($filters['status'])) { $where[] = 'status = %s'; $params[] = sanitize_key($filters['status']); }
        if (!empty($filters['search'])) { $where[] = '(original_name LIKE %s OR filename LIKE %s)'; $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%'; $params[] = $term; $params[] = $term; }

        return [empty($where) ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    private function orderSql(array $filters): string
    {
        $sort = $this->normaliseSort($filters);
        return ' ORDER BY ' . $this->sortableColumns[$sort['sort_by']] . ' ' . $sort['sort_order'];
    }

    private function normaliseSort(array $filters): array
    {
        $sortBy = sanitize_key($filters['sort_by'] ?? 'id');
        $sortOrder = strtoupper(sanitize_key($filters['sort_order'] ?? 'DESC'));
        if (!isset($this->sortableColumns[$sortBy])) { $sortBy = 'id'; }
        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) { $sortOrder = 'DESC'; }
        return ['sort_by' => $sortBy, 'sort_order' => $sortOrder];
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
            'asset_id' => absint($data['asset_id'] ?? 0), 'organisation_id' => absint($data['organisation_id'] ?? 0), 'project_id' => absint($data['project_id'] ?? 0), 'folder_id' => absint($data['folder_id'] ?? 0),
            'original_name' => sanitize_file_name($data['original_name'] ?? ''), 'filename' => sanitize_file_name($data['filename'] ?? ''), 'mime_type' => sanitize_text_field($data['mime_type'] ?? ''), 'size' => absint($data['size'] ?? 0),
            'file_hash' => sanitize_text_field($data['file_hash'] ?? ''), 'path' => sanitize_text_field($data['path'] ?? ''), 'url' => esc_url_raw($data['url'] ?? ''), 'thumbnail_path' => sanitize_text_field($data['thumbnail_path'] ?? ''), 'thumbnail_url' => esc_url_raw($data['thumbnail_url'] ?? ''),
            'is_favorite' => absint($data['is_favorite'] ?? 0), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function replace(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';
        return false !== $wpdb->update($table, [
            'original_name' => sanitize_file_name($data['original_name'] ?? ''), 'filename' => sanitize_file_name($data['filename'] ?? ''), 'mime_type' => sanitize_text_field($data['mime_type'] ?? ''), 'size' => absint($data['size'] ?? 0),
            'file_hash' => sanitize_text_field($data['file_hash'] ?? ''), 'path' => sanitize_text_field($data['path'] ?? ''), 'url' => esc_url_raw($data['url'] ?? ''), 'thumbnail_path' => sanitize_text_field($data['thumbnail_path'] ?? ''), 'thumbnail_url' => esc_url_raw($data['thumbnail_url'] ?? ''),
            'status' => 'active', 'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function rename(int $id, string $name): bool { global $wpdb; return false !== $wpdb->update($wpdb->prefix . 'dbg_file_records', ['original_name' => sanitize_file_name($name), 'updated_at' => current_time('mysql')], ['id' => $id]); }
    public function setFavorite(int $id, bool $favorite): bool { global $wpdb; return false !== $wpdb->update($wpdb->prefix . 'dbg_file_records', ['is_favorite' => $favorite ? 1 : 0, 'updated_at' => current_time('mysql')], ['id' => $id]); }
    public function updateThumbnail(int $id, array $thumbnail): bool { global $wpdb; return false !== $wpdb->update($wpdb->prefix . 'dbg_file_records', ['thumbnail_path' => sanitize_text_field($thumbnail['thumbnail_path'] ?? ''), 'thumbnail_url' => esc_url_raw($thumbnail['thumbnail_url'] ?? ''), 'updated_at' => current_time('mysql')], ['id' => $id]); }
    public function moveToFolder(int $id, int $folderId): bool { global $wpdb; return false !== $wpdb->update($wpdb->prefix . 'dbg_file_records', ['folder_id' => $folderId, 'updated_at' => current_time('mysql')], ['id' => $id]); }
    public function bulkMoveToFolder(array $ids, int $folderId): int { $count = 0; foreach ($this->cleanIds($ids) as $id) { $count += $this->moveToFolder($id, $folderId) ? 1 : 0; } return $count; }
    public function archive(int $id): bool { global $wpdb; return false !== $wpdb->update($wpdb->prefix . 'dbg_file_records', ['status' => 'archived', 'updated_at' => current_time('mysql')], ['id' => $id]); }
    public function bulkArchive(array $ids): int { $count = 0; foreach ($this->cleanIds($ids) as $id) { $count += $this->archive($id) ? 1 : 0; } return $count; }
    private function cleanIds(array $ids): array { return array_values(array_unique(array_filter(array_map('absint', $ids)))); }
}
