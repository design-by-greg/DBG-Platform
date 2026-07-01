<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Organisations\OrganisationUserService;

class OrganisationUserAdminHandler
{
    private array $roles = ['owner', 'administrator', 'manager', 'sales', 'designer', 'production', 'support', 'viewer'];

    public function register(): void
    {
        add_action('admin_post_dbg_add_organisation_user', [$this, 'add']);
        add_action('admin_post_dbg_update_organisation_user', [$this, 'update']);
        add_action('admin_post_dbg_archive_organisation_user', [$this, 'archive']);
        add_action('admin_post_dbg_restore_organisation_user', [$this, 'restore']);
        add_action('admin_post_dbg_owner_organisation_user', [$this, 'makeOwner']);
        add_action('admin_post_dbg_bulk_organisation_users', [$this, 'bulk']);
    }

    public function add(): void
    {
        $this->guard('dbg_add_organisation_user');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        $userId = absint($_POST['user_id'] ?? 0);
        $errors = $this->validatePayload(true);
        if (!empty($errors)) { $this->redirect('error', $errors, $organisationId); }
        $id = (new OrganisationUserService())->add($organisationId, $userId, $this->payload());
        if ($id <= 0) { $this->redirect('error', ['Organisation or WordPress user not found.'], $organisationId); }
        $this->redirect('created', [], $organisationId);
    }

    public function update(): void
    {
        $this->guard('dbg_update_organisation_user');
        $organisationUserId = absint($_POST['organisation_user_id'] ?? 0);
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        if ($organisationUserId <= 0) { $this->redirect('error', ['Organisation user ID is required.'], $organisationId); }
        $errors = $this->validatePayload(false);
        if (!empty($errors)) { $this->redirect('error', $errors, $organisationId); }
        (new OrganisationUserService())->update($organisationUserId, $this->payload());
        $this->redirect('updated', [], $organisationId);
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_organisation_user');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationUserService())->archive(absint($_POST['organisation_user_id'] ?? 0));
        $this->redirect('deleted', [], $organisationId);
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_organisation_user');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationUserService())->restore(absint($_POST['organisation_user_id'] ?? 0));
        $this->redirect('updated', [], $organisationId);
    }

    public function makeOwner(): void
    {
        $this->guard('dbg_owner_organisation_user');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationUserService())->makeOwner(absint($_POST['organisation_user_id'] ?? 0));
        $this->redirect('updated', [], $organisationId);
    }

    public function bulk(): void
    {
        $this->guard('dbg_bulk_organisation_users');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
        $ids = array_values(array_filter(array_map('absint', (array) ($_POST['organisation_user_ids'] ?? []))));
        if (empty($ids)) { $this->redirect('error', ['Select at least one user.'], $organisationId); }
        if (!in_array($bulkAction, ['archive', 'restore'], true)) { $this->redirect('error', ['Bulk action is invalid.'], $organisationId); }
        $service = new OrganisationUserService();
        $count = 0;
        foreach ($ids as $id) {
            $done = $bulkAction === 'archive' ? $service->archive($id) : $service->restore($id);
            if ($done) { $count++; }
        }
        set_transient('dbg_platform_form_errors_' . get_current_user_id(), [sprintf('%d user(s) processed.', $count)], 60);
        $this->redirect('updated', [], $organisationId);
    }

    private function payload(): array
    {
        return [
            'role' => sanitize_key($_POST['role'] ?? 'viewer'),
            'is_owner' => !empty($_POST['is_owner']),
        ];
    }

    private function validatePayload(bool $create): array
    {
        $errors = [];
        if ($create && absint($_POST['user_id'] ?? 0) <= 0) { $errors[] = 'WordPress user is required.'; }
        $role = sanitize_key($_POST['role'] ?? 'viewer');
        if (!in_array($role, $this->roles, true)) { $errors[] = 'Role is invalid.'; }
        return $errors;
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) { wp_die('Insufficient permissions.'); }
        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = [], int $organisationId = 0): void
    {
        if (!empty($errors)) { set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60); }
        $url = admin_url('admin.php?page=dbg-platform-organisation-users&dbg_status=' . $status);
        if ($organisationId > 0) { $url = add_query_arg('organisation_id', $organisationId, $url); }
        wp_safe_redirect($url);
        exit;
    }
}
