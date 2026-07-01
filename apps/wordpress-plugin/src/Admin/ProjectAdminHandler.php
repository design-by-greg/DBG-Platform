<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Projects\ProjectService;

class ProjectAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_project', [$this, 'create']);
        add_action('admin_post_dbg_update_project', [$this, 'update']);
        add_action('admin_post_dbg_archive_project', [$this, 'archive']);
        add_action('admin_post_dbg_restore_project', [$this, 'restore']);
        add_action('admin_post_dbg_project_status', [$this, 'status']);
        add_action('admin_post_dbg_bulk_projects', [$this, 'bulk']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_project');
        $payload = $this->payload();
        $errors = array_merge($this->validate(true), (new ProjectService())->validationErrors($payload, true));
        if (!empty($errors)) { $this->redirect('error', array_unique($errors)); }
        $id = (new ProjectService())->create($payload);
        if ($id <= 0) { $this->redirect('error', ['Project could not be created.']); }
        $this->redirect('created');
    }

    public function update(): void
    {
        $this->guard('dbg_update_project');
        $projectId = absint($_POST['project_id'] ?? 0);
        if ($projectId <= 0) { $this->redirect('error', ['Project ID is required.']); }
        $payload = $this->payload();
        $errors = array_merge($this->validate(false), (new ProjectService())->validationErrors($payload, false, $projectId));
        if (!empty($errors)) { $this->redirect('error', array_unique($errors)); }
        (new ProjectService())->update($projectId, $payload);
        $this->redirect('updated');
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_project');
        (new ProjectService())->archive(absint($_POST['project_id'] ?? 0));
        $this->redirect('deleted');
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_project');
        (new ProjectService())->restore(absint($_POST['project_id'] ?? 0));
        $this->redirect('updated');
    }

    public function status(): void
    {
        $this->guard('dbg_project_status');
        $status = sanitize_key($_POST['status'] ?? '');
        $allowed = (new ProjectService())->allowedValues();
        if (!in_array($status, $allowed['statuses'], true)) { $this->redirect('error', ['Status is invalid.']); }
        (new ProjectService())->changeStatus(absint($_POST['project_id'] ?? 0), $status);
        $this->redirect('updated');
    }

    public function bulk(): void
    {
        $this->guard('dbg_bulk_projects');
        $ids = array_values(array_filter(array_map('absint', (array) ($_POST['project_ids'] ?? []))));
        $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
        if (empty($ids)) { $this->redirect('error', ['Select at least one project.']); }
        if (!in_array($bulkAction, ['archive', 'restore'], true)) { $this->redirect('error', ['Bulk action is invalid.']); }
        $service = new ProjectService();
        $count = 0;
        foreach ($ids as $id) {
            $done = $bulkAction === 'archive' ? $service->archive($id) : $service->restore($id);
            if ($done) { $count++; }
        }
        set_transient('dbg_platform_form_errors_' . get_current_user_id(), [sprintf('%d project(s) processed.', $count)], 60);
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => absint($_POST['organisation_id'] ?? 0),
            'contact_id' => absint($_POST['contact_id'] ?? 0),
            'owner_user_id' => absint($_POST['owner_user_id'] ?? 0),
            'project_number' => sanitize_text_field($_POST['project_number'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'type' => sanitize_key($_POST['type'] ?? 'custom'),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'priority' => sanitize_key($_POST['priority'] ?? 'normal'),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'budget_estimate' => $_POST['budget_estimate'] ?? null,
            'currency' => sanitize_key($_POST['currency'] ?? 'EUR'),
            'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
        ];
    }

    private function validate(bool $create): array
    {
        $errors = [];
        $allowed = (new ProjectService())->allowedValues();
        if ($create && absint($_POST['organisation_id'] ?? 0) <= 0) { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($_POST['name'] ?? '')) === '') { $errors[] = 'Project name is required.'; }
        if (strlen((string) ($_POST['name'] ?? '')) > 255) { $errors[] = 'Project name is too long.'; }
        if (strlen((string) ($_POST['project_number'] ?? '')) > 64) { $errors[] = 'Project number is too long.'; }
        foreach (['type' => 'types', 'status' => 'statuses', 'priority' => 'priorities', 'currency' => 'currencies'] as $field => $bucket) {
            $value = $field === 'currency' ? strtoupper(sanitize_key($_POST[$field] ?? '')) : sanitize_key($_POST[$field] ?? '');
            if ($value !== '' && !in_array($value, $allowed[$bucket], true)) { $errors[] = ucfirst($field) . ' is invalid.'; }
        }
        if (!empty($_POST['due_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['due_date'])) { $errors[] = 'Due date must use YYYY-MM-DD format.'; }
        if (isset($_POST['budget_estimate']) && $_POST['budget_estimate'] !== '' && (float) $_POST['budget_estimate'] < 0) { $errors[] = 'Budget estimate cannot be negative.'; }
        return $errors;
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) { set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60); }
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-projects&dbg_status=' . $status));
        exit;
    }
}
