<?php

namespace DBGPlatform\Production;

class ProductionEventRepository
{
    public function record(int $jobId, string $eventType, string $title): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dbg_production_events', [
            'job_id' => $jobId,
            'actor_id' => get_current_user_id() ?: null,
            'event_type' => sanitize_key($eventType),
            'title' => sanitize_text_field($title),
            'payload' => '{}',
            'created_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function forJob(int $jobId, int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dbg_production_events';
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' WHERE job_id = %d ORDER BY id DESC LIMIT %d', $jobId, max(1, min(200, $limit))), ARRAY_A) ?: [];
    }
}
