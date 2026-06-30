<?php
if (!defined('ABSPATH')) { exit; }

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$brokenLinks = (new \DBGPlatform\Files\BrokenLinkChecker())->check();
$recordOrphans = $fileRepository->orphanRecords();
$physicalOrphans = (new \DBGPlatform\Files\OrphanFileScanner())->physicalOrphans();
$duplicateGroups = $fileRepository->duplicateGroups();
$archived = $fileRepository->search(['status' => 'archived'], 500);
$favorites = $fileRepository->search(['is_favorite' => 1, 'status' => 'active'], 500);
$totalActive = $fileRepository->paginated(['status' => 'active'], 1, 1)['pagination']['total'];
$healthIssues = count($brokenLinks) + count($recordOrphans) + count($physicalOrphans) + count($duplicateGroups);
$score = max(0, 100 - min(100, $healthIssues * 5));
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Health</h1>
    <p>Global health dashboard for DBG media library integrity.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html($score); ?>%</h2><p>Health score</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($totalActive); ?></h2><p>Active files</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($archived)); ?></h2><p>Archived files</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($favorites)); ?></h2><p>Favorites</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($duplicateGroups)); ?></h2><p>Duplicate groups</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($brokenLinks)); ?></h2><p>Broken links</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($recordOrphans)); ?></h2><p>Orphan records</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($physicalOrphans)); ?></h2><p>Physical orphans</p></div>
    </div>

    <div class="dbg-platform-panel">
        <h2>Quick actions</h2>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media')); ?>">Open Media</a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media-duplicates')); ?>">Review duplicates</a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media-broken-links')); ?>">Review broken links</a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media-orphans')); ?>">Review orphans</a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media-trash')); ?>">Restore archived files</a>
        </p>
    </div>

    <div class="dbg-platform-panel">
        <h2>Status summary</h2>
        <?php if ($healthIssues === 0) : ?>
            <p>No health issues detected.</p>
        <?php else : ?>
            <ul>
                <?php if (!empty($duplicateGroups)) : ?><li><?php echo esc_html(count($duplicateGroups)); ?> duplicate group(s) need review.</li><?php endif; ?>
                <?php if (!empty($brokenLinks)) : ?><li><?php echo esc_html(count($brokenLinks)); ?> broken link issue(s) detected.</li><?php endif; ?>
                <?php if (!empty($recordOrphans)) : ?><li><?php echo esc_html(count($recordOrphans)); ?> orphan database record(s) detected.</li><?php endif; ?>
                <?php if (!empty($physicalOrphans)) : ?><li><?php echo esc_html(count($physicalOrphans)); ?> physical orphan file(s) detected.</li><?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
