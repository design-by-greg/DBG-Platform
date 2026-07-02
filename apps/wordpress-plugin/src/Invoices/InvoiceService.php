<?php

namespace DBGPlatform\Invoices;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\InvoiceLineRepository;
use DBGPlatform\Database\Repositories\InvoiceRepository;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\ProjectRepository;
use DBGPlatform\Database\Repositories\QuoteRepository;
use DBGPlatform\Database\Repositories\OrderRepository;

class InvoiceService
{
    private InvoiceRepository $invoices;
    private InvoiceLineRepository $lines;
    private InvoiceEventRepository $events;
    private OrganisationRepository $organisations;
    private ProjectRepository $projects;
    private QuoteRepository $quotes;
    private OrderRepository $orders;
    private OrganisationContactRepository $contacts;
    private AuditLogger $audit;

    private array $statuses = ['draft', 'issued', 'sent', 'paid', 'overdue', 'cancelled', 'archived'];
    private array $paymentStatuses = ['unpaid', 'partial', 'paid', 'refunded'];
    private array $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];

    public function __construct()
    {
        $this->invoices = new InvoiceRepository();
        $this->lines = new InvoiceLineRepository();
        $this->events = new InvoiceEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->projects = new ProjectRepository();
        $this->quotes = new QuoteRepository();
        $this->orders = new OrderRepository();
        $this->contacts = new OrganisationContactRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        if (!empty($this->validationErrors($data, true))) { return 0; }
        $payload = $this->normalise($data, true);
        $id = $this->invoices->create($payload);
        $this->replaceLines($id, (array) ($data['lines'] ?? []));
        $this->recalculateTotals($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'created', 'Invoice created', ['after' => $after]);
        $this->audit->record('created', 'invoice', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->findWithLines($id);
        if (!$before || !empty($this->validationErrors($data, false, $id))) { return false; }
        $payload = $this->normalise($data, false);
        $updated = $this->invoices->update($id, $payload);
        if (array_key_exists('lines', $data)) { $this->replaceLines($id, (array) $data['lines']); }
        $this->recalculateTotals($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'updated', 'Invoice updated', ['before' => $before, 'after' => $after, 'changes' => $payload]);
        $this->audit->record('updated', 'invoice', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $done = $this->invoices->archive($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'archived', 'Invoice archived', ['before' => $before, 'after' => $after]);
        $this->audit->record('archived', 'invoice', $id, ['before' => $before, 'after' => $after, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $done = $this->invoices->restore($id);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'restored', 'Invoice restored', ['before' => $before, 'after' => $after]);
        $this->audit->record('restored', 'invoice', $id, ['before' => $before, 'after' => $after, 'restored' => $done]);
        return $done;
    }

    public function changeStatus(int $id, string $status): bool
    {
        $status = sanitize_key($status);
        if (!in_array($status, $this->statuses, true)) { return false; }
        $before = $this->findWithLines($id);
        if (!$before) { return false; }
        $payload = ['status' => $status];
        if ($status === 'issued' && empty($before['issued_at'])) { $payload['issued_at'] = current_time('mysql'); }
        if ($status === 'paid') { $payload['paid_at'] = current_time('mysql'); $payload['payment_status'] = 'paid'; }
        if ($status === 'cancelled') { $payload['cancelled_at'] = current_time('mysql'); }
        $done = $this->invoices->update($id, $payload);
        $after = $this->findWithLines($id);
        $this->events->record($id, 'status_changed', 'Invoice status changed to ' . $status, ['before' => $before, 'after' => $after]);
        $this->audit->record('status_changed', 'invoice', $id, ['before' => $before, 'after' => $after, 'status' => $status, 'updated' => $done]);
        return $done;
    }

    public function findWithLines(int $id): ?array
    {
        $invoice = $this->invoices->find($id);
        if (!$invoice) { return null; }
        $invoice['lines'] = $this->lines->forInvoice($id);
        return $invoice;
    }

    public function validationErrors(array $data, bool $create, ?int $invoiceId = null): array
    {
        $errors = [];
        $current = $invoiceId ? ($this->invoices->find($invoiceId) ?: []) : [];
        $merged = array_merge($current, $this->normalise($data, $create));
        if ($create && empty($merged['organisation_id'])) { $errors[] = 'Organisation is required.'; }
        if ($create && trim((string) ($merged['title'] ?? '')) === '') { $errors[] = 'Invoice title is required.'; }
        if (isset($merged['title']) && strlen((string) $merged['title']) > 255) { $errors[] = 'Invoice title must be 255 characters or less.'; }
        if (isset($merged['invoice_number']) && strlen((string) $merged['invoice_number']) > 64) { $errors[] = 'Invoice number must be 64 characters or less.'; }
        if (!empty($merged['invoice_number']) && $this->invoiceNumberExists((string) $merged['invoice_number'], $invoiceId)) { $errors[] = 'Invoice number already exists.'; }
        if (!$this->validOrganisation(absint($merged['organisation_id'] ?? 0))) { $errors[] = 'Organisation is invalid or archived.'; }
        if (!$this->validProject($merged)) { $errors[] = 'Project must belong to the selected organisation.'; }
        if (!$this->validQuote($merged)) { $errors[] = 'Quote must belong to the selected organisation.'; }
        if (!$this->validOrder($merged)) { $errors[] = 'Order must belong to the selected organisation.'; }
        if (!$this->validContact($merged)) { $errors[] = 'Contact must belong to the selected organisation.'; }
        if (!empty($merged['due_date']) && !$this->isDate((string) $merged['due_date'])) { $errors[] = 'Due date must use YYYY-MM-DD format.'; }
        foreach (['status' => $this->statuses, 'payment_status' => $this->paymentStatuses, 'currency' => $this->currencies] as $field => $allowed) {
            if (isset($merged[$field]) && !in_array((string) $merged[$field], $allowed, true)) { $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is invalid.'; }
        }
        if (array_key_exists('lines', $data)) { $errors = array_merge($errors, $this->lineValidationErrors((array) $data['lines'])); }
        return $errors;
    }

    public function allowedValues(): array
    {
        return ['statuses' => $this->statuses, 'payment_statuses' => $this->paymentStatuses, 'currencies' => $this->currencies];
    }

    private function replaceLines(int $invoiceId, array $lines): void
    {
        $this->lines->deleteForInvoice($invoiceId);
        foreach ($lines as $index => $line) {
            $line['invoice_id'] = $invoiceId;
            $line['sort_order'] = $index;
            $this->lines->create((array) $line);
        }
    }

    private function recalculateTotals(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);
        $totals = $this->lines->totals($invoiceId);
        $amountPaid = max(0, (float) ($invoice['amount_paid'] ?? 0));
        $totals['amount_due'] = max(0, (float) $totals['total_ttc'] - $amountPaid);
        $this->invoices->update($invoiceId, $totals);
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['organisation_id', 'project_id', 'quote_id', 'order_id', 'contact_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['invoice_number', 'title', 'due_date'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); } }
        foreach (['notes', 'terms'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_textarea_field($data[$field]); } }
        foreach (['status', 'payment_status'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); } }
        if (array_key_exists('currency', $data)) { $payload['currency'] = strtoupper(sanitize_key($data['currency'])); }
        if ($create) { $payload['status'] = $payload['status'] ?? 'draft'; $payload['payment_status'] = $payload['payment_status'] ?? 'unpaid'; $payload['currency'] = $payload['currency'] ?? 'EUR'; }
        return $payload;
    }

    private function lineValidationErrors(array $lines): array
    {
        $errors = [];
        foreach ($lines as $index => $line) {
            $line = (array) $line;
            $label = 'Line ' . ($index + 1) . ': ';
            if (trim((string) ($line['title'] ?? '')) === '') { $errors[] = $label . 'title is required.'; }
            if (isset($line['quantity']) && (float) $line['quantity'] < 0) { $errors[] = $label . 'quantity cannot be negative.'; }
            if (isset($line['unit_price_ht']) && (float) $line['unit_price_ht'] < 0) { $errors[] = $label . 'unit price cannot be negative.'; }
            if (isset($line['discount_rate']) && ((float) $line['discount_rate'] < 0 || (float) $line['discount_rate'] > 100)) { $errors[] = $label . 'discount must be between 0 and 100.'; }
            if (isset($line['tax_rate']) && (float) $line['tax_rate'] < 0) { $errors[] = $label . 'tax rate cannot be negative.'; }
        }
        return $errors;
    }

    private function invoiceNumberExists(string $invoiceNumber, ?int $exceptId): bool
    {
        $existing = $this->invoices->findByNumber($invoiceNumber);
        return $existing && (!$exceptId || absint($existing['id']) !== $exceptId);
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function validOrganisation(int $id): bool { $org = $this->organisations->find($id); return $org && ($org['status'] ?? '') !== 'archived'; }
    private function validProject(array $p): bool { if (empty($p['project_id'])) { return true; } $project = $this->projects->find(absint($p['project_id'])); return $project && absint($project['organisation_id']) === absint($p['organisation_id']) && ($project['status'] ?? '') !== 'archived'; }
    private function validQuote(array $p): bool { if (empty($p['quote_id'])) { return true; } $quote = $this->quotes->find(absint($p['quote_id'])); return $quote && absint($quote['organisation_id']) === absint($p['organisation_id']) && ($quote['status'] ?? '') !== 'archived'; }
    private function validOrder(array $p): bool { if (empty($p['order_id'])) { return true; } $order = $this->orders->find(absint($p['order_id'])); return $order && absint($order['organisation_id']) === absint($p['organisation_id']) && ($order['status'] ?? '') !== 'archived'; }
    private function validContact(array $p): bool { if (empty($p['contact_id'])) { return true; } $contact = $this->contacts->find(absint($p['contact_id'])); return $contact && absint($contact['organisation_id']) === absint($p['organisation_id']) && ($contact['status'] ?? '') !== 'archived'; }
}
