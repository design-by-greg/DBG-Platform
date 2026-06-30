<?php

namespace DBGPlatform\Files;

use DBGPlatform\Database\Repositories\FileRecordRepository;

class BrokenLinkChecker
{
    public function check(): array
    {
        $files = (new FileRecordRepository())->search(['status' => 'active'], 500);
        $broken = [];

        foreach ($files as $file) {
            $path = (string) ($file['path'] ?? '');
            $absolutePath = $this->absolutePath($path);

            if ($path === '') {
                $broken[] = $this->result($file, 'missing_path', 'File path is empty.');
                continue;
            }

            if (!file_exists($absolutePath)) {
                $broken[] = $this->result($file, 'missing_physical_file', 'File does not exist on disk.');
                continue;
            }

            if (!is_readable($absolutePath)) {
                $broken[] = $this->result($file, 'not_readable', 'File exists but is not readable.');
                continue;
            }

            $recordedSize = (int) ($file['size'] ?? 0);
            $actualSize = (int) filesize($absolutePath);

            if ($recordedSize > 0 && $actualSize !== $recordedSize) {
                $broken[] = $this->result($file, 'size_mismatch', 'Stored size does not match physical file size.', ['actual_size' => $actualSize]);
            }
        }

        return $broken;
    }

    private function absolutePath(string $relativePath): string
    {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . ltrim($relativePath, '/');
    }

    private function result(array $file, string $reason, string $message, array $extra = []): array
    {
        return array_merge([
            'file_id' => absint($file['id'] ?? 0),
            'original_name' => sanitize_text_field($file['original_name'] ?? ''),
            'path' => sanitize_text_field($file['path'] ?? ''),
            'url' => esc_url_raw($file['url'] ?? ''),
            'reason' => $reason,
            'message' => $message,
        ], $extra);
    }
}
