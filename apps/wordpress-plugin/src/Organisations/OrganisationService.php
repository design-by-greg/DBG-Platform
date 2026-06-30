<?php

namespace DBGPlatform\Organisations;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\OrganisationSettingsRepository;

class OrganisationService
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

    public function create(array $data): int
    {
        $payload = $this->normaliseOrganisation($data);
        $payload['uuid'] = $payload['uuid'] ?: wp_generate_uuid4();
        $payload['created_by'] = get_current_user_id() ?: null;
        $payload['updated_by'] = get_current_user_id() ?: null;

        $id = $this->organisations->create($payload);
        $this->settings->update($id, $data['settings'] ?? []);
        $this->audit->record('created', 'organisation', $id, $payload);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->normaliseOrganisation($data);
        $payload['updated_by'] = get_current_user_id() ?: null;

        $updated = $this->organisations->update($id, $payload);
        $this->audit->record('updated', 'organisation', $id, $payload);

        if (array_key_exists('settings', $data)) {
            $this->settings->update($id, (array) $data['settings']);
            $this->audit->record('updated', 'organisation_settings', $id, (array) $data['settings']);
        }

        return $updated;
    }

    public function archive(int $id): bool
    {
        $archived = $this->organisations->archive($id);
        $this->audit->record('archived', 'organisation', $id, ['archived' => $archived]);
        return $archived;
    }

    public function restore(int $id): bool
    {
        $restored = $this->organisations->restore($id);
        $this->audit->record('restored', 'organisation', $id, ['restored' => $restored]);
        return $restored;
    }

    public function findWithSettings(int $id): ?array
    {
        $organisation = $this->organisations->find($id);
        if (!$organisation) { return null; }
        $organisation['settings'] = $this->settings->find($id);
        return $organisation;
    }

    private function normaliseOrganisation(array $data): array
    {
        $fields = [
            'uuid', 'name', 'legal_name', 'type', 'status', 'vat_number', 'siret', 'ape', 'email', 'phone', 'website',
            'address', 'postal_code', 'city', 'country', 'logo_asset_id', 'notes', 'created_by', 'updated_by',
        ];
        $payload = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; }
        }
        if (!isset($payload['type'])) { $payload['type'] = 'company'; }
        if (!isset($payload['status'])) { $payload['status'] = 'active'; }
        return $payload;
    }
}
