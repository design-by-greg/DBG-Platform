<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationContactRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'job_title' => 'job_title',
        'department' => 'department',
        'is_primary' => 'is_primary',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function allForOrganisation(int $organisationId, array $filters = [], int $limit = 100): array
    {
        $filters['organisation_id'] = $organisationId;
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_contacts';
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
            'filters' => $filters,
        ];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_organisation_contacts WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $organisationId = absint($data['organisation_id'] ?? 0);
        $isPrimary = !empty($data['is_primary']) ? 1 : 0;
        if ($isPrimary) { $this->clearPrimary($organisationId); }
        $wpdb->insert($wpdb->prefix . 'dbg_organisation_contacts', [
            'organisation_id' => $organisationId,
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'job_title' => sanitize_text_field($data['job_title'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'mobile' => sanitize_text_field($data['mobile'] ?? ''),
            'department' => sanitize_text_field($data['department'] ?? ''),
            'is_primary' => $isPrimary,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'active'),
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => null,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $existing = $this->find($id);
        if (!$existing) { return false; }
        $payload = ['updated_at' => current_time('mysql')];
        foreach (['first_name', 'last_name', 'job_title', 'phone', 'mobile', 'department'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); }
        }
        if (array_key_exists('email', $data)) { $payload['email'] = sanitize_email($data['email']); }
        if (array_key_exists('notes', $data)) { $payload['notes'] = sanitize_textarea_field($data['notes']); }
        if (array_key_exists('status', $data)) { $payload['status'] = sanitize_key($data['status']); }
        if (array_key_exists('is_primary', $data)) {
            $payload['is_primary'] = !empty($data['is_primary']) ? 1 : 0;
            if ($payload['is_primary']) { $this->clearPrimary(absint($existing['organisation_id'])); }
        }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_organisation_contacts', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool { return $this->update($id, ['status' => 'archived', 'is_primary' => 0]) && $this->setArchivedAt($id, current_time('mysql')); }
    public function restore(int $id): bool { return $this->update($id, ['status' => 'active']) && $this->setArchivedAt($id, null); }
    public function setPrimary(int $id): bool { return $this->update($id, ['is_primary' => 1]); }

    public function departments(int $organisationId): array
    {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare("SELECT DISTINCT department FROM {$wpdb->prefix}dbg_organisation_contacts WHERE organisation_id = %d AND department IS NOT NULL AND department != '' ORDER BY department ASC", $organisationId)) ?: [];
    }

    private function setArchivedAt(int $id, ?string $date): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_organisation_contacts', ['archived_at' => $date, 'updated_at' => current_time('mysql')], ['id' => $id]);
    }

    private function clearPrimary(int $organisationId): void
    {
        global $wpdb;
        if ($organisationId <= 0) { return; }
        $wpdb->update($wpdb->prefix . 'dbg_organisation_contacts', ['is_primary' => 0, 'updated_at' => current_time('mysql')], ['organisation_id' => $organisationId]);
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        if (!empty($filters['organisation_id'])) { $where[] = 'organisation_id = %d'; $params[] = absint($filters['organisation_id']); }
        if (!empty($filters['status'])) { $where[] = 'status = %s'; $params[] = sanitize_key($filters['status']); }
        if (isset($filters['is_primary']) && $filters['is_primary'] !== '') { $where[] = 'is_primary = %d'; $params[] = absint($filters['is_primary']); }
        if (!empty($filters['department'])) { $where[] = 'department = %s'; $params[] = sanitize_text_field($filters['department']); }
        if (!empty($filters['has_email'])) { $where[] = "email IS NOT NULL AND email != ''"; }
        if (!empty($filters['missing_email'])) { $where[] = "(email IS NULL OR email = '')"; }
        if (!empty($filters['created_from'])) { $where[] = 'created_at >= %s'; $params[] = sanitize_text_field($filters['created_from']) . ' 00:00:00'; }
        if (!empty($filters['created_to'])) { $where[] = 'created_at <= %s'; $params[] = sanitize_text_field($filters['created_to']) . ' 23:59:59'; }
        if (!empty($filters['search'])) {
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s OR mobile LIKE %s OR job_title LIKE %s OR department LIKE %s)';
            $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            array_push($params, $term, $term, $term, $term, $term, $term, $term);
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
