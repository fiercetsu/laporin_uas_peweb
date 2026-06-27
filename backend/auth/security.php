<?php
// ================================================================
//  middleware/SecurityMiddleware.php
//  CORS, Security Headers, CSRF token, Content-Type enforcement
// ================================================================

declare(strict_types=1);

namespace App\Middleware;

use App\Errors\ErrorHandler;

class SecurityMiddleware
{
    /**
     * Pasang semua header keamanan — panggil di index.php sebelum routing
     */
    public static function apply(): void
    {
        // ── Security Headers ──────────────────────────────────
        // Cegah MIME-type sniffing
        header('X-Content-Type-Options: nosniff');
        // Cegah clickjacking
        header('X-Frame-Options: DENY');
        // Aktifkan XSS filter bawaan browser
        header('X-XSS-Protection: 1; mode=block');
        // Paksa HTTPS (jika production)
        if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        // Content Security Policy
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        // Tidak kirim Referer ke pihak lain
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Semua response adalah JSON
        header('Content-Type: application/json; charset=UTF-8');
        // Hapus header yang mengekspos teknologi
        header_remove('X-Powered-By');

        // ── CORS ──────────────────────────────────────────────
        $allowedOrigins = self::getAllowedOrigins();
        $origin         = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        } elseif (($_ENV['APP_ENV'] ?? 'development') === 'development') {
            // Development: izinkan localhost semua port
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // ── Method Validation ─────────────────────────────────
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $validMethods, true)) {
            ErrorHandler::methodNotAllowed('Method tidak didukung.');
        }

        // ── JSON Body Parsing ─────────────────────────────────
        // Untuk request dengan Content-Type: application/json
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            if (!empty($rawBody)) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ErrorHandler::badRequest('Format JSON request tidak valid.');
                }
                if (!is_array($decoded)) {
                    ErrorHandler::badRequest('Body JSON harus berupa object atau array.');
                }
                // Merge ke $_POST agar controller bisa akses via $_POST
                $_POST = array_merge($_POST, $decoded);
            }
        }
    }

    /**
     * Validasi Content-Type untuk endpoint tertentu (non-upload)
     */
    public static function requireJson(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false &&
            strpos($contentType, 'multipart/form-data') === false) {
            ErrorHandler::unsupportedMediaType('Content-Type harus application/json atau multipart/form-data.');
        }
    }

    /**
     * Sanitasi nilai dalam $_GET — cegah injection dari URL params
     */
    public static function sanitizeQuery(): array
    {
        $clean = [];
        foreach ($_GET as $key => $value) {
            $k = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key);
            if (is_array($value)) {
                $clean[$k] = array_map(fn($v) => htmlspecialchars(strip_tags((string)$v), ENT_QUOTES, 'UTF-8'), $value);
            } else {
                $clean[$k] = htmlspecialchars(strip_tags((string)$value), ENT_QUOTES, 'UTF-8');
            }
        }
        return $clean;
    }

    /**
     * Validasi & cast parameter integer dari URL (mis. ID)
     * Cegah SQL injection via ID manipulation
     */
    public static function getIntParam(array $query, string $key, int $default = 0): int
    {
        $val = $query[$key] ?? $default;
        $int = filter_var($val, FILTER_VALIDATE_INT);
        if ($int === false || $int < 0) {
            ErrorHandler::badRequest("Parameter '{$key}' tidak valid.");
        }
        return (int)$int;
    }

    private static function getAllowedOrigins(): array
    {
        $origins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        if (empty($origins)) return [];
        return array_map('trim', explode(',', $origins));
    }
}
