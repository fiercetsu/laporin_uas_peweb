<?php
declare(strict_types=1);

namespace App\Utils;

class Validator
{
    private array $data = [];
    private array $errors = [];

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $cleanKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key);
            $this->data[$cleanKey] = is_array($value) ? $value : $this->clean((string)$value);
        }
    }

    public function required(string $field, string $label = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value === '' || $value === null) {
            $this->errors[$field] = ($label ?: $field) . ' wajib diisi.';
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = ($label ?: $field) . " minimal {$min} karakter.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field] = ($label ?: $field) . " maksimal {$max} karakter.";
        }
        return $this;
    }

    public function email(string $field = 'email'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Email tidak valid.';
        }
        return $this;
    }

    public function nik(string $field = 'nik'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && !preg_match('/^\d{16}$/', $value)) {
            $this->errors[$field] = 'NIK harus 16 digit angka.';
        }
        return $this;
    }

    public function noHp(string $field = 'no_hp'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && !preg_match('/^(\+62|62|0)8[1-9][0-9]{6,11}$/', $value)) {
            $this->errors[$field] = 'Nomor HP tidak valid.';
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = ($label ?: $field) . ' harus berupa angka.';
        }
        return $this;
    }

    public function inList(string $field, array $list, string $label = ''): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && !in_array($value, $list, true)) {
            $this->errors[$field] = ($label ?: $field) . ' tidak valid.';
        }
        return $this;
    }

    public function date(string $field, string $label = ''): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $this->errors[$field] = ($label ?: $field) . ' harus format Y-m-d.';
            }
        }
        return $this;
    }

    public function password(string $field = 'password'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && (mb_strlen($value) < 8 || !preg_match('/[A-Z]/', $value) || !preg_match('/[0-9]/', $value))) {
            $this->errors[$field] = 'Password minimal 8 karakter, mengandung huruf kapital dan angka.';
        }
        return $this;
    }

    public function latitude(string $field = 'latitude'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && (float)$value < -90 || $value !== '' && (float)$value > 90) {
            $this->errors[$field] = 'Latitude tidak valid.';
        }
        return $this;
    }

    public function longitude(string $field = 'longitude'): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && (float)$value < -180 || $value !== '' && (float)$value > 180) {
            $this->errors[$field] = 'Longitude tidak valid.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    private function clean(string $value): string
    {
        return htmlspecialchars(str_replace("\0", '', trim(strip_tags($value))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
