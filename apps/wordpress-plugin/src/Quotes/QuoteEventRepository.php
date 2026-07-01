<?php

namespace DBGPlatform\Quotes;

class QuoteEventRepository
{
    public function record(int $quoteId, string $eventType, string $title, array $payload = []): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_quote_events', [
            'quote_id' => $quoteId,
            'actor_id' => get_current_user_id() ?: null,
            'event_type' => sanitize_key($eventType),
            'title' => sanitize_text_field($title),
            'payload' => wp_json_encode($payload),
            'created_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function forQuote(int $quoteId, int $limit = 100): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dbg_quote_events WHERE quote_id = %d ORDER BY id DESC LIMIT %d", $quoteId, max(1, min(200, $limit))), ARRAY_A) ?: [];
        return array_map(function (array $row): array {
            $row['payload_data'] = json_decode((string)($row['payload'] ?? '{}'), true) ?: [];
            return $row;
        }, $rows);
    }
}
