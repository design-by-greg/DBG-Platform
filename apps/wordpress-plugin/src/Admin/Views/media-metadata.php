<?php
if (!defined('ABSPATH')) { exit; }

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$metadataRepository = new \DBGPlatform\Database\Repositories\FileMetadataRepository();
$files = $fileRepository->search(['status' => 'active'], 100);
$selectedFileId = absint($_GET['file_id'] ?? 0);
$metadata = $selectedFileId > 0 ? $metadataRepository->allForFile($selectedFileId) : [];
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Metadata</h1>
    <p>Manage custom metadata linked to uploaded files.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Select file</h2>
        <form method="get">
            <input type="hidden" name="page" value="dbg-platform-media-metadata">
            <select name="file_id" required>
                <option value="0">Select a file</option>
                <?php foreach ($files as $file) : ?>
                    <option value="<?php echo esc_attr($file['id']); ?>" <?php selected($selectedFileId, (int) $file['id']); ?>>#<?php echo esc_html($file['id']); ?> — <?php echo esc_html($file['original_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary">Open metadata</button>
        </form>
    </div>

    <?php if ($selectedFileId > 0) : ?>
        <div class="dbg-platform-panel">
            <h2>Add / update metadata</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_update_file_metadata">
                <input type="hidden" name="file_id" value="<?php echo esc_attr($selectedFileId); ?>">
                <?php wp_nonce_field('dbg_update_file_metadata'); ?>
                <p><input type="text" name="meta_key" placeholder="metadata_key" required></p>
                <p><textarea name="meta_value" rows="4" class="large-text" placeholder="Metadata value"></textarea></p>
                <button class="button button-primary">Save metadata</button>
            </form>
        </div>

        <div class="dbg-platform-panel">
            <h2>Current metadata</h2>
            <table class="widefat striped">
                <thead><tr><th>Key</th><th>Value</th><th>Delete</th></tr></thead>
                <tbody>
                    <?php if (empty($metadata)) : ?>
                        <tr><td colspan="3">No metadata yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($metadata as $key => $value) : ?>
                            <tr>
                                <td><?php echo esc_html($key); ?></td>
                                <td><pre><?php echo esc_html(is_scalar($value) ? (string) $value : wp_json_encode($value, JSON_PRETTY_PRINT)); ?></pre></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="dbg_delete_file_metadata">
                                        <input type="hidden" name="file_id" value="<?php echo esc_attr($selectedFileId); ?>">
                                        <input type="hidden" name="meta_key" value="<?php echo esc_attr($key); ?>">
                                        <?php wp_nonce_field('dbg_delete_file_metadata'); ?>
                                        <button class="button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
