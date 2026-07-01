<?php

namespace DBGPlatform\Database\Repositories;

class OrderLineRepository
{
    public function forOrder(int $orderId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_order_lines WHERE order_id = %d ORDER BY sort_order ASC, id ASC", $orderId), ARRAY_A) ?: [];
        return array_map([$this, 'hydrate'], $rows);
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $quantity = max(0, (float) ($data['quantity'] ?? 1));
        $unitPrice = max(0, (float) ($data['unit_price_ht'] ?? 0));
        $discountRate = max(0, min(100, (float) ($data['discount_rate'] ?? 0)));
        $taxRate = max(0, (float) ($data['tax_rate'] ?? 20));
        $totalHt = round($quantity * $unitPrice * (1 - ($discountRate / 100)), 2);
        $totalTtc = round($totalHt * (1 + ($taxRate / 100)), 2);
        $wpdb->insert($wpdb->prefix . 'dbg_order_lines', [
            'order_id' => absint($data['order_id'] ?? 0),
            'quote_line_id' => absint($data['quote_line_id'] ?? 0) ?: null,
            'asset_id' => absint($data['asset_id'] ?? 0) ?: null,
            'line_type' => sanitize_key($data['line_type'] ?? 'item'),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'quantity' => $quantity,
            'unit' => sanitize_key($data['unit'] ?? 'unit'),
            'unit_price_ht' => $unitPrice,
            'discount_rate' => $discountRate,
            'tax_rate' => $taxRate,
            'line_total_ht' => $totalHt,
            'line_total_ttc' => $totalTtc,
            'production_status' => sanitize_key($data['production_status'] ?? 'not_started'),
            'sort_order' => absint($data['sort_order'] ?? 0),
            'metadata_json' => wp_json_encode((array)($data['metadata'] ?? [])),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForOrder(int $orderId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_order_lines', ['order_id' => $orderId]);
    }

    private function hydrate(array $row): array
    {
        $row['metadata'] = json_decode((string)($row['metadata_json'] ?? '{}'), true) ?: [];
        return $row;
    }
}
