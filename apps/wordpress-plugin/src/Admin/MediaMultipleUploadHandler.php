<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Database\Repositories\FileVersionRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Files\ThumbnailService;

class MediaMultipleUploadHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_upload_media', [$this, 'handle'], 1);
    }

    public function handle(): void
    {
        if (empty($_FILES['files']) || !is_array($_FILES['files']['name'] ?? null)) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('dbg_upload_media');

        $organisationId = sanitize_text_field((string) ($_POST['organisation_id'] ?? ''));
        $projectId = sanitize_text_field((string) ($_POST['project_id'] ?? ''));
        $folderId = absint($_POST['folder_id'] ?? 0);

        if (trim($organisationId) === '') {
            $this->redirect('error', ['Organisation ID is required.']);
        }

        $files = $this->splitFileArray($_FILES['files']);

        if (empty($files)) {
            $this->redirect('error', ['At least one file is required.']);
        }

        $uploaded = 0;
        $errors = [];
        $thumbnailService = new ThumbnailService();

        foreach ($files as $file) {
            $result = (new FileUploadService())->upload($file, [
                'organisation_id' => $organisationId,
                'project_id' => $projectId,
            ]);

            if (empty($result['success'])) {
                $errors[] = ($file['name'] ?? 'File') . ': ' . ($result['message'] ?? 'Upload failed.');
                continue;
            }

            $thumbnail = $thumbnailService->generate($result);
            if (!empty($thumbnail['success'])) {
                $result['thumbnail_path'] = $thumbnail['thumbnail_path'];
                $result['thumbnail_url'] = $thumbnail['thumbnail_url'];
            }

            $assetId = (new AssetRepository())->create([
                'organisation_id' => $organisationId,
                'project_id' => $projectId,
                'type' => 'document',
                'name' => $result['original_name'],
            ]);

            $result['asset_id'] = $assetId;
            $result['folder_id'] = $folderId;
            $result['file_record_id'] = (new FileRecordRepository())->create($result);
            (new FileVersionRepository())->create($result['file_record_id'], $result, 'Initial upload');
            (new AuditLogger())->record('uploaded', 'file', $result['file_record_id'], $result);
            $uploaded++;
        }

        if ($uploaded === 0 && !empty($errors)) {
            $this->redirect('error', $errors);
        }

        $this->redirect('uploaded');
    }

    private function splitFileArray(array $fileArray): array
    {
        $files = [];

        foreach ((array) ($fileArray['name'] ?? []) as $index => $name) {
            if ((int) ($fileArray['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => $name,
                'type' => $fileArray['type'][$index] ?? '',
                'tmp_name' => $fileArray['tmp_name'][$index] ?? '',
                'error' => $fileArray['error'][$index] ?? 0,
                'size' => $fileArray['size'][$index] ?? 0,
            ];
        }

        return $files;
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
