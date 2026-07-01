<?php

namespace DBGPlatform\Organisations;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\OrganisationSettingsRepository;

class OrganisationSettingsService
{
    private OrganisationRepository $organisations;
    private OrganisationSettingsRepository $settings;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->organisations = new OrganisationRepository();
        $this->settings = new OrganisationSettingsRepository();
        $this->audit = new AuditLogger();
    }

    public function find(int $organisationId): ?array
    {
        if (!$this->organisations->find($organisationId)) { return null; }
        return $this->settings->find($organisationId);
    }

    public function update(int $organisationId, array $data): ?array
    {
        if (!$this->organisations->find($organisationId)) { return null; }
        $before = $this->settings->find($organisationId);
        $after = $this->settings->update($organisationId, $this->normalise($data));
        $this->audit->record('updated', 'organisation_settings', $organisationId, ['before' => $before, 'after' => $after]);
        return $after;
    }

    private function normalise(array $data): array
    {
        $settings = (array) ($data['settings'] ?? []);
        return [
            'default_language' => sanitize_key($data['default_language'] ?? 'fr'),
            'default_currency' => strtoupper(sanitize_key($data['default_currency'] ?? 'EUR')),
            'default_project_status' => sanitize_key($data['default_project_status'] ?? 'draft'),
            'branding_enabled' => !empty($data['branding_enabled']),
            'settings' => $settings,
        ];
    }
}
