<?php
declare(strict_types=1);

namespace App\Utils;

class JwtHelper
{
    private string $secret;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'dev-local-jwt-secret-change-before-production-2026';
    }

    public function generateAccessToken(array $payload): string
    {
        $now = time();
        return $this->encode($payload + [
            'iat' => $now,
            'exp' => $now + ((int)($_ENV['JWT_EXPIRE_MINUTES'] ?? 60) * 60),
            'type' => 'access',
        ]);
    }

    public function generateRefreshToken(int $userId): string
    {
        $now = time();
        return $this->encode([
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + ((int)($_ENV['JWT_REFRESH_EXPIRE_DAYS'] ?? 7) * 86400),
            'type' => 'refresh',
        ]);
    }

    public function verify(string $token, string $type = 'access'): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Format token tidak valid.');
        }

        [$header, $payload, $signature] = $parts;
        if (!hash_equals($this->sign($header . '.' . $payload), $signature)) {
            throw new \InvalidArgumentException('Signature token tidak valid.');
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($claims) || ($claims['type'] ?? '') !== $type) {
            throw new \InvalidArgumentException('Tipe token tidak valid.');
        }

        if (!isset($claims['exp']) || time() > (int)$claims['exp']) {
            throw new \RuntimeException('Token kedaluwarsa.');
        }

        return $claims;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($payload));
        return $header . '.' . $body . '.' . $this->sign($header . '.' . $body);
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $data .= str_repeat('=', (4 - strlen($data) % 4) % 4);
        return base64_decode($data) ?: '';
    }
}
