<?php

namespace DBGPlatform\Files;

use DBGPlatform\Database\Repositories\FileRecordRepository;

class MediaHealthService
{
    public function summary(): array
    {
        $files = new FileRecordRepository();
        $brokenLinks = (new BrokenLinkChecker())->check();
        $recordOrphans = $files->orphanRecords();
        $physicalOrphans = (new OrphanFileScanner())->physicalOrphans();
        $duplicateGroups = $files->duplicateGroups();
        $activeTotal = $files->paginated(['status' => 'active'], 1, 1)['pagination']['total'] ?? 0;
        $archivedTotal = $files->paginated(['status' => 'archived'], 1, 1)['pagination']['total'] ?? 0;
        $favoriteTotal = $files->paginated(['status' => 'active', 'is_favorite' => 1], 1, 1)['pagination']['total'] ?? 0;
        $issueTotal = count($brokenLinks) + count($recordOrphans) + count($physicalOrphans) + count($duplicateGroups);

        return [
            'score' => max(0, 100 - min(100, $issueTotal * 5)),
            'totals' => [
                'active_files' => (int) $activeTotal,
                'archived_files' => (int) $archivedTotal,
                'favorite_files' => (int) $favoriteTotal,
                'duplicate_groups' => count($duplicateGroups),
                'broken_links' => count($brokenLinks),
                'orphan_records' => count($recordOrphans),
                'physical_orphans' => count($physicalOrphans),
                'issues' => $issueTotal,
            ],
            'issues' => [
                'broken_links' => $brokenLinks,
                'orphan_records' => $recordOrphans,
                'physical_orphans' => $physicalOrphans,
                'duplicate_groups' => $duplicateGroups,
            ],
        ];
    }
}
