<?php
if (!defined('ABSPATH')) { exit; }

$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$contactRepository = new \DBGPlatform\Database\Repositories\OrganisationContactRepository();
$settingsRepository = new \DBGPlatform\Database\Repositories\OrganisationSettingsRepository();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$organisationId = absint($_GET['organisation_id'] ?? ($organisations[0]['id'] ?? 0));
$organisation = $organisationId > 0 ? $organisationRepository->find($organisationId) : null;
$settings = $organisationId > 0 ? $settingsRepository->find($organisationId) : [];
$departments = $organisationId > 0 ? $contactRepository->departments($organisationId) : [];
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$filters = [
    'organisation_id' => $organisationId,
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'department' => sanitize_text_field($_GET['department'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'is_primary' => isset($_GET['is_primary']) ? absint($_GET['is_primary']) : '',
    'has_email' => absint($_GET['has_email'] ?? 0),
    'missing_email' => absint($_GET['missing_email'] ?? 0),
    'created_from' => sanitize_text_field($_GET['created_from'] ?? ''),
    'created_to' => sanitize_text_field($_GET['created_to'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $organisationId > 0 ? $contactRepository->paginated($filters, $currentPage, $perPage) : ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'total_pages' => 1], 'sort' => []];
$contacts = $result['items'];
$pagination = $result['pagination'];
$allContacts = $organisationId > 0 ? $contactRepository->allForOrganisation($organisationId, [], 500) : [];
$activeCount = count(array_filter($allContacts, fn($c) => ($c['status'] ?? '') === 'active'));
$archivedCount = count(array_filter($allContacts, fn($c) => ($c['status'] ?? '') === 'archived'));
$primaryCount = count(array_filter($allContacts, fn($c) => !empty($c['is_primary'])));
$missingEmailCount = count(array_filter($allContacts, fn($c) => empty($c['email'])));
$baseUrl = admin_url('admin.php?page=dbg-platform-organisation-contacts');
?>
<div class="wrap dbg-platform-admin">
    <h1>Organisation Contacts</h1>
    <p>Manage organisation contacts and organisation-level settings.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allContacts)); ?></h2><p>Total contacts</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($activeCount); ?></h2><p>Active contacts</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($archivedCount); ?></h2><p>Archived contacts</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($primaryCount); ?></h2><p>Primary contacts</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($missingEmailCount); ?></h2><p>Missing email</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-organisation-contacts">
        <select name="organisation_id"><?php foreach ($organisations as $item) : ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($organisationId, absint($item['id'])); ?>><?php echo esc_html($item['name']); ?></option><?php endforeach; ?></select>
        <input type="search" name="search" placeholder="Search contacts" value="<?php echo esc_attr($filters['search']); ?>">
        <select name="department"><option value="">All departments</option><?php foreach ($departments as $department) : ?><option value="<?php echo esc_attr($department); ?>" <?php selected($filters['department'], $department); ?>><?php echo esc_html($department); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option><option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option></select>
        <select name="is_primary"><option value="">All contacts</option><option value="1" <?php selected($filters['is_primary'], 1); ?>>Primary only</option><option value="0" <?php selected($filters['is_primary'], 0); ?>>Non-primary</option></select>
        <select name="has_email"><option value="0">Any email status</option><option value="1" <?php selected($filters['has_email'], 1); ?>>Has email</option></select>
        <select name="missing_email"><option value="0">Any missing status</option><option value="1" <?php selected($filters['missing_email'], 1); ?>>Missing email</option></select>
        <input type="date" name="created_from" value="<?php echo esc_attr($filters['created_from']); ?>"><input type="date" name="created_to" value="<?php echo esc_attr($filters['created_to']); ?>">
        <select name="sort_by"><option value="id" <?php selected($filters['sort_by'], 'id'); ?>>ID</option><option value="last_name" <?php selected($filters['sort_by'], 'last_name'); ?>>Last name</option><option value="email" <?php selected($filters['sort_by'], 'email'); ?>>Email</option><option value="department" <?php selected($filters['sort_by'], 'department'); ?>>Department</option><option value="created_at" <?php selected($filters['sort_by'], 'created_at'); ?>>Created</option></select>
        <select name="sort_order"><option value="ASC" <?php selected($filters['sort_order'], 'ASC'); ?>>ASC</option><option value="DESC" <?php selected($filters['sort_order'], 'DESC'); ?>>DESC</option></select>
        <select name="per_page"><?php foreach ([10,25,50,100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <?php if (!$organisation) : ?><div class="dbg-platform-panel"><p>No organisation selected.</p></div><?php else : ?>
        <div class="dbg-platform-panel"><h2>Create contact for <?php echo esc_html($organisation['name']); ?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><?php wp_nonce_field('dbg_create_organisation_contact'); ?>
            <p><input type="text" name="first_name" placeholder="First name" required> <input type="text" name="last_name" placeholder="Last name" required></p>
            <p><input type="email" name="email" placeholder="Email"> <input type="text" name="phone" placeholder="Phone"> <input type="text" name="mobile" placeholder="Mobile"></p>
            <p><input type="text" name="job_title" placeholder="Job title"> <input type="text" name="department" placeholder="Department"> <label><input type="checkbox" name="is_primary" value="1"> Primary contact</label></p>
            <p><textarea name="notes" placeholder="Notes" rows="2" class="large-text"></textarea></p><p><button class="button button-primary">Create contact</button></p>
        </form></div>

        <div class="dbg-platform-panel"><h2>Contacts</h2><p><?php echo esc_html($pagination['total']); ?> contact(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_bulk_organisation_contacts"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><?php wp_nonce_field('dbg_bulk_organisation_contacts'); ?>
                <p><select name="bulk_action"><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="restore">Restore selected</option></select> <button class="button">Apply</button></p>
                <table class="widefat striped"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-contact-check').forEach(cb => cb.checked = this.checked)"></th><th>ID</th><th>Contact</th><th>Created</th><th>Updated</th><th>Save</th><th>Actions</th></tr></thead><tbody>
                <?php if (empty($contacts)) : ?><tr><td colspan="7">No contacts found.</td></tr><?php else : foreach ($contacts as $contact) : ?>
                    <tr><td><input class="dbg-contact-check" type="checkbox" name="contact_ids[]" value="<?php echo esc_attr($contact['id']); ?>"></td><td><?php echo esc_html($contact['id']); ?></td><td>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_update_organisation_contact'); ?>
                        <input type="text" name="first_name" value="<?php echo esc_attr($contact['first_name']); ?>" required> <input type="text" name="last_name" value="<?php echo esc_attr($contact['last_name']); ?>" required>
                        <input type="text" name="job_title" value="<?php echo esc_attr($contact['job_title']); ?>"> <input type="text" name="department" value="<?php echo esc_attr($contact['department']); ?>"><br>
                        <input type="email" name="email" value="<?php echo esc_attr($contact['email']); ?>"> <input type="text" name="phone" value="<?php echo esc_attr($contact['phone']); ?>"> <input type="text" name="mobile" value="<?php echo esc_attr($contact['mobile']); ?>">
                        <label><input type="checkbox" name="is_primary" value="1" <?php checked(absint($contact['is_primary']), 1); ?>> Primary</label> <strong><?php echo esc_html($contact['status']); ?></strong>
                    </td><td><?php echo esc_html($contact['created_at']); ?></td><td><?php echo esc_html($contact['updated_at']); ?></td><td><button class="button">Save</button></form></td><td>
                        <?php if (empty($contact['is_primary'])) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_main_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_main_organisation_contact'); ?><button class="button">Main</button></form><?php endif; ?>
                        <?php if (($contact['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_restore_organisation_contact'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_archive_organisation_contact'); ?><button class="button">Archive</button></form><?php endif; ?>
                    </td></tr>
                <?php endforeach; endif; ?></tbody></table>
            </form>
            <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $baseUrl)); ?>">Previous</a><?php endif; ?> <?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $baseUrl)); ?>">Next</a><?php endif; ?></p>
        </div>

        <div class="dbg-platform-panel"><h2>Organisation settings</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_organisation_settings"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><?php wp_nonce_field('dbg_update_organisation_settings'); ?><p><input type="text" name="default_language" value="<?php echo esc_attr($settings['default_language'] ?? 'fr'); ?>" placeholder="fr"> <input type="text" name="default_currency" value="<?php echo esc_attr($settings['default_currency'] ?? 'EUR'); ?>" placeholder="EUR"> <input type="text" name="default_project_status" value="<?php echo esc_attr($settings['default_project_status'] ?? 'draft'); ?>" placeholder="draft"></p><p><label><input type="checkbox" name="branding_enabled" value="1" <?php checked(absint($settings['branding_enabled'] ?? 1), 1); ?>> Branding enabled</label></p><p><button class="button button-primary">Save settings</button></p></form></div>
    <?php endif; ?>
</div>
