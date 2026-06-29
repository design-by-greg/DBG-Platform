<?php
if (!defined('ABSPATH')) {
    exit;
}

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$folderRepository = new \DBGPlatform\Database\Repositories\MediaFolderRepository();
$versionRepository = new \DBGPlatform\Database\Repositories\FileVersionRepository();
$previewService = new \DBGPlatform\Files\FilePreviewService();
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'project_id' => absint($_GET['project_id'] ?? 0),
    'folder_id' => absint($_GET['folder_id'] ?? 0),
    'asset_id' => absint($_GET['asset_id'] ?? 0),
    'mime_type' => sanitize_text_field($_GET['mime_type'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'search' => sanitize_text_field($_GET['search'] ?? ''),
];
$files = $fileRepository->search($filters, 100);
$folders = $folderRepository->all(['status' => 'active']);
?>
<div class="wrap dbg-platform-admin">
    <h1>Media</h1>
    <p>Upload, filter, rename, folder, version and review files linked to DBG Platform assets.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Create folder</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_media_folder">
            <?php wp_nonce_field('dbg_create_media_folder'); ?>
            <input type="text" name="folder_name" placeholder="Folder name" required>
            <input type="number" name="organisation_id" placeholder="Organisation ID" required>
            <input type="number" name="project_id" placeholder="Project ID optional">
            <select name="parent_id">
                <option value="0">No parent</option>
                <?php foreach ($folders as $folder) : ?>
                    <option value="<?php echo esc_attr($folder['id']); ?>"><?php echo esc_html($folder['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary">Create folder</button>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Upload files</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_upload_media">
            <?php wp_nonce_field('dbg_upload_media'); ?>
            <p><input type="number" name="organisation_id" placeholder="Organisation ID" class="regular-text" required></p>
            <p><input type="number" name="project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p>
                <select name="folder_id" class="regular-text">
                    <option value="0">No folder</option>
                    <?php foreach ($folders as $folder) : ?>
                        <option value="<?php echo esc_attr($folder['id']); ?>"><?php echo esc_html($folder['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <div class="dbg-dropzone" data-dbg-dropzone>
                <strong data-dbg-dropzone-label>Drop files here or click to select</strong>
                <span>PDF, PNG, JPG, SVG, ZIP, EPS, AI — max 50 MB per file</span>
                <input type="file" name="files[]" multiple required>
            </div>
            <p><button class="button button-primary">Upload files</button></p>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Search files</h2>
        <form method="get">
            <input type="hidden" name="page" value="dbg-platform-media">
            <input type="search" name="search" placeholder="Search filename" value="<?php echo esc_attr($filters['search']); ?>">
            <input type="number" name="organisation_id" placeholder="Organisation ID" value="<?php echo esc_attr($filters['organisation_id'] ?: ''); ?>">
            <input type="number" name="project_id" placeholder="Project ID" value="<?php echo esc_attr($filters['project_id'] ?: ''); ?>">
            <select name="folder_id">
                <option value="0">All folders</option>
                <?php foreach ($folders as $folder) : ?>
                    <option value="<?php echo esc_attr($folder['id']); ?>" <?php selected($filters['folder_id'], (int) $folder['id']); ?>><?php echo esc_html($folder['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="asset_id" placeholder="Asset ID" value="<?php echo esc_attr($filters['asset_id'] ?: ''); ?>">
            <input type="text" name="mime_type" placeholder="MIME type" value="<?php echo esc_attr($filters['mime_type']); ?>">
            <select name="status">
                <option value="">All status</option>
                <option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option>
                <option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option>
            </select>
            <button class="button button-primary">Filter</button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dbg-platform-media')); ?>">Reset</a>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>File records</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th><th>Preview</th><th>Name</th><th>Rename</th><th>Folder</th><th>Type</th><th>Size</th><th>Status</th><th>Download</th><th>Move</th><th>New version</th><th>Versions</th><th>Archive</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($files)) : ?>
                <tr><td colspan="13">No file records found.</td></tr>
            <?php else : ?>
                <?php foreach ($files as $file) : ?>
                    <?php
                    $downloadUrl = wp_nonce_url(admin_url('admin-post.php?action=dbg_download_file&file_id=' . absint($file['id'])), 'dbg_download_file');
                    $versions = $versionRepository->allForFile((int) $file['id']);
                    ?>
                    <tr>
                        <td><?php echo esc_html($file['id']); ?></td>
                        <td><?php echo wp_kses_post($previewService->render($file)); ?></td>
                        <td><?php echo esc_html($file['original_name']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_rename_file">
                                <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                <?php wp_nonce_field('dbg_rename_file'); ?>
                                <input type="text" name="original_name" value="<?php echo esc_attr($file['original_name']); ?>" size="18" required>
                                <button class="button">Rename</button>
                            </form>
                        </td>
                        <td><?php echo esc_html($file['folder_id'] ?? '0'); ?></td>
                        <td><?php echo esc_html($file['mime_type']); ?></td>
                        <td><?php echo esc_html(size_format((int) $file['size'])); ?></td>
                        <td><?php echo esc_html($file['status']); ?></td>
                        <td><a class="button" href="<?php echo esc_url($downloadUrl); ?>">Download</a></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="dbg_move_file_folder">
                                <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                <?php wp_nonce_field('dbg_move_file_folder'); ?>
                                <select name="folder_id">
                                    <option value="0">No folder</option>
                                    <?php foreach ($folders as $folder) : ?>
                                        <option value="<?php echo esc_attr($folder['id']); ?>" <?php selected((int) ($file['folder_id'] ?? 0), (int) $folder['id']); ?>><?php echo esc_html($folder['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button">Move</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($file['status'] !== 'archived') : ?>
                                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_upload_file_version">
                                    <input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>">
                                    <?php wp_nonce_field('dbg_upload_file_version'); ?>
                                    <input type="file" name="file" required><input type="text" name="version_note" placeholder="Note" size="10"><button class="button">Add</button>
                                </form>
                            <?php else : ?>—<?php endif; ?>
                        </td>
                        <td><?php foreach ($versions as $version) : ?><div>v<?php echo esc_html($version['version_number']); ?></div><?php endforeach; ?></td>
                        <td>
                            <?php if ($file['status'] !== 'archived') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_archive_file"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_archive_file'); ?><button class="button">Archive</button>
                                </form>
                            <?php else : ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
