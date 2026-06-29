<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Database\Repositories\ProjectRepository();
$projects = $repository->all();
?>
<div class="wrap dbg-platform-admin">
    <h1>Projects</h1>
    <p>Project-first view for customer work, assets and orders.</p>

    <div class="dbg-platform-panel">
        <h2>Create Project</h2>
        <form method="post" action="">
            <p><input type="number" name="dbg_project_organisation_id" placeholder="Organisation ID" class="regular-text"></p>
            <p><input type="text" name="dbg_project_name" placeholder="Project name" class="regular-text"></p>
            <p><textarea name="dbg_project_description" placeholder="Description" class="large-text"></textarea></p>
            <p><button class="button button-primary" disabled>Create Project</button></p>
            <p class="description">Form UI ready. Submission handler will be added in next sprint.</p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Projects</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Organisation</th><th>Name</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($projects)) : ?>
                <tr><td colspan="4">No Projects found.</td></tr>
            <?php else : ?>
                <?php foreach ($projects as $project) : ?>
                    <tr>
                        <td><?php echo esc_html($project['id']); ?></td>
                        <td><?php echo esc_html($project['organisation_id']); ?></td>
                        <td><?php echo esc_html($project['name']); ?></td>
                        <td><?php echo esc_html($project['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
