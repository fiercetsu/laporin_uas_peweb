<?php
declare(strict_types=1);

namespace App\Errors;

use App\Utils\Response;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public static function handleException(\Throwable $e): void
    {
        error_log('[API] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if ($e instanceof ApiException) {
            Response::error($e->getMessage(), $e->getStatusCode(), $e->getErrors());
        }

        if (strtolower((string)($_ENV['APP_DEBUG'] ?? 'false')) === 'true') {
            Response::serverError($e->getMessage(), [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        Response::serverError();
    }

    public static function badRequest(string $message = 'Request tidak valid.'): void
    {
        throw new ApiException($message, 400);
    }

    public static function notFound(string $message = 'Endpoint tidak ditemukan.'): void
    {
        throw new ApiException($message, 404);
    }

    public static function methodNotAllowed(string $message = 'Method tidak didukung.'): void
    {
        throw new ApiException($message, 405);
    }

    public static function unsupportedMediaType(string $message = 'Content-Type tidak didukung.'): void
    {
        throw new ApiException($message, 415);
    }
}
