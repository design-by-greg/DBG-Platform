<?php

namespace DBGPlatform\Files;

use DBGPlatform\Database\Repositories\FileRecordRepository;

class SecureDownloadService
{
    public function download(int $fileId): void
    {
        if (!current_user_can('read')) {
            wp_die('Insufficient permissions.', 403);
        }

        $file = (new FileRecordRepository())->find($fileId);

        if (!$file || ($file['status'] ?? '') === 'archived') {
            wp_die('File not found.', 404);
        }

        $uploads = wp_upload_dir();
        $path = trailingslashit($uploads['basedir']) . ltrim((string) $file['path'], '/');

        if (!file_exists($path) || !is_readable($path)) {
            wp_die('File missing on disk.', 404);
        }

        $filename = sanitize_file_name((string) ($file['original_name'] ?? basename($path)));
        $mime = sanitize_text_field((string) ($file['mime_type'] ?? 'application/octet-stream'));

        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($path);
        exit;
    }
}
