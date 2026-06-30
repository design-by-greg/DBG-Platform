<?php
if (!defined('ABSPATH')) { exit; }

$result = get_transient('dbg_media_maintenance_result_' . get_current_user_id());
if ($result) {
    delete_transient('dbg_media_maintenance_result_' . get_current_user_id());
}
$actions = [
    'archive_broken_links' => 'Archive active records with missing, unreadable or inconsistent physical files.',
    'archive_orphan_records' => 'Archive records that are not attached to a valid asset.',
    'cleanup_duplicate_groups' => 'Keep the oldest file in each duplicate group and archive the others.',
    'delete_physical_orphans' => 'Remove disk files that do not have a database record.',
];
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Maintenance</h1>
    <p>Run controlled maintenance actions on the media library.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <?php if (!empty($result)) : ?>
        <div class="notice notice-success"><p>Action completed: <?php echo esc_html($result['action'] ?? ''); ?> · Count: <?php echo esc_html($result['count'] ?? 0); ?></p></div>
    <?php endif; ?>

    <div class="dbg-platform-panel">
        <h2>Available actions</h2>
        <table class="widefat striped">
            <thead><tr><th>Action</th><th>Description</th><th>Run</th></tr></thead>
            <tbody>
            <?php foreach ($actions as $actionKey => $description) : ?>
                <tr>
                    <td><code><?php echo esc_html($actionKey); ?></code></td>
                    <td><?php echo esc_html($description); ?></td>
                    <td>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="dbg_run_media_maintenance">
                            <input type="hidden" name="maintenance_action" value="<?php echo esc_attr($actionKey); ?>">
                            <?php wp_nonce_field('dbg_run_media_maintenance'); ?>
                            <button class="button button-primary">Run action</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
