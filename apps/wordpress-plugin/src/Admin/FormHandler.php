<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;

class FormHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_organisation', [$this, 'createOrganisation']);
        add_action('admin_post_dbg_create_project', [$this, 'createProject']);
        add_action('admin_post_dbg_create_asset', [$this, 'createAsset']);
    }

    public function createOrganisation(): void
    {
        $this->guard('dbg_create_organisation');

        $validator = (new FormValidator())
            ->required('dbg_organisation_name', 'Organisation name', $_POST)
            ->allowedValue('dbg_organisation_type', 'Organisation type', ['company', 'club', 'association', 'public_body', 'partner'], $_POST);

        if (!$validator->passes()) {
            $this->redirect('dbg-platform-organisations', 'error', $validator->errors());
        }

        (new OrganisationRepository())->create([
            'name' => sanitize_text_field($_POST['dbg_organisation_name'] ?? ''),
            'type' => sanitize_key($_POST['dbg_organisation_type'] ?? 'company'),
        ]);

        $this->redirect('dbg-platform-organisations', 'created');
    }

    public function createProject(): void
    {
        $this->guard('dbg_create_project');

        $validator = (new FormValidator())
            ->positiveInt('dbg_project_organisation_id', 'Organisation ID', $_POST)
            ->required('dbg_project_name', 'Project name', $_POST);

        if (!$validator->passes()) {
            $this->redirect('dbg-platform-projects', 'error', $validator->errors());
        }

        (new ProjectRepository())->create([
            'organisation_id' => absint($_POST['dbg_project_organisation_id'] ?? 0),
            'name' => sanitize_text_field($_POST['dbg_project_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['dbg_project_description'] ?? ''),
        ]);

        $this->redirect('dbg-platform-projects', 'created');
    }

    public function createAsset(): void
    {
        $this->guard('dbg_create_asset');

        $validator = (new FormValidator())
            ->positiveInt('dbg_asset_organisation_id', 'Organisation ID', $_POST)
            ->allowedValue('dbg_asset_type', 'Asset type', ['logo', 'product', 'bat', 'document', 'image', 'template'], $_POST)
            ->required('dbg_asset_name', 'Asset name', $_POST);

        if (!$validator->passes()) {
            $this->redirect('dbg-platform-assets', 'error', $validator->errors());
        }

        (new AssetRepository())->create([
            'organisation_id' => absint($_POST['dbg_asset_organisation_id'] ?? 0),
            'project_id' => absint($_POST['dbg_asset_project_id'] ?? 0),
            'type' => sanitize_key($_POST['dbg_asset_type'] ?? 'document'),
            'name' => sanitize_text_field($_POST['dbg_asset_name'] ?? ''),
        ]);

        $this->redirect('dbg-platform-assets', 'created');
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer($action);
    }

    private function redirect(string $page, string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $page . '&dbg_status=' . $status));
        exit;
    }
}
