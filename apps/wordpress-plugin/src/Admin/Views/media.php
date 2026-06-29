<?php
if (!defined('ABSPATH')) {
    exit;
}

$assetsRepository = new \DBGPlatform\Database\Repositories\AssetRepository();
$assets = array_filter($assetsRepository->all(), function ($asset) {
    return in_array($asset['type'], ['document', 'image', 'logo', 'bat', 'template'], true);
});
?>
<div class="wrap dbg-platform-admin">
    <h1>Media</h1>
    <p>Upload and review files linked to DBG Platform assets.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Upload file</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(rest_url('dbg/v1/files')); ?>">
            <p><input type="number" name="organisation_id" placeholder="Organisation ID" class="regular-text" required></p>
            <p><input type="number" name="project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p><input type="file" name="file" required></p>
            <p class="description">Accepted: PDF, PNG, JPG, SVG, ZIP, EPS, AI. Max size: 50 MB.</p>
            <p><button class="button button-primary" disabled>REST upload ready - admin-post handler next</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Media assets</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Organisation</th>
                    <th>Project</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($assets)) : ?>
                <tr><td colspan="6">No media assets found.</td></tr>
            <?php else : ?>
                <?php foreach ($assets as $asset) : ?>
                    <tr>
                        <td><?php echo esc_html($asset['id']); ?></td>
                        <td><?php echo esc_html($asset['organisation_id']); ?></td>
                        <td><?php echo esc_html($asset['project_id']); ?></td>
                        <td><?php echo esc_html($asset['type']); ?></td>
                        <td><?php echo esc_html($asset['name']); ?></td>
                        <td><?php echo esc_html($asset['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
