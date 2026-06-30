<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaFavoriteHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_toggle_file_favorite', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_toggle_file_favorite');

        $fileId = absint($_POST['file_id'] ?? 0);
        $favorite = !empty($_POST['is_favorite']);

        if ($fileId <= 0) {
            $this->redirect('error', ['File ID is required.']);
        }

        $updated = (new FileRecordRepository())->setFavorite($fileId, $favorite);
        (new AuditLogger())->record($favorite ? 'favorited' : 'unfavorited', 'file', $fileId, ['updated' => $updated]);

        $this->redirect($updated ? 'updated' : 'error', $updated ? [] : ['Unable to update favorite.']);
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
