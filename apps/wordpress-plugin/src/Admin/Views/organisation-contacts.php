<?php
if (!defined('ABSPATH')) { exit; }

$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$contactRepository = new \DBGPlatform\Database\Repositories\OrganisationContactRepository();
$settingsRepository = new \DBGPlatform\Database\Repositories\OrganisationSettingsRepository();

$organisations = $organisationRepository->all(['status' => 'active'], 500);
$organisationId = absint($_GET['organisation_id'] ?? ($organisations[0]['id'] ?? 0));
$organisation = $organisationId > 0 ? $organisationRepository->find($organisationId) : null;
$settings = $organisationId > 0 ? $settingsRepository->find($organisationId) : [];
$filters = [
    'organisation_id' => $organisationId,
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'is_primary' => isset($_GET['is_primary']) ? absint($_GET['is_primary']) : '',
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $organisationId > 0 ? $contactRepository->paginated($filters, max(1, absint($_GET['paged'] ?? 1)), 50) : ['items' => [], 'pagination' => ['total' => 0, 'page' => 1, 'total_pages' => 1], 'sort' => []];
$contacts = $result['items'];
?>
<div class="wrap dbg-platform-admin">
    <h1>Organisation Contacts</h1>
    <p>Manage organisation contacts and organisation-level settings.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Select organisation</h2>
        <form method="get">
            <input type="hidden" name="page" value="dbg-platform-organisation-contacts">
            <select name="organisation_id">
                <?php foreach ($organisations as $item) : ?>
                    <option value="<?php echo esc_attr($item['id']); ?>" <?php selected($organisationId, absint($item['id'])); ?>><?php echo esc_html($item['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="search" placeholder="Search contacts" value="<?php echo esc_attr($filters['search']); ?>">
            <select name="status"><option value="">All status</option><option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option><option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option></select>
            <button class="button button-primary">Filter</button>
        </form>
    </div>

    <?php if (!$organisation) : ?>
        <div class="dbg-platform-panel"><p>No organisation selected.</p></div>
    <?php else : ?>
        <div class="dbg-platform-panel">
            <h2>Create contact for <?php echo esc_html($organisation['name']); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_create_organisation_contact">
                <input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>">
                <?php wp_nonce_field('dbg_create_organisation_contact'); ?>
                <p><input type="text" name="first_name" placeholder="First name" required> <input type="text" name="last_name" placeholder="Last name" required></p>
                <p><input type="email" name="email" placeholder="Email"> <input type="text" name="phone" placeholder="Phone"> <input type="text" name="mobile" placeholder="Mobile"></p>
                <p><input type="text" name="job_title" placeholder="Job title"> <input type="text" name="department" placeholder="Department"> <label><input type="checkbox" name="is_primary" value="1"> Primary contact</label></p>
                <p><textarea name="notes" placeholder="Notes" rows="2" class="large-text"></textarea></p>
                <p><button class="button button-primary">Create contact</button></p>
            </form>
        </div>

        <div class="dbg-platform-panel">
            <h2>Contacts</h2>
            <p><?php echo esc_html($result['pagination']['total']); ?> contact(s)</p>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Phone</th><th>Primary</th><th>Status</th><th>Save</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($contacts)) : ?>
                    <tr><td colspan="9">No contacts found.</td></tr>
                <?php else : ?>
                    <?php foreach ($contacts as $contact) : ?>
                        <tr>
                            <td><?php echo esc_html($contact['id']); ?></td>
                            <td colspan="6">
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_update_organisation_contact">
                                    <input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>">
                                    <input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>">
                                    <?php wp_nonce_field('dbg_update_organisation_contact'); ?>
                                    <input type="text" name="first_name" value="<?php echo esc_attr($contact['first_name']); ?>" required>
                                    <input type="text" name="last_name" value="<?php echo esc_attr($contact['last_name']); ?>" required>
                                    <input type="text" name="job_title" value="<?php echo esc_attr($contact['job_title']); ?>">
                                    <input type="email" name="email" value="<?php echo esc_attr($contact['email']); ?>">
                                    <input type="text" name="phone" value="<?php echo esc_attr($contact['phone']); ?>">
                                    <label><input type="checkbox" name="is_primary" value="1" <?php checked(absint($contact['is_primary']), 1); ?>> Primary</label>
                                    <strong><?php echo esc_html($contact['status']); ?></strong>
                            </td>
                            <td><button class="button">Save</button></form></td>
                            <td>
                                <?php if (empty($contact['is_primary'])) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_main_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_main_organisation_contact'); ?><button class="button">Main</button></form>
                                <?php endif; ?>
                                <?php if (($contact['status'] ?? '') === 'archived') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_restore_organisation_contact'); ?><button class="button button-primary">Restore</button></form>
                                <?php else : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_organisation_contact"><input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>"><input type="hidden" name="contact_id" value="<?php echo esc_attr($contact['id']); ?>"><?php wp_nonce_field('dbg_archive_organisation_contact'); ?><button class="button">Archive</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="dbg-platform-panel">
            <h2>Organisation settings</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="dbg_update_organisation_settings">
                <input type="hidden" name="organisation_id" value="<?php echo esc_attr($organisationId); ?>">
                <?php wp_nonce_field('dbg_update_organisation_settings'); ?>
                <p><input type="text" name="default_language" value="<?php echo esc_attr($settings['default_language'] ?? 'fr'); ?>" placeholder="fr"> <input type="text" name="default_currency" value="<?php echo esc_attr($settings['default_currency'] ?? 'EUR'); ?>" placeholder="EUR"> <input type="text" name="default_project_status" value="<?php echo esc_attr($settings['default_project_status'] ?? 'draft'); ?>" placeholder="draft"></p>
                <p><label><input type="checkbox" name="branding_enabled" value="1" <?php checked(absint($settings['branding_enabled'] ?? 1), 1); ?>> Branding enabled</label></p>
                <p><button class="button button-primary">Save settings</button></p>
            </form>
        </div>
    <?php endif; ?>
</div>
