<?php

namespace DBGPlatform\Files;

class OrphanFileScanner
{
    public function physicalOrphans(): array
    {
        global $wpdb;

        $uploads = wp_upload_dir();
        $baseDir = trailingslashit($uploads['basedir']) . 'dbg-platform';

        if (!is_dir($baseDir)) {
            return [];
        }

        $table = $wpdb->prefix . 'dbg_file_records';
        $knownPaths = $wpdb->get_col("SELECT path FROM {$table}") ?: [];
        $knownLookup = array_fill_keys(array_map('strval', $knownPaths), true);
        $orphans = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = 'dbg-platform/' . ltrim(str_replace($baseDir, '', $absolutePath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if (isset($knownLookup[$relativePath])) {
                continue;
            }

            $orphans[] = [
                'path' => $relativePath,
                'absolute_path' => $absolutePath,
                'size' => (int) $file->getSize(),
                'modified_at' => gmdate('c', (int) $file->getMTime()),
                'reason' => 'physical_file_without_record',
            ];
        }

        return $orphans;
    }
}
