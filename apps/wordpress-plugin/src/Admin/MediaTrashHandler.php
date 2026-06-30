<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaTrashHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_restore_file', [$this, 'restore']);
        add_action('admin_post_dbg_bulk_restore_files', [$this, 'bulkRestore']);
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_file');
        $fileId = absint($_POST['file_id'] ?? 0);

        if ($fileId <= 0) {
            $this->redirect('error', ['File ID is required.']);
        }

        $restored = (new FileRecordRepository())->restore($fileId);
        (new AuditLogger())->record('restored', 'file', $fileId, ['restored' => $restored]);
        $this->redirect($restored ? 'updated' : 'error', $restored ? [] : ['Unable to restore file.']);
    }

    public function bulkRestore(): void
    {
        $this->guard('dbg_bulk_restore_files');
        $ids = array_map('absint', (array) ($_POST['file_ids'] ?? []));

        if (empty($ids)) {
            $this->redirect('error', ['Select at least one file.']);
        }

        $count = (new FileRecordRepository())->bulkRestore($ids);
        (new AuditLogger())->record('bulk_restored', 'file', null, ['ids' => $ids, 'count' => $count]);
        $this->redirect('updated');
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
        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-media-trash&dbg_status=' . $status));
        exit;
    }
}
