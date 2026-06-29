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

    <?php if (isset($_GET['dbg_status']) && $_GET['dbg_status'] === 'created') : ?>
        <div class="notice notice-success is-dismissible"><p>Organisation created.</p></div>
    <?php endif; ?>

    <div class="dbg-platform-panel">
        <h2>Create Organisation</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_organisation">
            <?php wp_nonce_field('dbg_create_organisation'); ?>
            <p><input type="text" name="dbg_organisation_name" placeholder="Organisation name" class="regular-text" required></p>
            <p><input type="text" name="dbg_organisation_type" placeholder="company, club, association" class="regular-text" required></p>
            <p><button class="button button-primary">Create Organisation</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Organisations</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($organisations)) : ?>
                <tr><td colspan="4">No Organisations found.</td></tr>
            <?php else : ?>
                <?php foreach ($organisations as $organisation) : ?>
                    <tr>
                        <td><?php echo esc_html($organisation['id']); ?></td>
                        <td><?php echo esc_html($organisation['name']); ?></td>
                        <td><?php echo esc_html($organisation['type']); ?></td>
                        <td><?php echo esc_html($organisation['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
