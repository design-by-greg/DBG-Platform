<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Assets\AssetService;

class AssetAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_asset', [$this, 'create']);
        add_action('admin_post_dbg_update_asset', [$this, 'update']);
        add_action('admin_post_dbg_archive_asset', [$this, 'archive']);
        add_action('admin_post_dbg_restore_asset', [$this, 'restore']);
        add_action('admin_post_dbg_asset_approval', [$this, 'approval']);
        add_action('admin_post_dbg_asset_version', [$this, 'version']);
        add_action('admin_post_dbg_bulk_assets', [$this, 'bulk']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_asset');
        $payload = $this->payload();
        $errors = array_merge($this->validate(true), (new AssetService())->validationErrors($payload, true));
        if (!empty($errors)) { $this->redirect('error', array_unique($errors)); }
        $id = (new AssetService())->create($payload);
        if ($id <= 0) { $this->redirect('error', ['Asset could not be created.']); }
        $this->redirect('created');
    }

    public function update(): void
    {
        $this->guard('dbg_update_asset');
        $assetId = absint($_POST['asset_id'] ?? 0);
        if ($assetId <= 0) { $this->redirect('error', ['Asset ID is required.']); }
        $payload = $this->payload();
        $errors = array_merge($this->validate(false), (new AssetService())->validationErrors($payload, false, $assetId));
        if (!empty($errors)) { $this->redirect('error', array_unique($errors)); }
        (new AssetService())->update($assetId, $payload);
        $this->redirect('updated');
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_asset');
        (new AssetService())->archive(absint($_POST['asset_id'] ?? 0));
        $this->redirect('deleted');
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_asset');
        (new AssetService())->restore(absint($_POST['asset_id'] ?? 0));
        $this->redirect('updated');
    }

    public function approval(): void
    {
        $this->guard('dbg_asset_approval');
        $status = sanitize_key($_POST['approval_status'] ?? '');
        $allowed = (new AssetService())->allowedValues();
        if (!in_array($status, $allowed['approval_statuses'], true)) { $this->redirect('error', ['Approval status is invalid.']); }
        (new AssetService())->changeApprovalStatus(absint($_POST['asset_id'] ?? 0), $status);
        $this->redirect('updated');
    }

    public function version(): void
    {
        $this->guard('dbg_asset_version');
        (new AssetService())->bumpVersion(absint($_POST['asset_id'] ?? 0));
        $this->redirect('updated');
    }

    public function bulk(): void
    {
        $this->guard('dbg_bulk_assets');
        $ids = array_values(array_filter(array_map('absint', (array) ($_POST['asset_ids'] ?? []))));
        $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
        if (empty($ids)) { $this->redirect('error', ['Select at least one asset.']); }
        if (!in_array($bulkAction, ['archive', 'restore'], true)) { $this->redirect('error', ['Bulk action is invalid.']); }
        $service = new AssetService();
        $count = 0;
        foreach ($ids as $id) {
            $done = $bulkAction === 'archive' ? $service->archive($id) : $service->restore($id);
            if ($done) { $count++; }
        }
        set_transient('dbg_platform_form_errors_' . get_current_user_id(), [sprintf('%d asset(s) processed.', $count)], 60);
        $this->redirect('updated');
    }

    private function payload(): array
    {
        return [
            'organisation_id' => sanitize_text_field((string) ($_POST['organisation_id'] ?? '')),
            'project_id' => sanitize_text_field((string) ($_POST['project_id'] ?? '')),
            'parent_asset_id' => absint($_POST['parent_asset_id'] ?? 0),
            'current_file_record_id' => absint($_POST['current_file_record_id'] ?? 0),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'type' => sanitize_key($_POST['type'] ?? 'document'),
            'category' => sanitize_key($_POST['category'] ?? 'general'),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'approval_status' => sanitize_key($_POST['approval_status'] ?? 'not_required'),
            'metadata' => [],
        ];
    }

    private function validate(bool $create): array
    {
        $errors = [];
        $allowed = (new AssetService())->allowedValues();
        if ($create && trim((string) ($_POST['organisation_id'] ?? '')) === '') { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($_POST['name'] ?? '')) === '') { $errors[] = 'Asset name is required.'; }
        if (strlen((string) ($_POST['name'] ?? '')) > 255) { $errors[] = 'Asset name is too long.'; }
        foreach (['type' => 'types', 'category' => 'categories', 'status' => 'statuses', 'approval_status' => 'approval_statuses'] as $field => $bucket) {
            $value = sanitize_key($_POST[$field] ?? '');
            if ($value !== '' && !in_array($value, $allowed[$bucket], true)) { $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is invalid.'; }
        }
        foreach (['parent_asset_id', 'current_file_record_id'] as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '' && absint($_POST[$field]) <= 0) { $errors[] = str_replace('_', ' ', $field) . ' must be a valid positive number.'; }
        }
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
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-assets&dbg_status=' . $status));
        exit;
    }
}
