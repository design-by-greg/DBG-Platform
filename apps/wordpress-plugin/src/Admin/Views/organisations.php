<?php
if (!defined('ABSPATH')) { exit; }

$repository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$filters = [
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'type' => sanitize_key($_GET['type'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$result = $repository->paginated($filters, $currentPage, $perPage);
$organisations = $result['items'];
$pagination = $result['pagination'];
$sort = $result['sort'];
$basePageUrl = admin_url('admin.php?page=dbg-platform-organisations');
$types = ['company' => 'Company', 'club' => 'Club', 'association' => 'Association', 'public_body' => 'Public body', 'partner' => 'Partner'];
$sortLink = function (string $key) use ($basePageUrl, $sort) {
    $nextOrder = ($sort['sort_by'] === $key && $sort['sort_order'] === 'ASC') ? 'DESC' : 'ASC';
    return esc_url(add_query_arg(array_merge($_GET, ['sort_by' => $key, 'sort_order' => $nextOrder, 'paged' => 1]), $basePageUrl));
};
?>
<div class="wrap dbg-platform-admin">
    <h1>Organisations</h1>
    <p>Create, filter, update, archive and restore Organisations connected to DBG Platform.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Create Organisation</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_organisation">
            <?php wp_nonce_field('dbg_create_organisation'); ?>
            <p><input type="text" name="dbg_organisation_name" placeholder="Organisation name" class="regular-text" required></p>
            <p><select name="dbg_organisation_type" class="regular-text" required><?php foreach ($types as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></p>
            <p><button class="button button-primary">Create Organisation</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Search Organisations</h2>
        <form method="get">
            <input type="hidden" name="page" value="dbg-platform-organisations">
            <input type="search" name="search" placeholder="Search name or type" value="<?php echo esc_attr($filters['search']); ?>">
            <select name="type"><option value="">All types</option><?php foreach ($types as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['type'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
            <select name="status"><option value="">All status</option><option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option><option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option></select>
            <select name="sort_by"><option value="id" <?php selected($sort['sort_by'], 'id'); ?>>Sort by ID</option><option value="name" <?php selected($sort['sort_by'], 'name'); ?>>Sort by name</option><option value="type" <?php selected($sort['sort_by'], 'type'); ?>>Sort by type</option><option value="status" <?php selected($sort['sort_by'], 'status'); ?>>Sort by status</option><option value="created_at" <?php selected($sort['sort_by'], 'created_at'); ?>>Sort by created</option><option value="updated_at" <?php selected($sort['sort_by'], 'updated_at'); ?>>Sort by updated</option></select>
            <select name="sort_order"><option value="ASC" <?php selected($sort['sort_order'], 'ASC'); ?>>Ascending</option><option value="DESC" <?php selected($sort['sort_order'], 'DESC'); ?>>Descending</option></select>
            <select name="per_page"><?php foreach ([10, 25, 50, 100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
            <button class="button button-primary">Filter</button>
            <a class="button" href="<?php echo esc_url($basePageUrl); ?>">Reset</a>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Existing Organisations</h2>
        <p><?php echo esc_html($pagination['total']); ?> organisation(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?> · sorted by <?php echo esc_html($sort['sort_by']); ?> <?php echo esc_html($sort['sort_order']); ?></p>
        <table class="widefat striped">
            <thead><tr><th><a href="<?php echo $sortLink('id'); ?>">ID</a></th><th><a href="<?php echo $sortLink('name'); ?>">Name</a></th><th><a href="<?php echo $sortLink('type'); ?>">Type</a></th><th><a href="<?php echo $sortLink('status'); ?>">Status</a></th><th><a href="<?php echo $sortLink('created_at'); ?>">Created</a></th><th><a href="<?php echo $sortLink('updated_at'); ?>">Updated</a></th><th>Update</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($organisations)) : ?>
                <tr><td colspan="8">No Organisations found.</td></tr>
            <?php else : ?>
                <?php foreach ($organisations as $organisation) : ?>
                    <tr>
                        <td><?php echo esc_html($organisation['id']); ?></td>
                        <td colspan="3">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_update_organisation">
                                <input type="hidden" name="dbg_id" value="<?php echo esc_attr($organisation['id']); ?>">
                                <?php wp_nonce_field('dbg_update_organisation'); ?>
                                <input type="text" name="dbg_organisation_name" value="<?php echo esc_attr($organisation['name']); ?>" required>
                                <select name="dbg_organisation_type" required><?php foreach ($types as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($organisation['type'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                                <strong><?php echo esc_html($organisation['status']); ?></strong>
                        </td>
                        <td><?php echo esc_html($organisation['created_at'] ?? ''); ?></td>
                        <td><?php echo esc_html($organisation['updated_at'] ?? ''); ?></td>
                        <td><button class="button">Save</button></form></td>
                        <td>
                            <?php if (($organisation['status'] ?? '') === 'archived') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_restore_organisation">
                                    <input type="hidden" name="dbg_id" value="<?php echo esc_attr($organisation['id']); ?>">
                                    <?php wp_nonce_field('dbg_restore_organisation'); ?>
                                    <button class="button button-primary">Restore</button>
                                </form>
                            <?php else : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_delete_organisation">
                                    <input type="hidden" name="dbg_id" value="<?php echo esc_attr($organisation['id']); ?>">
                                    <?php wp_nonce_field('dbg_delete_organisation'); ?>
                                    <button class="button">Archive</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $basePageUrl)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $basePageUrl)); ?>">Next</a><?php endif; ?></p>
    </div>
</div>
