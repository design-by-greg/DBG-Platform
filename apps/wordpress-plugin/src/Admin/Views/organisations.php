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

    <div class="dbg-platform-panel">
        <h2>Create Organisation</h2>
        <form method="post" action="">
            <p><input type="text" name="dbg_organisation_name" placeholder="Organisation name" class="regular-text"></p>
            <p><input type="text" name="dbg_organisation_type" placeholder="company, club, association" class="regular-text"></p>
            <p><button class="button button-primary" disabled>Create Organisation</button></p>
            <p class="description">Form UI ready. Submission handler will be added in next sprint.</p>
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
