<?php

namespace DBGPlatform\Files;

class ImageCompressionService
{
    public function compress(string $path, string $mimeType): array
    {
        if (!$this->supports($mimeType)) {
            return ['success' => false, 'message' => 'Compression not supported for this file type.'];
        }

        if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
            return ['success' => false, 'message' => 'File is not available for compression.'];
        }

        $before = filesize($path) ?: 0;

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $editor = wp_get_image_editor($path);

        if (is_wp_error($editor)) {
            return ['success' => false, 'message' => $editor->get_error_message()];
        }

        if (method_exists($editor, 'set_quality')) {
            $editor->set_quality($this->qualityFor($mimeType));
        }

        $saved = $editor->save($path, $mimeType);

        if (is_wp_error($saved)) {
            return ['success' => false, 'message' => $saved->get_error_message()];
        }

        clearstatcache(true, $path);
        $after = filesize($path) ?: $before;

        return [
            'success' => true,
            'compressed' => $after < $before,
            'before_size' => $before,
            'after_size' => $after,
            'saved_bytes' => max(0, $before - $after),
        ];
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    private function qualityFor(string $mimeType): int
    {
        if ($mimeType === 'image/png') {
            return 82;
        }

        return 84;
    }
}
