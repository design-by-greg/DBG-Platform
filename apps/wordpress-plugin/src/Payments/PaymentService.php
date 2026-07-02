<?php

namespace DBGPlatform\Payments;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\InvoiceRepository;
use DBGPlatform\Database\Repositories\OrderRepository;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Database\Repositories\PaymentAllocationRepository;
use DBGPlatform\Database\Repositories\PaymentRepository;

class PaymentService
{
    private PaymentRepository $payments;
    private PaymentAllocationRepository $allocations;
    private PaymentEventRepository $events;
    private OrganisationRepository $organisations;
    private InvoiceRepository $invoices;
    private OrderRepository $orders;
    private OrganisationContactRepository $contacts;
    private AuditLogger $audit;

    private array $statuses = ['pending', 'processing', 'authorized', 'paid', 'partially_paid', 'failed', 'cancelled', 'refunded', 'archived'];
    private array $methods = ['bank_transfer', 'card', 'cash', 'check', 'stripe', 'paypal', 'qonto', 'other'];
    private array $providers = ['manual', 'stripe', 'paypal', 'qonto', 'sumup', 'mollie', 'adyen'];
    private array $currencies = ['EUR', 'USD', 'GBP', 'CHF', 'CAD'];

    public function __construct()
    {
        $this->payments = new PaymentRepository();
        $this->allocations = new PaymentAllocationRepository();
        $this->events = new PaymentEventRepository();
        $this->organisations = new OrganisationRepository();
        $this->invoices = new InvoiceRepository();
        $this->orders = new OrderRepository();
        $this->contacts = new OrganisationContactRepository();
        $this->audit = new AuditLogger();
    }

