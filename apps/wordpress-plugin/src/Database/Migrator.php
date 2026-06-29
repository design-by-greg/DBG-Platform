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

        update_option('dbg_platform_db_version', '0.1.2');
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

            "CREATE TABLE {$prefix}file_records (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                asset_id BIGINT UNSIGNED NULL,
                organisation_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NULL,
                original_name VARCHAR(255) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(128) NOT NULL,
                size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                path TEXT NOT NULL,
                url TEXT NOT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY asset_id (asset_id),
                KEY organisation_id (organisation_id),
                KEY project_id (project_id),
                KEY mime_type (mime_type),
                KEY status (status)
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
