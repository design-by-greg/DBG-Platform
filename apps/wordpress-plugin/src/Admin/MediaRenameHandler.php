<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaRenameHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_rename_file', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_rename_file');

        $fileId = absint($_POST['file_id'] ?? 0);
        $name = sanitize_file_name((string) ($_POST['original_name'] ?? ''));

        if ($fileId <= 0) {
            $this->redirect('error', ['File ID is required.']);
        }

        if ($name === '') {
            $this->redirect('error', ['File name is required.']);
        }

        $renamed = (new FileRecordRepository())->rename($fileId, $name);
        (new AuditLogger())->record('renamed', 'file', $fileId, ['original_name' => $name, 'renamed' => $renamed]);

        $this->redirect($renamed ? 'updated' : 'error', $renamed ? [] : ['Unable to rename file.']);
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
