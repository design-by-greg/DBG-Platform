<?php

namespace DBGPlatform\Database;

class Migrator
{
    public function run(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'dbg_';

        foreach ($this->tables($prefix, $charset) as $sql) {
            dbDelta($sql);
        }

        update_option('dbg_platform_db_version', '0.1.6');
    }

    private function tables(string $prefix, string $charset): array
    {
        return [
            "CREATE TABLE {$prefix}organisations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(64) NOT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY type (type),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}projects (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                organisation_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY organisation_id (organisation_id),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}assets (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                organisation_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NULL,
                type VARCHAR(64) NOT NULL,
                name VARCHAR(255) NOT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY organisation_id (organisation_id),
                KEY project_id (project_id),
                KEY type (type),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}media_folders (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                organisation_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NULL,
                parent_id BIGINT UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY organisation_id (organisation_id),
                KEY project_id (project_id),
                KEY parent_id (parent_id),
                KEY slug (slug),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}media_tags (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                color VARCHAR(24) NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}file_tag_map (
                file_record_id BIGINT UNSIGNED NOT NULL,
                tag_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (file_record_id, tag_id),
                KEY file_record_id (file_record_id),
                KEY tag_id (tag_id)
            ) {$charset};",

            "CREATE TABLE {$prefix}file_records (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                asset_id BIGINT UNSIGNED NULL,
                organisation_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NULL,
                folder_id BIGINT UNSIGNED NULL,
                original_name VARCHAR(255) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(128) NOT NULL,
                size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                path TEXT NOT NULL,
                url TEXT NOT NULL,
                thumbnail_path TEXT NULL,
                thumbnail_url TEXT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY asset_id (asset_id),
                KEY organisation_id (organisation_id),
                KEY project_id (project_id),
                KEY folder_id (folder_id),
                KEY mime_type (mime_type),
                KEY status (status)
            ) {$charset};",

            "CREATE TABLE {$prefix}file_versions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                file_record_id BIGINT UNSIGNED NOT NULL,
                version_number INT UNSIGNED NOT NULL DEFAULT 1,
                original_name VARCHAR(255) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(128) NOT NULL,
                size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                path TEXT NOT NULL,
                url TEXT NOT NULL,
                note TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY file_record_id (file_record_id),
                KEY version_number (version_number),
                KEY created_by (created_by)
            ) {$charset};",

            "CREATE TABLE {$prefix}audit_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                actor_id BIGINT UNSIGNED NULL,
                action VARCHAR(128) NOT NULL,
                entity_type VARCHAR(128) NOT NULL,
                entity_id BIGINT UNSIGNED NULL,
                payload LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY actor_id (actor_id),
                KEY entity_type (entity_type),
                KEY entity_id (entity_id),
                KEY action (action),
                KEY created_at (created_at)
            ) {$charset};"
        ];
    }
}
