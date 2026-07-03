<?php

namespace DBGPlatform\Files;

class FileUploadService
{
    private array $allowedMimes = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/svg+xml',
        'application/zip',
        'application/x-zip-compressed',
        'application/postscript',
        'application/illustrator',
    ];

    public function upload(array $file, array $context = []): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No valid uploaded file.'];
        }

        $mime = (string) ($file['type'] ?? '');

        if (!in_array($mime, $this->allowedMimes, true)) {
            return ['success' => false, 'message' => 'File type is not allowed.'];
        }

        $maxSize = 50 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxSize) {
            return ['success' => false, 'message' => 'File is too large.'];
        }

        $uploads = wp_upload_dir();
        $baseDir = trailingslashit($uploads['basedir']) . 'dbg-platform';
        $baseUrl = trailingslashit($uploads['baseurl']) . 'dbg-platform';

        $organisationId = sanitize_text_field((string) ($context['organisation_id'] ?? ''));
        $projectId = trim((string) ($context['project_id'] ?? '')) !== '' ? sanitize_text_field((string) $context['project_id']) : null;
        $targetDir = $baseDir . '/org-' . $organisationId . '/project-' . ($projectId ?? 'none');

        if (!wp_mkdir_p($targetDir)) {
            return ['success' => false, 'message' => 'Unable to create upload directory.'];
        }

        $originalName = sanitize_file_name((string) ($file['name'] ?? 'upload'));
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid('dbg_', true) . ($extension ? '.' . strtolower($extension) : '');
        $destination = trailingslashit($targetDir) . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Unable to move uploaded file.'];
        }

        $compression = (new ImageCompressionService())->compress($destination, $mime);
        clearstatcache(true, $destination);
        $fileHash = file_exists($destination) ? hash_file('sha256', $destination) : null;

        $relativePath = 'dbg-platform/org-' . $organisationId . '/project-' . ($projectId ?? 'none') . '/' . $filename;

        return [
            'success' => true,
            'original_name' => $originalName,
            'filename' => $filename,
            'mime_type' => $mime,
            'size' => file_exists($destination) ? (int) filesize($destination) : (int) $file['size'],
            'file_hash' => $fileHash,
            'path' => $relativePath,
            'url' => trailingslashit($baseUrl) . 'org-' . $organisationId . '/project-' . ($projectId ?? 'none') . '/' . $filename,
            'organisation_id' => $organisationId,
            'project_id' => $projectId,
            'compression' => $compression,
        ];
    }
}
