<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\InvoiceRepository;
use DBGPlatform\Invoices\InvoiceEventRepository;
use DBGPlatform\Invoices\InvoiceService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class InvoiceRoutes
{
    private InvoiceRepository $invoices;
    private InvoiceEventRepository $events;
    private InvoiceService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->invoices = new InvoiceRepository();
        $this->events = new InvoiceEventRepository();
        $this->service = new InvoiceService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/invoices', [
            ['methods' => 'GET', 'callback' => [$this, 'listInvoices'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createInvoice'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/invoices/(?P<id>[0-9]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getInvoice'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateInvoice'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveInvoice'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/invoices/(?P<id>[0-9]+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreInvoice'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/invoices/(?P<id>[0-9]+)/status', [['methods' => 'PATCH', 'callback' => [$this, 'changeStatus'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/invoices/(?P<id>[0-9]+)/events', [['methods' => 'GET', 'callback' => [$this, 'invoiceEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listInvoices(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'project_id' => absint($request->get_param('project_id') ?? 0),
            'quote_id' => absint($request->get_param('quote_id') ?? 0),
            'order_id' => absint($request->get_param('order_id') ?? 0),
            'contact_id' => absint($request->get_param('contact_id') ?? 0),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'payment_status' => sanitize_key($request->get_param('payment_status') ?? ''),
        ];
        $result = $this->invoices->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'filters' => $filters, 'allowed' => $this->service->allowedValues()]);
    }

    public function getInvoice(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->service->findWithLines((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Invoice not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forInvoice((int) $request['id'], 50), 'allowed' => $this->service->allowedValues()]);
    }

    public function createInvoice(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id]) : ApiResponse::validation(['Invoice could not be created.']);
    }

    public function updateInvoice(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveInvoice(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreInvoice(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }
    public function changeStatus(WP_REST_Request $request): WP_REST_Response { $payload = $request->get_json_params() ?: []; return ApiResponse::ok(['updated' => $this->service->changeStatus((int) $request['id'], sanitize_key($payload['status'] ?? ''))]); }
    public function invoiceEvents(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['data' => $this->events->forInvoice((int) $request['id'], absint($request->get_param('limit') ?? 100))]); }
}
