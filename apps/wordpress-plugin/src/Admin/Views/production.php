<?php
if (!defined('ABSPATH')) { exit; }

$jobRepository = new \DBGPlatform\Database\Repositories\ProductionJobRepository();
$organisationRepository = new \DBGPlatform\Database\Repositories\OrganisationRepository();
$eventRepository = new \DBGPlatform\Production\ProductionEventRepository();
$productionService = new \DBGPlatform\Production\ProductionService();
$allowed = $productionService->allowedValues();
$organisations = $organisationRepository->all(['status' => 'active'], 500);
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'project_id' => absint($_GET['project_id'] ?? 0),
    'order_id' => absint($_GET['order_id'] ?? 0),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'priority' => sanitize_key($_GET['priority'] ?? ''),
];
$result = $jobRepository->paginated($filters, max(1, absint($_GET['paged'] ?? 1)), 25);
$jobs = $result['items'];
$pagination = $result['pagination'];
$allJobs = $jobRepository->all([], 500);
$openCount = count(array_filter($allJobs, fn($j) => !in_array(($j['status'] ?? ''), ['completed', 'cancelled', 'archived'], true)));
$activeCount = count(array_filter($allJobs, fn($j) => ($j['status'] ?? '') === 'in_progress'));
$baseUrl = admin_url('admin.php?page=dbg-platform-production');
?>
<div class="wrap dbg-platform-admin">
    <h1>Production</h1>
    <p>Manage production jobs, priorities, planning and job timeline.</p>
    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-grid">
        <div class="dbg-platform-card"><h2><?php echo esc_html(count($allJobs)); ?></h2><p>Total jobs</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($openCount); ?></h2><p>Open</p></div>
        <div class="dbg-platform-card"><h2><?php echo esc_html($activeCount); ?></h2><p>In progress</p></div>
    </div>

    <div class="dbg-platform-panel"><h2>Filters</h2><form method="get">
        <input type="hidden" name="page" value="dbg-platform-production">
        <select name="organisation_id"><option value="0">All organisations</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>" <?php selected($filters['organisation_id'], absint($org['id'])); ?>><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select>
        <select name="status"><option value="">All status</option><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select>
        <select name="priority"><option value="">All priorities</option><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($filters['priority'], $priority); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select>
        <button class="button button-primary">Filter</button><a class="button" href="<?php echo esc_url($baseUrl); ?>">Reset</a>
    </form></div>

    <div class="dbg-platform-panel"><h2>Create production job</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dbg_create_production_job"><?php wp_nonce_field('dbg_create_production_job'); ?>
        <p><select name="organisation_id" required><option value="">Select organisation</option><?php foreach ($organisations as $org) : ?><option value="<?php echo esc_attr($org['id']); ?>"><?php echo esc_html($org['name']); ?></option><?php endforeach; ?></select> <input type="text" name="title" placeholder="Production title" required> <input type="text" name="job_number" placeholder="Auto if empty"></p>
        <p><input type="number" name="project_id" placeholder="Project ID"> <input type="number" name="order_id" placeholder="Order ID"> <input type="datetime-local" name="planned_start_at"> <input type="datetime-local" name="planned_end_at"></p>
        <p><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option><?php endforeach; ?></select> <select name="priority"><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($priority, 'normal'); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select></p>
        <p><textarea name="description" rows="2" class="large-text" placeholder="Description"></textarea></p>
        <p><button class="button button-primary">Create job</button></p>
    </form></div>

    <div class="dbg-platform-panel"><h2>Production jobs</h2><p><?php echo esc_html($pagination['total']); ?> job(s)</p>
        <table class="widefat striped"><thead><tr><th>ID</th><th>Job</th><th>Status</th><th>Priority</th><th>Planning</th><th>Save</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($jobs)) : ?><tr><td colspan="7">No production jobs found.</td></tr><?php else : foreach ($jobs as $job) : ?>
            <tr><td><?php echo esc_html($job['id']); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_update_production_job"><input type="hidden" name="job_id" value="<?php echo esc_attr($job['id']); ?>"><?php wp_nonce_field('dbg_update_production_job'); ?><input type="hidden" name="organisation_id" value="<?php echo esc_attr($job['organisation_id']); ?>"><input type="hidden" name="project_id" value="<?php echo esc_attr($job['project_id']); ?>"><input type="hidden" name="order_id" value="<?php echo esc_attr($job['order_id']); ?>"><input type="text" name="job_number" value="<?php echo esc_attr($job['job_number']); ?>" style="width:130px"> <input type="text" name="title" value="<?php echo esc_attr($job['title']); ?>" required></td><td><select name="status"><?php foreach ($allowed['statuses'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($job['status'], $status); ?>><?php echo esc_html($status); ?></option><?php endforeach; ?></select></td><td><select name="priority"><?php foreach ($allowed['priorities'] as $priority) : ?><option value="<?php echo esc_attr($priority); ?>" <?php selected($job['priority'], $priority); ?>><?php echo esc_html($priority); ?></option><?php endforeach; ?></select></td><td><?php echo esc_html(($job['planned_start_at'] ?? '') . ' → ' . ($job['planned_end_at'] ?? '')); ?></td><td><button class="button">Save</button></form></td><td><?php if (($job['status'] ?? '') === 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_restore_production_job"><input type="hidden" name="job_id" value="<?php echo esc_attr($job['id']); ?>"><?php wp_nonce_field('dbg_restore_production_job'); ?><button class="button button-primary">Restore</button></form><?php else : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_archive_production_job"><input type="hidden" name="job_id" value="<?php echo esc_attr($job['id']); ?>"><?php wp_nonce_field('dbg_archive_production_job'); ?><button class="button">Archive</button></form><?php endif; ?></td></tr>
            <tr><td></td><td colspan="6"><strong>Recent events:</strong> <?php $events = $eventRepository->forJob(absint($job['id']), 1); echo empty($events) ? esc_html('No events.') : esc_html($events[0]['created_at'] . ' · ' . $events[0]['title']); ?></td></tr>
        <?php endforeach; endif; ?></tbody></table>
    </div>
</div>
