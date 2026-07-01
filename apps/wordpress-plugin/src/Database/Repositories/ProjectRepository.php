<?php

namespace DBGPlatform\Database\Repositories;

class ProjectRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'name' => 'name',
        'type' => 'type',
        'status' => 'status',
        'priority' => 'priority',
        'due_date' => 'due_date',
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
        $table = $wpdb->prefix . 'dbg_projects';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->whereSql($filters);
        $sort = $this->normaliseSort($filters);
        $countSql = "SELECT COUNT(*) FROM {$table}" . $whereSql;
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params)) : (int) $wpdb->get_var($countSql);
        $sql = "SELECT * FROM {$table}" . $whereSql . ' ORDER BY ' . $this->sortableColumns[$sort['sort_by']] . ' ' . $sort['sort_order'] . ' LIMIT %d OFFSET %d';

        return [
            'items' => $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$perPage, $offset])), ARRAY_A) ?: [],
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))],
            'sort' => $sort,
            'filters' => $filters,
        ];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_projects WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'dbg_projects', [
            'uuid' => $data['uuid'] ?? wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'contact_id' => absint($data['contact_id'] ?? 0) ?: null,
            'owner_user_id' => absint($data['owner_user_id'] ?? 0) ?: null,
            'project_number' => sanitize_text_field($data['project_number'] ?? ''),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'type' => sanitize_key($data['type'] ?? 'custom'),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'priority' => sanitize_key($data['priority'] ?? 'normal'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'budget_estimate' => isset($data['budget_estimate']) ? (float) $data['budget_estimate'] : null,
            'currency' => strtoupper(sanitize_key($data['currency'] ?? 'EUR')),
            'due_date' => sanitize_text_field($data['due_date'] ?? '') ?: null,
            'started_at' => $data['started_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
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
        foreach (['project_number', 'name', 'description', 'due_date', 'started_at', 'completed_at'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = is_string($data[$field]) ? sanitize_text_field($data[$field]) : $data[$field]; }
        }
        foreach (['organisation_id', 'contact_id', 'owner_user_id'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; }
        }
        foreach (['type', 'status', 'priority', 'currency'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = strtoupper($field === 'currency' ? sanitize_key($data[$field]) : '') ?: sanitize_key($data[$field]); }
        }
        if (array_key_exists('budget_estimate', $data)) { $payload['budget_estimate'] = (float) $data['budget_estimate']; }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_projects', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']) && $this->setArchivedAt($id, current_time('mysql'));
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['status' => 'draft']) && $this->setArchivedAt($id, null);
    }

    public function nextProjectNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dbg_projects WHERE project_number LIKE %s", 'P-' . $year . '-%'));
        return sprintf('P-%s-%04d', $year, $count + 1);
    }

    private function setArchivedAt(int $id, ?string $date): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_projects', ['archived_at' => $date, 'updated_at' => current_time('mysql')], ['id' => $id]);
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        foreach (['organisation_id', 'contact_id', 'owner_user_id'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); }
        }
        foreach (['type', 'status', 'priority'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %s'; $params[] = sanitize_key($filters[$field]); }
        }
        if (!empty($filters['due_from'])) { $where[] = 'due_date >= %s'; $params[] = sanitize_text_field($filters['due_from']); }
        if (!empty($filters['due_to'])) { $where[] = 'due_date <= %s'; $params[] = sanitize_text_field($filters['due_to']); }
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE %s OR project_number LIKE %s OR description LIKE %s)';
            $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            array_push($params, $term, $term, $term);
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
