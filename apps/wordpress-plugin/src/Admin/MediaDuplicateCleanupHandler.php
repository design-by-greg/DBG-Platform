<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaDuplicateCleanupHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_cleanup_duplicate_group', [$this, 'cleanupGroup']);
    }

    public function cleanupGroup(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_cleanup_duplicate_group');

        $hash = sanitize_text_field((string) ($_POST['file_hash'] ?? ''));
        $keepId = absint($_POST['keep_file_id'] ?? 0);

        if ($hash === '' || $keepId <= 0) {
            $this->redirect('error', ['Hash and file to keep are required.']);
        }

        $repository = new FileRecordRepository();
        $duplicates = $repository->duplicatesForHash($hash);
        $archived = [];

        foreach ($duplicates as $file) {
            $fileId = absint($file['id'] ?? 0);
            if ($fileId <= 0 || $fileId === $keepId) {
                continue;
            }

            if ($repository->archive($fileId)) {
                $archived[] = $fileId;
            }
        }

        (new AuditLogger())->record('duplicate_cleanup', 'file', $keepId, [
            'file_hash' => $hash,
            'keep_file_id' => $keepId,
            'archived_file_ids' => $archived,
        ]);

        $this->redirect('updated');
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-media-duplicates&dbg_status=' . $status));
        exit;
    }
}
