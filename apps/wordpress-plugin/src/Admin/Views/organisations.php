<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$organisations = $repository->all();
?>
<div class="wrap dbg-platform-admin">
    <h1>Organisations</h1>
    <p>List and manage Organisations connected to DBG Platform.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Create Organisation</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_organisation">
            <?php wp_nonce_field('dbg_create_organisation'); ?>
            <p><input type="text" name="dbg_organisation_name" placeholder="Organisation name" class="regular-text" required></p>
            <p><input type="text" name="dbg_organisation_type" placeholder="company" class="regular-text" required></p>
            <p><button class="button button-primary">Create Organisation</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Organisations</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Update</th><th>Archive</th></tr></thead>
            <tbody>
            <?php if (empty($organisations)) : ?>
                <tr><td colspan="6">No Organisations found.</td></tr>
            <?php else : ?>
                <?php foreach ($organisations as $organisation) : ?>
                    <tr>
                        <td><?php echo esc_html($organisation['id']); ?></td>
                        <td colspan="3">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_update_organisation">
                                <input type="hidden" name="dbg_id" value="<?php echo esc_attr($organisation['id']); ?>">
                                <?php wp_nonce_field('dbg_update_organisation'); ?>
                                <input type="text" name="dbg_organisation_name" value="<?php echo esc_attr($organisation['name']); ?>" required>
                                <input type="text" name="dbg_organisation_type" value="<?php echo esc_attr($organisation['type']); ?>" required>
                                <span><?php echo esc_html($organisation['status']); ?></span>
                        </td>
                        <td><button class="button">Save</button></form></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_delete_organisation">
                                <input type="hidden" name="dbg_id" value="<?php echo esc_attr($organisation['id']); ?>">
                                <?php wp_nonce_field('dbg_delete_organisation'); ?>
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
