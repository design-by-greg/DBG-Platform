<?php
if (!defined('ABSPATH')) { exit; }

$projectRepository = new \DBGPlatform\Database\Repositories\ProjectRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Projects\ProjectEventRepository();
$projectService = new \DBGPlatform\Projects\ProjectService();
$allowed = $projectService->allowedValues();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'contact_id' => absint($_GET['contact_id'] ?? 0),
    'owner_user_id' => absint($_GET['owner_user_id'] ?? 0),
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'type' => sanitize_key($_GET['type'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'priority' => sanitize_key($_GET['priority'] ?? ''),
    'due_from' => sanitize_text_field($_GET['due_from'] ?? ''),
    'due_to' => sanitize_text_field($_GET['due_to'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$result = $projectRepository->paginated($filters, $currentPage, $perPage);
$projects = $result['items'];
$pagination = $result['pagination'];
$allProjects = $projectRepository->all([], 500);
$activeCount = count(array_filter($allProjects, fn($p) => ($p['status'] ?? '') !== 'archived'));
$archivedCount = count(array_filter($allProjects, fn($p) => ($p['status'] ?? '') === 'archived'));
$urgentCount = count(array_filter($allProjects, fn($p) => ($p['priority'] ?? '') === 'urgent'));
$baseUrl = admin_url('admin.php?page=dbg-platform-projects');
?>
<div class="wrap dbg-platform-admin">
    <h1>Projects</h1>
    <p>Manage DBG projects, status, timeline and production workflow foundation.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allProjects)); ?></h2><p>Total projects</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($activeCount); ?></h2><p>Active</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($archivedCount); ?></h2><p>Archived</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($urgentCount); ?></h2><p>Urgent</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-projects">
        <input type="search" name="search" placeholder="Search project" value="<?php echo esc_attr($filters['search']); ?>">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="type"><option value="">All types</option><?php foreach ($allowed['types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>" <?php selected($filters['type'], $type); ?>><?php echo esc_html($type); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="priority"><option value="">All priorities</option><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($filters['priority'], $priority); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select>
        <input type="date" name="due_from" value="<?php echo esc_attr($filters['due_from']); ?>"><input type="date" name="due_to" value="<?php echo esc_attr($filters['due_to']); ?>">
        <select name="sort_by"><option value="id" <?php selected($filters['sort_by'], 'id'); ?>>ID</option><option value="name" <?php selected($filters['sort_by'], 'name'); ?>>Name</option><option value="status" <?php selected($filters['sort_by'], 'status'); ?>>Status</option><option value="priority" <?php selected($filters['sort_by'], 'priority'); ?>>Priority</option><option value="due_date" <?php selected($filters['sort_by'], 'due_date'); ?>>Due date</option><option value="created_at" <?php selected($filters['sort_by'], 'created_at'); ?>>Created</option></select>
        <select name="sort_order"><option value="ASC" <?php selected($filters['sort_order'], 'ASC'); ?>>ASC</option><option value="DESC" <?php selected($filters['sort_order'], 'DESC'); ?>>DESC</option></select>
        <select name="per_page"><?php foreach ([10,25,50,100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create project</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_project"><?php wp_nonce_field('dbg_create_project'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="name" placeholder="Project name" required> <input type="text" name="project_number" placeholder="Project number auto if empty"></p>
        <p><select name="type"><?php foreach ($allowed['types'] as $type) : ?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option><?php endforeach; ?></select> <select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($status, 'draft'); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="priority"><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($priority, 'normal'); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select></p>
        <p><input type="number" step="0.01" min="0" name="budget_estimate" placeholder="Budget estimate"> <select name="currency"><?php foreach ($allowed['currencies'] as $currency) : ?><option value="<?php echo esc_attr($currency); ?>" <?php selected($currency, 'EUR'); ?>><?php echo esc_html($currency); ?></option><?php endforeach; ?></select> <input type="date" name="due_date"></p>
        <p><textarea name="description" rows="3" class="large-text" placeholder="Description"></textarea></p>
        <p><button class="button button-primary">Create project</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Projects</h2><p><?php echo esc_html($pagination['total']); ?> project(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_bulk_projects"><?php wp_nonce_field('dbg_bulk_projects'); ?>
            <p><select name="bulk_action"><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="restore">Restore selected</option></select> <button class="button">Apply</button></p>
            <table class="widefat striped"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-project-check').forEach(cb => cb.checked = this.checked)"></th><th>ID</th><th>Project</th><th>Status</th><th>Priority</th><th>Due</th><th>Save</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($projects)) : ?><tr><td colspan="8">No projects found.</td></tr><?php else : foreach ($projects as $project) : ?>
                <tr><td><input class="dbg-project-check" type="checkbox" name="project_ids[]" value="<?php echo esc_attr($project['id']); ?>"></td><td><?php echo esc_html($project['id']); ?></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_project"><input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>"><?php wp_nonce_field('dbg_update_project'); ?>
                    <input type="hidden" name="organisation_id" value="<?php echo esc_attr($project['organisation_id']); ?>">
                    <input type="text" name="project_number" value="<?php echo esc_attr($project['project_number']); ?>" placeholder="Number"> <input type="text" name="name" value="<?php echo esc_attr($project['name']); ?>" required>
                    <br><textarea name="description" rows="2" class="large-text"><?php echo esc_textarea($project['description']); ?></textarea>
                </td><td><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($project['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="priority"><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($project['priority'], $priority); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select></td><td><input type="date" name="due_date" value="<?php echo esc_attr($project['due_date']); ?>"></td><td><button class="button">Save</button></form></td><td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_project_status"><input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>"><?php wp_nonce_field('dbg_project_status'); ?><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($project['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select><button class="button">Status</button></form>
                    <?php if (($project['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_restore_project"><input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>"><?php wp_nonce_field('dbg_restore_project'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block"><input type="hidden" name="action" value="dbg_archive_project"><input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>"><?php wp_nonce_field('dbg_archive_project'); ?><button class="button">Archive</button></form><?php endif; ?>
                </td></tr>
                <tr><td></td><td colspan="7"><strong>Recent events:</strong> <?php $events = $eventRepository->forProject(absint($project['id']), 3); if (empty($events)) { echo esc_html('No events.'); } else { foreach ($events as $event) { echo '<span style="margin-right:12px">' . esc_html($event['created_at'] . ' · ' . $event['title']) . '</span>'; } } ?></td></tr>
            <?php endforeach; endif; ?></tbody></table>
        </form>
        <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $baseUrl)); ?>">Previous</a><?php endif; ?> <?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $baseUrl)); ?>">Next</a><?php endif; ?></p>
    </div>
</div>
