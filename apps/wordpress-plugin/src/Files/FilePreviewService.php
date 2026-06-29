<?php

namespace DBGPlatform\Files;

class FilePreviewService
{
    public function previewType(array $file): string
    {
        $mime = (string) ($file['mime_type'] ?? '');

        if (strpos($mime, 'image/') === 0) {
            return 'image';
        }

        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        if (in_array($mime, ['application/zip', 'application/x-zip-compressed'], true)) {
            return 'archive';
        }

        if (in_array($mime, ['application/postscript', 'application/illustrator'], true)) {
            return 'design-file';
        }

        return 'file';
    }

    public function render(array $file): string
    {
        $type = $this->previewType($file);
        $url = esc_url((string) ($file['url'] ?? ''));
        $thumbnailUrl = esc_url((string) ($file['thumbnail_url'] ?? ''));
        $name = esc_html((string) ($file['original_name'] ?? 'File'));

        if ($url === '') {
            return '<span>No preview</span>';
        }

        if ($type === 'image') {
            $previewUrl = $thumbnailUrl !== '' ? $thumbnailUrl : $url;
            return '<a href="' . $url . '" target="_blank" rel="noopener"><img src="' . $previewUrl . '" alt="' . $name . '" style="max-width:90px;max-height:60px;object-fit:contain;background:#fff;border:1px solid #ddd;padding:2px;"></a>';
        }

        if ($type === 'pdf') {
            return '<a class="button" href="' . $url . '" target="_blank" rel="noopener">Preview PDF</a>';
        }

        return '<a href="' . $url . '" target="_blank" rel="noopener">' . esc_html(strtoupper($type)) . '</a>';
    }
}
