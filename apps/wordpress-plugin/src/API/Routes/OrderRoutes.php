<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Database\Repositories\OrderRepository;
use DBGPlatform\Orders\OrderEventRepository;
use DBGPlatform\Orders\OrderService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class OrderRoutes
{
    private OrderRepository $orders;
    private OrderEventRepository $events;
    private OrderService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->orders = new OrderRepository();
        $this->events = new OrderEventRepository();
        $this->service = new OrderService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/orders', [
            ['methods' => 'GET', 'callback' => [$this, 'listOrders'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createOrder'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/orders/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getOrder'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateOrder'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveOrder'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/orders/(?P<id>\d+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreOrder'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/orders/(?P<id>\d+)/status', [['methods' => 'PATCH', 'callback' => [$this, 'changeStatus'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/orders/(?P<id>\d+)/events', [['methods' => 'GET', 'callback' => [$this, 'orderEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listOrders(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'project_id' => absint($request->get_param('project_id') ?? 0),
            'quote_id' => absint($request->get_param('quote_id') ?? 0),
            'contact_id' => absint($request->get_param('contact_id') ?? 0),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'payment_status' => sanitize_key($request->get_param('payment_status') ?? ''),
            'production_status' => sanitize_key($request->get_param('production_status') ?? ''),
            'fulfillment_status' => sanitize_key($request->get_param('fulfillment_status') ?? ''),
        ];
        $result = $this->orders->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'filters' => $filters, 'allowed' => $this->service->allowedValues()]);
    }

    public function getOrder(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->service->findWithLines((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Order not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forOrder((int) $request['id'], 50), 'allowed' => $this->service->allowedValues()]);
    }

    public function createOrder(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, true);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id, 'message' => 'Order created']) : ApiResponse::validation(['Order could not be created.']);
    }

    public function updateOrder(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, false);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveOrder(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreOrder(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }

    public function changeStatus(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $status = sanitize_key($payload['status'] ?? '');
        if ($status === '') { return ApiResponse::validation(['Status is required.']); }
        return ApiResponse::ok(['updated' => $this->service->changeStatus((int) $request['id'], $status)]);
    }

    public function orderEvents(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->events->forOrder((int) $request['id'], absint($request->get_param('limit') ?? 100))]);
    }

    private function validatePayload(array $payload, bool $create): ?WP_REST_Response
    {
        $validator = new ApiValidator();
        if ($create) { $validator->positiveInt('organisation_id', 'Organisation ID', $payload)->required('title', 'Order title', $payload); }
        $allowed = $this->service->allowedValues();
        $validator->maxLength('order_number', 'Order number', 64, $payload)->maxLength('title', 'Order title', 255, $payload)->maxLength('currency', 'Currency', 8, $payload);
        if (isset($payload['status'])) { $validator->allowedValue('status', 'Status', $allowed['statuses'], $payload); }
        if (isset($payload['payment_status'])) { $validator->allowedValue('payment_status', 'Payment status', $allowed['payment_statuses'], $payload); }
        if (isset($payload['production_status'])) { $validator->allowedValue('production_status', 'Production status', $allowed['production_statuses'], $payload); }
        if (isset($payload['fulfillment_status'])) { $validator->allowedValue('fulfillment_status', 'Fulfillment status', $allowed['fulfillment_statuses'], $payload); }
        if (isset($payload['currency'])) { $validator->allowedValue('currency', 'Currency', $allowed['currencies'], $payload); }
        foreach (['project_id' => 'Project ID', 'quote_id' => 'Quote ID', 'contact_id' => 'Contact ID'] as $field => $label) {
            if (isset($payload[$field]) && $payload[$field] !== '' && $payload[$field] !== null) { $validator->positiveInt($field, $label, $payload); }
        }
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
