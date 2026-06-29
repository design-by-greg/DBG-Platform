<?php
if (!defined('ABSPATH')) {
    exit;
}

$repository = new \DBGPlatform\Database\Repositories\AuditLogRepository();
$logs = $repository->all(100);
?>
<div class="wrap dbg-platform-admin">
    <h1>Audit Logs</h1>
    <p>Trace important actions performed inside DBG Platform.</p>

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
