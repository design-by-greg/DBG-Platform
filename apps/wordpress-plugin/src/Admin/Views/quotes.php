<?php
if (!defined('ABSPATH')) { exit; }

$quoteRepository = new \DBGPlatform\Database\Repositories\QuoteRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Quotes\QuoteEventRepository();
$quoteService = new \DBGPlatform\Quotes\QuoteService();
$allowed = $quoteService->allowedValues();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'project_id' => absint($_GET['project_id'] ?? 0),
    'contact_id' => absint($_GET['contact_id'] ?? 0),
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'valid_from' => sanitize_text_field($_GET['valid_from'] ?? ''),
    'valid_to' => sanitize_text_field($_GET['valid_to'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $quoteRepository->paginated($filters, $currentPage, $perPage);
$quotes = $result['items'];
$pagination = $result['pagination'];
$allQuotes = $quoteRepository->all([], 500);
$draftCount = count(array_filter($allQuotes, fn($q) => ($q['status'] ?? '') === 'draft'));
$signedCount = count(array_filter($allQuotes, fn($q) => in_array(($q['status'] ?? ''), ['signed', 'accepted'], true)));
$archivedCount = count(array_filter($allQuotes, fn($q) => ($q['status'] ?? '') === 'archived'));
$baseUrl = admin_url('admin.php?page=dbg-platform-quotes');
?>
<div class="wrap dbg-platform-admin">
    <h1>Quotes</h1>
    <p>Manage quotes, lines, totals, statuses and quote timeline.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allQuotes)); ?></h2><p>Total quotes</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($draftCount); ?></h2><p>Draft</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($signedCount); ?></h2><p>Signed / accepted</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($archivedCount); ?></h2><p>Archived</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-quotes">
        <input type="search" name="search" placeholder="Search quote" value="<?php echo esc_attr($filters['search']); ?>">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <input type="number" name="project_id" placeholder="Project ID" value="<?php echo esc_attr($filters['project_id'] ?: ''); ?>">
        <input type="date" name="valid_from" value="<?php echo esc_attr($filters['valid_from']); ?>"><input type="date" name="valid_to" value="<?php echo esc_attr($filters['valid_to']); ?>">
        <select name="sort_by"><option value="id" <?php selected($filters['sort_by'], 'id'); ?>>ID</option><option value="quote_number" <?php selected($filters['sort_by'], 'quote_number'); ?>>Number</option><option value="status" <?php selected($filters['sort_by'], 'status'); ?>>Status</option><option value="total_ttc" <?php selected($filters['sort_by'], 'total_ttc'); ?>>Total TTC</option><option value="valid_until" <?php selected($filters['sort_by'], 'valid_until'); ?>>Valid until</option></select>
        <select name="sort_order"><option value="ASC" <?php selected($filters['sort_order'], 'ASC'); ?>>ASC</option><option value="DESC" <?php selected($filters['sort_order'], 'DESC'); ?>>DESC</option></select>
        <select name="per_page"><?php foreach ([10,25,50,100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create quote</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_quote"><?php wp_nonce_field('dbg_create_quote'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="title" placeholder="Quote title" required> <input type="text" name="quote_number" placeholder="Auto if empty"></p>
        <p><input type="number" name="project_id" placeholder="Project ID optional"> <input type="number" name="contact_id" placeholder="Contact ID optional"> <select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($status, 'draft'); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="currency"><?php foreach ($allowed['currencies'] as $currency) : ?><option value="<?php echo esc_attr($currency); ?>" <?php selected($currency, 'EUR'); ?>><?php echo esc_html($currency); ?></option><?php endforeach; ?></select> <input type="date" name="valid_until"></p>
        <h3>Lines</h3>
        <?php for ($i = 0; $i < 3; $i++) : ?><p><input type="text" name="line_title[]" placeholder="Line title"> <select name="line_type[]"><?php foreach ($allowed['line_types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option><?php endforeach; ?></select> <input type="number" step="0.001" name="line_quantity[]" value="1" style="width:80px"> <select name="line_unit[]"><?php foreach ($allowed['units'] as $unit) : ?><option value="<?php echo esc_attr($unit); ?>"><?php echo esc_html($unit); ?></option><?php endforeach; ?></select> <input type="number" step="0.01" name="line_unit_price_ht[]" placeholder="Unit HT" style="width:110px"> <input type="number" step="0.01" name="line_discount_rate[]" placeholder="Discount %" style="width:110px"> <input type="number" step="0.01" name="line_tax_rate[]" value="20" style="width:80px"></p><?php endfor; ?>
        <p><textarea name="terms" rows="2" class="large-text" placeholder="Terms"></textarea></p><p><textarea name="notes" rows="2" class="large-text" placeholder="Notes"></textarea></p>
        <p><button class="button button-primary">Create quote</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Quotes</h2><p><?php echo esc_html($pagination['total']); ?> quote(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_bulk_quotes"><?php wp_nonce_field('dbg_bulk_quotes'); ?>
            <p><select name="bulk_action"><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="restore">Restore selected</option></select> <button class="button">Apply</button></p>
            <table class="widefat striped"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-quote-check').forEach(cb => cb.checked = this.checked)"></th><th>ID</th><th>Quote</th><th>Status</th><th>Total HT</th><th>Total TTC</th><th>Valid</th><th>Save</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($quotes)) : ?><tr><td colspan="9">No quotes found.</td></tr><?php else : foreach ($quotes as $quote) : ?>
                <tr><td><input class="dbg-quote-check" type="checkbox" name="quote_ids[]" value="<?php echo esc_attr($quote['id']); ?>"></td><td><?php echo esc_html($quote['id']); ?></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_quote"><input type="hidden" name="quote_id" value="<?php echo esc_attr($quote['id']); ?>"><?php wp_nonce_field('dbg_update_quote'); ?><input type="hidden" name="organisation_id" value="<?php echo esc_attr($quote['organisation_id']); ?>"><input type="hidden" name="project_id" value="<?php echo esc_attr($quote['project_id']); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($quote['contact_id']); ?>">
                    <input type="text" name="quote_number" value="<?php echo esc_attr($quote['quote_number']); ?>" style="width:120px"> <input type="text" name="title" value="<?php echo esc_attr($quote['title']); ?>" required>
                </td><td><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($quote['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><?php echo esc_html(number_format((float) $quote['total_ht'], 2, ',', ' ')); ?></td><td><?php echo esc_html(number_format((float) $quote['total_ttc'], 2, ',', ' ')); ?></td><td><input type="date" name="valid_until" value="<?php echo esc_attr($quote['valid_until']); ?>"></td><td><button class="button">Save</button></form></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_quote_status"><input type="hidden" name="quote_id" value="<?php echo esc_attr($quote['id']); ?>"><?php wp_nonce_field('dbg_quote_status'); ?><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($quote['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select><button class="button">Status</button></form>
                    <?php if (($quote['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_quote"><input type="hidden" name="quote_id" value="<?php echo esc_attr($quote['id']); ?>"><?php wp_nonce_field('dbg_restore_quote'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_quote"><input type="hidden" name="quote_id" value="<?php echo esc_attr($quote['id']); ?>"><?php wp_nonce_field('dbg_archive_quote'); ?><button class="button">Archive</button></form><?php endif; ?>
                </td></tr>
                <tr><td></td><td colspan="8"><strong>Recent events:</strong> <?php $events = $eventRepository->forQuote(absint($quote['id']), 3); if (empty($events)) { echo esc_html('No events.'); } else { foreach ($events as $event) { echo '<span style="margin-right:12px">' . esc_html($event['created_at'] . ' · ' . $event['title']) . '</span>'; } } ?></td></tr>
            <?php endforeach; endif; ?></tbody></table>
        </form>
        <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $baseUrl)); ?>">Previous</a><?php endif; ?> <?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $baseUrl)); ?>">Next</a><?php endif; ?></p>
    </div>
</div>
