<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationUserRepository
{
    private array $sortableColumns = [
        'id' => 'id',
        'organisation_id' => 'organisation_id',
        'user_id' => 'user_id',
        'role' => 'role',
        'is_owner' => 'is_owner',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_users';
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

    public function allForOrganisation(int $organisationId, array $filters = [], int $limit = 100): array
    {
        $filters['organisation_id'] = $organisationId;
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function allForUser(int $userId, array $filters = [], int $limit = 100): array
    {
        $filters['user_id'] = $userId;
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_organisation_users WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function findByOrganisationUser(int $organisationId, int $userId): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_organisation_users WHERE organisation_id = %d AND user_id = %d", $organisationId, $userId), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $organisationId = absint($data['organisation_id'] ?? 0);
        $userId = absint($data['user_id'] ?? 0);
        $isOwner = !empty($data['is_owner']) ? 1 : 0;
        if ($isOwner) { $this->clearOwner($organisationId); }

        $existing = $this->findByOrganisationUser($organisationId, $userId);
        if ($existing) {
            $this->update(absint($existing['id']), $data);
            return absint($existing['id']);
        }

        $wpdb->insert($wpdb->prefix . 'dbg_organisation_users', [
            'organisation_id' => $organisationId,
            'user_id' => $userId,
            'role' => sanitize_key($data['role'] ?? 'viewer'),
            'is_owner' => $isOwner,
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
        if (array_key_exists('role', $data)) { $payload['role'] = sanitize_key($data['role']); }
        if (array_key_exists('status', $data)) { $payload['status'] = sanitize_key($data['status']); }
        if (array_key_exists('is_owner', $data)) {
            $payload['is_owner'] = !empty($data['is_owner']) ? 1 : 0;
            if ($payload['is_owner']) { $this->clearOwner(absint($existing['organisation_id'])); }
        }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_organisation_users', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['status' => 'archived', 'is_owner' => 0]) && $this->setArchivedAt($id, current_time('mysql'));
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['status' => 'active']) && $this->setArchivedAt($id, null);
    }

    public function setOwner(int $id): bool
    {
        return $this->update($id, ['is_owner' => 1]);
    }

    private function clearOwner(int $organisationId): void
    {
        global $wpdb;
        if ($organisationId <= 0) { return; }
        $wpdb->update($wpdb->prefix . 'dbg_organisation_users', ['is_owner' => 0, 'updated_at' => current_time('mysql')], ['organisation_id' => $organisationId]);
    }

    private function setArchivedAt(int $id, ?string $date): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_organisation_users', ['archived_at' => $date, 'updated_at' => current_time('mysql')], ['id' => $id]);
    }

    private function whereSql(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['organisation_id'])) { $where[] = 'organisation_id = %d'; $params[] = absint($filters['organisation_id']); }
        if (!empty($filters['user_id'])) { $where[] = 'user_id = %d'; $params[] = absint($filters['user_id']); }
        if (!empty($filters['role'])) { $where[] = 'role = %s'; $params[] = sanitize_key($filters['role']); }
        if (!empty($filters['status'])) { $where[] = 'status = %s'; $params[] = sanitize_key($filters['status']); }
        if (isset($filters['is_owner']) && $filters['is_owner'] !== '') { $where[] = 'is_owner = %d'; $params[] = absint($filters['is_owner']); }
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
