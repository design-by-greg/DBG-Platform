<?php

namespace DBGPlatform\Production;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrderRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProductionAssignmentRepository;
use DBGPlatform\Database\Repositories\ProductionCheckRepository;
use DBGPlatform\Database\Repositories\ProductionJobRepository;
use DBGPlatform\Database\Repositories\ProductionOperationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;

class ProductionService
{
    private ProductionJobRepository $jobs;
    private ProductionOperationRepository $operations;
    private ProductionAssignmentRepository $assignments;
    private ProductionCheckRepository $checks;
    private ProductionEventRepository $events;
    private OrganisationRepository $organisations;
    private ProjectRepository $projects;
    private OrderRepository $orders;
    private AuditLogger $audit;

    private array $statuses = ['draft', 'planned', 'waiting', 'in_progress', 'paused', 'quality_control', 'completed', 'cancelled', 'archived'];
    private array $priorities = ['low', 'normal', 'high', 'urgent'];
    private array $operationStatuses = ['todo', 'waiting', 'in_progress', 'blocked', 'quality_control', 'done', 'cancelled'];
    private array $resourceTypes = ['user', 'team', 'machine', 'supplier'];

    public function __construct()
    {
        $this->jobs = new ProductionJobRepository();
        $this->operations = new ProductionOperationRepository();
        $this->assignments = new ProductionAssignmentRepository();
        $this->checks = new ProductionCheckRepository();
        $this->events = new ProductionEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->projects = new ProjectRepository();
        $this->orders = new OrderRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        if (!empty($this->validationErrors($data, true))) { return 0; }
        $payload = $this->normalise($data, true);
        $id = $this->jobs->create($payload);
        $this->replaceOperations($id, (array) ($data['operations'] ?? []));
        $after = $this->findFull($id);
        $this->events->record($id, 'created', 'Production job created');
        $this->audit->record('created', 'production_job', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->findFull($id);
        if (!$before || !empty($this->validationErrors($data, false, $id))) { return false; }
        $payload = $this->normalise($data, false);
        $updated = $this->jobs->update($id, $payload);
        if (array_key_exists('operations', $data)) { $this->replaceOperations($id, (array) $data['operations']); }
        $after = $this->findFull($id);
        $this->events->record($id, 'updated', 'Production job updated');
        $this->audit->record('updated', 'production_job', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->findFull($id);
        if (!$before) { return false; }
        $done = $this->jobs->archive($id);
        $this->events->record($id, 'archived', 'Production job archived');
        $this->audit->record('archived', 'production_job', $id, ['before' => $before, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->findFull($id);
        if (!$before) { return false; }
        $done = $this->jobs->restore($id);
        $this->events->record($id, 'restored', 'Production job restored');
        $this->audit->record('restored', 'production_job', $id, ['before' => $before, 'restored' => $done]);
        return $done;
    }

    public function changeStatus(int $id, string $status): bool
    {
        $status = sanitize_key($status);
        if (!in_array($status, $this->statuses, true)) { return false; }
        $payload = ['status' => $status];
        if ($status === 'in_progress') { $payload['started_at'] = current_time('mysql'); }
        if ($status === 'completed') { $payload['completed_at'] = current_time('mysql'); }
        $done = $this->jobs->update($id, $payload);
        $this->events->record($id, 'status_changed', 'Production status changed to ' . $status);
        $this->audit->record('status_changed', 'production_job', $id, ['status' => $status, 'updated' => $done]);
        return $done;
    }

    public function findFull(int $id): ?array
    {
        $job = $this->jobs->find($id);
        if (!$job) { return null; }
        $operations = $this->operations->forJob($id);
        foreach ($operations as &$operation) {
            $operation['assignments'] = $this->assignments->forOperation(absint($operation['id']));
            $operation['checks'] = $this->checks->listForOperation(absint($operation['id']));
        }
        $job['operations'] = $operations;
        return $job;
    }

    public function validationErrors(array $data, bool $create, ?int $jobId = null): array
    {
        $errors = [];
        $current = $jobId ? ($this->jobs->find($jobId) ?: []) : [];
        $merged = array_merge($current, $this->normalise($data, $create));
        if ($create && empty($merged['organisation_id'])) { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($merged['title'] ?? '')) === '') { $errors[] = 'Production title is required.'; }
        if (!$this->validOrganisation(absint($merged['organisation_id'] ?? 0))) { $errors[] = 'Organisation is invalid or archived.'; }
        if (!$this->validProject($merged)) { $errors[] = 'Project must belong to the selected organisation.'; }
        if (!$this->validOrder($merged)) { $errors[] = 'Order must belong to the selected organisation.'; }
        foreach (['status' => $this->statuses, 'priority' => $this->priorities] as $field => $allowed) {
            if (isset($merged[$field]) && !in_array((string) $merged[$field], $allowed, true)) { $errors[] = ucfirst($field) . ' is invalid.'; }
        }
        if (array_key_exists('operations', $data)) { $errors = array_merge($errors, $this->operationValidationErrors((array) $data['operations'])); }
        return $errors;
    }

    public function allowedValues(): array
    {
        return ['statuses' => $this->statuses, 'priorities' => $this->priorities, 'operation_statuses' => $this->operationStatuses, 'resource_types' => $this->resourceTypes];
    }

    private function replaceOperations(int $jobId, array $operations): void
    {
        $this->operations->deleteForJob($jobId);
        foreach ($operations as $index => $operation) {
            $operation = (array) $operation;
            $operation['job_id'] = $jobId;
            $operation['sort_order'] = $index;
            $operationId = $this->operations->create($operation);
            foreach ((array) ($operation['assignments'] ?? []) as $assignment) { $assignment['operation_id'] = $operationId; $this->assignments->create((array) $assignment); }
            foreach ((array) ($operation['checks'] ?? []) as $check) { $check['operation_id'] = $operationId; $this->checks->create((array) $check); }
        }
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['organisation_id', 'project_id', 'order_id', 'production_site_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['job_number', 'title'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); } }
        if (array_key_exists('description', $data)) { $payload['description'] = sanitize_textarea_field($data['description']); }
        foreach (['status', 'priority'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); } }
        foreach (['planned_start_at', 'planned_end_at'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); } }
        if ($create) { $payload['status'] = $payload['status'] ?? 'draft'; $payload['priority'] = $payload['priority'] ?? 'normal'; }
        return $payload;
    }

    private function operationValidationErrors(array $operations): array
    {
        $errors = [];
        foreach ($operations as $index => $operation) {
            $operation = (array) $operation;
            if (trim((string) ($operation['title'] ?? '')) === '') { $errors[] = 'Operation ' . ($index + 1) . ': title is required.'; }
            if (isset($operation['status']) && !in_array(sanitize_key($operation['status']), $this->operationStatuses, true)) { $errors[] = 'Operation ' . ($index + 1) . ': status is invalid.'; }
            foreach ((array) ($operation['assignments'] ?? []) as $assignment) {
                if (isset($assignment['resource_type']) && !in_array(sanitize_key($assignment['resource_type']), $this->resourceTypes, true)) { $errors[] = 'Operation ' . ($index + 1) . ': resource type is invalid.'; }
            }
        }
        return $errors;
    }

    private function validOrganisation(int $id): bool { $org = $this->organisations->find($id); return $org && ($org['status'] ?? '') !== 'archived'; }
    private function validProject(array $p): bool { if (empty($p['project_id'])) { return true; } $project = $this->projects->find(absint($p['project_id'])); return $project && absint($project['organisation_id']) === absint($p['organisation_id']) && ($project['status'] ?? '') !== 'archived'; }
    private function validOrder(array $p): bool { if (empty($p['order_id'])) { return true; } $order = $this->orders->find(absint($p['order_id'])); return $order && absint($order['organisation_id']) === absint($p['organisation_id']) && ($order['status'] ?? '') !== 'archived'; }
}
