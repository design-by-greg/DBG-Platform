<?php

namespace DBGPlatform\Database\Repositories;

class InvoiceLineRepository
{
    public function forInvoice(int $invoiceId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_invoice_lines WHERE invoice_id = %d ORDER BY sort_order ASC, id ASC", $invoiceId), ARRAY_A) ?: [];
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
        $wpdb->insert($wpdb->prefix . 'dbg_invoice_lines', [
            'invoice_id' => absint($data['invoice_id'] ?? 0),
            'order_line_id' => absint($data['order_line_id'] ?? 0) ?: null,
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
            'sort_order' => absint($data['sort_order'] ?? 0),
            'metadata_json' => wp_json_encode((array)($data['metadata'] ?? [])),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForInvoice(int $invoiceId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_invoice_lines', ['invoice_id' => $invoiceId]);
    }

    public function totals(int $invoiceId): array
    {
        $subtotal = 0; $totalHt = 0; $totalTtc = 0; $discount = 0;
        foreach ($this->forInvoice($invoiceId) as $line) {
            $lineSubtotal = (float) $line['quantity'] * (float) $line['unit_price_ht'];
            $subtotal += $lineSubtotal;
            $totalHt += (float) $line['line_total_ht'];
            $totalTtc += (float) $line['line_total_ttc'];
            $discount += max(0, $lineSubtotal - (float) $line['line_total_ht']);
        }
        return ['subtotal_ht' => round($subtotal, 2), 'discount_total' => round($discount, 2), 'tax_total' => round(max(0, $totalTtc - $totalHt), 2), 'total_ht' => round($totalHt, 2), 'total_ttc' => round($totalTtc, 2)];
    }

    private function hydrate(array $row): array
    {
        $row['metadata'] = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];
        return $row;
    }
}
