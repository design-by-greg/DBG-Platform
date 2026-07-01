<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Quotes\QuoteService;

class QuoteAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_quote', [$this, 'create']);
        add_action('admin_post_dbg_update_quote', [$this, 'update']);
        add_action('admin_post_dbg_archive_quote', [$this, 'archive']);
        add_action('admin_post_dbg_restore_quote', [$this, 'restore']);
        add_action('admin_post_dbg_quote_status', [$this, 'status']);
        add_action('admin_post_dbg_bulk_quotes', [$this, 'bulk']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_quote');
        $payload = $this->payload();
        $service = new QuoteService();
        $errors = $service->validationErrors($payload, true);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $id = $service->create($payload);
        $this->redirect($id > 0 ? 'created' : 'error', $id > 0 ? [] : ['Quote could not be created.']);
    }

    public function update(): void
    {
        $this->guard('dbg_update_quote');
        $quoteId = absint($_POST['quote_id'] ?? 0);
        if ($quoteId <= 0) { $this->redirect('error', ['Quote ID is required.']); }
        $payload = $this->payload();
        $service = new QuoteService();
        $errors = $service->validationErrors($payload, false, $quoteId);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $service->update($quoteId, $payload);
        $this->redirect('updated');
    }

    public function archive(): void { $this->guard('dbg_archive_quote'); (new QuoteService())->archive(absint($_POST['quote_id'] ?? 0)); $this->redirect('deleted'); }
    public function restore(): void { $this->guard('dbg_restore_quote'); (new QuoteService())->restore(absint($_POST['quote_id'] ?? 0)); $this->redirect('updated'); }

    public function status(): void
    {
        $this->guard('dbg_quote_status');
        $status = sanitize_key($_POST['status'] ?? '');
        $service = new QuoteService();
        $allowed = $service->allowedValues();
        if (!in_array($status, $allowed['statuses'], true)) { $this->redirect('error', ['Status is invalid.']); }
        $service->changeStatus(absint($_POST['quote_id'] ?? 0), $status);
        $this->redirect('updated');
    }

    public function bulk(): void
    {
        $this->guard('dbg_bulk_quotes');
        $ids = array_values(array_filter(array_map('absint', (array) ($_POST['quote_ids'] ?? []))));
        $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
        if (empty($ids)) { $this->redirect('error', ['Select at least one quote.']); }
        if (!in_array($bulkAction, ['archive', 'restore'], true)) { $this->redirect('error', ['Bulk action is invalid.']); }
        $service = new QuoteService();
        foreach ($ids as $id) { $bulkAction === 'archive' ? $service->archive($id) : $service->restore($id); }
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => absint($_POST['organisation_id'] ?? 0),
            'project_id' => absint($_POST['project_id'] ?? 0),
            'contact_id' => absint($_POST['contact_id'] ?? 0),
            'quote_number' => sanitize_text_field($_POST['quote_number'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'currency' => sanitize_key($_POST['currency'] ?? 'EUR'),
            'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
            'terms' => sanitize_textarea_field($_POST['terms'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'lines' => $this->linesPayload(),
        ];
    }

    private function linesPayload(): array
    {
        $lines = [];
        foreach ((array) ($_POST['line_title'] ?? []) as $index => $title) {
            if (trim((string) $title) === '') { continue; }
            $lines[] = [
                'title' => sanitize_text_field($title),
                'line_type' => sanitize_key($_POST['line_type'][$index] ?? 'item'),
                'quantity' => (float) ($_POST['line_quantity'][$index] ?? 1),
                'unit' => sanitize_key($_POST['line_unit'][$index] ?? 'unit'),
                'unit_price_ht' => (float) ($_POST['line_unit_price_ht'][$index] ?? 0),
                'discount_rate' => (float) ($_POST['line_discount_rate'][$index] ?? 0),
                'tax_rate' => (float) ($_POST['line_tax_rate'][$index] ?? 20),
                'sort_order' => $index,
            ];
        }
        return $lines;
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) { set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60); }
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-quotes&dbg_status=' . $status));
        exit;
    }
}
