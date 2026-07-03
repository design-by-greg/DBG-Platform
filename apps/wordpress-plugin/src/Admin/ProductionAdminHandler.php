<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Production\ProductionService;

class ProductionAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_production_job', [$this, 'create']);
        add_action('admin_post_dbg_update_production_job', [$this, 'update']);
        add_action('admin_post_dbg_archive_production_job', [$this, 'archive']);
        add_action('admin_post_dbg_restore_production_job', [$this, 'restore']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_production_job');
        $service = new ProductionService();
        $payload = $this->payload();
        $errors = $service->validationErrors($payload, true);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $id = $service->create($payload);
        $this->redirect($id > 0 ? 'created' : 'error', $id > 0 ? [] : ['Production job could not be created.']);
    }

    public function update(): void
    {
        $this->guard('dbg_update_production_job');
        $jobId = absint($_POST['job_id'] ?? 0);
        if ($jobId <= 0) { $this->redirect('error', ['Production job ID is required.']); }
        $service = new ProductionService();
        $payload = $this->payload();
        $errors = $service->validationErrors($payload, false, $jobId);
        if (!empty($errors)) { $this->redirect('error', $errors); }
        $service->update($jobId, $payload);
        $this->redirect('updated');
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_production_job');
        (new ProductionService())->archive(absint($_POST['job_id'] ?? 0));
        $this->redirect('deleted');
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_production_job');
        (new ProductionService())->restore(absint($_POST['job_id'] ?? 0));
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => absint($_POST['organisation_id'] ?? 0),
            'project_id' => absint($_POST['project_id'] ?? 0),
            'order_id' => absint($_POST['order_id'] ?? 0),
            'job_number' => sanitize_text_field($_POST['job_number'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'priority' => sanitize_key($_POST['priority'] ?? 'normal'),
            'planned_start_at' => sanitize_text_field($_POST['planned_start_at'] ?? ''),
            'planned_end_at' => sanitize_text_field($_POST['planned_end_at'] ?? ''),
            'operations' => [],
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
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-production&dbg_status=' . $status));
        exit;
    }
}
