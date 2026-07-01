<?php

namespace DBGPlatform\Database\Repositories;

class AssetRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'name' => 'name',
        'type' => 'type',
        'category' => 'category',
        'status' => 'status',
        'approval_status' => 'approval_status',
        'version_number' => 'version_number',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function all(array $filters = [], int $limit = 100): array
    {
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_assets';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->whereSql($filters);
        $sort = $this->normaliseSort($filters);
        $countSql = "SELECT COUNT(*) FROM {$table}" . $whereSql;
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params)) : (int) $wpdb->get_var($countSql);
        $sql = "SELECT * FROM {$table}" . $whereSql . ' ORDER BY ' . $this->sortableColumns[$sort['sort_by']] . ' ' . $sort['sort_order'] . ' LIMIT %d OFFSET %d';
        return [
            'items' => array_map([$this, 'hydrate'], $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$perPage, $offset])), ARRAY_A) ?: []),
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))],
            'sort' => $sort,
            'filters' => $filters,
        ];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_assets WHERE id = %d", $id), ARRAY_A);
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'dbg_assets', [
            'uuid' => $data['uuid'] ?? wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0) ?: null,
            'parent_asset_id' => absint($data['parent_asset_id'] ?? 0) ?: null,
            'type' => sanitize_key($data['type'] ?? 'document'),
            'category' => sanitize_key($data['category'] ?? 'general'),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'approval_status' => sanitize_key($data['approval_status'] ?? 'not_required'),
            'current_file_record_id' => absint($data['current_file_record_id'] ?? 0) ?: null,
            'version_number' => max(1, absint($data['version_number'] ?? 1)),
            'metadata_json' => wp_json_encode((array) ($data['metadata'] ?? [])),
            'created_by' => absint($data['created_by'] ?? get_current_user_id()) ?: null,
            'updated_by' => absint($data['updated_by'] ?? get_current_user_id()) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => null,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $payload = ['updated_at' => current_time('mysql'), 'updated_by' => get_current_user_id() ?: null];
        foreach (['name', 'description'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = $field === 'description' ? sanitize_textarea_field($data[$field]) : sanitize_text_field($data[$field]); }
        }
        foreach (['organisation_id', 'project_id', 'parent_asset_id', 'current_file_record_id', 'version_number'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; }
        }
        foreach (['type', 'category', 'status', 'approval_status'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); }
        }
        if (array_key_exists('metadata', $data)) { $payload['metadata_json'] = wp_json_encode((array) $data['metadata']); }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_assets', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']) && $this->setArchivedAt($id, current_time('mysql'));
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['status' => 'draft']) && $this->setArchivedAt($id, null);
    }

    public function incrementVersion(int $id): bool
    {
        $asset = $this->find($id);
        if (!$asset) { return false; }
        return $this->update($id, ['version_number' => absint($asset['version_number']) + 1]);
    }

    private function setArchivedAt(int $id, ?string $date): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_assets', ['archived_at' => $date, 'updated_at' => current_time('mysql')], ['id' => $id]);
    }

    private function hydrate(array $row): array
    {
        $row['metadata'] = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];
        return $row;
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        foreach (['organisation_id', 'project_id', 'parent_asset_id', 'current_file_record_id'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); }
        }
        foreach (['type', 'category', 'status', 'approval_status'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %s'; $params[] = sanitize_key($filters[$field]); }
        }
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE %s OR description LIKE %s)';
            $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            array_push($params, $term, $term);
        }
        return [empty($where) ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    private function normaliseSort(array $filters): array
    {
        $sortBy = sanitize_key($filters['sort_by'] ?? 'id');
        $sortOrder = strtoupper(sanitize_key($filters['sort_order'] ?? 'DESC'));
        if (!isset($this->sortableColumns[$sortBy])) { $sortBy = 'id'; }
        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) { $sortOrder = 'DESC'; }
        return ['sort_by' => $sortBy, 'sort_order' => $sortOrder];
    }
}
