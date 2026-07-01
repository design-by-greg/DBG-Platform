<?php

namespace DBGPlatform\Database\Repositories;

class OrganisationSettingsRepository
{
    private array $allowedLanguages = ['fr', 'en', 'es', 'de', 'it'];
    private array $allowedCurrencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];
    private array $allowedProjectStatuses = ['draft', 'quote', 'approved', 'production', 'delivered', 'cancelled'];

    public function find(int $organisationId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_settings';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE organisation_id = %d", $organisationId), ARRAY_A);
        if (!$row) { return $this->defaults($organisationId); }
        $row['settings'] = json_decode((string) ($row['settings_json'] ?? '{}'), true) ?: [];
        return $row;
    }

    public function update(int $organisationId, array $data): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_organisation_settings';
        $now = current_time('mysql');
        $existing = $this->find($organisationId);
        $settings = array_merge((array) ($existing['settings'] ?? []), (array) ($data['settings'] ?? []));
        $payload = [
            'organisation_id' => $organisationId,
            'default_language' => $this->normaliseLanguage($data['default_language'] ?? ($existing['default_language'] ?? 'fr')),
            'default_currency' => $this->normaliseCurrency($data['default_currency'] ?? ($existing['default_currency'] ?? 'EUR')),
            'default_project_status' => $this->normaliseProjectStatus($data['default_project_status'] ?? ($existing['default_project_status'] ?? 'draft')),
            'branding_enabled' => array_key_exists('branding_enabled', $data) ? (!empty($data['branding_enabled']) ? 1 : 0) : absint($existing['branding_enabled'] ?? 1),
            'settings_json' => wp_json_encode($settings),
            'updated_at' => $now,
        ];

        $exists = $wpdb->get_var($wpdb->prepare("SELECT organisation_id FROM {$table} WHERE organisation_id = %d", $organisationId));
        if ($exists) { $wpdb->update($table, $payload, ['organisation_id' => $organisationId]); }
        else { $wpdb->insert($table, $payload); }

        return $this->find($organisationId);
    }

    public function allowedValues(): array
    {
        return [
            'languages' => $this->allowedLanguages,
            'currencies' => $this->allowedCurrencies,
            'project_statuses' => $this->allowedProjectStatuses,
        ];
    }

    private function normaliseLanguage(string $value): string
    {
        $value = sanitize_key($value ?: 'fr');
        return in_array($value, $this->allowedLanguages, true) ? $value : 'fr';
    }

    private function normaliseCurrency(string $value): string
    {
        $value = strtoupper(sanitize_key($value ?: 'EUR'));
        return in_array($value, $this->allowedCurrencies, true) ? $value : 'EUR';
    }

    private function normaliseProjectStatus(string $value): string
    {
        $value = sanitize_key($value ?: 'draft');
        return in_array($value, $this->allowedProjectStatuses, true) ? $value : 'draft';
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