    public function create(array $data): int
    {
        if (!empty($this->validationErrors($data, true))) { return 0; }
        $payload = $this->normalise($data, true);
        $id = $this->payments->create($payload);
        $this->replaceAllocations($id, (array) ($data['allocations'] ?? []));
        $after = $this->findWithAllocations($id);
        $this->events->record($id, 'created', 'Payment created', ['after' => $after]);
        $this->audit->record('created', 'payment', $id, ['after' => $after, 'input' => $payload]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->findWithAllocations($id);
        if (!$before || !empty($this->validationErrors($data, false, $id))) { return false; }
        $payload = $this->normalise($data, false);
        $updated = $this->payments->update($id, $payload);
        if (array_key_exists('allocations', $data)) { $this->replaceAllocations($id, (array) $data['allocations']); }
        $after = $this->findWithAllocations($id);
        $this->events->record($id, 'updated', 'Payment updated', ['before' => $before, 'after' => $after, 'changes' => $payload]);
        $this->audit->record('updated', 'payment', $id, ['before' => $before, 'after' => $after, 'changes' => $payload, 'updated' => $updated]);
        return $updated;
    }

    public function archive(int $id): bool
    {
        $before = $this->findWithAllocations($id);
        if (!$before) { return false; }
        $done = $this->payments->archive($id);
        $after = $this->findWithAllocations($id);
        $this->events->record($id, 'archived', 'Payment archived', ['before' => $before, 'after' => $after]);
        $this->audit->record('archived', 'payment', $id, ['before' => $before, 'after' => $after, 'archived' => $done]);
        return $done;
    }

    public function restore(int $id): bool
    {
        $before = $this->findWithAllocations($id);
        if (!$before) { return false; }
        $done = $this->payments->restore($id);
        $after = $this->findWithAllocations($id);
        $this->events->record($id, 'restored', 'Payment restored', ['before' => $before, 'after' => $after]);
        $this->audit->record('restored', 'payment', $id, ['before' => $before, 'after' => $after, 'restored' => $done]);
        return $done;
    }

    public function changeStatus(int $id, string $status): bool
    {
        $status = sanitize_key($status);
        if (!in_array($status, $this->statuses, true)) { return false; }
        $before = $this->findWithAllocations($id);
        if (!$before) { return false; }
        $payload = ['status' => $status];
        if ($status === 'paid') { $payload['paid_at'] = current_time('mysql'); $payload['received_at'] = current_time('mysql'); }
        $done = $this->payments->update($id, $payload);
        $after = $this->findWithAllocations($id);
        $this->events->record($id, 'status_changed', 'Payment status changed to ' . $status, ['before' => $before, 'after' => $after]);
        $this->audit->record('status_changed', 'payment', $id, ['before' => $before, 'after' => $after, 'status' => $status, 'updated' => $done]);
        return $done;
    }

    public function findWithAllocations(int $id): ?array
    {
        $payment = $this->payments->find($id);
        if (!$payment) { return null; }
        $payment['allocations'] = $this->allocations->forPayment($id);
        $payment['metadata'] = json_decode((string) ($payment['metadata_json'] ?? '{}'), true) ?: [];
        return $payment;
    }

    public function validationErrors(array $data, bool $create, ?int $paymentId = null): array
    {
        $errors = [];
        $current = $paymentId ? ($this->payments->find($paymentId) ?: []) : [];
        $merged = array_merge($current, $this->normalise($data, $create));
        if ($create && empty($merged['organisation_id'])) { $errors[] = 'Organisation is required.'; }
        if (!$this->validOrganisation(absint($merged['organisation_id'] ?? 0))) { $errors[] = 'Organisation is invalid or archived.'; }
        if (!$this->validInvoice($merged)) { $errors[] = 'Invoice must belong to the selected organisation.'; }
        if (!$this->validOrder($merged)) { $errors[] = 'Order must belong to the selected organisation.'; }
        if (!$this->validContact($merged)) { $errors[] = 'Contact must belong to the selected organisation.'; }
        if (!empty($merged['payment_number']) && $this->paymentNumberExists((string) $merged['payment_number'], $paymentId)) { $errors[] = 'Payment number already exists.'; }
        if (isset($merged['amount']) && (float) $merged['amount'] <= 0) { $errors[] = 'Amount must be greater than zero.'; }
        if (isset($merged['fee_amount']) && (float) $merged['fee_amount'] < 0) { $errors[] = 'Fee amount cannot be negative.'; }
        if (isset($merged['fee_amount'], $merged['amount']) && (float) $merged['fee_amount'] > (float) $merged['amount']) { $errors[] = 'Fee amount cannot exceed payment amount.'; }
        foreach (['status' => $this->statuses, 'method' => $this->methods, 'provider' => $this->providers, 'currency' => $this->currencies] as $field => $allowed) {
            if (isset($merged[$field]) && !in_array((string) $merged[$field], $allowed, true)) { $errors[] = ucfirst($field) . ' is invalid.'; }
        }
        if (array_key_exists('allocations', $data)) { $errors = array_merge($errors, $this->allocationValidationErrors((array) $data['allocations'], absint($merged['organisation_id'] ?? 0), (float) ($merged['amount'] ?? 0))); }
        return $errors;
    }

    public function allowedValues(): array
    {
        return ['statuses' => $this->statuses, 'methods' => $this->methods, 'providers' => $this->providers, 'currencies' => $this->currencies];
    }

    private function normalise(array $data, bool $create): array
    {
        $payload = [];
        foreach (['organisation_id', 'invoice_id', 'order_id', 'contact_id'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = absint($data[$field]) ?: null; } }
        foreach (['payment_number', 'external_reference'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_text_field($data[$field]); } }
        if (array_key_exists('notes', $data)) { $payload['notes'] = sanitize_textarea_field($data['notes']); }
        foreach (['provider', 'method', 'status'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = sanitize_key($data[$field]); } }
        if (array_key_exists('currency', $data)) { $payload['currency'] = strtoupper(sanitize_key($data['currency'])); }
        foreach (['amount', 'fee_amount'] as $field) { if (array_key_exists($field, $data)) { $payload[$field] = max(0, (float) $data[$field]); } }
        if (isset($payload['amount']) || isset($payload['fee_amount'])) { $payload['net_amount'] = max(0, (float) ($payload['amount'] ?? 0) - (float) ($payload['fee_amount'] ?? 0)); }
        if (array_key_exists('metadata', $data)) { $payload['metadata'] = (array) $data['metadata']; }
        if ($create) { $payload['provider'] = $payload['provider'] ?? 'manual'; $payload['method'] = $payload['method'] ?? 'bank_transfer'; $payload['status'] = $payload['status'] ?? 'pending'; $payload['currency'] = $payload['currency'] ?? 'EUR'; }
        return $payload;
    }

    private function replaceAllocations(int $paymentId, array $allocations): void
    {
        $this->allocations->deleteForPayment($paymentId);
        foreach ($allocations as $allocation) {
            $allocation = (array) $allocation;
            $allocation['payment_id'] = $paymentId;
            $this->allocations->create($allocation);
        }
    }

    private function allocationValidationErrors(array $allocations, int $organisationId, float $paymentAmount): array
    {
        $errors = [];
        $total = 0;
        foreach ($allocations as $index => $allocation) {
            $allocation = (array) $allocation;
            $invoiceId = absint($allocation['invoice_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);
            $total += $amount;
            if ($invoiceId <= 0) { $errors[] = 'Allocation ' . ($index + 1) . ': invoice is required.'; continue; }
            $invoice = $this->invoices->find($invoiceId);
            if (!$invoice || absint($invoice['organisation_id']) !== $organisationId) { $errors[] = 'Allocation ' . ($index + 1) . ': invoice is invalid.'; }
            if ($amount <= 0) { $errors[] = 'Allocation ' . ($index + 1) . ': amount must be greater than zero.'; }
        }
        if ($total > $paymentAmount) { $errors[] = 'Allocated amount cannot exceed payment amount.'; }
        return $errors;
    }

    private function paymentNumberExists(string $paymentNumber, ?int $exceptId): bool
    {
        $existing = $this->payments->findByNumber($paymentNumber);
        return $existing && (!$exceptId || absint($existing['id']) !== $exceptId);
    }

    private function validOrganisation(int $id): bool { $org = $this->organisations->find($id); return $org && ($org['status'] ?? '') !== 'archived'; }
    private function validInvoice(array $p): bool { if (empty($p['invoice_id'])) { return true; } $invoice = $this->invoices->find(absint($p['invoice_id'])); return $invoice && absint($invoice['organisation_id']) === absint($p['organisation_id']) && ($invoice['status'] ?? '') !== 'archived'; }
    private function validOrder(array $p): bool { if (empty($p['order_id'])) { return true; } $order = $this->orders->find(absint($p['order_id'])); return $order && absint($order['organisation_id']) === absint($p['organisation_id']) && ($order['status'] ?? '') !== 'archived'; }
    private function validContact(array $p): bool { if (empty($p['contact_id'])) { return true; } $contact = $this->contacts->find(absint($p['contact_id'])); return $contact && absint($contact['organisation_id']) === absint($p['organisation_id']) && ($contact['status'] ?? '') !== 'archived'; }
}
