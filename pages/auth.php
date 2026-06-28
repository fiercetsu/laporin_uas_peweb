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

    $errors = [];
    $success = '';
    if ($path === '/login' && $method === 'POST') {
        unset($_SESSION['session_error']);
    }

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
            } elseif ($path === '/profil') {
                [$errors, $success] = processProfileForm();
            }
        }
    } catch (Throwable $e) {
        $errors = ['Gagal memproses permintaan: ' . $e->getMessage()];
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

    if ($path === '/' || $path === '/login') {
        if (!empty($_SESSION['auth_user'])) {
            redirectTo('/dashboard');
        }
        if (!empty($_SESSION['session_error'])) {
            unset($_SESSION['session_error']);
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
    } elseif ($path === '/profil') {
        renderProfilePage($errors, $success);
    } elseif ($path === '/laporan-pdf') {
        renderLaporanPdfPage();
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
