<?php
declare(strict_types=1);

use App\Errors\ErrorHandler;
use App\Services\Admin\DashboardService as AdminDashboardService;
use App\Services\Admin\KategoriService as AdminKategoriService;
use App\Services\Admin\KonfigurasiService as AdminKonfigurasiService;
use App\Services\Admin\LaporanService as AdminLaporanService;
use App\Services\Admin\PengumumanService as AdminPengumumanService;
use App\Services\Admin\UsersService as AdminUsersService;
use App\Services\Petugas\DashboardService as PetugasDashboardService;
use App\Services\Petugas\TugasService as PetugasTugasService;
use App\Services\Rt\DashboardService as RtDashboardService;
use App\Services\Rt\LaporanService as RtLaporanService;
use App\Services\Rt\PengumumanService as RtPengumumanService;
use App\Services\Rt\PetugasService as RtPetugasService;
use App\Services\Users\GpsService as UsersGpsService;
use App\Services\Users\LaporanService as UsersLaporanService;
use App\Services\Users\LoginService as UsersLoginService;
use App\Services\Users\LogoutService as UsersLogoutService;
use App\Services\Users\NotificationService as UsersNotificationService;
use App\Services\Users\ProfileService as UsersProfileService;
use App\Services\Users\PublicDataService as UsersPublicDataService;
use App\Services\Users\RefreshService as UsersRefreshService;
use App\Services\Users\RegisterService as UsersRegisterService;

$routes = [
    ['POST', '/auth/register', UsersRegisterService::class, 'handle', ['post']],
    ['POST', '/auth/login', UsersLoginService::class, 'handle', ['post']],
    ['POST', '/auth/refresh', UsersRefreshService::class, 'handle', ['post']],
    ['POST', '/auth/logout', UsersLogoutService::class, 'handle', ['post']],

    ['GET', '/users/me', UsersProfileService::class, 'me'],
    ['PUT', '/users/me', UsersProfileService::class, 'update', ['post']],
    ['GET', '/users/categories', UsersPublicDataService::class, 'categories'],
    ['GET', '/users/pengumuman', UsersPublicDataService::class, 'announcements'],
    ['GET', '/users/notifikasi', UsersNotificationService::class, 'list'],
    ['PATCH', '/users/notifikasi/{id}/read', UsersNotificationService::class, 'markRead', ['id']],

    ['POST', '/gps/location', UsersGpsService::class, 'update', ['post']],
    ['GET', '/gps/location', UsersGpsService::class, 'current'],
    ['GET', '/gps/locations', UsersGpsService::class, 'list'],

    ['GET', '/laporan', UsersLaporanService::class, 'index'],
    ['POST', '/laporan', UsersLaporanService::class, 'create', ['post', 'files']],
    ['GET', '/laporan/{id}', UsersLaporanService::class, 'show', ['id']],
    ['DELETE', '/laporan/{id}', UsersLaporanService::class, 'cancel', ['id']],
    ['POST', '/laporan/{id}/rating', UsersLaporanService::class, 'rating', ['id', 'post']],

    ['GET', '/admin/dashboard', AdminDashboardService::class, 'handle'],
    ['GET', '/admin/users', AdminUsersService::class, 'list'],
    ['PATCH', '/admin/users/{id}/status', AdminUsersService::class, 'updateStatus', ['id', 'post']],
    ['POST', '/admin/petugas', AdminUsersService::class, 'createPetugas', ['post']],
    ['GET', '/admin/laporan', AdminLaporanService::class, 'list'],
    ['PATCH', '/admin/laporan/{id}/verify', AdminLaporanService::class, 'verify', ['id', 'post']],
    ['PATCH', '/admin/laporan/{id}/reject', AdminLaporanService::class, 'reject', ['id', 'post']],
    ['PATCH', '/admin/laporan/{id}/assign', AdminLaporanService::class, 'assign', ['id', 'post']],
    ['GET', '/admin/konfigurasi', AdminKonfigurasiService::class, 'list'],
    ['PUT', '/admin/konfigurasi', AdminKonfigurasiService::class, 'update', ['post']],
    ['GET', '/admin/kategori', AdminKategoriService::class, 'list'],
    ['POST', '/admin/kategori', AdminKategoriService::class, 'create', ['post']],
    ['PUT', '/admin/kategori/{id}', AdminKategoriService::class, 'update', ['id', 'post']],
    ['POST', '/admin/pengumuman', AdminPengumumanService::class, 'create', ['post']],

    ['GET', '/petugas/dashboard', PetugasDashboardService::class, 'handle'],
    ['GET', '/petugas/tugas', PetugasTugasService::class, 'list'],
    ['GET', '/petugas/tugas/{id}', PetugasTugasService::class, 'show', ['id']],
    ['PATCH', '/petugas/tugas/{id}/mulai', PetugasTugasService::class, 'mulai', ['id']],
    ['POST', '/petugas/tugas/{id}/progress', PetugasTugasService::class, 'progress', ['id', 'post', 'files']],
    ['PATCH', '/petugas/tugas/{id}/tindak-lanjut', PetugasTugasService::class, 'tindakLanjut', ['id', 'post']],
    ['POST', '/petugas/tugas/{id}/selesai', PetugasTugasService::class, 'selesai', ['id', 'post', 'files']],

    ['GET', '/rt/dashboard', RtDashboardService::class, 'handle'],
    ['GET', '/rt/laporan', RtLaporanService::class, 'list'],
    ['GET', '/rt/petugas', RtPetugasService::class, 'list'],
    ['PATCH', '/rt/pengumuman/{id}/publish', RtPengumumanService::class, 'publish', ['id']],
    ['PATCH', '/rt/pengumuman/{id}/unpublish', RtPengumumanService::class, 'unpublish', ['id']],
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
        [$routeMethod, $routePath, $serviceClass, $action] = $route;
        $argumentTypes = $route[4] ?? [];
        $pattern = '#^' . preg_replace('#\{id\}#', '(\d+)', $routePath) . '$#';
        if (!preg_match($pattern, $uri, $matches)) {
            continue;
        }
        $pathMatched = true;
        if ($method !== $routeMethod) {
            continue;
        }
        if (!class_exists($serviceClass) || !method_exists($serviceClass, $action)) {
            throw new RuntimeException("Route service tidak valid: {$serviceClass}::{$action}");
        }
        $service = new $serviceClass();
        $service->$action(...routeArguments($argumentTypes, isset($matches[1]) ? (int)$matches[1] : null));
        return;
    }

    if ($pathMatched) {
        ErrorHandler::methodNotAllowed('Method tidak didukung untuk endpoint ini.');
    }
    ErrorHandler::notFound('Endpoint tidak ditemukan.');
}

function routeArguments(array $types, ?int $id): array
{
    $arguments = [];

    foreach ($types as $type) {
        if ($type === 'id') {
            if ($id === null) {
                throw new RuntimeException('ID route tidak ditemukan.');
            }
            $arguments[] = $id;
            continue;
        }

        if ($type === 'post') {
            $arguments[] = $_POST;
            continue;
        }

        if ($type === 'files') {
            $arguments[] = $_FILES;
            continue;
        }

        throw new RuntimeException("Argumen route tidak dikenal: {$type}");
    }

    return $arguments;
}

return $routes;
