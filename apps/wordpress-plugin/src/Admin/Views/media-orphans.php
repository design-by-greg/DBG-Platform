<?php
if (!defined('ABSPATH')) { exit; }

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$scanner = new \DBGPlatform\Files\OrphanFileScanner();
$recordOrphans = $fileRepository->orphanRecords();
$physicalOrphans = $scanner->physicalOrphans();
$previewService = new \DBGPlatform\Files\FilePreviewService();
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Orphans</h1>
    <p>Detect files or records that are no longer correctly attached to assets.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Orphan records</h2>
        <p>Database records with no valid asset relation.</p>
        <?php if (empty($recordOrphans)) : ?>
            <p>No orphan records detected.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Preview</th><th>Name</th><th>Asset ID</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($recordOrphans as $file) : ?>
                    <tr>
                        <td><?php echo esc_html($file['id']); ?></td>
                        <td><?php echo wp_kses_post($previewService->render($file)); ?></td>
                        <td><?php echo esc_html($file['original_name']); ?></td>
                        <td><?php echo esc_html($file['asset_id']); ?></td>
                        <td><?php echo esc_html($file['orphan_reason']); ?></td>
                        <td><?php echo esc_html($file['status']); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media&only_orphans=1')); ?>">View in media</a>
                            <?php if ($file['status'] !== 'archived') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block">
                                    <input type="hidden" name="action" value="dbg_archive_file">
                                    <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                    <?php wp_nonce_field('dbg_archive_file'); ?>
                                    <button class="button">Archive</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="dbg-platform-panel">
        <h2>Physical orphan files</h2>
        <p>Files present on disk but not registered in the database.</p>
        <?php if (empty($physicalOrphans)) : ?>
            <p>No physical orphan files detected.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th>Path</th><th>Size</th><th>Modified</th><th>Reason</th></tr></thead>
                <tbody>
                <?php foreach ($physicalOrphans as $file) : ?>
                    <tr>
                        <td><code><?php echo esc_html($file['path']); ?></code></td>
                        <td><?php echo esc_html(size_format((int) $file['size'])); ?></td>
                        <td><?php echo esc_html($file['modified_at']); ?></td>
                        <td><?php echo esc_html($file['reason']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
