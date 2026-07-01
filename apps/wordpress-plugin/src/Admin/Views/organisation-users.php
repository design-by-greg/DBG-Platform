<?php
if (!defined('ABSPATH')) { exit; }

$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$userRepository = new \DBGPlatform\Database\Repositories\OrganisationUserRepository();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$organisationId = absint($_GET['organisation_id'] ?? ($organisations[0]['id'] ?? 0));
$organisation = $organisationId > 0 ? $organisationRepository->find($organisationId) : null;
$roles = ['owner', 'administrator', 'manager', 'sales', 'designer', 'production', 'support', 'viewer'];
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$filters = [
    'organisation_id' => $organisationId,
    'role' => sanitize_key($_GET['role'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'is_owner' => isset($_GET['is_owner']) ? absint($_GET['is_owner']) : '',
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $organisationId > 0 ? $userRepository->paginated($filters, $currentPage, $perPage) : ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'total_pages' => 1], 'sort' => []];
$items = $result['items'];
$pagination = $result['pagination'];
$wpUsers = get_users(['number' => 500, 'orderby' => 'display_name', 'order' => 'ASC']);
$allItems = $organisationId > 0 ? $userRepository->allForOrganisation($organisationId, [], 500) : [];
$activeCount = count(array_filter($allItems, fn($item) => ($item['status'] ?? '') === 'active'));
$archivedCount = count(array_filter($allItems, fn($item) => ($item['status'] ?? '') === 'archived'));
$ownerCount = count(array_filter($allItems, fn($item) => !empty($item['is_owner'])));
$baseUrl = admin_url('admin.php?page=dbg-platform-organisation-users');
?>
<div class="wrap dbg-platform-admin">
    <h1>Organisation Users</h1>
    <p>Attach WordPress users to organisations and manage roles.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allItems)); ?></h2><p>Total users</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($activeCount); ?></h2><p>Active users</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($archivedCount); ?></h2><p>Archived users</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($ownerCount); ?></h2><p>Owner</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-organisation-users">
        <select name="organisation_id"><?php foreach ($organisations as $item) : ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($organisationId, absint($item['id'])); ?>><?php echo esc_html($item['name']); ?></option><?php endforeach; ?></select>
        <select name="role"><option value="">All roles</option><?php foreach ($roles as $role) : ?><option value="<?php echo esc_attr($role); ?>" <?php selected($filters['role'], $role); ?>><?php echo esc_html($role); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option><option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option></select>
        <select name="is_owner"><option value="">All users</option><option value="1" <?php selected($filters['is_owner'], 1); ?>>Owner only</option><option value="0" <?php selected($filters['is_owner'], 0); ?>>Non-owner</option></select>
        <select name="sort_by"><option value="id" <?php selected($filters['sort_by'], 'id'); ?>>ID</option><option value="role" <?php selected($filters['sort_by'], 'role'); ?>>Role</option><option value="status" <?php selected($filters['sort_by'], 'status'); ?>>Status</option><option value="created_at" <?php selected($filters['sort_by'], 'created_at'); ?>>Created</option></select>
        <select name="sort_order"><option value="ASC" <?php selected($filters['sort_order'], 'ASC'); ?>>ASC</option><option value="DESC" <?php selected($filters['sort_order'], 'DESC'); ?>>DESC</option></select>
        <select name="per_page"><?php foreach ([10,25,50,100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <?php if (!$organisation) : ?><div class="dbg-platform-panel"><p>No organisation selected.</p></div><?php else : ?>
        <div class="dbg-platform-panel"><h2>Add user to <?php echo esc_html($organisation['name']); ?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_add_organisation_user"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><?php wp_nonce_field('dbg_add_organisation_user'); ?>
            <select name="user_id" required><option value="">Select WordPress user</option><?php foreach ($wpUsers as $wpUser) : ?><option value="<?php echo esc_attr($wpUser->ID); ?>"><?php echo esc_html($wpUser->display_name . ' — ' . $wpUser->user_email); ?></option><?php endforeach; ?></select>
            <select name="role"><?php foreach ($roles as $role) : ?><option value="<?php echo esc_attr($role); ?>" <?php selected($role, 'viewer'); ?>><?php echo esc_html($role); ?></option><?php endforeach; ?></select>
            <label><input type="checkbox" name="is_owner" value="1"> Owner</label>
            <button class="button button-primary">Add user</button>
        </form></div>

        <div class="dbg-platform-panel"><h2>Users</h2><p><?php echo esc_html($pagination['total']); ?> user(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_bulk_organisation_users"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><?php wp_nonce_field('dbg_bulk_organisation_users'); ?>
                <p><select name="bulk_action"><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="restore">Restore selected</option></select> <button class="button">Apply</button></p>
                <table class="widefat striped"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-org-user-check').forEach(cb => cb.checked = this.checked)"></th><th>ID</th><th>User</th><th>Role</th><th>Owner</th><th>Status</th><th>Created</th><th>Save</th><th>Actions</th></tr></thead><tbody>
                <?php if (empty($items)) : ?><tr><td colspan="9">No users found.</td></tr><?php else : foreach ($items as $item) : $wpUser = get_userdata(absint($item['user_id'])); ?>
                    <tr><td><input class="dbg-org-user-check" type="checkbox" name="organisation_user_ids[]" value="<?php echo esc_attr($item['id']); ?>"></td><td><?php echo esc_html($item['id']); ?></td><td><?php echo esc_html($wpUser ? $wpUser->display_name . ' — ' . $wpUser->user_email : 'User #' . $item['user_id']); ?></td><td>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_organisation_user"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="organisation_user_id" value="<?php echo esc_attr($item['id']); ?>"><?php wp_nonce_field('dbg_update_organisation_user'); ?>
                        <select name="role"><?php foreach ($roles as $role) : ?><option value="<?php echo esc_attr($role); ?>" <?php selected($item['role'], $role); ?>><?php echo esc_html($role); ?></option><?php endforeach; ?></select>
                    </td><td><label><input type="checkbox" name="is_owner" value="1" <?php checked(absint($item['is_owner']), 1); ?>> Owner</label></td><td><?php echo esc_html($item['status']); ?></td><td><?php echo esc_html($item['created_at']); ?></td><td><button class="button">Save</button></form></td><td>
                        <?php if (empty($item['is_owner'])) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_owner_organisation_user"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="organisation_user_id" value="<?php echo esc_attr($item['id']); ?>"><?php wp_nonce_field('dbg_owner_organisation_user'); ?><button class="button">Owner</button></form><?php endif; ?>
                        <?php if (($item['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_organisation_user"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="organisation_user_id" value="<?php echo esc_attr($item['id']); ?>"><?php wp_nonce_field('dbg_restore_organisation_user'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_organisation_user"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="organisation_user_id" value="<?php echo esc_attr($item['id']); ?>"><?php wp_nonce_field('dbg_archive_organisation_user'); ?><button class="button">Archive</button></form><?php endif; ?>
                    </td></tr>
                <?php endforeach; endif; ?></tbody></table>
            </form>
            <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $baseUrl)); ?>">Previous</a><?php endif; ?> <?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $baseUrl)); ?>">Next</a><?php endif; ?></p>
        </div>
    <?php endif; ?>
</div>
