<?php
if (!defined('ABSPATH')) { exit; }

$invoiceRepository = new \DBGPlatform\Database\Repositories\InvoiceRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Invoices\InvoiceEventRepository();
$invoiceService = new \DBGPlatform\Invoices\InvoiceService();
$allowed = $invoiceService->allowedValues();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'payment_status' => sanitize_key($_GET['payment_status'] ?? ''),
];
$result = $invoiceRepository->paginated($filters, max(1, absint($_GET['paged'] ?? 1)), 25);
$invoices = $result['items'];
$pagination = $result['pagination'];
$allInvoices = $invoiceRepository->all([], 500);
$dueTotal = array_sum(array_map(fn($i) => (float) ($i['amount_due'] ?? 0), $allInvoices));
$baseUrl = admin_url('admin.php?page=dbg-platform-invoices');
?>
<div class="wrap dbg-platform-admin">
    <h1>Invoices</h1>
    <p>Manage invoices, statuses, due dates and payment tracking.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allInvoices)); ?></h2><p>Total invoices</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html(number_format($dueTotal, 2, ',', ' ')); ?></h2><p>Amount due</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-invoices">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="payment_status"><option value="">All payments</option><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['payment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create invoice</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_invoice"><?php wp_nonce_field('dbg_create_invoice'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="title" placeholder="Invoice title" required> <input type="text" name="invoice_number" placeholder="Auto if empty"></p>
        <p><input type="number" name="project_id" placeholder="Project ID"> <input type="number" name="quote_id" placeholder="Quote ID"> <input type="number" name="order_id" placeholder="Order ID"> <input type="number" name="contact_id" placeholder="Contact ID"> <input type="date" name="due_date"></p>
        <p><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="payment_status"><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <textarea name="notes" rows="2" class="large-text" placeholder="Notes"></textarea></p>
        <p><button class="button button-primary">Create invoice</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Invoices</h2><p><?php echo esc_html($pagination['total']); ?> invoice(s)</p>
        <table class="widefat striped"><thead><tr><th>ID</th><th>Invoice</th><th>Status</th><th>Payment</th><th>Total</th><th>Due</th><th>Due date</th><th>Save</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($invoices)) : ?><tr><td colspan="9">No invoices found.</td></tr><?php else : foreach ($invoices as $invoice) : ?>
            <tr><td><?php echo esc_html($invoice['id']); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_invoice"><input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice['id']); ?>"><?php wp_nonce_field('dbg_update_invoice'); ?><input type="hidden" name="organisation_id" value="<?php echo esc_attr($invoice['organisation_id']); ?>"><input type="text" name="invoice_number" value="<?php echo esc_attr($invoice['invoice_number']); ?>" style="width:120px"> <input type="text" name="title" value="<?php echo esc_attr($invoice['title']); ?>" required></td><td><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($invoice['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="payment_status"><?php foreach ($allowed['payment_statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($invoice['payment_status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><?php echo esc_html(number_format((float) $invoice['total_ttc'], 2, ',', ' ')); ?></td><td><?php echo esc_html(number_format((float) $invoice['amount_due'], 2, ',', ' ')); ?></td><td><input type="date" name="due_date" value="<?php echo esc_attr($invoice['due_date']); ?>"></td><td><button class="button">Save</button></form></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_archive_invoice"><input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice['id']); ?>"><?php wp_nonce_field('dbg_archive_invoice'); ?><button class="button">Archive</button></form></td></tr>
            <tr><td></td><td colspan="8"><strong>Recent events:</strong> <?php $events = $eventRepository->forInvoice(absint($invoice['id']), 3); echo empty($events) ? esc_html('No events.') : esc_html($events[0]['created_at'] . ' · ' . $events[0]['title']); ?></td></tr>
        <?php endforeach; endif; ?></tbody></table>
    </div>
</div>
