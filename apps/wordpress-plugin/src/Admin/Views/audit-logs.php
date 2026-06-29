<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Database\Repositories\AuditLogRepository();
$filters = [
    'action' => sanitize_key($_GET['audit_action'] ?? ''),
    'entity_type' => sanitize_key($_GET['entity_type'] ?? ''),
    'actor_id' => absint($_GET['actor_id'] ?? 0),
    'entity_id' => absint($_GET['entity_id'] ?? 0),
];
$logs = $repository->search($filters, 100);
?>
<div class="wrap dbg-platform-admin">
    <h1>Audit Logs</h1>
    <p>Trace important actions performed inside DBG Platform.</p>

    <div class="dbg-platform-panel">
        <form method="get">
            <input type="hidden" name="page" value="dbg-platform-audit-logs">
            <input type="text" name="audit_action" placeholder="created, updated, archived" value="<?php echo esc_attr($filters['action']); ?>">
            <input type="text" name="entity_type" placeholder="organisation, project, asset" value="<?php echo esc_attr($filters['entity_type']); ?>">
            <input type="number" name="actor_id" placeholder="Actor ID" value="<?php echo esc_attr($filters['actor_id'] ?: ''); ?>">
            <input type="number" name="entity_id" placeholder="Entity ID" value="<?php echo esc_attr($filters['entity_id'] ?: ''); ?>">
            <button class="button button-primary">Filter</button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-audit-logs')); ?>">Reset</a>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Entity ID</th>
                    <th>Date</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)) : ?>
                <tr><td colspan="7">No audit logs found.</td></tr>
            <?php else : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log['id']); ?></td>
                        <td><?php echo esc_html($log['actor_id']); ?></td>
                        <td><?php echo esc_html($log['action']); ?></td>
                        <td><?php echo esc_html($log['entity_type']); ?></td>
                        <td><?php echo esc_html($log['entity_id']); ?></td>
                        <td><?php echo esc_html($log['created_at']); ?></td>
                        <td><code><?php echo esc_html(wp_trim_words((string) $log['payload'], 20)); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
