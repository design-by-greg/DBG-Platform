<?php

namespace DBGPlatform\Database\Repositories;

class OrderRepository
{
    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_orders';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $table . ' WHERE id = %d', $id), ARRAY_A);
        return $row ?: null;
    }

    public function all(array $filters = [], int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_orders';
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' ORDER BY id DESC LIMIT %d', max(1, min(500, $limit))), ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'dbg_orders', [
            'uuid' => wp_generate_uuid4(),
            'organisation_id' => absint($data['organisation_id'] ?? 0),
            'project_id' => absint($data['project_id'] ?? 0) ?: null,
            'quote_id' => absint($data['quote_id'] ?? 0) ?: null,
            'contact_id' => absint($data['contact_id'] ?? 0) ?: null,
            'order_number' => sanitize_text_field($data['order_number'] ?? $this->nextOrderNumber()),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'status' => sanitize_key($data['status'] ?? 'draft'),
            'payment_status' => sanitize_key($data['payment_status'] ?? 'unpaid'),
            'production_status' => sanitize_key($data['production_status'] ?? 'not_started'),
            'fulfillment_status' => sanitize_key($data['fulfillment_status'] ?? 'not_fulfilled'),
            'currency' => strtoupper(sanitize_key($data['currency'] ?? 'EUR')),
            'subtotal_ht' => (float) ($data['subtotal_ht'] ?? 0),
            'discount_total' => (float) ($data['discount_total'] ?? 0),
            'tax_total' => (float) ($data['tax_total'] ?? 0),
            'total_ht' => (float) ($data['total_ht'] ?? 0),
            'total_ttc' => (float) ($data['total_ttc'] ?? 0),
            'due_date' => sanitize_text_field($data['due_date'] ?? '') ?: null,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
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
        foreach (['title', 'order_number', 'due_date', 'notes'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = is_string($data[$field]) ? sanitize_text_field($data[$field]) : $data[$field]; }
        }
        foreach (['status', 'payment_status', 'production_status', 'fulfillment_status', 'currency'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = $field === 'currency' ? strtoupper(sanitize_key($data[$field])) : sanitize_key($data[$field]); }
        }
        foreach (['subtotal_ht', 'discount_total', 'tax_total', 'total_ht', 'total_ttc'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = (float) $data[$field]; }
        }
        return false !== $wpdb->update($wpdb->prefix . 'dbg_orders', $payload, ['id' => $id]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']);
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['status' => 'draft']);
    }

    public function nextOrderNumber(): string
    {
        global $wpdb;
        $year = gmdate('Y');
        $table = $wpdb->prefix . 'dbg_orders';
        $count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE order_number LIKE %s', 'C-' . $year . '-%'));
        return sprintf('C-%s-%04d', $year, $count + 1);
    }
}
