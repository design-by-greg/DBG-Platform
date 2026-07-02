<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Payments\PaymentService;

class PaymentAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_payment', [$this, 'create']);
        add_action('admin_post_dbg_update_payment', [$this, 'update']);
        add_action('admin_post_dbg_archive_payment', [$this, 'archive']);
        add_action('admin_post_dbg_restore_payment', [$this, 'restore']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_payment');
        $payload = $this->payload();
        $service = new PaymentService();
        $id = $service->create($payload);
        $this->redirect($id > 0 ? 'created' : 'error');
    }

    public function update(): void
    {
        $this->guard('dbg_update_payment');
        $id = absint($_POST['payment_id'] ?? 0);
        if ($id > 0) { (new PaymentService())->update($id, $this->payload()); }
        $this->redirect('updated');
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_payment');
        (new PaymentService())->archive(absint($_POST['payment_id'] ?? 0));
        $this->redirect('deleted');
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_payment');
        (new PaymentService())->restore(absint($_POST['payment_id'] ?? 0));
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => absint($_POST['organisation_id'] ?? 0),
            'invoice_id' => absint($_POST['invoice_id'] ?? 0),
            'order_id' => absint($_POST['order_id'] ?? 0),
            'contact_id' => absint($_POST['contact_id'] ?? 0),
            'payment_number' => sanitize_text_field($_POST['payment_number'] ?? ''),
            'provider' => sanitize_key($_POST['provider'] ?? 'manual'),
            'method' => sanitize_key($_POST['method'] ?? 'bank_transfer'),
            'status' => sanitize_key($_POST['status'] ?? 'pending'),
            'currency' => sanitize_key($_POST['currency'] ?? 'EUR'),
            'amount' => (float) ($_POST['amount'] ?? 0),
            'fee_amount' => (float) ($_POST['fee_amount'] ?? 0),
            'external_reference' => sanitize_text_field($_POST['external_reference'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) { exit; }
        check_admin_referer($action);
    }

    private function redirect(string $status): void
    {
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-payments&dbg_status=' . $status));
        exit;
    }
}
