<?php

namespace DBGPlatform\Organisations;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\OrganisationUserRepository;

class OrganisationUserService
{
    private OrganisationUserRepository $users;
    private OrganisationRepository $organisations;
    private AuditLogger $audit;

    private array $roles = ['owner', 'administrator', 'manager', 'sales', 'designer', 'production', 'support', 'viewer'];

    public function __construct()
    {
        $this->users = new OrganisationUserRepository();
        $this->organisations = new OrganisationRepository();
        $this->audit = new AuditLogger();
    }

    public function add(int $organisationId, int $userId, array $data = []): int
    {
        $organisation = $this->organisations->find($organisationId);
        if (!$organisation || ($organisation['status'] ?? '') === 'archived' || get_userdata($userId) === false) { return 0; }
        $payload = $this->normalise($data);
        $payload['organisation_id'] = $organisationId;
        $payload['user_id'] = $userId;
        $id = $this->users->create($payload);
        $this->audit->record('created', 'organisation_user', $id, ['after' => $this->users->find($id), 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->users->find($id);
        if (!$before) { return false; }
        $payload = $this->normalise($data);
        $updated = $this->users->update($id, $payload);
        $this->audit->record('updated', 'organisation_user', $id, ['before' => $before, 'after' => $this->users->find($id), 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->users->find($id);
        if (!$before) { return false; }
        $done = $this->users->archive($id);
        $this->audit->record('archived', 'organisation_user', $id, ['before' => $before, 'after' => $this->users->find($id), 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->users->find($id);
        if (!$before) { return false; }
        $done = $this->users->restore($id);
        $this->audit->record('restored', 'organisation_user', $id, ['before' => $before, 'after' => $this->users->find($id), 'restored' => $done]);
        return $done;
    }

    public function makeOwner(int $id): bool
    {
        $before = $this->users->find($id);
        if (!$before) { return false; }
        $done = $this->users->setOwner($id);
        if ($done) { $this->users->update($id, ['role' => 'owner']); }
        $this->audit->record('owner', 'organisation_user', $id, ['before' => $before, 'after' => $this->users->find($id), 'done' => $done]);
        return $done;
    }

    private function normalise(array $data): array
    {
        $payload = [];
        foreach (['role', 'is_owner', 'status'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = $data[$field]; }
        }
        $payload['role'] = sanitize_key($payload['role'] ?? 'viewer');
        if (!in_array($payload['role'], $this->roles, true)) { $payload['role'] = 'viewer'; }
        $payload['status'] = sanitize_key($payload['status'] ?? 'active');
        if (!in_array($payload['status'], ['active', 'archived'], true)) { $payload['status'] = 'active'; }
        $payload['is_owner'] = !empty($payload['is_owner']) || $payload['role'] === 'owner';
        if ($payload['is_owner']) { $payload['role'] = 'owner'; }
        return $payload;
    }
}
