<?php

namespace DBGPlatform\Files;

class ThumbnailService
{
    public function generate(array $file): array
    {
        $mime = (string) ($file['mime_type'] ?? '');

        if (strpos($mime, 'image/') !== 0 || $mime === 'image/svg+xml') {
            return ['success' => false, 'message' => 'Thumbnail not supported for this file type.'];
        }

        $uploads = wp_upload_dir();
        $sourcePath = trailingslashit($uploads['basedir']) . ltrim((string) ($file['path'] ?? ''), '/');

        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return ['success' => false, 'message' => 'Source file is missing.'];
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $editor = wp_get_image_editor($sourcePath);

        if (is_wp_error($editor)) {
            return ['success' => false, 'message' => $editor->get_error_message()];
        }

        $editor->resize(320, 320, false);

        $info = pathinfo((string) ($file['filename'] ?? basename($sourcePath)));
        $thumbName = ($info['filename'] ?? uniqid('thumb_', true)) . '-thumb.jpg';
        $thumbDir = trailingslashit(dirname($sourcePath)) . 'thumbnails';

        if (!wp_mkdir_p($thumbDir)) {
            return ['success' => false, 'message' => 'Unable to create thumbnail directory.'];
        }

        $thumbPath = trailingslashit($thumbDir) . $thumbName;
        $saved = $editor->save($thumbPath, 'image/jpeg');

        if (is_wp_error($saved)) {
            return ['success' => false, 'message' => $saved->get_error_message()];
        }

        $relativePath = trailingslashit(dirname((string) ($file['path'] ?? ''))) . 'thumbnails/' . $thumbName;
        $url = trailingslashit($uploads['baseurl']) . ltrim($relativePath, '/');

        return [
            'success' => true,
            'thumbnail_path' => $relativePath,
            'thumbnail_url' => $url,
        ];
    }
}
