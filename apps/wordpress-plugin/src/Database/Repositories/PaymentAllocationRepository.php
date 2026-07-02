<?php

namespace DBGPlatform\Database\Repositories;

class PaymentAllocationRepository
{
    public function forPayment(int $paymentId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_payment_allocations WHERE payment_id = %d ORDER BY id ASC", $paymentId), ARRAY_A) ?: [];
    }

    public function forInvoice(int $invoiceId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_payment_allocations WHERE invoice_id = %d ORDER BY id ASC", $invoiceId), ARRAY_A) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_payment_allocations', [
            'payment_id' => absint($data['payment_id'] ?? 0),
            'invoice_id' => absint($data['invoice_id'] ?? 0),
            'amount' => max(0, (float) ($data['amount'] ?? 0)),
            'created_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function deleteForPayment(int $paymentId): bool
    {
        global $wpdb;
        return false !== $wpdb->delete($wpdb->prefix . 'dbg_payment_allocations', ['payment_id' => $paymentId]);
    }

    public function totalForInvoice(int $invoiceId): float
    {
        global $wpdb;
        return (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}dbg_payment_allocations WHERE invoice_id = %d", $invoiceId));
    }
}
