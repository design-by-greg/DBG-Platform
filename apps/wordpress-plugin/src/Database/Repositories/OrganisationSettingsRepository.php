<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationSettingsRepository
{
    public function find(int $organisationId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_settings';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE organisation_id = %d", $organisationId), ARRAY_A);
        if (!$row) {
            return $this->defaults($organisationId);
        }
        $row['settings'] = json_decode((string) ($row['settings_json'] ?? '{}'), true) ?: [];
        return $row;
    }

    public function update(int $organisationId, array $data): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_settings';
        $now = current_time('mysql');
        $payload = [
            'organisation_id' => $organisationId,
            'default_language' => sanitize_key($data['default_language'] ?? 'fr'),
            'default_currency' => strtoupper(sanitize_key($data['default_currency'] ?? 'EUR')),
            'default_project_status' => sanitize_key($data['default_project_status'] ?? 'draft'),
            'branding_enabled' => !empty($data['branding_enabled']) ? 1 : 0,
            'settings_json' => wp_json_encode((array) ($data['settings'] ?? [])),
            'updated_at' => $now,
        ];

        $exists = $wpdb->get_var($wpdb->prepare("SELECT organisation_id FROM {$table} WHERE organisation_id = %d", $organisationId));
        if ($exists) {
            $wpdb->update($table, $payload, ['organisation_id' => $organisationId]);
        } else {
            $wpdb->insert($table, $payload);
        }

        return $this->find($organisationId);
    }

    private function defaults(int $organisationId): array
    {
        return [
            'organisation_id' => $organisationId,
            'default_language' => 'fr',
            'default_currency' => 'EUR',
            'default_project_status' => 'draft',
            'branding_enabled' => 1,
            'settings_json' => '{}',
            'settings' => [],
            'updated_at' => null,
        ];
    }
}
