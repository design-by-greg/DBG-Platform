<?php

namespace DBGPlatform\Assets;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;

class AssetService
{
    private AssetRepository $assets;
    private AssetEventRepository $events;
    private OrganisationRepository $organisations;
    private ProjectRepository $projects;
    private AuditLogger $audit;

    private array $types = ['document', 'logo', 'brand_guide', 'source_file', 'proof', 'mockup', 'production_file', 'photo', 'template', 'other'];
    private array $categories = ['general', 'graphic', 'bat', 'production', 'client', 'supplier', 'internal'];
    private array $statuses = ['draft', 'active', 'archived'];
    private array $approvalStatuses = ['not_required', 'pending', 'approved', 'rejected', 'changes_requested'];

    public function __construct()
    {
        $this->assets = new AssetRepository();
        $this->events = new AssetEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->projects = new ProjectRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        $errors = $this->validationErrors($data, true);
        if (!empty($errors)) { return 0; }
        $payload = $this->normalise($data, true);
        $payload['uuid'] = $payload['uuid'] ?? wp_generate_uuid4();
        $payload['created_by'] = get_current_user_id() ?: null;
        $payload['updated_by'] = get_current_user_id() ?: null;

        $id = $this->assets->create($payload);
        $after = $this->assets->find($id);
        $this->events->record($id, 'created', 'Asset created', ['after' => $after]);
        $this->audit->record('created', 'asset', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->assets->find($id);
        if (!$before) { return false; }
        $errors = $this->validationErrors($data, false, $id);
        if (!empty($errors)) { return false; }
        $payload = $this->normalise($data, false);
        $updated = $this->assets->update($id, $payload);
        $after = $this->assets->find($id);
        $this->events->record($id, 'updated', 'Asset updated', ['before' => $before, 'after' => $after, 'changes' => $payload]);
        $this->audit->record('updated', 'asset', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->assets->find($id);
        if (!$before) { return false; }
        $done = $this->assets->archive($id);
        $after = $this->assets->find($id);
        $this->events->record($id, 'archived', 'Asset archived', ['before' => $before, 'after' => $after]);
        $this->audit->record('archived', 'asset', $id, ['before' => $before, 'after' => $after, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->assets->find($id);
        if (!$before) { return false; }
        $done = $this->assets->restore($id);
        $after = $this->assets->find($id);
        $this->events->record($id, 'restored', 'Asset restored', ['before' => $before, 'after' => $after]);
        $this->audit->record('restored', 'asset', $id, ['before' => $before, 'after' => $after, 'restored' => $done]);
        return $done;
    }

    public function changeApprovalStatus(int $id, string $status): bool
    {
        $status = sanitize_key($status);
        if (!in_array($status, $this->approvalStatuses, true)) { return false; }
        $before = $this->assets->find($id);
        if (!$before) { return false; }
        $done = $this->assets->update($id, ['approval_status' => $status]);
        $after = $this->assets->find($id);
        $this->events->record($id, 'approval_changed', 'Approval changed to ' . $status, ['before' => $before, 'after' => $after]);
        $this->audit->record('approval_changed', 'asset', $id, ['before' => $before, 'after' => $after, 'approval_status' => $status, 'updated' => $done]);
        return $done;
    }

    public function bumpVersion(int $id): bool
    {
        $before = $this->assets->find($id);
        if (!$before) { return false; }
        $done = $this->assets->incrementVersion($id);
        $after = $this->assets->find($id);
        $this->events->record($id, 'version_bumped', 'Asset version bumped', ['before' => $before, 'after' => $after]);
        $this->audit->record('version_bumped', 'asset', $id, ['before' => $before, 'after' => $after, 'updated' => $done]);
        return $done;
    }

    public function validationErrors(array $data, bool $create, ?int $assetId = null): array
    {
        $errors = [];
        $current = $assetId ? ($this->assets->find($assetId) ?: []) : [];
        $merged = array_merge($current, $this->normalise($data, $create));

        if ($assetId && empty($current)) { $errors[] = 'Asset not found.'; }
        if ($create && empty($merged['organisation_id'])) { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($merged['name'] ?? '')) === '') { $errors[] = 'Asset name is required.'; }
        if (isset($merged['name']) && strlen((string) $merged['name']) > 255) { $errors[] = 'Asset name must be 255 characters or less.'; }
        if (isset($merged['uuid']) && strlen((string) $merged['uuid']) > 36) { $errors[] = 'UUID must be 36 characters or less.'; }
        if (!$this->isValidOrganisation(absint($merged['organisation_id'] ?? 0))) { $errors[] = 'Organisation is invalid or archived.'; }
        if (!$this->isValidProject($merged)) { $errors[] = 'Project must belong to the selected organisation.'; }
        if (!$this->isValidParent($merged, $assetId)) { $errors[] = 'Parent asset must belong to the selected organisation and cannot be itself.'; }
        if (!$this->isValidCurrentFile($merged)) { $errors[] = 'Current file must belong to the selected asset, project, or organisation.'; }
        if (isset($merged['version_number']) && absint($merged['version_number']) < 1) { $errors[] = 'Version number must be positive.'; }
        foreach (['type' => $this->types, 'category' => $this->categories, 'status' => $this->statuses, 'approval_status' => $this->approvalStatuses] as $field => $allowed) {
            if (isset($merged[$field]) && !in_array((string) $merged[$field], $allowed, true)) { $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is invalid.'; }
        }
        if (isset($data['metadata']) && !is_array($data['metadata'])) { $errors[] = 'Metadata must be an object.'; }
        return $errors;
    }

    public function allowedValues(): array
    {
        return ['types' => $this->types, 'categories' => $this->categories, 'statuses' => $this->statuses, 'approval_statuses' => $this->approvalStatuses];
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['uuid', 'name'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); }
        }
        if (array_key_exists('description', $data)) { $payload['description'] = sanitize_textarea_field($data['description']); }
        foreach (['organisation_id', 'project_id', 'parent_asset_id', 'current_file_record_id', 'version_number'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; }
        }
        foreach (['type', 'category', 'status', 'approval_status'] as $field) {
            if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); }
        }
        if (array_key_exists('metadata', $data)) { $payload['metadata'] = $this->sanitizeMetadata((array) $data['metadata']); }

        if ($create) {
            $payload['type'] = $payload['type'] ?? 'document';
            $payload['category'] = $payload['category'] ?? 'general';
            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['approval_status'] = $payload['approval_status'] ?? 'not_required';
            $payload['version_number'] = $payload['version_number'] ?? 1;
        }
        if (isset($payload['type']) && !in_array($payload['type'], $this->types, true)) { $payload['type'] = 'document'; }
        if (isset($payload['category']) && !in_array($payload['category'], $this->categories, true)) { $payload['category'] = 'general'; }
        if (isset($payload['status']) && !in_array($payload['status'], $this->statuses, true)) { $payload['status'] = 'draft'; }
        if (isset($payload['approval_status']) && !in_array($payload['approval_status'], $this->approvalStatuses, true)) { $payload['approval_status'] = 'not_required'; }
        return $payload;
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];
        foreach ($metadata as $key => $value) {
            $safeKey = sanitize_key((string) $key);
            if ($safeKey === '') { continue; }
            if (is_array($value)) { $clean[$safeKey] = $this->sanitizeMetadata($value); continue; }
            $clean[$safeKey] = is_scalar($value) ? sanitize_text_field((string) $value) : '';
        }
        return $clean;
    }

    private function isValidOrganisation(int $organisationId): bool
    {
        $organisation = $this->organisations->find($organisationId);
        return $organisation && ($organisation['status'] ?? '') !== 'archived';
    }

    private function isValidProject(array $payload): bool
    {
        if (empty($payload['project_id'])) { return true; }
        $project = $this->projects->find(absint($payload['project_id']));
        return $project && absint($project['organisation_id']) === absint($payload['organisation_id']) && ($project['status'] ?? '') !== 'archived';
    }

    private function isValidParent(array $payload, ?int $assetId): bool
    {
        if (empty($payload['parent_asset_id'])) { return true; }
        if ($assetId && absint($payload['parent_asset_id']) === $assetId) { return false; }
        $parent = $this->assets->find(absint($payload['parent_asset_id']));
        return $parent && absint($parent['organisation_id']) === absint($payload['organisation_id']) && ($parent['status'] ?? '') !== 'archived';
    }

    private function isValidCurrentFile(array $payload): bool
    {
        if (empty($payload['current_file_record_id'])) { return true; }
        global $wpdb;
        $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_file_records WHERE id = %d", absint($payload['current_file_record_id'])), ARRAY_A);
        if (!$file || ($file['status'] ?? '') === 'archived') { return false; }
        if (!empty($file['organisation_id']) && absint($file['organisation_id']) !== absint($payload['organisation_id'])) { return false; }
        if (!empty($payload['project_id']) && !empty($file['project_id']) && absint($file['project_id']) !== absint($payload['project_id'])) { return false; }
        return true;
    }
}
