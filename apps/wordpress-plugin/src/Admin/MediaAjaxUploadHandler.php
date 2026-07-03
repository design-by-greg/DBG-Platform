<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Database\Repositories\FileVersionRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Files\ThumbnailService;

class MediaAjaxUploadHandler
{
    public function register(): void
    {
        add_action('wp_ajax_dbg_ajax_upload_media', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
        }

        check_ajax_referer('dbg_ajax_upload_media', 'nonce');

        $organisationId = sanitize_text_field((string) ($_POST['organisation_id'] ?? ''));
        $projectId = sanitize_text_field((string) ($_POST['project_id'] ?? ''));
        $folderId = absint($_POST['folder_id'] ?? 0);

        if (trim($organisationId) === '') {
            wp_send_json_error(['message' => 'Organisation ID is required.'], 422);
        }

        $files = $this->normaliseUploadedFiles();

        if (empty($files)) {
            wp_send_json_error(['message' => 'At least one file is required.'], 422);
        }

        $uploaded = [];
        $errors = [];
        $uploadService = new FileUploadService();
        $thumbnailService = new ThumbnailService();
        $fileRepository = new FileRecordRepository();
        $audit = new AuditLogger();

        foreach ($files as $file) {
            $result = $uploadService->upload($file, [
                'organisation_id' => $organisationId,
                'project_id' => $projectId,
            ]);

            if (empty($result['success'])) {
                $errors[] = [
                    'file' => $file['name'] ?? 'File',
                    'message' => $result['message'] ?? 'Upload failed.',
                ];
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
            $result['file_record_id'] = $fileRepository->create($result);

            (new FileVersionRepository())->create($result['file_record_id'], $result, 'Initial upload');
            $audit->record('uploaded', 'file', $result['file_record_id'], $result);
            $uploaded[] = $result;
        }

        if (empty($uploaded) && !empty($errors)) {
            wp_send_json_error(['message' => 'Upload failed.', 'errors' => $errors], 422);
        }

        wp_send_json_success([
            'message' => count($uploaded) . ' file(s) uploaded',
            'uploaded' => $uploaded,
            'errors' => $errors,
        ]);
    }

    private function normaliseUploadedFiles(): array
    {
        if (!empty($_FILES['files']) && is_array($_FILES['files']['name'] ?? null)) {
            return $this->splitFileArray($_FILES['files']);
        }

        if (!empty($_FILES['file']) && is_array($_FILES['file']['name'] ?? null)) {
            return $this->splitFileArray($_FILES['file']);
        }

        return empty($_FILES['file']) ? [] : [$_FILES['file']];
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
}
