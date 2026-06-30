<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'name' => 'name',
        'type' => 'type',
        'status' => 'status',
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
        $table = $wpdb->prefix . 'dbg_organisations';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->whereSql($filters);
        $sort = $this->normaliseSort($filters);
        $orderSql = ' ORDER BY ' . $this->sortableColumns[$sort['sort_by']] . ' ' . $sort['sort_order'];

        $countSql = "SELECT COUNT(*) FROM {$table}" . $whereSql;
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params)) : (int) $wpdb->get_var($countSql);
        $sql = "SELECT * FROM {$table}" . $whereSql . $orderSql . ' LIMIT %d OFFSET %d';

        return [
            'items' => $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$perPage, $offset])), ARRAY_A) ?: [],
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))],
            'sort' => $sort,
        ];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisations';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisations';
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'type' => sanitize_key($data['type'] ?? 'company'),
            'status' => sanitize_key($data['status'] ?? 'active'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisations';
        $payload = ['updated_at' => current_time('mysql')];

        if (array_key_exists('name', $data)) {
            $payload['name'] = sanitize_text_field($data['name']);
        }

        if (array_key_exists('type', $data)) {
            $payload['type'] = sanitize_key($data['type']);
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = sanitize_key($data['status']);
        }

        return false !== $wpdb->update($table, $payload, ['id' => $id]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']);
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }

    public function delete(int $id): bool
    {
        return $this->archive($id);
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'type = %s';
            $params[] = sanitize_key($filters['type']);
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_key($filters['status']);
        }

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE %s OR type LIKE %s)';
            $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            $params[] = $term;
            $params[] = $term;
        }

        return [empty($where) ? '' : ' WHERE ' . implode(' AND ', $where), $params];
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
}
