<?php

namespace DBGPlatform\Admin;

class FormValidator
{
    private array $errors = [];

    public function required(string $field, string $label, array $source): self
    {
        $value = trim((string)($source[$field] ?? ''));

        if ($value === '') {
            $this->errors[] = $label . ' is required.';
        }

        return $this;
    }

    public function positiveInt(string $field, string $label, array $source): self
    {
        $value = absint($source[$field] ?? 0);

        if ($value <= 0) {
            $this->errors[] = $label . ' must be a valid positive number.';
        }

        return $this;
    }

    public function allowedValue(string $field, string $label, array $allowed, array $source): self
    {
        $value = sanitize_key((string)($source[$field] ?? ''));

        if (!in_array($value, $allowed, true)) {
            $this->errors[] = $label . ' is not allowed.';
        }

        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
