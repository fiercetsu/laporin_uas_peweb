<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/helpers.php';
require_once __DIR__ . '/auth/db_helpers.php';
require_once __DIR__ . '/auth/processors/auth_processors.php';
require_once __DIR__ . '/auth/processors/laporan_processors.php';
require_once __DIR__ . '/auth/processors/admin_processors.php';
require_once __DIR__ . '/auth/processors/rt_processors.php';
require_once __DIR__ . '/auth/processors/petugas_processors.php';
require_once __DIR__ . '/auth/page_renderers.php';

function isWebAuthPage(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base !== '' && $base !== '/' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    $path = normalizeWebAuthPath($path);
    return in_array($path, [
        '/', '/login', '/register', '/dashboard', '/logout', '/laporan', 
        '/laporan-saya', '/edit-laporan', '/hapus-laporan', '/admin-users', 
        '/admin-laporan', '/rt-darurat', '/rt-monitoring', '/petugas-tugas', 
        '/petugas-riwayat', '/reset-password'
    ], true);
}

function handleWebAuthPage(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = authCurrentPath();
    $errors = [];
    $success = '';

    try {
        if ($method === 'POST') {
            verifyCsrfToken();

            if ($path === '/login') {
                [$errors, $success] = processLoginForm();
            } elseif ($path === '/register') {
                [$errors, $success] = processRegisterForm();
            } elseif ($path === '/reset-password') {
                [$errors, $success] = processResetPasswordForm();
            } elseif ($path === '/laporan') {
                [$errors, $success] = processLaporanForm();
            } elseif ($path === '/edit-laporan') {
                [$errors, $success] = processEditLaporanForm();
            } elseif ($path === '/hapus-laporan') {
                processDeleteLaporanForm();
            } elseif ($path === '/admin-users') {
                [$errors, $success] = processAdminUserStatusForm();
            } elseif ($path === '/admin-laporan') {
                [$errors, $success] = processAdminLaporanForm();
            } elseif ($path === '/rt-monitoring') {
                [$errors, $success] = processRtMonitoringForm();
            } elseif ($path === '/petugas-tugas') {
                [$errors, $success] = processPetugasTaskForm();
            }
        }
    } catch (Throwable $e) {
        $errors = ['Gagal memproses permintaan: ' . $e->getMessage()];
    }

    if ($path === '/logout') {
        unset($_SESSION['auth_user']);
        redirectTo('/login');
    }

    if ($path === '/' || $path === '/login') {
        if (!empty($_SESSION['auth_user'])) {
            redirectTo('/dashboard');
        }
        renderAuthPage('login', $errors, $success);
    } elseif ($path === '/register') {
        if (!empty($_SESSION['auth_user'])) {
            redirectTo('/dashboard');
        }
        renderAuthPage('register', $errors, $success);
    } elseif ($path === '/reset-password') {
        if (!empty($_SESSION['auth_user'])) {
            redirectTo('/dashboard');
        }
        renderResetPasswordPage($errors, $success);
    } elseif ($path === '/dashboard') {
        renderDashboardPage();
    } elseif ($path === '/laporan') {
        renderLaporanPage($errors, $success);
    } elseif ($path === '/laporan-saya') {
        renderLaporanSayaPage();
    } elseif ($path === '/edit-laporan') {
        renderEditLaporanPage($errors, $success);
    } elseif ($path === '/admin-users') {
        renderAdminUsersPage($errors, $success);
    } elseif ($path === '/admin-laporan') {
        renderAdminLaporanPage($errors, $success);
    } elseif ($path === '/rt-darurat') {
        renderRtDaruratPage();
    } elseif ($path === '/rt-monitoring') {
        renderRtMonitoringPage($errors, $success);
    } elseif ($path === '/petugas-tugas') {
        renderPetugasTugasPage($errors, $success);
    } elseif ($path === '/petugas-riwayat') {
        renderPetugasRiwayatPage();
    }
}

function authCurrentPath(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base !== '' && $base !== '/' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    return normalizeWebAuthPath($path);
}

function normalizeWebAuthPath(string $path): string
{
    $aliases = [
        '/handlers/features/login.php' => '/login',
        '/handlers/features/register.php' => '/register',
        '/handlers/features/dashboard.php' => '/dashboard',
        '/handlers/features/logout.php' => '/logout',
        '/handlers/features/laporan.php' => '/laporan',
        '/handlers/users/laporan-saya.php' => '/laporan-saya',
        '/handlers/features/edit-laporan.php' => '/edit-laporan',
        '/handlers/features/hapus-laporan.php' => '/hapus-laporan',
        '/handlers/admin/admin-users.php' => '/admin-users',
        '/handlers/admin/admin-laporan.php' => '/admin-laporan',
        '/handlers/rt/rt-darurat.php' => '/rt-darurat',
        '/handlers/rt/rt-monitoring.php' => '/rt-monitoring',
        '/handlers/petugas/petugas-tugas.php' => '/petugas-tugas',
        '/handlers/petugas/petugas-riwayat.php' => '/petugas-riwayat',
        '/handlers/features/reset-password.php' => '/reset-password',
    ];

    return $aliases[$path] ?? $path;
}
