<?php

namespace DBGPlatform\Settings;

class SettingsRepository
{
    private string $optionName = 'dbg_platform_settings';

    public function all(): array
    {
        $defaults = [
            'api_base_url' => '',
            'api_token' => '',
            'sync_mode' => 'local',
            'woocommerce_enabled' => false,
            'debug_enabled' => false,
        ];

        $stored = get_option($this->optionName, []);
        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    public function update(array $payload): array
    {
        $settings = [
            'api_base_url' => esc_url_raw($payload['api_base_url'] ?? ''),
            'api_token' => sanitize_text_field($payload['api_token'] ?? ''),
            'sync_mode' => sanitize_key($payload['sync_mode'] ?? 'local'),
            'woocommerce_enabled' => !empty($payload['woocommerce_enabled']),
            'debug_enabled' => !empty($payload['debug_enabled']),
        ];

        update_option($this->optionName, $settings);

        return $settings;
    }
}
