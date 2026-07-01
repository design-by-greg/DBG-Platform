<?php

namespace DBGPlatform\Database\Repositories;

class QuoteLineRepository
{
    public function forQuote(int $quoteId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_quote_lines WHERE quote_id = %d ORDER BY sort_order ASC, id ASC", $quoteId), ARRAY_A) ?: [];
        return array_map([$this, 'hydrate'], $rows);
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $calculated = $this->calculate($data);
        $wpdb->insert($wpdb->prefix . 'dbg_quote_lines', [
            'quote_id' => absint($data['quote_id'] ?? 0),
            'asset_id' => absint($data['asset_id'] ?? 0) ?: null,
            'line_type' => sanitize_key($data['line_type'] ?? 'item'),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'quantity' => (float)($data['quantity'] ?? 1),
            'unit' => sanitize_key($data['unit'] ?? 'unit'),
            'unit_price_ht' => (float)($data['unit_price_ht'] ?? 0),
            'discount_rate' => (float)($data['discount_rate'] ?? 0),
            'tax_rate' => (float)($data['tax_rate'] ?? 20),
            'line_total_ht' => $calculated['line_total_ht'],
            'line_total_ttc' => $calculated['line_total_ttc'],
            'sort_order' => absint($data['sort_order'] ?? 0),
            'metadata_json' => wp_json_encode((array)($data['metadata'] ?? [])),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForQuote(int $quoteId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_quote_lines', ['quote_id' => $quoteId]);
    }

    public function totals(int $quoteId): array
    {
        $subtotal = 0; $totalHt = 0; $totalTtc = 0; $discount = 0; $tax = 0;
        foreach ($this->forQuote($quoteId) as $line) {
            $lineSubtotal = (float)$line['quantity'] * (float)$line['unit_price_ht'];
            $subtotal += $lineSubtotal;
            $totalHt += (float)$line['line_total_ht'];
            $totalTtc += (float)$line['line_total_ttc'];
            $discount += max(0, $lineSubtotal - (float)$line['line_total_ht']);
        }
        $tax = max(0, $totalTtc - $totalHt);
        return ['subtotal_ht'=>round($subtotal,2), 'discount_total'=>round($discount,2), 'tax_total'=>round($tax,2), 'total_ht'=>round($totalHt,2), 'total_ttc'=>round($totalTtc,2)];
    }

    private function calculate(array $data): array
    {
        $quantity = max(0, (float)($data['quantity'] ?? 1));
        $unitPrice = max(0, (float)($data['unit_price_ht'] ?? 0));
        $discountRate = max(0, min(100, (float)($data['discount_rate'] ?? 0)));
        $taxRate = max(0, (float)($data['tax_rate'] ?? 20));
        $ht = $quantity * $unitPrice * (1 - ($discountRate / 100));
        $ttc = $ht * (1 + ($taxRate / 100));
        return ['line_total_ht' => round($ht, 2), 'line_total_ttc' => round($ttc, 2)];
    }

    private function hydrate(array $row): array
    {
        $row['metadata'] = json_decode((string)($row['metadata_json'] ?? '{}'), true) ?: [];
        return $row;
    }
}
