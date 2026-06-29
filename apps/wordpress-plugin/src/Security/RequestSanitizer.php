<?php

namespace DBGPlatform\Security;

class RequestSanitizer
{
    public function text(?string $value): string
    {
        return sanitize_text_field($value ?? '');
    }

    public function textarea(?string $value): string
    {
        return sanitize_textarea_field($value ?? '');
    }

    public function integer($value): int
    {
        return absint($value);
    }

    public function payload(array $payload): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->payload($value);
            }

            if (is_numeric($value)) {
                return $value;
            }

            return sanitize_text_field((string) $value);
        }, $payload);
    }
}
