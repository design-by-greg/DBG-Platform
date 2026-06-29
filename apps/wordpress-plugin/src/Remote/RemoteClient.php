<?php

namespace DBGPlatform\Remote;

use DBGPlatform\Settings\SettingsRepository;

class RemoteClient
{
    private array $settings;

    public function __construct()
    {
        $this->settings = (new SettingsRepository())->all();
    }

    public function post(string $endpoint, array $payload): array
    {
        $baseUrl = rtrim((string) ($this->settings['api_base_url'] ?? ''), '/');
        $token = (string) ($this->settings['api_token'] ?? '');

        if ($baseUrl === '') {
            return [
                'success' => false,
                'message' => 'Remote API base URL is not configured.',
            ];
        }

        $response = wp_remote_post($baseUrl . '/' . ltrim($endpoint, '/'), [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $token !== '' ? 'Bearer ' . $token : '',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        return [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => is_array($body) ? $body : [],
        ];
    }
}
