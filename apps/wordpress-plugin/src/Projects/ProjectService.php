<?php

namespace DBGPlatform\Projects;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\OrganisationUserRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;

class ProjectService
{
    private ProjectRepository $projects;
    private ProjectEventRepository $events;
    private OrganisationRepository $organisations;
    private OrganisationContactRepository $contacts;
    private OrganisationUserRepository $users;
    private AuditLogger $audit;

    private array $types = ['custom', 'textile', 'print', 'signage', 'goodies', 'web_to_print', 'internal'];
    private array $statuses = ['draft', 'quote', 'approved', 'production', 'delivered', 'cancelled', 'archived'];
    private array $priorities = ['low', 'normal', 'high', 'urgent'];
    private array $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];

    public function __construct()
    {
        $this->projects = new ProjectRepository();
        $this->events = new ProjectEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->contacts = new OrganisationContactRepository();
        $this->users = new OrganisationUserRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        $payload = $this->normalise($data, true);
        if (!$this->isValidOrganisation(absint($payload['organisation_id'] ?? 0))) { return 0; }
        if (!$this->isValidContact($payload)) { return 0; }
        if (!$this->isValidOwner($payload)) { return 0; }

        $payload['uuid'] = $payload['uuid'] ?? wp_generate_uuid4();
        $payload['project_number'] = $payload['project_number'] ?: $this->projects->nextProjectNumber();
        $payload['created_by'] = get_current_user_id() ?: null;
        $payload['updated_by'] = get_current_user_id() ?: null;

        $id = $this->projects->create($payload);
        $after = $this->projects->find($id);
        $this->events->record($id, 'created', 'Project created', ['after' => $after]);
        $this->audit->record('created', 'project', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->projects->find($id);
        if (!$before) { return false; }
        $payload = $this->normalise($data, false);
        $merged = array_merge($before, $payload);
        if (!$this->isValidOrganisation(absint($merged['organisation_id'] ?? 0))) { return false; }
        if (!$this->isValidContact($merged)) { return false; }
        if (!$this->isValidOwner($merged)) { return false; }

        $updated = $this->projects->update($id, $payload);
        $after = $this->projects->find($id);
        $this->events->record($id, 'updated', 'Project updated', ['before' => $before, 'after' => $after, 'changes' => $payload]);
        $this->audit->record('updated', 'project', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->projects->find($id);
        if (!$before) { return false; }
        $done = $this->projects->archive($id);
        $after = $this->projects->find($id);
        $this->events->record($id, 'archived', 'Project archived', ['before' => $before, 'after' => $after]);
        $this->audit->record('archived', 'project', $id, ['before' => $before, 'after' => $after, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->projects->find($id);
        if (!$before) { return false; }
        $done = $this->projects->restore($id);
        $after = $this->projects->find($id);
        $this->events->record($id, 'restored', 'Project restored', ['before' => $before, 'after' => $after]);
        $this->audit->record('restored', 'project', $id, ['before' => $before, 'after' => $after, 'restored' => $done]);
        return $done;
    }

    public function changeStatus(int $id, string $status): bool
    {
        if (!in_array($status, $this->statuses, true)) { return false; }
        $before = $this->projects->find($id);
        if (!$before) { return false; }
        $payload = ['status' => $status];
        if ($status === 'production' && empty($before['started_at'])) { $payload['started_at'] = current_time('mysql'); }
        if ($status === 'delivered') { $payload['completed_at'] = current_time('mysql'); }
        $done = $this->projects->update($id, $payload);
        $after = $this->projects->find($id);
        $this->events->record($id, 'status_changed', 'Project status changed to ' . $status, ['before' => $before, 'after' => $after]);
        $this->audit->record('status_changed', 'project', $id, ['before' => $before, 'after' => $after, 'status' => $status]);
        return $done;
    }

    public function allowedValues(): array
    {
        return ['types' => $this->types, 'statuses' => $this->statuses, 'priorities' => $this->priorities, 'currencies' => $this->currencies];
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['uuid', 'project_number', 'name', 'description', 'due_date', 'started_at', 'completed_at'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = is_string($data[$field]) ? sanitize_text_field($data[$field]) : $data[$field]; }
        }
        foreach (['organisation_id', 'contact_id', 'owner_user_id'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; }
        }
        foreach (['type', 'status', 'priority'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); }
        }
        if (array_key_exists('currency', $data)) { $payload['currency'] = strtoupper(sanitize_key($data['currency'])); }
        if (array_key_exists('budget_estimate', $data)) { $payload['budget_estimate'] = max(0, (float) $data['budget_estimate']); }

        if ($create) {
            $payload['type'] = $payload['type'] ?? 'custom';
            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['priority'] = $payload['priority'] ?? 'normal';
            $payload['currency'] = $payload['currency'] ?? 'EUR';
        }
        if (isset($payload['type']) && !in_array($payload['type'], $this->types, true)) { $payload['type'] = 'custom'; }
        if (isset($payload['status']) && !in_array($payload['status'], $this->statuses, true)) { $payload['status'] = 'draft'; }
        if (isset($payload['priority']) && !in_array($payload['priority'], $this->priorities, true)) { $payload['priority'] = 'normal'; }
        if (isset($payload['currency']) && !in_array($payload['currency'], $this->currencies, true)) { $payload['currency'] = 'EUR'; }
        return $payload;
    }

    private function isValidOrganisation(int $organisationId): bool
    {
        $organisation = $this->organisations->find($organisationId);
        return $organisation && ($organisation['status'] ?? '') !== 'archived';
    }

    private function isValidContact(array $payload): bool
    {
        if (empty($payload['contact_id'])) { return true; }
        $contact = $this->contacts->find(absint($payload['contact_id']));
        return $contact && absint($contact['organisation_id']) === absint($payload['organisation_id']) && ($contact['status'] ?? '') !== 'archived';
    }

    private function isValidOwner(array $payload): bool
    {
        if (empty($payload['owner_user_id'])) { return true; }
        $memberships = $this->users->allForUser(absint($payload['owner_user_id']), ['organisation_id' => absint($payload['organisation_id']), 'status' => 'active'], 1);
        return !empty($memberships);
    }
}
