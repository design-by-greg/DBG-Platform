<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Orders\OrderService;

class OrderAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_order', [$this, 'create']);
        add_action('admin_post_dbg_update_order', [$this, 'update']);
        add_action('admin_post_dbg_archive_order', [$this, 'archive']);
        add_action('admin_post_dbg_restore_order', [$this, 'restore']);
        add_action('admin_post_dbg_order_status', [$this, 'status']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_order');
        $service = new OrderService();
        $payload = $this->payload();
        $errors = $service->validationErrors($payload, true);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $id = $service->create($payload);
        $this->redirect($id > 0 ? 'created' : 'error', $id > 0 ? [] : ['Order could not be created.']);
    }

    public function update(): void
    {
        $this->guard('dbg_update_order');
        $orderId = absint($_POST['order_id'] ?? 0);
        if ($orderId <= 0) { $this->redirect('error', ['Order ID is required.']); }
        $service = new OrderService();
        $payload = $this->payload();
        $errors = $service->validationErrors($payload, false, $orderId);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $service->update($orderId, $payload);
        $this->redirect('updated');
    }

    public function archive(): void { $this->guard('dbg_archive_order'); (new OrderService())->archive(absint($_POST['order_id'] ?? 0)); $this->redirect('deleted'); }
    public function restore(): void { $this->guard('dbg_restore_order'); (new OrderService())->restore(absint($_POST['order_id'] ?? 0)); $this->redirect('updated'); }

    public function status(): void
    {
        $this->guard('dbg_order_status');
        $status = sanitize_key($_POST['status'] ?? '');
        $service = new OrderService();
        $allowed = $service->allowedValues();
        if (!in_array($status, $allowed['statuses'], true)) { $this->redirect('error', ['Status is invalid.']); }
        $service->changeStatus(absint($_POST['order_id'] ?? 0), $status);
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => absint($_POST['organisation_id'] ?? 0),
            'project_id' => absint($_POST['project_id'] ?? 0),
            'quote_id' => absint($_POST['quote_id'] ?? 0),
            'contact_id' => absint($_POST['contact_id'] ?? 0),
            'order_number' => sanitize_text_field($_POST['order_number'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'payment_status' => sanitize_key($_POST['payment_status'] ?? 'unpaid'),
            'production_status' => sanitize_key($_POST['production_status'] ?? 'not_started'),
            'fulfillment_status' => sanitize_key($_POST['fulfillment_status'] ?? 'not_fulfilled'),
            'currency' => sanitize_key($_POST['currency'] ?? 'EUR'),
            'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'lines' => [],
        ];
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) { set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60); }
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-orders&dbg_status=' . $status));
        exit;
    }
}
