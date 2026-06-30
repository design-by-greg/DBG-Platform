<?php

namespace DBGPlatform\API;

class ApiValidator
{
    private array $errors = [];

    public function required(string $field, string $label, array $source): self
    {
        $value = trim((string)($source[$field] ?? ''));
        if ($value === '') { $this->errors[] = $label . ' is required.'; }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max, array $source): self
    {
        if (!array_key_exists($field, $source)) { return $this; }
        $value = trim((string) ($source[$field] ?? ''));
        if (strlen($value) > $max) { $this->errors[] = $label . ' must be ' . $max . ' characters or less.'; }
        return $this;
    }

    public function minLength(string $field, string $label, int $min, array $source): self
    {
        if (!array_key_exists($field, $source)) { return $this; }
        $value = trim((string) ($source[$field] ?? ''));
        if ($value !== '' && strlen($value) < $min) { $this->errors[] = $label . ' must be at least ' . $min . ' characters.'; }
        return $this;
    }

    public function positiveInt(string $field, string $label, array $source): self
    {
        $value = absint($source[$field] ?? 0);
        if ($value <= 0) { $this->errors[] = $label . ' must be a valid positive number.'; }
        return $this;
    }

    public function email(string $field, string $label, array $source): self
    {
        if (!array_key_exists($field, $source) || trim((string) $source[$field]) === '') { return $this; }
        if (!is_email((string) $source[$field])) { $this->errors[] = $label . ' must be a valid email address.'; }
        return $this;
    }

    public function booleanish(string $field, string $label, array $source): self
    {
        if (!array_key_exists($field, $source)) { return $this; }
        if (!in_array($source[$field], [true, false, 0, 1, '0', '1'], true)) { $this->errors[] = $label . ' must be true or false.'; }
        return $this;
    }

    public function allowedValue(string $field, string $label, array $allowed, array $source): self
    {
        if (!array_key_exists($field, $source) || $source[$field] === '') { return $this; }
        $value = sanitize_key((string)($source[$field] ?? ''));
        if (!in_array($value, $allowed, true)) { $this->errors[] = $label . ' is not allowed.'; }
        return $this;
    }

    public function errors(): array { return $this->errors; }
    public function passes(): bool { return empty($this->errors); }
}
