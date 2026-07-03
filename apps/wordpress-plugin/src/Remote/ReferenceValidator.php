<?php

namespace DBGPlatform\Remote;

use DBGPlatform\Settings\SettingsRepository;

/**
 * Validates that an organisation_id / project_id referenced from WordPress
 * still exists (and isn't archived) in the ATLAS ERP Base44 app, via the
 * `validateReference` bridge function.
 *
 * Behaviour depends on the plugin's `sync_mode` setting:
 * - local:  no-op. The reference is trusted as-is (shape validation only,
 *           done upstream in AssetService). Returns checked=false.
 * - hybrid: best-effort. If the bridge answers clearly, its answer is used.
 *           If the bridge is unreachable/misconfigured, the reference is
 *           trusted (fail open) so a network hiccup never blocks work.
 * - remote: strict. If the bridge doesn't answer clearly, the reference is
 *           treated as invalid (fail closed).
 */
class ReferenceValidator
{
    private array $settings;
    private RemoteClient $client;

    public function __construct()
    {
        $this->settings = (new SettingsRepository())->all();
        $this->client = new RemoteClient();
    }

    public function check(string $type, string $id): array
    {
        $mode = (string) ($this->settings['sync_mode'] ?? 'local');
        $id = trim($id);

        if ($mode === 'local' || $id === '') {
            return ['checked' => false, 'valid' => true, 'archived' => false];
        }

        $token = (string) ($this->settings['api_token'] ?? '');
        $result = $this->client->post('validateReference', [
            'type' => $type,
            'id' => $id,
            'api_key' => $token,
        ]);

        if (!empty($result['success']) && isset($result['body']['valid'])) {
            return [
                'checked' => true,
                'valid' => (bool) $result['body']['valid'],
                'archived' => (bool) ($result['body']['archived'] ?? false),
            ];
        }

        // Bridge unreachable or returned something unexpected.
        if ($mode === 'remote') {
            return ['checked' => true, 'valid' => false, 'archived' => false];
        }

        // hybrid: fail open, don't block on transient bridge issues.
        return ['checked' => false, 'valid' => true, 'archived' => false];
    }
}
