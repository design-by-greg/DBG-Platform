<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationRepository;

class OrganisationAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_restore_organisation', [$this, 'restore']);
    }

    public function restore(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_restore_organisation');

        $organisationId = absint($_POST['dbg_id'] ?? 0);
        if ($organisationId <= 0) {
            $this->redirect('error', ['Organisation ID is required.']);
        }

        $restored = (new OrganisationRepository())->restore($organisationId);
        (new AuditLogger())->record('restored', 'organisation', $organisationId, ['restored' => $restored]);

        $this->redirect($restored ? 'updated' : 'error', $restored ? [] : ['Unable to restore organisation.']);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-organisations&dbg_status=' . $status));
        exit;
    }
}
