<?php
if (!defined('ABSPATH')) {
    exit;
}

$fileRepository = new \DBGPlatform\Database\Repositories\FileRecordRepository();
$folderRepository = new \DBGPlatform\Database\Repositories\MediaFolderRepository();
$versionRepository = new \DBGPlatform\Database\Repositories\FileVersionRepository();
$tagRepository = new \DBGPlatform\Database\Repositories\MediaTagRepository();
$previewService = new \DBGPlatform\Files\FilePreviewService();
$filters = [
    'organisation_id' => absint($_GET['organisation_id'] ?? 0),
    'project_id' => absint($_GET['project_id'] ?? 0),
    'folder_id' => absint($_GET['folder_id'] ?? 0),
    'asset_id' => absint($_GET['asset_id'] ?? 0),
    'mime_type' => sanitize_text_field($_GET['mime_type'] ?? ''),
    'status' => sanitize_key($_GET['status'] ?? ''),
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'sort_by' => sanitize_key($_GET['sort_by'] ?? 'id'),
    'sort_order' => sanitize_key($_GET['sort_order'] ?? 'DESC'),
];
$currentPage = max(1, absint($_GET['paged'] ?? 1));
$perPage = max(10, min(100, absint($_GET['per_page'] ?? 25)));
$result = $fileRepository->paginated($filters, $currentPage, $perPage);
$files = $result['items'];
$pagination = $result['pagination'];
$sort = $result['sort'];
$folders = $folderRepository->all(['status' => 'active']);
$tags = $tagRepository->all('active');
$basePageUrl = admin_url('admin.php?page=dbg-platform-media');
$sortLink = function (string $key) use ($basePageUrl, $sort) {
    $nextOrder = ($sort['sort_by'] === $key && $sort['sort_order'] === 'ASC') ? 'DESC' : 'ASC';
    return esc_url(add_query_arg(array_merge($_GET, ['sort_by' => $key, 'sort_order' => $nextOrder, 'paged' => 1]), $basePageUrl));
};
?>
<div class="wrap dbg-platform-admin">
    <h1>Media</h1>
    <p>Upload, filter, rename, folder, version, tags, bulk manage, sort and review files linked to DBG Platform assets.</p>

    <?php include DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/notices.php'; ?>

    <div class="dbg-platform-panel">
        <h2>Create tag</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_media_tag">
            <?php wp_nonce_field('dbg_create_media_tag'); ?>
            <input type="text" name="tag_name" placeholder="Tag name" required>
            <input type="color" name="tag_color" value="#2271b1">
            <button class="button button-primary">Create tag</button>
        </form>
        <?php if (!empty($tags)) : ?>
            <p>
                <?php foreach ($tags as $tag) : ?>
                    <span class="dbg-media-tag" style="background:<?php echo esc_attr($tag['color'] ?: '#f0f0f1'); ?>"><?php echo esc_html($tag['name']); ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="dbg-platform-panel">
        <h2>Create folder</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_create_media_folder">
            <?php wp_nonce_field('dbg_create_media_folder'); ?>
            <input type="text" name="folder_name" placeholder="Folder name" required>
            <input type="number" name="organisation_id" placeholder="Organisation ID" required>
            <input type="number" name="project_id" placeholder="Project ID optional">
            <select name="parent_id"><option value="0">No parent</option><?php foreach ($folders as $folder) : ?><option value="<?php echo esc_attr($folder['id']); ?>"><?php echo esc_html($folder['name']); ?></option><?php endforeach; ?></select>
            <button class="button button-primary">Create folder</button>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>Upload files</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-dbg-upload-form>
            <input type="hidden" name="action" value="dbg_upload_media">
            <?php wp_nonce_field('dbg_upload_media'); ?>
            <p><input type="number" name="organisation_id" placeholder="Organisation ID" class="regular-text" required></p>
            <p><input type="number" name="project_id" placeholder="Project ID optional" class="regular-text"></p>
            <p><select name="folder_id" class="regular-text"><option value="0">No folder</option><?php foreach ($folders as $folder) : ?><option value="<?php echo esc_attr($folder['id']); ?>"><?php echo esc_html($folder['name']); ?></option><?php endforeach; ?></select></p>
            <div class="dbg-dropzone" data-dbg-dropzone><strong data-dbg-dropzone-label>Drop files here or click to select</strong><span>PDF, PNG, JPG, SVG, ZIP, EPS, AI — max 50 MB per file</span><input type="file" name="files[]" multiple required></div>
            <div class="dbg-upload-progress" data-dbg-upload-progress hidden><div class="dbg-upload-progress-track"><span class="dbg-upload-progress-bar" data-dbg-upload-progress-bar role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></span></div><span class="dbg-upload-progress-text" data-dbg-upload-progress-text>Waiting...</span></div>
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
            <select name="folder_id"><option value="0">All folders</option><?php foreach ($folders as $folder) : ?><option value="<?php echo esc_attr($folder['id']); ?>" <?php selected($filters['folder_id'], (int) $folder['id']); ?>><?php echo esc_html($folder['name']); ?></option><?php endforeach; ?></select>
            <input type="number" name="asset_id" placeholder="Asset ID" value="<?php echo esc_attr($filters['asset_id'] ?: ''); ?>">
            <input type="text" name="mime_type" placeholder="MIME type" value="<?php echo esc_attr($filters['mime_type']); ?>">
            <select name="status"><option value="">All status</option><option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option><option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option></select>
            <select name="sort_by"><option value="id" <?php selected($sort['sort_by'], 'id'); ?>>Sort by ID</option><option value="name" <?php selected($sort['sort_by'], 'name'); ?>>Sort by name</option><option value="type" <?php selected($sort['sort_by'], 'type'); ?>>Sort by type</option><option value="size" <?php selected($sort['sort_by'], 'size'); ?>>Sort by size</option><option value="status" <?php selected($sort['sort_by'], 'status'); ?>>Sort by status</option><option value="created_at" <?php selected($sort['sort_by'], 'created_at'); ?>>Sort by created</option><option value="updated_at" <?php selected($sort['sort_by'], 'updated_at'); ?>>Sort by updated</option></select>
            <select name="sort_order"><option value="ASC" <?php selected($sort['sort_order'], 'ASC'); ?>>Ascending</option><option value="DESC" <?php selected($sort['sort_order'], 'DESC'); ?>>Descending</option></select>
            <select name="per_page"><?php foreach ([10, 25, 50, 100] as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($perPage, $option); ?>><?php echo esc_html($option); ?> / page</option><?php endforeach; ?></select>
            <button class="button button-primary">Filter</button>
            <a class="button" href="<?php echo esc_url($basePageUrl); ?>">Reset</a>
        </form>
    </div>

    <div class="dbg-platform-panel">
        <h2>File records</h2>
        <p><?php echo esc_html($pagination['total']); ?> file(s) · page <?php echo esc_html($pagination['page']); ?> / <?php echo esc_html($pagination['total_pages']); ?> · sorted by <?php echo esc_html($sort['sort_by']); ?> <?php echo esc_html($sort['sort_order']); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="dbg_bulk_media_action">
            <?php wp_nonce_field('dbg_bulk_media_action'); ?>
            <p><select name="bulk_action" required><option value="">Bulk action</option><option value="archive">Archive selected</option><option value="move">Move selected</option></select><select name="bulk_folder_id"><option value="0">No folder</option><?php foreach ($folders as $folder) : ?><option value="<?php echo esc_attr($folder['id']); ?>"><?php echo esc_html($folder['name']); ?></option><?php endforeach; ?></select><button class="button">Apply</button></p>
            <table class="widefat striped">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.dbg-file-select').forEach(cb => cb.checked = this.checked);"></th><th><a href="<?php echo $sortLink('id'); ?>">ID</a></th><th>Preview</th><th><a href="<?php echo $sortLink('name'); ?>">Name</a></th><th>Tags</th><th>Rename</th><th>Folder</th><th><a href="<?php echo $sortLink('type'); ?>">Type</a></th><th><a href="<?php echo $sortLink('size'); ?>">Size</a></th><th><a href="<?php echo $sortLink('status'); ?>">Status</a></th><th>Download</th><th>Move</th><th>New version</th><th>Versions</th><th>Archive</th></tr></thead>
                <tbody>
                <?php if (empty($files)) : ?><tr><td colspan="15">No file records found.</td></tr><?php else : ?>
                    <?php foreach ($files as $file) : ?>
                        <?php $downloadUrl = wp_nonce_url(admin_url('admin-post.php?action=dbg_download_file&file_id=' . absint($file['id'])), 'dbg_download_file'); $versions = $versionRepository->allForFile((int) $file['id']); $fileTags = $tagRepository->tagsForFile((int) $file['id']); $fileTagIds = array_map('intval', array_column($fileTags, 'id')); ?>
                        <tr>
                            <td><input class="dbg-file-select" type="checkbox" name="file_ids[]" value="<?php echo esc_attr($file['id']); ?>"></td><td><?php echo esc_html($file['id']); ?></td><td><?php echo wp_kses_post($previewService->render($file)); ?></td><td><?php echo esc_html($file['original_name']); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dbg_sync_file_tags"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_sync_file_tags'); ?>
                                    <?php if (empty($tags)) : ?>—<?php else : ?>
                                        <select name="tag_ids[]" multiple size="3"><?php foreach ($tags as $tag) : ?><option value="<?php echo esc_attr($tag['id']); ?>" <?php selected(in_array((int) $tag['id'], $fileTagIds, true)); ?>><?php echo esc_html($tag['name']); ?></option><?php endforeach; ?></select><button class="button">Save</button>
                                    <?php endif; ?>
                                </form>
                                <?php foreach ($fileTags as $tag) : ?><span class="dbg-media-tag" style="background:<?php echo esc_attr($tag['color'] ?: '#f0f0f1'); ?>"><?php echo esc_html($tag['name']); ?></span><?php endforeach; ?>
                            </td>
                            <td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_rename_file"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_rename_file'); ?><input type="text" name="original_name" value="<?php echo esc_attr($file['original_name']); ?>" size="18" required><button class="button">Rename</button></form></td>
                            <td><?php echo esc_html($file['folder_id'] ?? '0'); ?></td><td><?php echo esc_html($file['mime_type']); ?></td><td><?php echo esc_html(size_format((int) $file['size'])); ?></td><td><?php echo esc_html($file['status']); ?></td><td><a class="button" href="<?php echo esc_url($downloadUrl); ?>">Download</a></td>
                            <td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_move_file_folder"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_move_file_folder'); ?><select name="folder_id"><option value="0">No folder</option><?php foreach ($folders as $folder) : ?><option value="<?php echo esc_attr($folder['id']); ?>" <?php selected((int) ($file['folder_id'] ?? 0), (int) $folder['id']); ?>><?php echo esc_html($folder['name']); ?></option><?php endforeach; ?></select><button class="button">Move</button></form></td>
                            <td><?php if ($file['status'] !== 'archived') : ?><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_upload_file_version"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_upload_file_version'); ?><input type="file" name="file" required><input type="text" name="version_note" placeholder="Note" size="10"><button class="button">Add</button></form><?php else : ?>—<?php endif; ?></td>
                            <td><?php foreach ($versions as $version) : ?><div>v<?php echo esc_html($version['version_number']); ?></div><?php endforeach; ?></td><td><?php if ($file['status'] !== 'archived') : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="dbg_archive_file"><input type="hidden" name="file_id" value="<?php echo esc_attr($file['id']); ?>"><?php wp_nonce_field('dbg_archive_file'); ?><button class="button">Archive</button></form><?php else : ?>—<?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </form>
        <p><?php if ($pagination['page'] > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] - 1]), $basePageUrl)); ?>">Previous</a><?php endif; ?><?php if ($pagination['page'] < $pagination['total_pages']) : ?><a class="button" href="<?php echo esc_url(add_query_arg(array_merge($_GET, ['paged' => $pagination['page'] + 1]), $basePageUrl)); ?>">Next</a><?php endif; ?></p>
    </div>
</div>
