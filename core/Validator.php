<?php

namespace Core;

class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label = ''): self
    {
        $label = $label ?: ucfirst($field);
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = "El campo {$label} es obligatorio.";
        }
        return $this;
    }

    public function email(string $field, string $value, string $label = 'Correo electrónico'): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo {$label} no tiene un formato válido.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label = ''): self
    {
        $label = $label ?: ucfirst($field);
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "El campo {$label} no puede exceder {$max} caracteres.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst($field);
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field] = "El campo {$label} debe tener al menos {$min} caracteres.";
        }
        return $this;
    }

    public function matches(string $field, string $value, string $other, string $label = ''): self
    {
        $label = $label ?: ucfirst($field);
        if ($value !== $other) {
            $this->errors[$field] = "Los campos de contraseña no coinciden.";
        }
        return $this;
    }

    public function strongPassword(string $field, string $value): self
    {
        if (empty($value)) {
            return $this;
        }
        $errors = [];
        if (mb_strlen($value) < 8) {
            $errors[] = 'mínimo 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'al menos una mayúscula';
        }
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'al menos una minúscula';
        }
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'al menos un número';
        }
        if (!preg_match('/[\W_]/', $value)) {
            $errors[] = 'al menos un carácter especial';
        }
        if (!empty($errors)) {
            $this->errors[$field] = 'La contraseña debe contener: ' . implode(', ', $errors) . '.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }
}
