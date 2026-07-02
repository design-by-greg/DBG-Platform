<?php
if (!defined('ABSPATH')) { exit; }

$paymentRepository = new \DBGPlatform\Database\Repositories\PaymentRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Payments\PaymentEventRepository();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'invoice_id' => absint($_GET['invoice_id'] ?? 0),
    'order_id' => absint($_GET['order_id'] ?? 0),
    'provider' => sanitize_key($_GET['provider'] ?? ''),
    'method' => sanitize_key($_GET['method'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
];
$result = $paymentRepository->paginated($filters, max(1, absint($_GET['paged'] ?? 1)), 25);
$payments = $result['items'];
$pagination = $result['pagination'];
$allPayments = $paymentRepository->all([], 500);
$totalAmount = array_sum(array_map(fn($p) => (float) ($p['amount'] ?? 0), $allPayments));
$netAmount = array_sum(array_map(fn($p) => (float) ($p['net_amount'] ?? 0), $allPayments));
$baseUrl = admin_url('admin.php?page=dbg-platform-payments');
$statuses = ['pending', 'processing', 'authorized', 'paid', 'partially_paid', 'failed', 'cancelled', 'refunded', 'archived'];
$methods = ['bank_transfer', 'card', 'cash', 'check', 'stripe', 'paypal', 'qonto', 'other'];
$providers = ['manual', 'stripe', 'paypal', 'qonto', 'sumup', 'mollie', 'adyen'];
?>
<div class="wrap dbg-platform-admin">
    <h1>Payments</h1>
    <p>Manage payments, providers, methods and reconciliation.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allPayments)); ?></h2><p>Total payments</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(number_format($totalAmount, 2, ',', ' ')); ?></h2><p>Gross amount</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(number_format($netAmount, 2, ',', ' ')); ?></h2><p>Net amount</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-payments">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($statuses as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="method"><option value="">All methods</option><?php foreach ($methods as $method) : ?><option value="<?php echo esc_attr($method); ?>" <?php selected($filters['method'], $method); ?>><?php echo esc_html($method); ?></option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create payment</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_payment"><?php wp_nonce_field('dbg_create_payment'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="payment_number" placeholder="Auto if empty"> <input type="number" name="invoice_id" placeholder="Invoice ID"> <input type="number" name="order_id" placeholder="Order ID"></p>
        <p><select name="provider"><?php foreach ($providers as $provider) : ?><option value="<?php echo esc_attr($provider); ?>"><?php echo esc_html($provider); ?></option><?php endforeach; ?></select> <select name="method"><?php foreach ($methods as $method) : ?><option value="<?php echo esc_attr($method); ?>"><?php echo esc_html($method); ?></option><?php endforeach; ?></select> <select name="status"><?php foreach ($statuses as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select></p>
        <p><input type="number" step="0.01" name="amount" placeholder="Amount" required> <input type="number" step="0.01" name="fee_amount" placeholder="Fees"> <input type="text" name="external_reference" placeholder="External reference"></p>
        <p><textarea name="notes" rows="2" class="large-text" placeholder="Notes"></textarea></p>
        <p><button class="button button-primary">Create payment</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Payments</h2><p><?php echo esc_html($pagination['total']); ?> payment(s)</p>
        <table class="widefat striped"><thead><tr><th>ID</th><th>Payment</th><th>Provider</th><th>Method</th><th>Status</th><th>Amount</th><th>Net</th><th>Save</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($payments)) : ?><tr><td colspan="9">No payments found.</td></tr><?php else : foreach ($payments as $payment) : ?>
            <tr><td><?php echo esc_html($payment['id']); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_payment"><input type="hidden" name="payment_id" value="<?php echo esc_attr($payment['id']); ?>"><?php wp_nonce_field('dbg_update_payment'); ?><input type="hidden" name="organisation_id" value="<?php echo esc_attr($payment['organisation_id']); ?>"><input type="text" name="payment_number" value="<?php echo esc_attr($payment['payment_number']); ?>" style="width:120px"> <input type="text" name="external_reference" value="<?php echo esc_attr($payment['external_reference']); ?>" placeholder="Reference"></td><td><select name="provider"><?php foreach ($providers as $provider) : ?><option value="<?php echo esc_attr($provider); ?>" <?php selected($payment['provider'], $provider); ?>><?php echo esc_html($provider); ?></option><?php endforeach; ?></select></td><td><select name="method"><?php foreach ($methods as $method) : ?><option value="<?php echo esc_attr($method); ?>" <?php selected($payment['method'], $method); ?>><?php echo esc_html($method); ?></option><?php endforeach; ?></select></td><td><select name="status"><?php foreach ($statuses as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($payment['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><input type="number" step="0.01" name="amount" value="<?php echo esc_attr($payment['amount']); ?>" style="width:100px"></td><td><?php echo esc_html(number_format((float) $payment['net_amount'], 2, ',', ' ')); ?></td><td><button class="button">Save</button></form></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_archive_payment"><input type="hidden" name="payment_id" value="<?php echo esc_attr($payment['id']); ?>"><?php wp_nonce_field('dbg_archive_payment'); ?><button class="button">Archive</button></form></td></tr>
            <tr><td></td><td colspan="8"><strong>Recent events:</strong> <?php $events = $eventRepository->forPayment(absint($payment['id']), 1); echo empty($events) ? esc_html('No events.') : esc_html($events[0]['created_at'] . ' · ' . $events[0]['title']); ?></td></tr>
        <?php endforeach; endif; ?></tbody></table>
    </div>
</div>
