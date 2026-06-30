<?php
if (!defined('ABSPATH')) { exit; }

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$previewService = new \DBGPlatform\Files\FilePreviewService();
$groups = $fileRepository->duplicateGroups();
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Duplicates</h1>
    <p>Review duplicate uploaded files detected by SHA-256 hash.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Duplicate groups</h2>
        <?php if (empty($groups)) : ?>
            <p>No duplicate files detected.</p>
        <?php else : ?>
            <p><?php echo esc_html(count($groups)); ?> duplicate group(s) detected.</p>
            <?php foreach ($groups as $group) : ?>
                <div class="dbg-platform-panel">
                    <h3>Hash <?php echo esc_html($group['file_hash']); ?></h3>
                    <p><?php echo esc_html($group['duplicate_count']); ?> duplicate files</p>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Preview</th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Organisation</th>
                                <th>Project</th>
                                <th>Folder</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((array) ($group['files'] ?? []) as $file) : ?>
                                <?php $downloadUrl = wp_nonce_url(admin_url('admin-post.php?action=dbg_download_file&file_id=' . absint($file['id'])), 'dbg_download_file'); ?>
                                <tr>
                                    <td><?php echo esc_html($file['id']); ?></td>
                                    <td><?php echo wp_kses_post($previewService->render($file)); ?></td>
                                    <td><?php echo esc_html($file['original_name']); ?></td>
                                    <td><?php echo esc_html(size_format((int) $file['size'])); ?></td>
                                    <td><?php echo esc_html($file['organisation_id']); ?></td>
                                    <td><?php echo esc_html($file['project_id']); ?></td>
                                    <td><?php echo esc_html($file['folder_id']); ?></td>
                                    <td><?php echo esc_html($file['status']); ?></td>
                                    <td>
                                        <a class="button" href="<?php echo esc_url($downloadUrl); ?>">Download</a>
                                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media&file_hash=' . rawurlencode((string) $group['file_hash']))); ?>">Filter</a>
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
