<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Database\Repositories\AssetRepository();
$assets = $repository->all();
?>
<div class="wrap dbg-platform-admin">
    <h1>Assets</h1>
    <p>Manage reusable assets owned by Organisations.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Create Asset</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_asset">
            <?php wp_nonce_field('dbg_create_asset'); ?>
            <p><input type="number" name="dbg_asset_organisation_id" placeholder="Organisation ID" class="regular-text" required></p>
            <p><input type="number" name="dbg_asset_project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p><input type="text" name="dbg_asset_type" placeholder="logo, product, bat, document" class="regular-text" required></p>
            <p><input type="text" name="dbg_asset_name" placeholder="Asset name" class="regular-text" required></p>
            <p><button class="button button-primary">Create Asset</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Assets</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Organisation</th><th>Project</th><th>Type</th><th>Name</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($assets)) : ?>
                <tr><td colspan="7">No Assets found.</td></tr>
            <?php else : ?>
                <?php foreach ($assets as $asset) : ?>
                    <tr>
                        <td><?php echo esc_html($asset['id']); ?></td>
                        <td><?php echo esc_html($asset['organisation_id']); ?></td>
                        <td><?php echo esc_html($asset['project_id']); ?></td>
                        <td><?php echo esc_html($asset['type']); ?></td>
                        <td><?php echo esc_html($asset['name']); ?></td>
                        <td><?php echo esc_html($asset['status']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_delete_asset">
                                <input type="hidden" name="dbg_id" value="<?php echo esc_attr($asset['id']); ?>">
                                <?php wp_nonce_field('dbg_delete_asset'); ?>
                                <button class="button">Archive</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
