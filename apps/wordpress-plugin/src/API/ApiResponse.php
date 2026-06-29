<?php

namespace DBGPlatform\API;

use WP_REST_Response;

class ApiResponse
{
    public static function validation(array $errors): WP_REST_Response
    {
        return new WP_REST_Response([
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    public static function notFound(string $message): WP_REST_Response
    {
        return new WP_REST_Response([
            'message' => $message,
        ], 404);
    }

    public static function ok(array $payload): WP_REST_Response
    {
        return new WP_REST_Response($payload, 200);
    }

    public static function created(array $payload): WP_REST_Response
    {
        return new WP_REST_Response($payload, 201);
    }
}
