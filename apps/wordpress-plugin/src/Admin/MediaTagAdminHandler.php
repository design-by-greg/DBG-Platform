<?php

namespace DBGPlatform\Admin;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\MediaTagRepository;

class MediaTagAdminHandler
{
    public function register(): void
    {
        add_action('admin_post_dbg_create_media_tag', [$this, 'createTag']);
        add_action('admin_post_dbg_sync_file_tags', [$this, 'syncFileTags']);
        add_action('admin_post_dbg_archive_media_tag', [$this, 'archiveTag']);
    }

    public function createTag(): void
    {
        $this->guard('dbg_create_media_tag');

        if (trim((string) ($_POST['tag_name'] ?? '')) === '') {
            $this->redirect('error', ['Tag name is required.']);
        }

        $id = (new MediaTagRepository())->create([
            'name' => $_POST['tag_name'] ?? '',
            'color' => $_POST['tag_color'] ?? '',
        ]);

        (new AuditLogger())->record('created', 'media_tag', $id, []);
        $this->redirect('created');
    }

    public function syncFileTags(): void
    {
        $this->guard('dbg_sync_file_tags');

        $fileId = absint($_POST['file_id'] ?? 0);
        $tagIds = array_map('absint', (array) ($_POST['tag_ids'] ?? []));

        if ($fileId <= 0) {
            $this->redirect('error', ['File ID is required.']);
        }

        $count = (new MediaTagRepository())->syncFileTags($fileId, $tagIds);
        (new AuditLogger())->record('tagged', 'file', $fileId, ['tag_ids' => $tagIds, 'count' => $count]);

        $this->redirect('updated');
    }

    public function archiveTag(): void
    {
        $this->guard('dbg_archive_media_tag');

        $tagId = absint($_POST['tag_id'] ?? 0);
        if ($tagId <= 0) {
            $this->redirect('error', ['Tag ID is required.']);
        }

        $archived = (new MediaTagRepository())->archive($tagId);
        (new AuditLogger())->record('archived', 'media_tag', $tagId, ['archived' => $archived]);

        $this->redirect('deleted');
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer($action);
    }

    private function redirect(string $status, array $errors = []): void
    {
        if (!empty($errors)) {
            set_transient('dbg_platform_form_errors_' . get_current_user_id(), $errors, 60);
        }

        wp_safe_redirect(admin_url('admin.php?page=dbg-platform-media&dbg_status=' . $status));
        exit;
    }
}
