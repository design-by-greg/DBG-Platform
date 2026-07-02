<?php

namespace DBGPlatform\Database\Repositories;

class InvoiceRepository
{
    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_invoices WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function findByNumber(string $invoiceNumber): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_invoices WHERE invoice_number = %s", sanitize_text_field($invoiceNumber)), ARRAY_A);
        return $row ?: null;
    }

    public function all(array $filters = [], int $limit = 100): array
    {
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_invoices';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        foreach (['organisation_id', 'project_id', 'quote_id', 'order_id', 'contact_id'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); }
        }
        foreach (['status', 'payment_status'] as $field) {
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
        $totalTtc = max(0, (float) ($data['total_ttc'] ?? 0));
        $amountPaid = max(0, (float) ($data['amount_paid'] ?? 0));
        $wpdb->insert($wpdb->prefix . 'dbg_invoices', [
            'uuid' => wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0) ?: null,
            'quote_id' => absint($data['quote_id'] ?? 0) ?: null,
            'order_id' => absint($data['order_id'] ?? 0) ?: null,
            'contact_id' => absint($data['contact_id'] ?? 0) ?: null,
            'invoice_number' => sanitize_text_field($data['invoice_number'] ?? $this->nextInvoiceNumber()),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'payment_status' => sanitize_key($data['payment_status'] ?? 'unpaid'),
            'currency' => strtoupper(sanitize_key($data['currency'] ?? 'EUR')),
            'subtotal_ht' => max(0, (float) ($data['subtotal_ht'] ?? 0)),
            'discount_total' => max(0, (float) ($data['discount_total'] ?? 0)),
            'tax_total' => max(0, (float) ($data['tax_total'] ?? 0)),
            'total_ht' => max(0, (float) ($data['total_ht'] ?? 0)),
            'total_ttc' => $totalTtc,
            'amount_paid' => $amountPaid,
            'amount_due' => max(0, $totalTtc - $amountPaid),
            'issued_at' => $data['issued_at'] ?? null,
            'due_date' => sanitize_text_field($data['due_date'] ?? '') ?: null,
            'paid_at' => $data['paid_at'] ?? null,
            'cancelled_at' => $data['cancelled_at'] ?? null,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'terms' => sanitize_textarea_field($data['terms'] ?? ''),
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
        foreach (['invoice_number', 'title', 'due_date'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]) ?: null; } }
        foreach (['notes', 'terms'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_textarea_field($data[$field]); } }
        foreach (['organisation_id', 'project_id', 'quote_id', 'order_id', 'contact_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['status', 'payment_status', 'currency'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = $field === 'currency' ? strtoupper(sanitize_key($data[$field])) : sanitize_key($data[$field]); } }
        foreach (['subtotal_ht', 'discount_total', 'tax_total', 'total_ht', 'total_ttc', 'amount_paid', 'amount_due'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = max(0, (float) $data[$field]); } }
        foreach (['issued_at', 'paid_at', 'cancelled_at', 'archived_at'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; } }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_invoices', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool { return $this->update($id, ['status' => 'archived', 'archived_at' => current_time('mysql')]); }
    public function restore(int $id): bool { return $this->update($id, ['status' => 'draft', 'archived_at' => null]); }

    public function nextInvoiceNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dbg_invoices WHERE invoice_number LIKE %s", 'F-' . $year . '-%'));
        return sprintf('F-%s-%04d', $year, $count + 1);
    }
}
