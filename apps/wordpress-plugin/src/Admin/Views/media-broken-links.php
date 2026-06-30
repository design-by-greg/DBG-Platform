<?php
if (!defined('ABSPATH')) { exit; }

$checker = new \DBGPlatform\Files\BrokenLinkChecker();
$brokenLinks = $checker->check();
?>
<div class="wrap dbg-platform-admin">
    <h1>Media Broken Links</h1>
    <p>Check active media records for missing paths, missing files, unreadable files and size mismatches.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Health check results</h2>
        <?php if (empty($brokenLinks)) : ?>
            <p>No broken media links detected.</p>
        <?php else : ?>
            <p><?php echo esc_html(count($brokenLinks)); ?> issue(s) detected.</p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>File ID</th>
                        <th>Name</th>
                        <th>Reason</th>
                        <th>Message</th>
                        <th>Path</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($brokenLinks as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['file_id']); ?></td>
                        <td><?php echo esc_html($item['original_name']); ?></td>
                        <td><code><?php echo esc_html($item['reason']); ?></code></td>
                        <td><?php echo esc_html($item['message']); ?><?php if (isset($item['actual_size'])) : ?> Actual size: <?php echo esc_html(size_format((int) $item['actual_size'])); ?><?php endif; ?></td>
                        <td><code><?php echo esc_html($item['path']); ?></code></td>
                        <td><?php if (!empty($item['url'])) : ?><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener">Open</a><?php else : ?>—<?php endif; ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media&search=' . rawurlencode((string) $item['original_name']))); ?>">View in media</a>
                            <?php if (!empty($item['file_id'])) : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block">
                                    <input type="hidden" name="action" value="dbg_archive_file">
                                    <input type="hidden" name="file_id" value="<?php echo esc_attr($item['file_id']); ?>">
                                    <?php wp_nonce_field('dbg_archive_file'); ?>
                                    <button class="button">Archive</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
