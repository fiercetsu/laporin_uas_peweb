<?php
declare(strict_types=1);

use App\Errors\ErrorHandler;

$routes = [
    ['POST', '/auth/register', \App\Controllers\Users\UsersController::class, 'register'],
    ['POST', '/auth/login', \App\Controllers\Users\UsersController::class, 'login'],
    ['POST', '/auth/refresh', \App\Controllers\Users\UsersController::class, 'refresh'],
    ['POST', '/auth/logout', \App\Controllers\Users\UsersController::class, 'logout'],

    ['GET', '/users/me', \App\Controllers\Users\UsersController::class, 'me'],
    ['PUT', '/users/me', \App\Controllers\Users\UsersController::class, 'updateMe'],
    ['GET', '/users/categories', \App\Controllers\Users\UsersController::class, 'categories'],
    ['GET', '/users/pengumuman', \App\Controllers\Users\UsersController::class, 'announcements'],
    ['GET', '/users/notifikasi', \App\Controllers\Users\UsersController::class, 'notifications'],
    ['PATCH', '/users/notifikasi/{id}/read', \App\Controllers\Users\UsersController::class, 'readNotification', true],

    ['POST', '/gps/location', \App\Controllers\Users\UsersController::class, 'gpsUpdate'],
    ['GET', '/gps/location', \App\Controllers\Users\UsersController::class, 'gpsCurrent'],
    ['GET', '/gps/locations', \App\Controllers\Users\UsersController::class, 'gpsList'],

    ['GET', '/laporan', \App\Controllers\Users\UsersController::class, 'laporanIndex'],
    ['POST', '/laporan', \App\Controllers\Users\UsersController::class, 'laporanCreate'],
    ['GET', '/laporan/{id}', \App\Controllers\Users\UsersController::class, 'laporanShow', true],
    ['DELETE', '/laporan/{id}', \App\Controllers\Users\UsersController::class, 'laporanCancel', true],
    ['POST', '/laporan/{id}/rating', \App\Controllers\Users\UsersController::class, 'laporanRating', true],

    ['GET', '/admin/dashboard', \App\Controllers\Admin\AdminController::class, 'dashboard'],
    ['GET', '/admin/users', \App\Controllers\Admin\AdminController::class, 'users'],
    ['PATCH', '/admin/users/{id}/status', \App\Controllers\Admin\AdminController::class, 'updateUserStatus', true],
    ['POST', '/admin/petugas', \App\Controllers\Admin\AdminController::class, 'createPetugas'],
    ['GET', '/admin/laporan', \App\Controllers\Admin\AdminController::class, 'laporan'],
    ['PATCH', '/admin/laporan/{id}/verify', \App\Controllers\Admin\AdminController::class, 'verifyLaporan', true],
    ['PATCH', '/admin/laporan/{id}/reject', \App\Controllers\Admin\AdminController::class, 'rejectLaporan', true],
    ['PATCH', '/admin/laporan/{id}/assign', \App\Controllers\Admin\AdminController::class, 'assignLaporan', true],
    ['GET', '/admin/konfigurasi', \App\Controllers\Admin\AdminController::class, 'konfigurasi'],
    ['PUT', '/admin/konfigurasi', \App\Controllers\Admin\AdminController::class, 'updateKonfigurasi'],
    ['GET', '/admin/kategori', \App\Controllers\Admin\AdminController::class, 'kategori'],
    ['POST', '/admin/kategori', \App\Controllers\Admin\AdminController::class, 'createKategori'],
    ['PUT', '/admin/kategori/{id}', \App\Controllers\Admin\AdminController::class, 'updateKategori', true],
    ['POST', '/admin/pengumuman', \App\Controllers\Admin\AdminController::class, 'pengumuman'],

    ['GET', '/petugas/dashboard', \App\Controllers\Petugas\PetugasController::class, 'dashboard'],
    ['GET', '/petugas/tugas', \App\Controllers\Petugas\PetugasController::class, 'tugas'],
    ['GET', '/petugas/tugas/{id}', \App\Controllers\Petugas\PetugasController::class, 'show', true],
    ['PATCH', '/petugas/tugas/{id}/mulai', \App\Controllers\Petugas\PetugasController::class, 'mulai', true],
    ['POST', '/petugas/tugas/{id}/progress', \App\Controllers\Petugas\PetugasController::class, 'progress', true],
    ['PATCH', '/petugas/tugas/{id}/tindak-lanjut', \App\Controllers\Petugas\PetugasController::class, 'tindakLanjut', true],
    ['POST', '/petugas/tugas/{id}/selesai', \App\Controllers\Petugas\PetugasController::class, 'selesai', true],

    ['GET', '/rt/dashboard', \App\Controllers\Rt\RtController::class, 'dashboard'],
    ['GET', '/rt/laporan', \App\Controllers\Rt\RtController::class, 'laporan'],
    ['GET', '/rt/petugas', \App\Controllers\Rt\RtController::class, 'petugas'],
    ['PATCH', '/rt/pengumuman/{id}/publish', \App\Controllers\Rt\RtController::class, 'publishPengumuman', true],
    ['PATCH', '/rt/pengumuman/{id}/unpublish', \App\Controllers\Rt\RtController::class, 'unpublishPengumuman', true],
];

function dispatch(array $routes): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base !== '' && $base !== '/' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    $uri = rtrim('/' . ltrim($uri, '/'), '/') ?: '/';

    if (strpos($uri, '..') !== false || strpos($uri, "\0") !== false || !preg_match('#^[/a-zA-Z0-9\-_]+$#', $uri)) {
        ErrorHandler::badRequest('URL tidak valid.');
    }

    $pathMatched = false;
    foreach ($routes as $route) {
        [$routeMethod, $routePath, $class, $action] = $route;
        $requiresId = $route[4] ?? false;
        $pattern = '#^' . preg_replace('#\{id\}#', '(\d+)', $routePath) . '$#';
        if (!preg_match($pattern, $uri, $matches)) {
            continue;
        }
        $pathMatched = true;
        if ($method !== $routeMethod) {
            continue;
        }
        if (!class_exists($class) || !method_exists($class, $action)) {
            throw new RuntimeException("Route handler tidak valid: {$class}::{$action}");
        }
        $controller = new $class();
        $requiresId ? $controller->$action((int)$matches[1]) : $controller->$action();
        return;
    }

    if ($pathMatched) {
        ErrorHandler::methodNotAllowed('Method tidak didukung untuk endpoint ini.');
    }
    ErrorHandler::notFound('Endpoint tidak ditemukan.');
}

return $routes;
