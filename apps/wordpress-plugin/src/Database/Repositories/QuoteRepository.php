<?php

namespace DBGPlatform\Database\Repositories;

class QuoteRepository
{
    private array $sortableColumns = ['id'=>'id','quote_number'=>'quote_number','title'=>'title','status'=>'status','total_ttc'=>'total_ttc','valid_until'=>'valid_until','created_at'=>'created_at','updated_at'=>'updated_at'];

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_quotes';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->whereSql($filters);
        $sort = $this->normaliseSort($filters);
        $countSql = "SELECT COUNT(*) FROM {$table}" . $whereSql;
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($countSql, $params)) : (int) $wpdb->get_var($countSql);
        $sql = "SELECT * FROM {$table}" . $whereSql . ' ORDER BY ' . $this->sortableColumns[$sort['sort_by']] . ' ' . $sort['sort_order'] . ' LIMIT %d OFFSET %d';
        return ['items'=>$wpdb->get_results($wpdb->prepare($sql, array_merge($params, [$perPage, $offset])), ARRAY_A) ?: [], 'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total,'total_pages'=>max(1,(int)ceil($total/$perPage))], 'sort'=>$sort, 'filters'=>$filters];
    }

    public function all(array $filters = [], int $limit = 100): array { return $this->paginated($filters, 1, $limit)['items']; }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_quotes WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'dbg_quotes', [
            'uuid' => $data['uuid'] ?? wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0) ?: null,
            'contact_id' => absint($data['contact_id'] ?? 0) ?: null,
            'quote_number' => sanitize_text_field($data['quote_number'] ?? $this->nextQuoteNumber()),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'currency' => strtoupper(sanitize_key($data['currency'] ?? 'EUR')),
            'subtotal_ht' => (float)($data['subtotal_ht'] ?? 0),
            'discount_total' => (float)($data['discount_total'] ?? 0),
            'tax_total' => (float)($data['tax_total'] ?? 0),
            'total_ht' => (float)($data['total_ht'] ?? 0),
            'total_ttc' => (float)($data['total_ttc'] ?? 0),
            'valid_until' => sanitize_text_field($data['valid_until'] ?? '') ?: null,
            'terms' => sanitize_textarea_field($data['terms'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_by' => get_current_user_id() ?: null,
            'updated_by' => get_current_user_id() ?: null,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => null,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $payload = ['updated_at'=>current_time('mysql'), 'updated_by'=>get_current_user_id() ?: null];
        foreach (['quote_number','title','valid_until'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = sanitize_text_field($data[$field]) ?: null; } }
        foreach (['terms','notes'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = sanitize_textarea_field($data[$field]); } }
        foreach (['organisation_id','project_id','contact_id'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['status','currency'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = $field === 'currency' ? strtoupper(sanitize_key($data[$field])) : sanitize_key($data[$field]); } }
        foreach (['subtotal_ht','discount_total','tax_total','total_ht','total_ttc'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = (float)$data[$field]; } }
        foreach (['signed_at','accepted_at','rejected_at'] as $field) { if (array_key_exists($field,$data)) { $payload[$field] = $data[$field]; } }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_quotes', $payload, ['id'=>$id]);
    }

    public function archive(int $id): bool { return $this->update($id, ['status'=>'archived']) && $this->setArchivedAt($id, current_time('mysql')); }
    public function restore(int $id): bool { return $this->update($id, ['status'=>'draft']) && $this->setArchivedAt($id, null); }

    public function nextQuoteNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dbg_quotes WHERE quote_number LIKE %s", 'D-' . $year . '-%'));
        return sprintf('D-%s-%04d', $year, $count + 1);
    }

    private function setArchivedAt(int $id, ?string $date): bool
    {
        global $wpdb;
        return false !== $wpdb->update($wpdb->prefix . 'dbg_quotes', ['archived_at'=>$date,'updated_at'=>current_time('mysql')], ['id'=>$id]);
    }

    private function whereSql(array $filters): array
    {
        global $wpdb;
        $where = []; $params = [];
        foreach (['organisation_id','project_id','contact_id'] as $field) { if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); } }
        if (!empty($filters['status'])) { $where[] = 'status = %s'; $params[] = sanitize_key($filters['status']); }
        if (!empty($filters['valid_from'])) { $where[] = 'valid_until >= %s'; $params[] = sanitize_text_field($filters['valid_from']); }
        if (!empty($filters['valid_to'])) { $where[] = 'valid_until <= %s'; $params[] = sanitize_text_field($filters['valid_to']); }
        if (!empty($filters['search'])) { $where[] = '(title LIKE %s OR quote_number LIKE %s OR notes LIKE %s)'; $term = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%'; array_push($params, $term, $term, $term); }
        return [empty($where) ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    private function normaliseSort(array $filters): array
    {
        $sortBy = sanitize_key($filters['sort_by'] ?? 'id');
        $sortOrder = strtoupper(sanitize_key($filters['sort_order'] ?? 'DESC'));
        if (!isset($this->sortableColumns[$sortBy])) { $sortBy = 'id'; }
        if (!in_array($sortOrder, ['ASC','DESC'], true)) { $sortOrder = 'DESC'; }
        return ['sort_by'=>$sortBy, 'sort_order'=>$sortOrder];
    }
}
