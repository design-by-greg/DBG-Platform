<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Files\MediaMaintenanceService;

class MediaMaintenanceAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_run_media_maintenance', [$this, 'run']);
    }

    public function run(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_run_media_maintenance');

        $action = sanitize_key((string) ($_POST['maintenance_action'] ?? ''));

        if ($action === '') {
            $this->redirect('error', ['Maintenance action is required.']);
        }

        $result = (new MediaMaintenanceService())->run($action);
        (new AuditLogger())->record('media_maintenance', 'media', null, $result);

        if (empty($result['success'])) {
            $this->redirect('error', [$result['message'] ?? 'Maintenance failed.']);
        }

        set_transient('dbg_media_maintenance_result_' . get_current_user_id(), $result, 60);
        $this->redirect('updated');
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-media-maintenance&dbg_status=' . $status));
        exit;
    }
}
