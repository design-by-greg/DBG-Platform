<?php

namespace DBGPlatform\Files;

use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaMaintenanceService
{
    public function run(string $action): array
    {
        $action = sanitize_key($action);

        if ($action === 'archive_broken_links') {
            return $this->archiveBrokenLinks();
        }

        if ($action === 'archive_orphan_records') {
            return $this->archiveOrphanRecords();
        }

        if ($action === 'cleanup_duplicate_groups') {
            return $this->cleanupDuplicateGroups();
        }

        if ($action === 'delete_physical_orphans') {
            return $this->deletePhysicalOrphans();
        }

        return ['success' => false, 'message' => 'Unsupported maintenance action.', 'action' => $action];
    }

    private function archiveBrokenLinks(): array
    {
        $repository = new FileRecordRepository();
        $brokenLinks = (new BrokenLinkChecker())->check();
        $ids = [];

        foreach ($brokenLinks as $item) {
            $fileId = absint($item['file_id'] ?? 0);
            if ($fileId > 0 && $repository->archive($fileId)) {
                $ids[] = $fileId;
            }
        }

        return ['success' => true, 'action' => 'archive_broken_links', 'count' => count($ids), 'ids' => $ids];
    }

    private function archiveOrphanRecords(): array
    {
        $repository = new FileRecordRepository();
        $orphans = $repository->orphanRecords();
        $ids = [];

        foreach ($orphans as $file) {
            $fileId = absint($file['id'] ?? 0);
            if ($fileId > 0 && $repository->archive($fileId)) {
                $ids[] = $fileId;
            }
        }

        return ['success' => true, 'action' => 'archive_orphan_records', 'count' => count($ids), 'ids' => $ids];
    }

    private function cleanupDuplicateGroups(): array
    {
        $repository = new FileRecordRepository();
        $groups = $repository->duplicateGroups();
        $archived = [];

        foreach ($groups as $group) {
            $files = (array) ($group['files'] ?? []);
            if (count($files) < 2) {
                continue;
            }

            usort($files, fn($a, $b) => absint($a['id'] ?? 0) <=> absint($b['id'] ?? 0));
            array_shift($files);

            foreach ($files as $file) {
                $fileId = absint($file['id'] ?? 0);
                if ($fileId > 0 && $repository->archive($fileId)) {
                    $archived[] = $fileId;
                }
            }
        }

        return ['success' => true, 'action' => 'cleanup_duplicate_groups', 'count' => count($archived), 'ids' => $archived];
    }

    private function deletePhysicalOrphans(): array
    {
        $orphans = (new OrphanFileScanner())->physicalOrphans();
        $deleted = [];
        $failed = [];

        foreach ($orphans as $file) {
            $absolutePath = (string) ($file['absolute_path'] ?? '');
            if ($absolutePath === '' || !file_exists($absolutePath)) {
                continue;
            }

            if (@unlink($absolutePath)) {
                $deleted[] = sanitize_text_field((string) ($file['path'] ?? $absolutePath));
            } else {
                $failed[] = sanitize_text_field((string) ($file['path'] ?? $absolutePath));
            }
        }

        return ['success' => true, 'action' => 'delete_physical_orphans', 'count' => count($deleted), 'deleted' => $deleted, 'failed' => $failed];
    }
}
