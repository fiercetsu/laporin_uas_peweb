<?php
declare(strict_types=1);

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        throw new RuntimeException('Token form tidak valid. Silakan refresh halaman.');
    }
}

function redirectTo(string $path): void
{
    header('Location: ' . urlFor($path), true, 302);
    exit;
}

function urlFor(string $path): string
{
    $pageAliases = [
        '/login' => '/handlers/features/login.php',
        '/register' => '/handlers/features/register.php',
        '/dashboard' => '/handlers/features/dashboard.php',
        '/logout' => '/handlers/features/logout.php',
        '/laporan' => '/handlers/features/laporan.php',
        '/laporan-saya' => '/handlers/users/laporan-saya.php',
        '/edit-laporan' => '/handlers/features/edit-laporan.php',
        '/hapus-laporan' => '/handlers/features/hapus-laporan.php',
        '/admin-users' => '/handlers/admin/admin-users.php',
        '/admin-laporan' => '/handlers/admin/admin-laporan.php',
        '/rt-darurat' => '/handlers/rt/rt-darurat.php',
        '/rt-monitoring' => '/handlers/rt/rt-monitoring.php',
        '/petugas-tugas' => '/handlers/petugas/petugas-tugas.php',
        '/petugas-riwayat' => '/handlers/petugas/petugas-riwayat.php',
        '/reset-password' => '/handlers/features/reset-password.php',
    ];

    $path = $pageAliases[$path] ?? $path;
    $base = basePath();
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function basePath(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    
    // Jika diakses melalui script di dalam subfolder handlers,
    // kita bersihkan sub-path '/handlers' agar mendapatkan root base proyek.
    $handlersPos = strpos($base, '/handlers');
    if ($handlersPos !== false) {
        $base = substr($base, 0, $handlersPos);
    }
    
    return $base === '/' ? '' : $base;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function selected(string $field, string $value, string $default = ''): string
{
    $current = (string)($_POST[$field] ?? $default);
    return $current === $value ? 'selected' : '';
}

function old(string $field, string $default = ''): string
{
    return e((string)($_POST[$field] ?? $default));
}

function formatDashboardDate(string $value): string
{
    if ($value === '') {
        return '-';
    }

    try {
        return (new DateTime($value))->format('d M Y H:i');
    } catch (Throwable $e) {
        return $value;
    }
}

function nullableInput(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function isValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}
