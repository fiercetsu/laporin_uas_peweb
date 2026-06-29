<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/helpers.php';
require_once __DIR__ . '/auth/db_helpers.php';
require_once __DIR__ . '/auth/processors/auth_processors.php';
require_once __DIR__ . '/auth/processors/laporan_processors.php';
require_once __DIR__ . '/auth/processors/admin_processors.php';
require_once __DIR__ . '/auth/processors/rt_processors.php';
require_once __DIR__ . '/auth/processors/petugas_processors.php';
require_once __DIR__ . '/auth/processors/profile_processors.php';

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
        '/petugas-riwayat', '/profil', '/laporan-pdf', '/reset-password', '/session-ping'
    ], true);
}

function handleWebAuthPage(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = authCurrentPath();
    $publicAuthPaths = ['/', '/login', '/register', '/reset-password'];
    $isPublicAuthPath = in_array($path, $publicAuthPaths, true);

    // Check if user is logged in, and verify their active web session token
    if (!empty($_SESSION['auth_user']) && $path !== '/logout') {
        if ($isPublicAuthPath && $method === 'POST') {
            unset($_SESSION['auth_user'], $_SESSION['session_error']);
        } else {
            $userId = (int)($_SESSION['auth_user']['id'] ?? 0);
            $sessionId = session_id();

            $db = \App\Db\Database::getInstance();
            $db->query("DELETE FROM user_sessions WHERE expired_at < NOW()");
            $activeSession = $db->query(
                "SELECT id FROM user_sessions WHERE user_id = ? AND session_token = ? AND is_active = 1 AND expired_at >= NOW() LIMIT 1",
                [$userId, $sessionId]
            )->fetch();

            if (!$activeSession) {
                $db->query("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?", [$userId, $sessionId]);
                unset($_SESSION['auth_user']);

                if ($path === '/session-ping') {
                    http_response_code(401);
                    return;
                }

                if ($isPublicAuthPath) {
                    unset($_SESSION['session_error']);
                } else {
                    $_SESSION['session_error'] = 'Sesi Anda telah berakhir karena tidak ada aktivitas atau login di perangkat lain.';
                    redirectTo('/login');
                }
            } else {
                $db->query(
                    "UPDATE user_sessions SET expired_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?",
                    [webSessionIdleMinutes(), (int)$activeSession['id']]
                );
            }
        }
    }

    if ($path === '/session-ping') {
        if (empty($_SESSION['auth_user'])) {
            http_response_code(401);
            return;
        }

        http_response_code(204);
        return;
    }

    // Hapus laporan — POST-only, tidak punya halaman sendiri
    if ($path === '/hapus-laporan') {
        if ($method === 'POST') {
            try {
                verifyCsrfToken();
                processDeleteLaporanForm();
            } catch (Throwable $e) {
                $_SESSION['flash'] = ['errors' => ['Gagal menghapus laporan: ' . $e->getMessage()], 'success' => ''];
            }
        }
        redirectTo('/laporan-saya');
    }

    if ($path === '/logout') {
        if (!empty($_SESSION['auth_user'])) {
            $db = \App\Db\Database::getInstance();
            $db->query("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?", [
                (int)$_SESSION['auth_user']['id'],
                session_id()
            ]);
            unset($_SESSION['auth_user']);
        }
        redirectTo('/login');
    }

    // Redirect logged-in users dari halaman publik
    if (in_array($path, ['/', '/login', '/register', '/reset-password'], true) && !empty($_SESSION['auth_user'])) {
        redirectTo('/dashboard');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Page Script dispatch — setiap halaman handle POST + render sendiri
    // ═══════════════════════════════════════════════════════════════════
    header('Content-Type: text/html; charset=UTF-8');

    if ($path === '/' || $path === '/login') {
        require __DIR__ . '/login.php';
    } elseif ($path === '/register') {
        require __DIR__ . '/register.php';
    } elseif ($path === '/reset-password') {
        require __DIR__ . '/reset_password.php';
    } elseif ($path === '/dashboard') {
        require __DIR__ . '/dashboard.php';
    } elseif ($path === '/laporan') {
        require __DIR__ . '/laporan.php';
    } elseif ($path === '/laporan-saya') {
        require __DIR__ . '/users/laporan_saya.php';
    } elseif ($path === '/edit-laporan') {
        require __DIR__ . '/users/laporan_edit.php';
    } elseif ($path === '/admin-users') {
        require __DIR__ . '/admin/admin_users.php';
    } elseif ($path === '/admin-laporan') {
        require __DIR__ . '/admin/admin_laporan.php';
    } elseif ($path === '/rt-darurat') {
        require __DIR__ . '/rt/rt_darurat.php';
    } elseif ($path === '/rt-monitoring') {
        require __DIR__ . '/rt/rt_monitoring.php';
    } elseif ($path === '/petugas-tugas') {
        require __DIR__ . '/petugas/petugas_tugas.php';
    } elseif ($path === '/petugas-riwayat') {
        require __DIR__ . '/petugas/petugas_riwayat.php';
    } elseif ($path === '/profil') {
        require __DIR__ . '/profile.php';
    } elseif ($path === '/laporan-pdf') {
        require __DIR__ . '/laporan_pdf.php';
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
        '/handlers/features/profil.php' => '/profil',
        '/handlers/features/laporan-pdf.php' => '/laporan-pdf',
        '/handlers/features/reset-password.php' => '/reset-password',
        '/handlers/features/session-ping.php' => '/session-ping',
    ];

    return $aliases[$path] ?? $path;
}
