<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Settings\SettingsRepository();
$settings = $repository->all();
?>
<div class="wrap dbg-platform-admin">
    <h1>Settings</h1>
    <p>Configure DBG Platform plugin settings.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_update_settings">
            <?php wp_nonce_field('dbg_update_settings'); ?>

            <h2>API</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="api_base_url">API base URL</label></th>
                    <td><input type="url" id="api_base_url" name="api_base_url" class="regular-text" value="<?php echo esc_attr($settings['api_base_url']); ?>" placeholder="https://api.designbygreg.fr"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="api_token">API token</label></th>
                    <td><input type="password" id="api_token" name="api_token" class="regular-text" value="<?php echo esc_attr($settings['api_token']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sync_mode">Sync mode</label></th>
                    <td>
                        <select id="sync_mode" name="sync_mode">
                            <option value="local" <?php selected($settings['sync_mode'], 'local'); ?>>Local</option>
                            <option value="remote" <?php selected($settings['sync_mode'], 'remote'); ?>>Remote</option>
                            <option value="hybrid" <?php selected($settings['sync_mode'], 'hybrid'); ?>>Hybrid</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2>WooCommerce</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">WooCommerce integration</th>
                    <td><label><input type="checkbox" name="woocommerce_enabled" value="1" <?php checked(!empty($settings['woocommerce_enabled'])); ?>> Enabled</label></td>
                </tr>
                <tr>
                    <th scope="row">Debug mode</th>
                    <td><label><input type="checkbox" name="debug_enabled" value="1" <?php checked(!empty($settings['debug_enabled'])); ?>> Enabled</label></td>
                </tr>
            </table>

            <p><button class="button button-primary">Save settings</button></p>
        </form>
    </div>
</div>
