<?php
if (!defined('ABSPATH')) { exit; }

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$previewService = new \DBGPlatform\Files\FilePreviewService();
$files = $fileRepository->search(['status' => 'archived'], 100);
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Trash</h1>
    <p>Review archived media files and restore them when needed.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Archived files</h2>
        <?php if (empty($files)) : ?>
            <p>No archived files.</p>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_bulk_restore_files">
                <?php wp_nonce_field('dbg_bulk_restore_files'); ?>
                <p><button class="button button-primary">Restore selected</button></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="document.querySelectorAll('.dbg-trash-select').forEach(cb => cb.checked = this.checked);"></th>
                            <th>ID</th><th>Preview</th><th>Name</th><th>Type</th><th>Size</th><th>Archived</th><th>Restore</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file) : ?>
                            <tr>
                                <td><input class="dbg-trash-select" type="checkbox" name="file_ids[]" value="<?php echo esc_attr($file['id']); ?>"></td>
                                <td><?php echo esc_html($file['id']); ?></td>
                                <td><?php echo wp_kses_post($previewService->render($file)); ?></td>
                                <td><?php echo esc_html($file['original_name']); ?></td>
                                <td><?php echo esc_html($file['mime_type']); ?></td>
                                <td><?php echo esc_html(size_format((int) $file['size'])); ?></td>
                                <td><?php echo esc_html($file['updated_at'] ?? ''); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="dbg_restore_file">
                                        <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                        <?php wp_nonce_field('dbg_restore_file'); ?>
                                        <button class="button">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>
</div>
