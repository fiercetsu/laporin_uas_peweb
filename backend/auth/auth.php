<?php
// ================================================================
//  middleware/AuthMiddleware.php
//  Guard JWT — verifikasi token, validasi role, block direct URL
// ================================================================

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\JwtHelper;
use App\Utils\Response;
use App\Db\Database;

class AuthMiddleware
{
    private JwtHelper $jwt;
    private Database  $db;

    public function __construct()
    {
        $this->jwt = new JwtHelper();
        $this->db  = Database::getInstance();
    }

    /**
     * Verifikasi JWT dari Authorization header
     * Return payload jika valid
     */
    public function requireAuth(): array
    {
        $token = $this->extractToken();

        if ($token === null) {
            Response::unauthorized('Token autentikasi tidak ditemukan. Silakan login.');
        }

        try {
            $payload = $this->jwt->verify($token, 'access');
        } catch (\InvalidArgumentException $e) {
            Response::unauthorized('Token tidak valid: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            Response::unauthorized($e->getMessage());
        }

        // Periksa apakah user masih aktif di DB
        // (cegah token lama dipakai setelah akun dinonaktifkan)
        $user = $this->db->query(
            "SELECT id, role, status_akun FROM users WHERE id = ? LIMIT 1",
            [$payload['sub']]
        )->fetch();

        if (!$user) {
            Response::unauthorized('Akun tidak ditemukan.');
        }

        if ($user['status_akun'] !== 'aktif') {
            Response::forbidden('Akun Anda tidak aktif. Hubungi admin.');
        }

        // Pastikan role di token sesuai role di DB (cegah privilege escalation)
        if (($payload['role'] ?? '') !== $user['role']) {
            Response::forbidden('Role token tidak sesuai. Silakan login ulang.');
        }

        return $payload;
    }

    /**
     * Guard role — throw 403 jika role tidak diizinkan
     * Contoh: $this->requireRole(['admin','rt'])
     */
    public function requireRole(array $allowedRoles, ?array $payload = null): array
    {
        $payload = $payload ?? $this->requireAuth();

        if (!in_array($payload['role'] ?? '', $allowedRoles, true)) {
            Response::forbidden(
                'Aksi ini memerlukan role: ' . implode(' / ', $allowedRoles) . '.'
            );
        }

        return $payload;
    }

    /**
     * Shortcut guards per role
     */
    public function requireWarga(): array    { return $this->requireRole(['warga']); }
    public function requirePetugas(): array  { return $this->requireRole(['petugas']); }
    public function requireAdmin(): array    { return $this->requireRole(['admin', 'rt']); }
    public function requireRt(): array       { return $this->requireRole(['rt']); }
    public function requireStaff(): array    { return $this->requireRole(['admin', 'rt', 'petugas']); }

    /**
     * Ekstrak Bearer token dari header Authorization
     */
    private function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? '';

        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            $token = trim($matches[1]);
            // Validasi panjang dasar (mencegah payload bomb)
            if (strlen($token) > 2048) {
                return null;
            }
            return $token;
        }

        return null;
    }
}
