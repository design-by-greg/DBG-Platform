<?php

namespace DBGPlatform\Integrations\WooCommerce;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Remote\RemoteClient;
use DBGPlatform\Settings\SettingsRepository;

class WooCommerceOrderSync
{
    private WooCommerceOrderMapper $mapper;
    private AuditLogger $audit;
    private RemoteClient $remote;

    public function __construct()
    {
        $this->mapper = new WooCommerceOrderMapper();
        $this->audit = new AuditLogger();
        $this->remote = new RemoteClient();
    }

    public function handleOrderCreated(int $orderId): void
    {
        $order = wc_get_order($orderId);
        $payload = $this->mapper->map($order);

        if (empty($payload)) {
            return;
        }

        $this->audit->record('woocommerce_order_created', 'woocommerce_order', $orderId, $payload);
        $this->maybeSyncRemote('woocommerce/orders', $orderId, $payload);
    }

    public function handleOrderStatusChanged(int $orderId, string $oldStatus, string $newStatus): void
    {
        $order = wc_get_order($orderId);
        $payload = $this->mapper->map($order);
        $payload['old_status'] = $oldStatus;
        $payload['new_status'] = $newStatus;

        $this->audit->record('woocommerce_order_status_changed', 'woocommerce_order', $orderId, $payload);
        $this->maybeSyncRemote('woocommerce/orders/status-changed', $orderId, $payload);
    }

    private function maybeSyncRemote(string $endpoint, int $orderId, array $payload): void
    {
        $settings = (new SettingsRepository())->all();

        if (empty($settings['woocommerce_enabled'])) {
            return;
        }

        if ($settings['sync_mode'] === 'local') {
            return;
        }

        $result = $this->remote->post($endpoint, $payload);

        $this->audit->record(
            !empty($result['success']) ? 'remote_sync_success' : 'remote_sync_failed',
            'woocommerce_order',
            $orderId,
            $result
        );
    }
}
