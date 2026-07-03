<?php
if (!defined('ABSPATH')) { exit; }

$assetRepository = new \DBGPlatform\Database\Repositories\AssetRepository();
$eventRepository = new \DBGPlatform\Assets\AssetEventRepository();
$assetService = new \DBGPlatform\Assets\AssetService();
$allowed = $assetService->allowedValues();
// Organisations now live in ATLAS ERP (Base44), not locally — referenced by ID only until an API bridge exists.
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$filters = [
    'organisation_id' => sanitize_text_field($_GET['organisation_id'] ?? ''),
    'project_id' => sanitize_text_field($_GET['project_id'] ?? ''),
    'parent_asset_id' => absint($_GET['parent_asset_id'] ?? 0),
    'current_file_record_id' => absint($_GET['current_file_record_id'] ?? 0),
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'type' => sanitize_key($_GET['type'] ?? ''),
    'category' => sanitize_key($_GET['category'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'approval_status' => sanitize_key($_GET['approval_status'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $assetRepository->paginated($filters, $currentPage, $perPage);
$assets = $result['items'];
$pagination = $result['pagination'];
$allAssets = $assetRepository->all([], 500);
$activeCount = count(array_filter($allAssets, fn($a) => ($a['status'] ?? '') !== 'archived'));
$archivedCount = count(array_filter($allAssets, fn($a) => ($a['status'] ?? '') === 'archived'));
$pendingCount = count(array_filter($allAssets, fn($a) => ($a['approval_status'] ?? '') === 'pending'));
$baseUrl = admin_url('admin.php?page=dbg-platform-assets');
?>
<div class="wrap dbg-platform-admin">
    <h1>Assets</h1>
    <p>Manage logos, BAT, source files, mockups and production documents.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allAssets)); ?></h2><p>Total assets</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($activeCount); ?></h2><p>Active</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($archivedCount); ?></h2><p>Archived</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($pendingCount); ?></h2><p>Pending approval</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-assets">
        <input type="search" name="search" placeholder="Search asset" value="<?php echo esc_attr($filters['search']); ?>">
        <input type="text" name="organisation_id" placeholder="Organisation ID (ATLAS ERP)" value="<?php echo esc_attr($filters['organisation_id'] ?: ''); ?>">
        <select name="type"><option value="">All types</option><?php foreach ($allowed['types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>" <?php selected($filters['type'], $type); ?>><?php echo esc_html($type); ?></option><?php endforeach; ?></select>
        <select name="category"><option value="">All categories</option><?php foreach ($allowed['categories'] as $category) : ?><option value="<?php echo esc_attr($category); ?>" <?php selected($filters['category'], $category); ?>><?php echo esc_html($category); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="approval_status"><option value="">All approvals</option><?php foreach ($allowed['approval_statuses'] as $approval) : ?><option value="<?php echo esc_attr($approval); ?>" <?php selected($filters['approval_status'], $approval); ?>><?php echo esc_html($approval); ?></option><?php endforeach; ?></select>
        <input type="text" name="project_id" placeholder="Project ID" value="<?php echo esc_attr($filters['project_id'] ?: ''); ?>">
        <select name="sort_by"><option value="id" <?php selected($filters['sort_by'], 'id'); ?>>ID</option><option value="name" <?php selected($filters['sort_by'], 'name'); ?>>Name</option><option value="type" <?php selected($filters['sort_by'], 'type'); ?>>Type</option><option value="category" <?php selected($filters['sort_by'], 'category'); ?>>Category</option><option value="approval_status" <?php selected($filters['sort_by'], 'approval_status'); ?>>Approval</option><option value="updated_at" <?php selected($filters['sort_by'], 'updated_at'); ?>>Updated</option></select>
        <select name="sort_order"><option value="ASC" <?php selected($filters['sort_order'], 'ASC'); ?>>ASC</option><option value="DESC" <?php selected($filters['sort_order'], 'DESC'); ?>>DESC</option></select>
        <select name="per_page"><?php foreach ([10,25,50,100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create asset</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_asset"><?php wp_nonce_field('dbg_create_asset'); ?>
        <p><input type="text" name="organisation_id" placeholder="Organisation ID (ATLAS ERP)" required> <input type="text" name="name" placeholder="Asset name" required> <input type="text" name="project_id" placeholder="Project ID optional"></p>
        <p><select name="type"><?php foreach ($allowed['types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option><?php endforeach; ?></select> <select name="category"><?php foreach ($allowed['categories'] as $category) : ?><option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option><?php endforeach; ?></select> <select name="approval_status"><?php foreach ($allowed['approval_statuses'] as $approval) : ?><option value="<?php echo esc_attr($approval); ?>"><?php echo esc_html($approval); ?></option><?php endforeach; ?></select></p>
        <p><textarea name="description" rows="3" class="large-text" placeholder="Description"></textarea></p>
        <p><button class="button button-primary">Create asset</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Assets</h2><p><?php echo esc_html($pagination['total']); ?> asset(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_bulk_assets"><?php wp_nonce_field('dbg_bulk_assets'); ?>
            <p><select name="bulk_action"><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="restore">Restore selected</option></select> <button class="button">Apply</button></p>
            <table class="widefat striped"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-asset-check').forEach(cb => cb.checked = this.checked)"></th><th>ID</th><th>Asset</th><th>Type</th><th>Category</th><th>Approval</th><th>Version</th><th>Save</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($assets)) : ?><tr><td colspan="9">No assets found.</td></tr><?php else : foreach ($assets as $asset) : ?>
                <tr><td><input class="dbg-asset-check" type="checkbox" name="asset_ids[]" value="<?php echo esc_attr($asset['id']); ?>"></td><td><?php echo esc_html($asset['id']); ?></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_asset"><input type="hidden" name="asset_id" value="<?php echo esc_attr($asset['id']); ?>"><?php wp_nonce_field('dbg_update_asset'); ?>
                    <input type="hidden" name="organisation_id" value="<?php echo esc_attr($asset['organisation_id']); ?>"><input type="hidden" name="project_id" value="<?php echo esc_attr($asset['project_id']); ?>">
                    <input type="text" name="name" value="<?php echo esc_attr($asset['name']); ?>" required><br><textarea name="description" rows="2" class="large-text"><?php echo esc_textarea($asset['description']); ?></textarea>
                </td><td><select name="type"><?php foreach ($allowed['types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>" <?php selected($asset['type'], $type); ?>><?php echo esc_html($type); ?></option><?php endforeach; ?></select></td><td><select name="category"><?php foreach ($allowed['categories'] as $category) : ?><option value="<?php echo esc_attr($category); ?>" <?php selected($asset['category'], $category); ?>><?php echo esc_html($category); ?></option><?php endforeach; ?></select></td><td><select name="approval_status"><?php foreach ($allowed['approval_statuses'] as $approval) : ?><option value="<?php echo esc_attr($approval); ?>" <?php selected($asset['approval_status'], $approval); ?>><?php echo esc_html($approval); ?></option><?php endforeach; ?></select></td><td>v<?php echo esc_html($asset['version_number']); ?></td><td><button class="button">Save</button></form></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_asset_version"><input type="hidden" name="asset_id" value="<?php echo esc_attr($asset['id']); ?>"><?php wp_nonce_field('dbg_asset_version'); ?><button class="button">+ Version</button></form>
                    <?php if (($asset['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_asset"><input type="hidden" name="asset_id" value="<?php echo esc_attr($asset['id']); ?>"><?php wp_nonce_field('dbg_restore_asset'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_asset"><input type="hidden" name="asset_id" value="<?php echo esc_attr($asset['id']); ?>"><?php wp_nonce_field('dbg_archive_asset'); ?><button class="button">Archive</button></form><?php endif; ?>
                </td></tr>
                <tr><td></td><td colspan="8"><strong>Recent events:</strong> <?php $events = $eventRepository->forAsset(absint($asset['id']), 3); if (empty($events)) { echo esc_html('No events.'); } else { foreach ($events as $event) { echo '<span style="margin-right:12px">' . esc_html($event['created_at'] . ' · ' . $event['title']) . '</span>'; } } ?></td></tr>
            <?php endforeach; endif; ?></tbody></table>
        </form>
        <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $baseUrl)); ?>">Previous</a><?php endif; ?> <?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $baseUrl)); ?>">Next</a><?php endif; ?></p>
    </div>
</div>
