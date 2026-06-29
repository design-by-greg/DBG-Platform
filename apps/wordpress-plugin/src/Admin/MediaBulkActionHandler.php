<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaBulkActionHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_bulk_media_action', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_bulk_media_action');

        $action = sanitize_key($_POST['bulk_action'] ?? '');
        $ids = array_map('absint', (array) ($_POST['file_ids'] ?? []));
        $ids = array_values(array_filter(array_unique($ids)));

        if (empty($ids)) {
            $this->redirect('error', ['Select at least one file.']);
        }

        $repository = new FileRecordRepository();
        $count = 0;

        if ($action === 'archive') {
            $count = $repository->bulkArchive($ids);
        } elseif ($action === 'move') {
            $folderId = absint($_POST['bulk_folder_id'] ?? 0);
            $count = $repository->bulkMoveToFolder($ids, $folderId);
        } else {
            $this->redirect('error', ['Bulk action is required.']);
        }

        (new AuditLogger())->record('bulk_' . $action, 'file', null, [
            'ids' => $ids,
            'count' => $count,
            'folder_id' => absint($_POST['bulk_folder_id'] ?? 0),
        ]);

        $this->redirect('updated');
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
