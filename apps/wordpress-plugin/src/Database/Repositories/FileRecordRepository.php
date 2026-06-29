<?php

namespace DBGPlatform\Database\Repositories;

class FileRecordRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'name' => 'original_name',
        'type' => 'mime_type',
        'size' => 'size',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function all(int $limit = 100): array
    {
        return $this->search([], $limit);
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        return $this->paginated($filters, 1, $limit)['items'];
    }

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
        $total = !empty($params)
            ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params))
            : (int) $wpdb->get_var($countSql);

        $sql = "SELECT * FROM {$table}" . $whereSql . $orderSql . ' LIMIT %d OFFSET %d';
        $queryParams = array_merge($params, [$perPage, $offset]);

        return [
            'items' => $wpdb->get_results($wpdb->prepare($sql, $queryParams), ARRAY_A) ?: [],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
            'sort' => $this->normaliseSort($filters),
        ];
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
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

        if (!empty($filters['folder_id'])) {
            $where[] = 'folder_id = %d';
            $params[] = absint($filters['folder_id']);
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

        if (!isset($this->sortableColumns[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'DESC';
        }

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
            'asset_id' => absint($data['asset_id'] ?? 0),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0),
            'folder_id' => absint($data['folder_id'] ?? 0),
            'original_name' => sanitize_file_name($data['original_name'] ?? ''),
            'filename' => sanitize_file_name($data['filename'] ?? ''),
            'mime_type' => sanitize_text_field($data['mime_type'] ?? ''),
            'size' => absint($data['size'] ?? 0),
            'path' => sanitize_text_field($data['path'] ?? ''),
            'url' => esc_url_raw($data['url'] ?? ''),
            'thumbnail_path' => sanitize_text_field($data['thumbnail_path'] ?? ''),
            'thumbnail_url' => esc_url_raw($data['thumbnail_url'] ?? ''),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function replace(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';

        return false !== $wpdb->update($table, [
            'original_name' => sanitize_file_name($data['original_name'] ?? ''),
            'filename' => sanitize_file_name($data['filename'] ?? ''),
            'mime_type' => sanitize_text_field($data['mime_type'] ?? ''),
            'size' => absint($data['size'] ?? 0),
            'path' => sanitize_text_field($data['path'] ?? ''),
            'url' => esc_url_raw($data['url'] ?? ''),
            'thumbnail_path' => sanitize_text_field($data['thumbnail_path'] ?? ''),
            'thumbnail_url' => esc_url_raw($data['thumbnail_url'] ?? ''),
            'status' => 'active',
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function rename(int $id, string $name): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';

        return false !== $wpdb->update($table, [
            'original_name' => sanitize_file_name($name),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function updateThumbnail(int $id, array $thumbnail): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';

        return false !== $wpdb->update($table, [
            'thumbnail_path' => sanitize_text_field($thumbnail['thumbnail_path'] ?? ''),
            'thumbnail_url' => esc_url_raw($thumbnail['thumbnail_url'] ?? ''),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function moveToFolder(int $id, int $folderId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_file_records';

        return false !== $wpdb->update($table, [
            'folder_id' => $folderId,
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    public function bulkMoveToFolder(array $ids, int $folderId): int
    {
        $count = 0;
        foreach ($this->cleanIds($ids) as $id) {
            $count += $this->moveToFolder($id, $folderId) ? 1 : 0;
        }
        return $count;
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

    public function bulkArchive(array $ids): int
    {
        $count = 0;
        foreach ($this->cleanIds($ids) as $id) {
            $count += $this->archive($id) ? 1 : 0;
        }
        return $count;
    }

    private function cleanIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }
}
