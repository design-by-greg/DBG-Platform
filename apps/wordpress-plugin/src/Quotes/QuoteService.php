<?php

namespace DBGPlatform\Quotes;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;
use DBGPlatform\Database\Repositories\QuoteLineRepository;
use DBGPlatform\Database\Repositories\QuoteRepository;

class QuoteService
{
    private QuoteRepository $quotes;
    private QuoteLineRepository $lines;
    private QuoteEventRepository $events;
    private OrganisationRepository $organisations;
    private OrganisationContactRepository $contacts;
    private ProjectRepository $projects;
    private AssetRepository $assets;
    private AuditLogger $audit;

    private array $statuses = ['draft', 'sent', 'signed', 'accepted', 'rejected', 'expired', 'converted', 'archived'];
    private array $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];
    private array $lineTypes = ['item', 'textile', 'print', 'design', 'service', 'discount', 'shipping', 'other'];
    private array $units = ['unit', 'piece', 'hour', 'day', 'pack', 'm2', 'ml'];

    public function __construct()
    {
        $this->quotes = new QuoteRepository();
        $this->lines = new QuoteLineRepository();
        $this->events = new QuoteEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->contacts = new OrganisationContactRepository();
        $this->projects = new ProjectRepository();
        $this->assets = new AssetRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        if (!empty($this->validationErrors($data, true))) { return 0; }
        $payload = $this->normalise($data, true);
        $payload['uuid'] = $payload['uuid'] ?? wp_generate_uuid4();
        $payload['quote_number'] = $payload['quote_number'] ?: $this->quotes->nextQuoteNumber();
        $id = $this->quotes->create($payload);
        $this->replaceLines($id, (array) ($data['lines'] ?? []));
        $this->recalculateTotals($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'created', 'Quote created', ['after' => $after]);
        $this->audit->record('created', 'quote', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->findWithLines($id);
        if (!$before || !empty($this->validationErrors($data, false, $id))) { return false; }
        $payload = $this->normalise($data, false);
        $updated = $this->quotes->update($id, $payload);
        if (array_key_exists('lines', $data)) { $this->replaceLines($id, (array) $data['lines']); }
        $this->recalculateTotals($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'updated', 'Quote updated', ['before' => $before, 'after' => $after, 'changes' => $payload]);
        $this->audit->record('updated', 'quote', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $done = $this->quotes->archive($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'archived', 'Quote archived', ['before' => $before, 'after' => $after]);
        $this->audit->record('archived', 'quote', $id, ['before' => $before, 'after' => $after, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $done = $this->quotes->restore($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'restored', 'Quote restored', ['before' => $before, 'after' => $after]);
        $this->audit->record('restored', 'quote', $id, ['before' => $before, 'after' => $after, 'restored' => $done]);
        return $done;
    }

    public function changeStatus(int $id, string $status): bool
    {
        $status = sanitize_key($status);
        if (!in_array($status, $this->statuses, true)) { return false; }
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $payload = ['status' => $status];
        if ($status === 'signed') { $payload['signed_at'] = current_time('mysql'); }
        if ($status === 'accepted') { $payload['accepted_at'] = current_time('mysql'); }
        if ($status === 'rejected') { $payload['rejected_at'] = current_time('mysql'); }
        $done = $this->quotes->update($id, $payload);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'status_changed', 'Quote status changed to ' . $status, ['before' => $before, 'after' => $after]);
        $this->audit->record('status_changed', 'quote', $id, ['before' => $before, 'after' => $after, 'status' => $status, 'updated' => $done]);
        return $done;
    }

    public function findWithLines(int $id): ?array
    {
        $quote = $this->quotes->find($id);
        if (!$quote) { return null; }
        $quote['lines'] = $this->lines->forQuote($id);
        return $quote;
    }

    public function validationErrors(array $data, bool $create, ?int $quoteId = null): array
    {
        $errors = [];
        $current = $quoteId ? ($this->quotes->find($quoteId) ?: []) : [];
        $merged = array_merge($current, $this->normalise($data, $create));
        if ($create && empty($merged['organisation_id'])) { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($merged['title'] ?? '')) === '') { $errors[] = 'Quote title is required.'; }
        if (isset($merged['title']) && strlen((string) $merged['title']) > 255) { $errors[] = 'Quote title must be 255 characters or less.'; }
        if (isset($merged['quote_number']) && strlen((string) $merged['quote_number']) > 64) { $errors[] = 'Quote number must be 64 characters or less.'; }
        if (!empty($merged['quote_number']) && $this->quoteNumberExists((string) $merged['quote_number'], $quoteId)) { $errors[] = 'Quote number already exists.'; }
        if (!$this->isValidOrganisation(absint($merged['organisation_id'] ?? 0))) { $errors[] = 'Organisation is invalid or archived.'; }
        if (!$this->isValidProject($merged)) { $errors[] = 'Project must belong to the selected organisation.'; }
        if (!$this->isValidContact($merged)) { $errors[] = 'Contact must belong to the selected organisation and be active.'; }
        if (!empty($merged['valid_until']) && !$this->isDate((string) $merged['valid_until'])) { $errors[] = 'Valid until must use YYYY-MM-DD format.'; }
        foreach (['status' => $this->statuses, 'currency' => $this->currencies] as $field => $allowed) {
            if (isset($merged[$field]) && !in_array((string) $merged[$field], $allowed, true)) { $errors[] = ucfirst($field) . ' is invalid.'; }
        }
        if (array_key_exists('lines', $data)) { $errors = array_merge($errors, $this->lineValidationErrors((array) $data['lines'], absint($merged['organisation_id'] ?? 0))); }
        return $errors;
    }

    public function allowedValues(): array
    {
        return ['statuses' => $this->statuses, 'currencies' => $this->currencies, 'line_types' => $this->lineTypes, 'units' => $this->units];
    }

    private function replaceLines(int $quoteId, array $lines): void
    {
        $this->lines->deleteForQuote($quoteId);
        foreach ($lines as $index => $line) {
            $line['quote_id'] = $quoteId;
            $line['sort_order'] = $line['sort_order'] ?? $index;
            $this->lines->create($this->normaliseLine((array) $line));
        }
    }

    private function recalculateTotals(int $quoteId): void
    {
        $this->quotes->update($quoteId, $this->lines->totals($quoteId));
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['uuid', 'quote_number', 'title', 'valid_until'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); } }
        foreach (['terms', 'notes'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_textarea_field($data[$field]); } }
        foreach (['organisation_id', 'project_id', 'contact_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        if (array_key_exists('status', $data)) { $payload['status'] = sanitize_key($data['status']); }
        if (array_key_exists('currency', $data)) { $payload['currency'] = strtoupper(sanitize_key($data['currency'])); }
        if ($create) { $payload['quote_number'] = $payload['quote_number'] ?? ''; $payload['status'] = $payload['status'] ?? 'draft'; $payload['currency'] = $payload['currency'] ?? 'EUR'; }
        if (isset($payload['status']) && !in_array($payload['status'], $this->statuses, true)) { $payload['status'] = 'draft'; }
        if (isset($payload['currency']) && !in_array($payload['currency'], $this->currencies, true)) { $payload['currency'] = 'EUR'; }
        return $payload;
    }

    private function normaliseLine(array $line): array
    {
        return [
            'quote_id' => absint($line['quote_id'] ?? 0),
            'asset_id' => absint($line['asset_id'] ?? 0),
            'line_type' => sanitize_key($line['line_type'] ?? 'item'),
            'title' => sanitize_text_field($line['title'] ?? ''),
            'description' => sanitize_textarea_field($line['description'] ?? ''),
            'quantity' => max(0, (float) ($line['quantity'] ?? 1)),
            'unit' => sanitize_key($line['unit'] ?? 'unit'),
            'unit_price_ht' => max(0, (float) ($line['unit_price_ht'] ?? 0)),
            'discount_rate' => max(0, min(100, (float) ($line['discount_rate'] ?? 0))),
            'tax_rate' => max(0, (float) ($line['tax_rate'] ?? 20)),
            'sort_order' => absint($line['sort_order'] ?? 0),
            'metadata' => (array) ($line['metadata'] ?? []),
        ];
    }

    private function lineValidationErrors(array $lines, int $organisationId): array
    {
        $errors = [];
        foreach ($lines as $index => $line) {
            $line = (array) $line; $label = 'Line ' . ($index + 1) . ': ';
            if (trim((string) ($line['title'] ?? '')) === '') { $errors[] = $label . 'title is required.'; }
            if (isset($line['title']) && strlen((string) $line['title']) > 255) { $errors[] = $label . 'title must be 255 characters or less.'; }
            if (!empty($line['line_type']) && !in_array(sanitize_key($line['line_type']), $this->lineTypes, true)) { $errors[] = $label . 'line type is invalid.'; }
            if (!empty($line['unit']) && !in_array(sanitize_key($line['unit']), $this->units, true)) { $errors[] = $label . 'unit is invalid.'; }
            if (isset($line['quantity']) && (float) $line['quantity'] < 0) { $errors[] = $label . 'quantity cannot be negative.'; }
            if (isset($line['unit_price_ht']) && (float) $line['unit_price_ht'] < 0) { $errors[] = $label . 'unit price cannot be negative.'; }
            if (isset($line['discount_rate']) && ((float) $line['discount_rate'] < 0 || (float) $line['discount_rate'] > 100)) { $errors[] = $label . 'discount must be between 0 and 100.'; }
            if (isset($line['tax_rate']) && (float) $line['tax_rate'] < 0) { $errors[] = $label . 'tax rate cannot be negative.'; }
            if (!empty($line['asset_id']) && !$this->isValidAsset(absint($line['asset_id']), $organisationId)) { $errors[] = $label . 'asset must belong to the selected organisation.'; }
        }
        return $errors;
    }

    private function quoteNumberExists(string $quoteNumber, ?int $exceptId): bool
    {
        $existing = $this->quotes->findByNumber($quoteNumber);
        return $existing && (!$exceptId || absint($existing['id']) !== $exceptId);
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
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

    private function isValidContact(array $payload): bool
    {
        if (empty($payload['contact_id'])) { return true; }
        $contact = $this->contacts->find(absint($payload['contact_id']));
        return $contact && absint($contact['organisation_id']) === absint($payload['organisation_id']) && ($contact['status'] ?? '') !== 'archived';
    }

    private function isValidAsset(int $assetId, int $organisationId): bool
    {
        $asset = $this->assets->find($assetId);
        return $asset && absint($asset['organisation_id']) === $organisationId && ($asset['status'] ?? '') !== 'archived';
    }
}
