<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Database\Repositories\OrganisationSettingsRepository;
use DBGPlatform\Organisations\OrganisationContactService;

class OrganisationContactAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_organisation_contact', [$this, 'create']);
        add_action('admin_post_dbg_update_organisation_contact', [$this, 'update']);
        add_action('admin_post_dbg_archive_organisation_contact', [$this, 'archive']);
        add_action('admin_post_dbg_restore_organisation_contact', [$this, 'restore']);
        add_action('admin_post_dbg_main_organisation_contact', [$this, 'makeMain']);
        add_action('admin_post_dbg_bulk_organisation_contacts', [$this, 'bulk']);
        add_action('admin_post_dbg_update_organisation_settings', [$this, 'settings']);
    }

    public function create(): void
    {
        $this->guard('dbg_create_organisation_contact');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        if ($organisationId <= 0) { $this->redirect('error', ['Organisation ID is required.']); }
        $errors = $this->validateContact(true);
        if (!empty($errors)) { $this->redirect('error', $errors, $organisationId); }
        (new OrganisationContactService())->create($organisationId, $this->contactData());
        $this->redirect('created', [], $organisationId);
    }

    public function update(): void
    {
        $this->guard('dbg_update_organisation_contact');
        $contactId = absint($_POST['contact_id'] ?? 0);
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        if ($contactId <= 0) { $this->redirect('error', ['Contact ID is required.'], $organisationId); }
        $errors = $this->validateContact(false);
        if (!empty($errors)) { $this->redirect('error', $errors, $organisationId); }
        (new OrganisationContactService())->update($contactId, $this->contactData());
        $this->redirect('updated', [], $organisationId);
    }

    public function archive(): void
    {
        $this->guard('dbg_archive_organisation_contact');
        $contactId = absint($_POST['contact_id'] ?? 0);
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationContactService())->archive($contactId);
        $this->redirect('deleted', [], $organisationId);
    }

    public function restore(): void
    {
        $this->guard('dbg_restore_organisation_contact');
        $contactId = absint($_POST['contact_id'] ?? 0);
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationContactService())->restore($contactId);
        $this->redirect('updated', [], $organisationId);
    }

    public function makeMain(): void
    {
        $this->guard('dbg_main_organisation_contact');
        $contactId = absint($_POST['contact_id'] ?? 0);
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        (new OrganisationContactService())->makeMain($contactId);
        $this->redirect('updated', [], $organisationId);
    }

    public function bulk(): void
    {
        $this->guard('dbg_bulk_organisation_contacts');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
        $ids = array_values(array_filter(array_map('absint', (array) ($_POST['contact_ids'] ?? []))));

        if (empty($ids)) { $this->redirect('error', ['Select at least one contact.'], $organisationId); }
        if (!in_array($bulkAction, ['archive', 'restore'], true)) { $this->redirect('error', ['Bulk action is invalid.'], $organisationId); }

        $service = new OrganisationContactService();
        $count = 0;
        foreach ($ids as $id) {
            $done = $bulkAction === 'archive' ? $service->archive($id) : $service->restore($id);
            if ($done) { $count++; }
        }

        set_transient('dbg_platform_form_errors_' . get_current_user_id(), [sprintf('%d contact(s) processed.', $count)], 60);
        $this->redirect('updated', [], $organisationId);
    }

    public function settings(): void
    {
        $this->guard('dbg_update_organisation_settings');
        $organisationId = absint($_POST['organisation_id'] ?? 0);
        $errors = $this->validateSettings();
        if (!empty($errors)) { $this->redirect('error', $errors, $organisationId); }
        (new OrganisationSettingsRepository())->update($organisationId, [
            'default_language' => sanitize_key($_POST['default_language'] ?? 'fr'),
            'default_currency' => sanitize_key($_POST['default_currency'] ?? 'EUR'),
            'default_project_status' => sanitize_key($_POST['default_project_status'] ?? 'draft'),
            'branding_enabled' => !empty($_POST['branding_enabled']),
        ]);
        $this->redirect('updated', [], $organisationId);
    }

    private function contactData(): array
    {
        return [
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'job_title' => sanitize_text_field($_POST['job_title'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'mobile' => sanitize_text_field($_POST['mobile'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'is_primary' => !empty($_POST['is_primary']),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];
    }

    private function validateContact(bool $create): array
    {
        $errors = [];
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        if ($create && $firstName === '') { $errors[] = 'First name is required.'; }
        if ($create && $lastName === '') { $errors[] = 'Last name is required.'; }
        if ($firstName !== '' && strlen($firstName) < 2) { $errors[] = 'First name must be at least 2 characters.'; }
        if ($lastName !== '' && strlen($lastName) < 2) { $errors[] = 'Last name must be at least 2 characters.'; }
        if (strlen($firstName) > 120) { $errors[] = 'First name must be 120 characters or less.'; }
        if (strlen($lastName) > 120) { $errors[] = 'Last name must be 120 characters or less.'; }
        foreach (['job_title' => 190, 'email' => 190, 'phone' => 64, 'mobile' => 64, 'department' => 120] as $field => $max) {
            if (strlen((string) ($_POST[$field] ?? '')) > $max) { $errors[] = $field . ' is too long.'; }
        }
        if (!empty($_POST['email']) && !is_email($_POST['email'])) { $errors[] = 'Email is invalid.'; }
        return $errors;
    }

    private function validateSettings(): array
    {
        $errors = [];
        foreach (['default_language' => 16, 'default_currency' => 8, 'default_project_status' => 64] as $field => $max) {
            if (strlen((string) ($_POST[$field] ?? '')) > $max) { $errors[] = $field . ' is too long.'; }
        }
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
        $url = admin_url('admin.php?page=dbg-platform-organisation-contacts&dbg_status=' . $status);
        if ($organisationId > 0) { $url = add_query_arg('organisation_id', $organisationId, $url); }
        wp_safe_redirect($url);
        exit;
    }
}
