<?php

namespace DBGPlatform\Database\Repositories;

class ProductionJobRepository
{
    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_production_jobs WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function findByNumber(string $jobNumber): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_production_jobs WHERE job_number = %s", sanitize_text_field($jobNumber)), ARRAY_A);
        return $row ?: null;
    }

    public function all(array $filters = [], int $limit = 100): array
    {
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_production_jobs';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        foreach (['organisation_id', 'project_id', 'order_id'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); }
        }
        foreach (['status', 'priority'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %s'; $params[] = sanitize_key($filters[$field]); }
        }
        $whereSql = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        $totalSql = 'SELECT COUNT(*) FROM ' . $table . $whereSql;
        $total = $params ? (int) $wpdb->get_var($wpdb->prepare($totalSql, $params)) : (int) $wpdb->get_var($totalSql);
        $sql = 'SELECT * FROM ' . $table . $whereSql . ' ORDER BY id DESC LIMIT %d OFFSET %d';
        $items = $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$perPage, $offset])), ARRAY_A) ?: [];
        return ['items' => $items, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => max(1, (int) ceil($total / $perPage))], 'filters' => $filters];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'dbg_production_jobs', [
            'uuid' => wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0) ?: null,
            'order_id' => absint($data['order_id'] ?? 0) ?: null,
            'job_number' => sanitize_text_field($data['job_number'] ?? $this->nextJobNumber()),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'priority' => sanitize_key($data['priority'] ?? 'normal'),
            'planned_start_at' => $data['planned_start_at'] ?? null,
            'planned_end_at' => $data['planned_end_at'] ?? null,
            'started_at' => $data['started_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'production_site_id' => absint($data['production_site_id'] ?? 0) ?: null,
            'created_by' => get_current_user_id() ?: null,
            'updated_by' => get_current_user_id() ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $payload = ['updated_at' => current_time('mysql'), 'updated_by' => get_current_user_id() ?: null];
        foreach (['job_number', 'title'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]) ?: null; } }
        if (array_key_exists('description', $data)) { $payload['description'] = sanitize_textarea_field($data['description']); }
        foreach (['organisation_id', 'project_id', 'order_id', 'production_site_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['status', 'priority'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); } }
        foreach (['planned_start_at', 'planned_end_at', 'started_at', 'completed_at', 'archived_at'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; } }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_production_jobs', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool { return $this->update($id, ['status' => 'archived', 'archived_at' => current_time('mysql')]); }
    public function restore(int $id): bool { return $this->update($id, ['status' => 'draft', 'archived_at' => null]); }

    public function nextJobNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dbg_production_jobs WHERE job_number LIKE %s", 'PR-' . $year . '-%'));
        return sprintf('PR-%s-%04d', $year, $count + 1);
    }
}
