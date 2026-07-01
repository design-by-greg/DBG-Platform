<?php

namespace DBGPlatform\Organisations;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;

class OrganisationContactService
{
    private OrganisationContactRepository $contacts;
    private OrganisationRepository $organisations;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->contacts = new OrganisationContactRepository();
        $this->organisations = new OrganisationRepository();
        $this->audit = new AuditLogger();
    }

    public function create(int $organisationId, array $data): int
    {
        if (!$this->organisations->find($organisationId)) { return 0; }
        $payload = $this->normaliseContact($data);
        $payload['organisation_id'] = $organisationId;
        $id = $this->contacts->create($payload);
        $this->audit->record('created', 'organisation_contact', $id, ['after' => $this->contacts->find($id), 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->contacts->find($id);
        $payload = $this->normaliseContact($data);
        $updated = $this->contacts->update($id, $payload);
        $this->audit->record('updated', 'organisation_contact', $id, ['before' => $before, 'after' => $this->contacts->find($id), 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->contacts->find($id);
        $done = $this->contacts->archive($id);
        $this->audit->record('archived', 'organisation_contact', $id, ['before' => $before, 'after' => $this->contacts->find($id), 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->contacts->find($id);
        $done = $this->contacts->restore($id);
        $this->audit->record('restored', 'organisation_contact', $id, ['before' => $before, 'after' => $this->contacts->find($id), 'restored' => $done]);
        return $done;
    }

    public function makeMain(int $id): bool
    {
        $before = $this->contacts->find($id);
        $done = $this->contacts->setPrimary($id);
        $this->audit->record('main_contact', 'organisation_contact', $id, ['before' => $before, 'after' => $this->contacts->find($id), 'done' => $done]);
        return $done;
    }

    private function normaliseContact(array $data): array
    {
        $fields = ['first_name', 'last_name', 'job_title', 'email', 'phone', 'mobile', 'department', 'is_primary', 'notes', 'status'];
        $payload = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; }
        }
        if (!isset($payload['status'])) { $payload['status'] = 'active'; }
        return $payload;
    }
}
