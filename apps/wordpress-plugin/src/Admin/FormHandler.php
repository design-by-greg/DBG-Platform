<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Settings\SettingsRepository;

class FormHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_organisation', [$this, 'createOrganisation']);
        add_action('admin_post_dbg_update_organisation', [$this, 'updateOrganisation']);
        add_action('admin_post_dbg_delete_organisation', [$this, 'deleteOrganisation']);
        add_action('admin_post_dbg_create_project', [$this, 'createProject']);
        add_action('admin_post_dbg_update_project', [$this, 'updateProject']);
        add_action('admin_post_dbg_delete_project', [$this, 'deleteProject']);
        add_action('admin_post_dbg_create_asset', [$this, 'createAsset']);
        add_action('admin_post_dbg_update_asset', [$this, 'updateAsset']);
        add_action('admin_post_dbg_delete_asset', [$this, 'deleteAsset']);
        add_action('admin_post_dbg_update_settings', [$this, 'updateSettings']);
        add_action('admin_post_dbg_upload_media', [$this, 'uploadMedia']);
        add_action('admin_post_dbg_archive_file', [$this, 'archiveFile']);
    }

    public function createOrganisation(): void
    {
        $this->guard('dbg_create_organisation');
        $this->validateOrganisation();
        (new OrganisationRepository())->create($this->organisationData());
        $this->redirect('dbg-platform-organisations', 'created');
    }

    public function updateOrganisation(): void
    {
        $this->guard('dbg_update_organisation');
        $this->validateOrganisation();
        (new OrganisationRepository())->update(absint($_POST['dbg_id'] ?? 0), $this->organisationData());
        $this->redirect('dbg-platform-organisations', 'updated');
    }

    public function deleteOrganisation(): void
    {
        $this->guard('dbg_delete_organisation');
        (new OrganisationRepository())->delete(absint($_POST['dbg_id'] ?? 0));
        $this->redirect('dbg-platform-organisations', 'deleted');
    }

    public function createProject(): void
    {
        $this->guard('dbg_create_project');
        $this->validateProject();
        (new ProjectRepository())->create($this->projectData());
        $this->redirect('dbg-platform-projects', 'created');
    }

    public function updateProject(): void
    {
        $this->guard('dbg_update_project');
        $this->validateProject();
        (new ProjectRepository())->update(absint($_POST['dbg_id'] ?? 0), $this->projectData());
        $this->redirect('dbg-platform-projects', 'updated');
    }

    public function deleteProject(): void
    {
        $this->guard('dbg_delete_project');
        (new ProjectRepository())->delete(absint($_POST['dbg_id'] ?? 0));
        $this->redirect('dbg-platform-projects', 'deleted');
    }

    public function createAsset(): void
    {
        $this->guard('dbg_create_asset');
        $this->validateAsset();
        (new AssetRepository())->create($this->assetData());
        $this->redirect('dbg-platform-assets', 'created');
    }

    public function updateAsset(): void
    {
        $this->guard('dbg_update_asset');
        $this->validateAsset();
        (new AssetRepository())->update(absint($_POST['dbg_id'] ?? 0), $this->assetData());
        $this->redirect('dbg-platform-assets', 'updated');
    }

    public function deleteAsset(): void
    {
        $this->guard('dbg_delete_asset');
        (new AssetRepository())->delete(absint($_POST['dbg_id'] ?? 0));
        $this->redirect('dbg-platform-assets', 'deleted');
    }

    public function updateSettings(): void
    {
        $this->guard('dbg_update_settings');
        $this->validateSettings();

        $settings = (new SettingsRepository())->update([
            'api_base_url' => $_POST['api_base_url'] ?? '',
            'api_token' => $_POST['api_token'] ?? '',
            'sync_mode' => $_POST['sync_mode'] ?? 'local',
            'woocommerce_enabled' => !empty($_POST['woocommerce_enabled']),
            'debug_enabled' => !empty($_POST['debug_enabled']),
        ]);

        (new AuditLogger())->record('updated', 'settings', null, ['sync_mode' => $settings['sync_mode']]);
        $this->redirect('dbg-platform-settings', 'updated');
    }

    public function uploadMedia(): void
    {
        $this->guard('dbg_upload_media');

        $organisationId = absint($_POST['organisation_id'] ?? 0);
        $projectId = absint($_POST['project_id'] ?? 0);

        if ($organisationId <= 0) {
            $this->redirect('dbg-platform-media', 'error', ['Organisation ID is required.']);
        }

        if (empty($_FILES['file'])) {
            $this->redirect('dbg-platform-media', 'error', ['File is required.']);
        }

        $result = (new FileUploadService())->upload($_FILES['file'], [
            'organisation_id' => $organisationId,
            'project_id' => $projectId,
        ]);

        if (empty($result['success'])) {
            $this->redirect('dbg-platform-media', 'error', [$result['message'] ?? 'Upload failed.']);
        }

        $assetId = (new AssetRepository())->create([
            'organisation_id' => $organisationId,
            'project_id' => $projectId,
            'type' => 'document',
            'name' => $result['original_name'],
        ]);

        $result['asset_id'] = $assetId;
        $result['file_record_id'] = (new FileRecordRepository())->create($result);

        (new AuditLogger())->record('uploaded', 'file', $result['file_record_id'], $result);

        $this->redirect('dbg-platform-media', 'uploaded');
    }

    public function archiveFile(): void
    {
        $this->guard('dbg_archive_file');

        $fileId = absint($_POST['file_id'] ?? 0);

        if ($fileId <= 0) {
            $this->redirect('dbg-platform-media', 'error', ['File ID is required.']);
        }

        $archived = (new FileRecordRepository())->archive($fileId);
        (new AuditLogger())->record('archived', 'file', $fileId, ['archived' => $archived]);

        $this->redirect('dbg-platform-media', 'deleted');
    }

    private function validateOrganisation(): void
    {
        $validator = (new FormValidator())
            ->required('dbg_organisation_name', 'Organisation name', $_POST)
            ->allowedValue('dbg_organisation_type', 'Organisation type', ['company', 'club', 'association', 'public_body', 'partner'], $_POST);
        if (!$validator->passes()) {
            $this->redirect('dbg-platform-organisations', 'error', $validator->errors());
        }
    }

    private function validateProject(): void
    {
        $validator = (new FormValidator())
            ->positiveInt('dbg_project_organisation_id', 'Organisation ID', $_POST)
            ->required('dbg_project_name', 'Project name', $_POST);
        if (!$validator->passes()) {
            $this->redirect('dbg-platform-projects', 'error', $validator->errors());
        }
    }

    private function validateAsset(): void
    {
        $validator = (new FormValidator())
            ->positiveInt('dbg_asset_organisation_id', 'Organisation ID', $_POST)
            ->allowedValue('dbg_asset_type', 'Asset type', ['logo', 'product', 'bat', 'document', 'image', 'template'], $_POST)
            ->required('dbg_asset_name', 'Asset name', $_POST);
        if (!$validator->passes()) {
            $this->redirect('dbg-platform-assets', 'error', $validator->errors());
        }
    }

    private function validateSettings(): void
    {
        $validator = (new FormValidator())
            ->allowedValue('sync_mode', 'Sync mode', ['local', 'remote', 'hybrid'], $_POST);
        if (!$validator->passes()) {
            $this->redirect('dbg-platform-settings', 'error', $validator->errors());
        }
    }

    private function organisationData(): array
    {
        return [
            'name' => sanitize_text_field($_POST['dbg_organisation_name'] ?? ''),
            'type' => sanitize_key($_POST['dbg_organisation_type'] ?? 'company'),
        ];
    }

    private function projectData(): array
    {
        return [
            'organisation_id' => absint($_POST['dbg_project_organisation_id'] ?? 0),
            'name' => sanitize_text_field($_POST['dbg_project_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['dbg_project_description'] ?? ''),
        ];
    }

    private function assetData(): array
    {
        return [
            'organisation_id' => absint($_POST['dbg_asset_organisation_id'] ?? 0),
            'project_id' => absint($_POST['dbg_asset_project_id'] ?? 0),
            'type' => sanitize_key($_POST['dbg_asset_type'] ?? 'document'),
            'name' => sanitize_text_field($_POST['dbg_asset_name'] ?? ''),
        ];
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
