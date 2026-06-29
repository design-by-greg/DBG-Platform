<?php

namespace DBGPlatform\Integrations\WooCommerce;

use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Settings\SettingsRepository;

class WooCommerceOrderSync
{
    private WooCommerceOrderMapper $mapper;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->mapper = new WooCommerceOrderMapper();
        $this->audit = new AuditLogger();
    }

    public function handleOrderCreated(int $orderId): void
    {
        $order = wc_get_order($orderId);
        $payload = $this->mapper->map($order);

        if (empty($payload)) {
            return;
        }

        $this->audit->record('woocommerce_order_created', 'woocommerce_order', $orderId, $payload);
        $this->maybeSyncRemote($payload);
    }

    public function handleOrderStatusChanged(int $orderId, string $oldStatus, string $newStatus): void
    {
        $order = wc_get_order($orderId);
        $payload = $this->mapper->map($order);
        $payload['old_status'] = $oldStatus;
        $payload['new_status'] = $newStatus;

        $this->audit->record('woocommerce_order_status_changed', 'woocommerce_order', $orderId, $payload);
        $this->maybeSyncRemote($payload);
    }

    private function maybeSyncRemote(array $payload): void
    {
        $settings = (new SettingsRepository())->all();

        if (empty($settings['woocommerce_enabled'])) {
            return;
        }

        if ($settings['sync_mode'] === 'local') {
            return;
        }

        // Remote sync transport will be implemented in a dedicated sprint.
    }
}
