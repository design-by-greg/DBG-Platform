<?php
if (!defined('ABSPATH')) { exit; }

$orderRepository = new \DBGPlatform\Database\Repositories\OrderRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Orders\OrderEventRepository();
$orderService = new \DBGPlatform\Orders\OrderService();
$allowed = $orderService->allowedValues();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'project_id' => absint($_GET['project_id'] ?? 0),
    'quote_id' => absint($_GET['quote_id'] ?? 0),
    'contact_id' => absint($_GET['contact_id'] ?? 0),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'payment_status' => sanitize_key($_GET['payment_status'] ?? ''),
    'production_status' => sanitize_key($_GET['production_status'] ?? ''),
    'fulfillment_status' => sanitize_key($_GET['fulfillment_status'] ?? ''),
];
$result = $orderRepository->paginated($filters, max(1, absint($_GET['paged'] ?? 1)), max(10, min(100, absint($_GET['per_page'] ?? 25))));
$orders = $result['items'];
$pagination = $result['pagination'];
$allOrders = $orderRepository->all([], 500);
$openCount = count(array_filter($allOrders, fn($o) => !in_array(($o['status'] ?? ''), ['completed', 'cancelled', 'archived'], true)));
$paidCount = count(array_filter($allOrders, fn($o) => ($o['payment_status'] ?? '') === 'paid'));
$productionCount = count(array_filter($allOrders, fn($o) => ($o['production_status'] ?? '') === 'in_progress'));
$baseUrl = admin_url('admin.php?page=dbg-platform-orders');
?>
<div class="wrap dbg-platform-admin">
    <h1>Orders</h1>
    <p>Manage confirmed orders, payment status, production status and fulfillment.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allOrders)); ?></h2><p>Total orders</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($openCount); ?></h2><p>Open</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($paidCount); ?></h2><p>Paid</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($productionCount); ?></h2><p>In production</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-orders">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="payment_status"><option value="">All payments</option><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['payment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="production_status"><option value="">All production</option><?php foreach ($allowed['production_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['production_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="fulfillment_status"><option value="">All fulfillment</option><?php foreach ($allowed['fulfillment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['fulfillment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create order</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_order"><?php wp_nonce_field('dbg_create_order'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="title" placeholder="Order title" required> <input type="text" name="order_number" placeholder="Auto if empty"></p>
        <p><input type="number" name="project_id" placeholder="Project ID"> <input type="number" name="quote_id" placeholder="Quote ID"> <input type="number" name="contact_id" placeholder="Contact ID"> <input type="date" name="due_date"></p>
        <p><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="payment_status"><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="production_status"><?php foreach ($allowed['production_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="fulfillment_status"><?php foreach ($allowed['fulfillment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select></p>
        <p><textarea name="notes" rows="2" class="large-text" placeholder="Notes"></textarea></p>
        <p><button class="button button-primary">Create order</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Orders</h2><p><?php echo esc_html($pagination['total']); ?> order(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
        <table class="widefat striped"><thead><tr><th>ID</th><th>Order</th><th>Status</th><th>Payment</th><th>Production</th><th>Fulfillment</th><th>Total TTC</th><th>Save</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($orders)) : ?><tr><td colspan="9">No orders found.</td></tr><?php else : foreach ($orders as $order) : ?>
            <tr><td><?php echo esc_html($order['id']); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_order"><input type="hidden" name="order_id" value="<?php echo esc_attr($order['id']); ?>"><?php wp_nonce_field('dbg_update_order'); ?><input type="hidden" name="organisation_id" value="<?php echo esc_attr($order['organisation_id']); ?>"><input type="hidden" name="project_id" value="<?php echo esc_attr($order['project_id']); ?>"><input type="hidden" name="quote_id" value="<?php echo esc_attr($order['quote_id']); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($order['contact_id']); ?>"><input type="text" name="order_number" value="<?php echo esc_attr($order['order_number']); ?>" style="width:120px"> <input type="text" name="title" value="<?php echo esc_attr($order['title']); ?>" required></td><td><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($order['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="payment_status"><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($order['payment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="production_status"><?php foreach ($allowed['production_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($order['production_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="fulfillment_status"><?php foreach ($allowed['fulfillment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($order['fulfillment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><?php echo esc_html(number_format((float) $order['total_ttc'], 2, ',', ' ')); ?></td><td><button class="button">Save</button></form></td><td><?php if (($order['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_restore_order"><input type="hidden" name="order_id" value="<?php echo esc_attr($order['id']); ?>"><?php wp_nonce_field('dbg_restore_order'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_archive_order"><input type="hidden" name="order_id" value="<?php echo esc_attr($order['id']); ?>"><?php wp_nonce_field('dbg_archive_order'); ?><button class="button">Archive</button></form><?php endif; ?></td></tr>
            <tr><td></td><td colspan="8"><strong>Recent events:</strong> <?php $events = $eventRepository->forOrder(absint($order['id']), 3); if (empty($events)) { echo esc_html('No events.'); } else { foreach ($events as $event) { echo '<span style="margin-right:12px">' . esc_html($event['created_at'] . ' · ' . $event['title']) . '</span>'; } } ?></td></tr>
        <?php endforeach; endif; ?></tbody></table>
    </div>
</div>
