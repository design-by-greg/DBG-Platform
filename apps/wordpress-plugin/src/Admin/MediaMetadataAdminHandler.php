<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileMetadataRepository;

class MediaMetadataAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_update_file_metadata', [$this, 'updateMetadata']);
        add_action('admin_post_dbg_delete_file_metadata', [$this, 'deleteMetadata']);
    }

    public function updateMetadata(): void
    {
        $this->guard('dbg_update_file_metadata');

        $fileId = absint($_POST['file_id'] ?? 0);
        $key = sanitize_key((string) ($_POST['meta_key'] ?? ''));
        $value = sanitize_textarea_field((string) ($_POST['meta_value'] ?? ''));

        if ($fileId <= 0) {
            $this->redirect('error', ['File ID is required.']);
        }

        if ($key === '') {
            $this->redirect('error', ['Metadata key is required.']);
        }

        $updated = (new FileMetadataRepository())->set($fileId, $key, $value);
        (new AuditLogger())->record('metadata_updated', 'file', $fileId, ['key' => $key, 'updated' => $updated]);

        $this->redirect($updated ? 'updated' : 'error', $updated ? [] : ['Unable to update metadata.']);
    }

    public function deleteMetadata(): void
    {
        $this->guard('dbg_delete_file_metadata');

        $fileId = absint($_POST['file_id'] ?? 0);
        $key = sanitize_key((string) ($_POST['meta_key'] ?? ''));

        if ($fileId <= 0 || $key === '') {
            $this->redirect('error', ['File ID and metadata key are required.']);
        }

        $deleted = (new FileMetadataRepository())->delete($fileId, $key);
        (new AuditLogger())->record('metadata_deleted', 'file', $fileId, ['key' => $key, 'deleted' => $deleted]);

        $this->redirect($deleted ? 'deleted' : 'error', $deleted ? [] : ['Unable to delete metadata.']);
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-media&dbg_status=' . $status));
        exit;
    }
}
