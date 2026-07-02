<?php

namespace DBGPlatform\Database\Repositories;

class PaymentRepository
{
    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_payments WHERE id = %d", $id), ARRAY_A);
        return $row ?: null;
    }

    public function findByNumber(string $paymentNumber): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_payments WHERE payment_number = %s", sanitize_text_field($paymentNumber)), ARRAY_A);
        return $row ?: null;
    }

    public function all(array $filters = [], int $limit = 100): array
    {
        return $this->paginated($filters, 1, $limit)['items'];
    }

    public function paginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_payments';
        $page = max(1, absint($page));
        $perPage = max(1, min(100, absint($perPage)));
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        foreach (['organisation_id', 'invoice_id', 'order_id', 'contact_id'] as $field) {
            if (!empty($filters[$field])) { $where[] = $field . ' = %d'; $params[] = absint($filters[$field]); }
        }
        foreach (['provider', 'method', 'status'] as $field) {
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
        $amount = max(0, (float) ($data['amount'] ?? 0));
        $fee = max(0, (float) ($data['fee_amount'] ?? 0));
        $wpdb->insert($wpdb->prefix . 'dbg_payments', [
            'uuid' => wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'invoice_id' => absint($data['invoice_id'] ?? 0) ?: null,
            'order_id' => absint($data['order_id'] ?? 0) ?: null,
            'contact_id' => absint($data['contact_id'] ?? 0) ?: null,
            'payment_number' => sanitize_text_field($data['payment_number'] ?? $this->nextPaymentNumber()),
            'provider' => sanitize_key($data['provider'] ?? 'manual'),
            'method' => sanitize_key($data['method'] ?? 'bank_transfer'),
            'status' => sanitize_key($data['status'] ?? 'pending'),
            'currency' => strtoupper(sanitize_key($data['currency'] ?? 'EUR')),
            'amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => max(0, $amount - $fee),
            'external_reference' => sanitize_text_field($data['external_reference'] ?? ''),
            'paid_at' => $data['paid_at'] ?? null,
            'received_at' => $data['received_at'] ?? null,
            'reconciled_at' => $data['reconciled_at'] ?? null,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'metadata_json' => wp_json_encode((array) ($data['metadata'] ?? [])),
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
        foreach (['payment_number', 'external_reference'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]) ?: null; } }
        if (array_key_exists('notes', $data)) { $payload['notes'] = sanitize_textarea_field($data['notes']); }
        foreach (['organisation_id', 'invoice_id', 'order_id', 'contact_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['provider', 'method', 'status', 'currency'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = $field === 'currency' ? strtoupper(sanitize_key($data[$field])) : sanitize_key($data[$field]); } }
        foreach (['amount', 'fee_amount', 'net_amount'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = max(0, (float) $data[$field]); } }
        foreach (['paid_at', 'received_at', 'reconciled_at', 'archived_at'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; } }
        if (array_key_exists('metadata', $data)) { $payload['metadata_json'] = wp_json_encode((array) $data['metadata']); }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_payments', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool { return $this->update($id, ['status' => 'archived', 'archived_at' => current_time('mysql')]); }
    public function restore(int $id): bool { return $this->update($id, ['status' => 'pending', 'archived_at' => null]); }

    public function nextPaymentNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dbg_payments WHERE payment_number LIKE %s", 'P-' . $year . '-%'));
        return sprintf('P-%s-%04d', $year, $count + 1);
    }
}
