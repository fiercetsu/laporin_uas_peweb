<?php
declare(strict_types=1);

namespace App\Utils;

class Response
{
    public static function json(array $body, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $message = 'Berhasil.', int $code = 200): void
    {
        self::json(['status' => 'success', 'message' => $message, 'data' => $data], $code);
    }

    public static function created($data = null, string $message = 'Data berhasil dibuat.'): void
    {
        self::success($data, $message, 201);
    }

    public static function error(string $message, int $code = 400, array $errors = []): void
    {
        $body = ['status' => 'error', 'message' => $message];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }
        self::json($body, $code);
    }

    public static function validationError(array $errors): void
    {
        self::error('Validasi gagal.', 422, $errors);
    }

    public static function unauthorized(string $message = 'Silakan login terlebih dahulu.'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Akses ditolak.'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Data tidak ditemukan.'): void
    {
        self::error($message, 404);
    }

    public static function serverError(string $message = 'Terjadi kesalahan server.', array $errors = []): void
    {
        self::error($message, 500, $errors);
    }
}
