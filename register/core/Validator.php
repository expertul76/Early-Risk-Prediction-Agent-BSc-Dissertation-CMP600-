<?php
class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): self {
        $label = $label ?: $field;
        if (empty(trim($this->data[$field] ?? ''))) {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if (strlen($value) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if ($value && !is_numeric($value)) {
            $this->errors[$field] = "{$label} must be a number.";
        }
        return $this;
    }

    public function min(string $field, int $min, string $label = ''): self {
        $label = $label ?: $field;
        $value = (int)($this->data[$field] ?? 0);
        if ($value < $min) {
            $this->errors[$field] = "{$label} must be at least {$min}.";
        }
        return $this;
    }

    public function max(string $field, int $max, string $label = ''): self {
        $label = $label ?: $field;
        $value = (int)($this->data[$field] ?? 0);
        if ($value > $max) {
            $this->errors[$field] = "{$label} must be at most {$max}.";
        }
        return $this;
    }

    public function date(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if ($value && !strtotime($value)) {
            $this->errors[$field] = "{$label} must be a valid date.";
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label = ''): self {
        $label = $label ?: $field;
        if (($this->data[$field] ?? '') !== ($this->data[$otherField] ?? '')) {
            $this->errors[$field] = "{$label} does not match.";
        }
        return $this;
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): string {
        return reset($this->errors) ?: '';
    }
}
