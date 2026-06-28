<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $staticPath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $requestedPath);
    $extension = strtolower(pathinfo($staticPath, PATHINFO_EXTENSION));

    if ($extension !== 'php' && is_file($staticPath)) {
        return false;
    }
}

$backendPath = __DIR__ . '/backend';

foreach (['.env', '.env_development'] as $envName) {
    $envFile = $backendPath . '/' . $envName;
    if (!is_file($envFile)) {
        continue;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

spl_autoload_register(static function (string $class) use ($backendPath): void {
    $map = [
        'App\\Db\\Database' => $backendPath . '/db/koneksi.php',
        'App\\Errors\\ApiException' => $backendPath . '/errors/ApiException.php',
        'App\\Errors\\ErrorHandler' => $backendPath . '/errors/ErrorHandler.php',
        'App\\Middleware\\AuthMiddleware' => $backendPath . '/auth/auth.php',
        'App\\Middleware\\SecurityMiddleware' => $backendPath . '/auth/security.php',
        'App\\Utils\\Response' => $backendPath . '/utils/response.php',
        'App\\Utils\\Validator' => $backendPath . '/utils/validation.php',
        'App\\Utils\\JwtHelper' => $backendPath . '/utils/jwtHelper.php',
        'App\\Utils\\CodeGenerator' => $backendPath . '/utils/codeGenerator.php',
        'App\\Utils\\FileUpload' => $backendPath . '/utils/fileUpload.php',
        'App\\Services\\Users\\RegisterService' => $backendPath . '/services/users/register.php',
        'App\\Services\\Users\\LoginService' => $backendPath . '/services/users/login.php',
        'App\\Services\\Users\\RefreshService' => $backendPath . '/services/users/refresh.php',
        'App\\Services\\Users\\LogoutService' => $backendPath . '/services/users/logout.php',
        'App\\Services\\Users\\ProfileService' => $backendPath . '/services/users/profile.php',
        'App\\Services\\Users\\PublicDataService' => $backendPath . '/services/users/publicData.php',
        'App\\Services\\Users\\NotificationService' => $backendPath . '/services/users/notification.php',
        'App\\Services\\Users\\LaporanService' => $backendPath . '/services/users/laporan.php',
        'App\\Services\\Users\\GpsService' => $backendPath . '/services/users/gps.php',
        'App\\Services\\Admin\\DashboardService' => $backendPath . '/services/admin/dashboard.php',
        'App\\Services\\Admin\\UsersService' => $backendPath . '/services/admin/users.php',
        'App\\Services\\Admin\\LaporanService' => $backendPath . '/services/admin/laporan.php',
        'App\\Services\\Admin\\KonfigurasiService' => $backendPath . '/services/admin/konfigurasi.php',
        'App\\Services\\Admin\\KategoriService' => $backendPath . '/services/admin/kategori.php',
        'App\\Services\\Admin\\PengumumanService' => $backendPath . '/services/admin/pengumuman.php',
        'App\\Services\\Petugas\\DashboardService' => $backendPath . '/services/petugas/dashboard.php',
        'App\\Services\\Petugas\\TugasService' => $backendPath . '/services/petugas/tugas.php',
        'App\\Services\\Rt\\DashboardService' => $backendPath . '/services/rt/dashboard.php',
        'App\\Services\\Rt\\LaporanService' => $backendPath . '/services/rt/laporan.php',
        'App\\Services\\Rt\\PetugasService' => $backendPath . '/services/rt/petugas.php',
        'App\\Services\\Rt\\PengumumanService' => $backendPath . '/services/rt/pengumuman.php',
    ];

    if (isset($map[$class]) && is_file($map[$class])) {
        require_once $map[$class];
    }
});

require_once __DIR__ . '/pages/auth.php';

if (isWebAuthPage()) {
    handleWebAuthPage();
    exit;
}

\App\Errors\ErrorHandler::register();
\App\Middleware\SecurityMiddleware::apply();

$routes = require $backendPath . '/routes/api.php';
dispatch($routes);
