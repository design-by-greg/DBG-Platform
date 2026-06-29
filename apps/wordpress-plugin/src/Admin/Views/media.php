<?php
if (!defined('ABSPATH')) {
    exit;
}

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$files = $fileRepository->all(100);
?>
<div class="wrap dbg-platform-admin">
    <h1>Media</h1>
    <p>Upload and review files linked to DBG Platform assets.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Upload file</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_upload_media">
            <?php wp_nonce_field('dbg_upload_media'); ?>
            <p><input type="number" name="organisation_id" placeholder="Organisation ID" class="regular-text" required></p>
            <p><input type="number" name="project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p><input type="file" name="file" required></p>
            <p class="description">Accepted: PDF, PNG, JPG, SVG, ZIP, EPS, AI. Max size: 50 MB.</p>
            <p><button class="button button-primary">Upload file</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>File records</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
                    <th>Organisation</th>
                    <th>Project</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Open</th>
                    <th>Archive</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($files)) : ?>
                <tr><td colspan="10">No file records found.</td></tr>
            <?php else : ?>
                <?php foreach ($files as $file) : ?>
                    <tr>
                        <td><?php echo esc_html($file['id']); ?></td>
                        <td><?php echo esc_html($file['asset_id']); ?></td>
                        <td><?php echo esc_html($file['organisation_id']); ?></td>
                        <td><?php echo esc_html($file['project_id']); ?></td>
                        <td><?php echo esc_html($file['original_name']); ?></td>
                        <td><?php echo esc_html($file['mime_type']); ?></td>
                        <td><?php echo esc_html(size_format((int) $file['size'])); ?></td>
                        <td><?php echo esc_html($file['status']); ?></td>
                        <td><a href="<?php echo esc_url($file['url']); ?>" target="_blank" rel="noopener">Open</a></td>
                        <td>
                            <?php if ($file['status'] !== 'archived') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_archive_file">
                                    <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                    <?php wp_nonce_field('dbg_archive_file'); ?>
                                    <button class="button">Archive</button>
                                </form>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
