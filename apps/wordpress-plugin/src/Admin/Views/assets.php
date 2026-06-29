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

    <div class="dbg-platform-panel">
        <h2>Create Asset</h2>
        <form method="post" action="">
            <p><input type="number" name="dbg_asset_organisation_id" placeholder="Organisation ID" class="regular-text"></p>
            <p><input type="number" name="dbg_asset_project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p><input type="text" name="dbg_asset_type" placeholder="logo, product, bat, document" class="regular-text"></p>
            <p><input type="text" name="dbg_asset_name" placeholder="Asset name" class="regular-text"></p>
            <p><button class="button button-primary" disabled>Create Asset</button></p>
            <p class="description">Form UI ready. Submission handler will be added in next sprint.</p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Assets</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Organisation</th><th>Project</th><th>Type</th><th>Name</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($assets)) : ?>
                <tr><td colspan="6">No Assets found.</td></tr>
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
