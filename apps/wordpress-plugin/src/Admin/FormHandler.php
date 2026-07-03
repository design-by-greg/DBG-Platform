<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Database\Repositories\FileVersionRepository;
use DBGPlatform\Database\Repositories\MediaFolderRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Files\SecureDownloadService;
use DBGPlatform\Settings\SettingsRepository;

/**
 * Handles admin-post form submissions that are NOT already owned by a more
 * specific handler. Asset create/update/delete live in AssetAdminHandler,
 * and multi-file media upload lives in MediaMultipleUploadHandler — do not
 * re-register those hooks here (see ADR-007 changelog 2026-07-03 cleanup:
 * this class used to shadow-register `dbg_create_asset` / `dbg_update_asset`
 * / `dbg_upload_media`, but those callbacks were unreachable dead code
 * because the other handlers run first and `exit` after redirecting; the
 * standalone `dbg_delete_asset` handler was also removed because no view
 * ever submits to it and it called a repository method that doesn't exist —
 * asset removal goes through archive/restore in AssetAdminHandler instead).
 */
class FormHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_update_settings', [$this, 'updateSettings']);
        add_action('admin_post_dbg_archive_file', [$this, 'archiveFile']);
        add_action('admin_post_dbg_download_file', [$this, 'downloadFile']);
        add_action('admin_post_dbg_upload_file_version', [$this, 'uploadFileVersion']);
        add_action('admin_post_dbg_create_media_folder', [$this, 'createMediaFolder']);
        add_action('admin_post_dbg_move_file_folder', [$this, 'moveFileFolder']);
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

    public function uploadFileVersion(): void
    {
        $this->guard('dbg_upload_file_version');

        $fileId = absint($_POST['file_id'] ?? 0);
        $existing = (new FileRecordRepository())->find($fileId);

        if (!$existing) {
            $this->redirect('dbg-platform-media', 'error', ['File record not found.']);
        }

        if (empty($_FILES['file'])) {
            $this->redirect('dbg-platform-media', 'error', ['Version file is required.']);
        }

        $result = (new FileUploadService())->upload($_FILES['file'], [
            'organisation_id' => (string) ($existing['organisation_id'] ?? ''),
            'project_id' => $existing['project_id'] ?? null,
        ]);

        if (empty($result['success'])) {
            $this->redirect('dbg-platform-media', 'error', [$result['message'] ?? 'Upload failed.']);
        }

        $result['asset_id'] = absint($existing['asset_id']);
        $versionId = (new FileVersionRepository())->create($fileId, $result, sanitize_textarea_field($_POST['version_note'] ?? ''));
        (new FileRecordRepository())->replace($fileId, $result);

        (new AuditLogger())->record('version_uploaded', 'file', $fileId, ['version_id' => $versionId]);

        $this->redirect('dbg-platform-media', 'updated');
    }

    public function createMediaFolder(): void
    {
        $this->guard('dbg_create_media_folder');

        if (trim((string) ($_POST['folder_name'] ?? '')) === '') {
            $this->redirect('dbg-platform-media', 'error', ['Folder name is required.']);
        }

        $folderId = (new MediaFolderRepository())->create([
            'organisation_id' => sanitize_text_field((string) ($_POST['organisation_id'] ?? '')),
            'project_id' => trim((string) ($_POST['project_id'] ?? '')) !== '' ? sanitize_text_field((string) $_POST['project_id']) : null,
            'parent_id' => absint($_POST['parent_id'] ?? 0),
            'name' => $_POST['folder_name'] ?? '',
        ]);

        (new AuditLogger())->record('created', 'media_folder', $folderId, []);
        $this->redirect('dbg-platform-media', 'created');
    }

    public function moveFileFolder(): void
    {
        $this->guard('dbg_move_file_folder');

        $fileId = absint($_POST['file_id'] ?? 0);
        $folderId = absint($_POST['folder_id'] ?? 0);
        $moved = (new FileRecordRepository())->moveToFolder($fileId, $folderId);

        (new AuditLogger())->record('moved', 'file', $fileId, ['folder_id' => $folderId, 'moved' => $moved]);
        $this->redirect('dbg-platform-media', 'updated');
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

    public function downloadFile(): void
    {
        $this->guard('dbg_download_file');
        $fileId = absint($_GET['file_id'] ?? $_POST['file_id'] ?? 0);
        (new AuditLogger())->record('downloaded', 'file', $fileId, []);
        (new SecureDownloadService())->download($fileId);
    }

    private function validateSettings(): void
    {
        $validator = (new FormValidator())
            ->allowedValue('sync_mode', 'Sync mode', ['local', 'remote', 'hybrid'], $_POST);
        if (!$validator->passes()) {
            $this->redirect('dbg-platform-settings', 'error', $validator->errors());
        }
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
