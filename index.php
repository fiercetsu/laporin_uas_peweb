<?php
declare(strict_types=1);

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
        'App\\Controllers\\Users\\UsersController' => $backendPath . '/controller/users/usersController.php',
        'App\\Controllers\\Admin\\AdminController' => $backendPath . '/controller/admin/adminController.php',
        'App\\Controllers\\Petugas\\PetugasController' => $backendPath . '/controller/petugas/petugasController.php',
        'App\\Controllers\\Rt\\RtController' => $backendPath . '/controller/rt/rtController.php',
    ];

    if (isset($map[$class]) && is_file($map[$class])) {
        require_once $map[$class];
    }
});

if (isWebAuthPage()) {
    handleWebAuthPage();
    exit;
}

\App\Errors\ErrorHandler::register();
\App\Middleware\SecurityMiddleware::apply();

$routes = require $backendPath . '/routes/api.php';
dispatch($routes);

function isWebAuthPage(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base !== '' && $base !== '/' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    $path = normalizeWebAuthPath($path);
    return in_array($path, ['/', '/login', '/register', '/dashboard', '/logout'], true);
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
            } elseif ($path === '/logout') {
                $_SESSION = [];
                session_destroy();
                redirectTo('/login');
            }
        }
    } catch (Throwable $e) {
        $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
    }

    if ($path === '/logout' && $method !== 'POST') {
        redirectTo('/login');
    }

    if ($path === '/dashboard') {
        renderDashboardPage();
        return;
    }

    renderAuthPage($path === '/register' ? 'register' : 'login', $errors, $success);
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
    $path = rtrim('/' . ltrim($path, '/'), '/') ?: '/';
    $aliases = [
        '/index.php' => '/',
        '/login.php' => '/login',
        '/register.php' => '/register',
        '/dashboard.php' => '/dashboard',
        '/logout.php' => '/logout',
    ];

    return $aliases[$path] ?? $path;
}

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

function processLoginForm(): array
{
    $nik = trim((string)($_POST['nik'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $errors = [];

    if (!preg_match('/^\d{16}$/', $nik)) {
        $errors[] = 'NIK harus 16 digit angka.';
    }

    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    }

    if ($errors !== []) {
        return [$errors, ''];
    }

    $db = \App\Db\Database::getInstance();
    $user = $db->query(
        "SELECT id, kode_user, nik, nama_lengkap, password_hash, role, status_akun
         FROM users WHERE nik = ? LIMIT 1",
        [$nik]
    )->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return [['NIK atau password salah.'], ''];
    }

    if ($user['status_akun'] !== 'aktif') {
        return [['Akun belum aktif atau sedang dinonaktifkan.'], ''];
    }

    unset($user['password_hash']);
    $_SESSION['auth_user'] = $user;
    redirectTo('/dashboard');
    return [[], ''];
}

function processRegisterForm(): array
{
    $input = [
        'nik' => trim((string)($_POST['nik'] ?? '')),
        'nama_lengkap' => trim((string)($_POST['nama_lengkap'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'no_hp' => trim((string)($_POST['no_hp'] ?? '')),
        'password' => (string)($_POST['password'] ?? ''),
        'password_confirm' => (string)($_POST['password_confirm'] ?? ''),
        'no_kk' => trim((string)($_POST['no_kk'] ?? '')),
        'no_rt' => trim((string)($_POST['no_rt'] ?? '')),
        'no_rw' => trim((string)($_POST['no_rw'] ?? '')),
        'alamat_lengkap' => trim((string)($_POST['alamat_lengkap'] ?? '')),
        'kelurahan' => trim((string)($_POST['kelurahan'] ?? '')),
        'kecamatan' => trim((string)($_POST['kecamatan'] ?? '')),
        'kota_kabupaten' => trim((string)($_POST['kota_kabupaten'] ?? '')),
        'tempat_lahir' => trim((string)($_POST['tempat_lahir'] ?? '')),
        'tanggal_lahir' => trim((string)($_POST['tanggal_lahir'] ?? '')),
        'jenis_kelamin' => trim((string)($_POST['jenis_kelamin'] ?? '')),
        'agama' => trim((string)($_POST['agama'] ?? '')),
        'status_perkawinan' => trim((string)($_POST['status_perkawinan'] ?? '')),
        'pekerjaan' => trim((string)($_POST['pekerjaan'] ?? '')),
        'status_tinggal' => trim((string)($_POST['status_tinggal'] ?? 'tetap')),
        'tanggal_pindah_masuk' => trim((string)($_POST['tanggal_pindah_masuk'] ?? '')),
    ];

    $errors = validateRegisterInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    $db = \App\Db\Database::getInstance();
    if ($db->query("SELECT id FROM users WHERE nik = ? LIMIT 1", [$input['nik']])->fetch()) {
        return [['NIK sudah terdaftar.'], ''];
    }

    $db->beginTransaction();
    try {
        $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
        $db->query(
            "INSERT INTO users (nik, nama_lengkap, email, no_hp, password_hash, role, status_akun)
             VALUES (?, ?, ?, ?, ?, 'warga', 'pending')",
            [
                $input['nik'],
                $input['nama_lengkap'],
                $input['email'] !== '' ? $input['email'] : null,
                $input['no_hp'] !== '' ? $input['no_hp'] : null,
                $hash,
            ]
        );

        $userId = (int)$db->lastInsertId();
        $db->query(
            "INSERT INTO profil_warga
             (user_id, no_kk, no_rt, no_rw, alamat_lengkap, kelurahan, kecamatan, kota_kabupaten,
              tempat_lahir, tanggal_lahir, jenis_kelamin, agama, status_perkawinan, pekerjaan,
              status_tinggal, tanggal_pindah_masuk)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                nullableInput($input['no_kk']),
                $input['no_rt'],
                $input['no_rw'],
                $input['alamat_lengkap'],
                nullableInput($input['kelurahan']),
                nullableInput($input['kecamatan']),
                nullableInput($input['kota_kabupaten']),
                nullableInput($input['tempat_lahir']),
                nullableInput($input['tanggal_lahir']),
                nullableInput($input['jenis_kelamin']),
                nullableInput($input['agama']),
                nullableInput($input['status_perkawinan']),
                nullableInput($input['pekerjaan']),
                $input['status_tinggal'] !== '' ? $input['status_tinggal'] : 'tetap',
                nullableInput($input['tanggal_pindah_masuk']),
            ]
        );

        $db->commit();
        $_POST = [];
        return [[], 'Registrasi berhasil. Akun menunggu verifikasi admin sebelum bisa login.'];
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function validateRegisterInput(array $input): array
{
    $errors = [];

    if (!preg_match('/^\d{16}$/', $input['nik'])) {
        $errors[] = 'NIK harus 16 digit angka.';
    }

    if ($input['no_kk'] !== '' && !preg_match('/^\d{16}$/', $input['no_kk'])) {
        $errors[] = 'No KK harus 16 digit angka.';
    }

    if (mb_strlen($input['nama_lengkap']) < 3 || mb_strlen($input['nama_lengkap']) > 150) {
        $errors[] = 'Nama lengkap harus 3 sampai 150 karakter.';
    }

    if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if ($input['no_hp'] !== '' && !preg_match('/^(\+62|62|0)8[1-9][0-9]{6,11}$/', $input['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid.';
    }

    if (mb_strlen($input['password']) < 8 || !preg_match('/[A-Z]/', $input['password']) || !preg_match('/[0-9]/', $input['password'])) {
        $errors[] = 'Password minimal 8 karakter, mengandung huruf kapital dan angka.';
    }

    if ($input['password'] !== $input['password_confirm']) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if ($input['no_rt'] === '') {
        $errors[] = 'No RT wajib diisi.';
    }

    if ($input['no_rw'] === '') {
        $errors[] = 'No RW wajib diisi.';
    }

    if ($input['alamat_lengkap'] === '') {
        $errors[] = 'Alamat lengkap wajib diisi.';
    }

    foreach (['kelurahan', 'kecamatan', 'kota_kabupaten', 'tempat_lahir', 'pekerjaan'] as $field) {
        if ($input[$field] !== '' && mb_strlen($input[$field]) > 100) {
            $errors[] = labelFor($field) . ' maksimal 100 karakter.';
        }
    }

    if ($input['agama'] !== '' && mb_strlen($input['agama']) > 30) {
        $errors[] = 'Agama maksimal 30 karakter.';
    }

    if ($input['tanggal_lahir'] !== '' && !isValidDate($input['tanggal_lahir'])) {
        $errors[] = 'Tanggal lahir harus format YYYY-MM-DD.';
    }

    if ($input['tanggal_pindah_masuk'] !== '' && !isValidDate($input['tanggal_pindah_masuk'])) {
        $errors[] = 'Tanggal pindah masuk harus format YYYY-MM-DD.';
    }

    if ($input['jenis_kelamin'] !== '' && !in_array($input['jenis_kelamin'], ['L', 'P'], true)) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }

    if ($input['status_perkawinan'] !== '' && !in_array($input['status_perkawinan'], ['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'], true)) {
        $errors[] = 'Status perkawinan tidak valid.';
    }

    if ($input['status_tinggal'] !== '' && !in_array($input['status_tinggal'], ['tetap', 'kontrak', 'kost', 'numpang'], true)) {
        $errors[] = 'Status tinggal tidak valid.';
    }

    return $errors;
}

function nullableInput(string $value): ?string
{
    return $value !== '' ? $value : null;
}

function isValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function labelFor(string $field): string
{
    $labels = [
        'kelurahan' => 'Kelurahan',
        'kecamatan' => 'Kecamatan',
        'kota_kabupaten' => 'Kota/Kabupaten',
        'tempat_lahir' => 'Tempat lahir',
        'pekerjaan' => 'Pekerjaan',
    ];

    return $labels[$field] ?? $field;
}

function renderAuthPage(string $mode, array $errors = [], string $success = ''): void
{
    header('Content-Type: text/html; charset=UTF-8');

    $isRegister = $mode === 'register';
    $title = $isRegister ? 'Register Warga' : 'Login';
    $action = urlFor($isRegister ? '/register.php' : '/login');
    $csrf = e(csrfToken());
    $nik = e((string)($_POST['nik'] ?? ''));
    $nama = e((string)($_POST['nama_lengkap'] ?? ''));
    $email = e((string)($_POST['email'] ?? ''));
    $noHp = e((string)($_POST['no_hp'] ?? ''));
    $noKk = e((string)($_POST['no_kk'] ?? ''));
    $noRt = e((string)($_POST['no_rt'] ?? ''));
    $noRw = e((string)($_POST['no_rw'] ?? ''));
    $alamat = e((string)($_POST['alamat_lengkap'] ?? ''));
    $kelurahan = e((string)($_POST['kelurahan'] ?? ''));
    $kecamatan = e((string)($_POST['kecamatan'] ?? ''));
    $kotaKabupaten = e((string)($_POST['kota_kabupaten'] ?? ''));
    $tempatLahir = e((string)($_POST['tempat_lahir'] ?? ''));
    $tanggalLahir = e((string)($_POST['tanggal_lahir'] ?? ''));
    $agama = e((string)($_POST['agama'] ?? ''));
    $pekerjaan = e((string)($_POST['pekerjaan'] ?? ''));
    $tanggalPindahMasuk = e((string)($_POST['tanggal_pindah_masuk'] ?? ''));
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Laporin RT</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth {
            width: 100%;
            max-width: 720px;
            background: #fff;
            border: 1px solid #dce4f0;
            border-radius: 8px;
            padding: 28px;
            box-shadow: 0 14px 35px rgba(31, 45, 61, .08);
        }
        h1 { margin: 0 0 6px; font-size: 26px; }
        p { margin: 0 0 22px; color: #667085; line-height: 1.5; }
        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }
        .tabs a {
            text-align: center;
            text-decoration: none;
            padding: 10px 12px;
            border: 1px solid #cfd8e5;
            border-radius: 6px;
            color: #344054;
            font-weight: 700;
        }
        .tabs a.active {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
        }
        label { display: block; margin: 14px 0 6px; font-weight: 700; }
        input, textarea, select {
            width: 100%;
            border: 1px solid #cfd8e5;
            border-radius: 6px;
            padding: 11px 12px;
            font: inherit;
            background: #fff;
        }
        textarea { min-height: 92px; resize: vertical; }
        fieldset {
            border: 1px solid #e4e9f2;
            border-radius: 8px;
            padding: 14px;
            margin: 18px 0 0;
        }
        legend {
            padding: 0 8px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 12px;
        }
        .full { grid-column: 1 / -1; }
        button {
            width: 100%;
            margin-top: 20px;
            border: 0;
            border-radius: 6px;
            padding: 12px;
            background: #1d4ed8;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .alert {
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .alert.success { background: #dcfce7; color: #166534; }
        .hint { margin-top: 16px; font-size: 13px; color: #667085; }
        @media (max-width: 640px) {
            body { padding: 14px; align-items: flex-start; }
            .auth { padding: 18px; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="auth">
        <h1><?= e($title) ?></h1>
        <p><?= $isRegister ? 'Daftar akun warga baru untuk membuat laporan kerusakan.' : 'Masuk memakai NIK dan password akun kamu.' ?></p>

        <nav class="tabs" aria-label="Auth navigation">
            <a class="<?= !$isRegister ? 'active' : '' ?>" href="<?= e(urlFor('/login')) ?>">Login</a>
            <a class="<?= $isRegister ? 'active' : '' ?>" href="<?= e(urlFor('/register.php')) ?>">Register</a>
        </nav>

        <?php if ($errors !== []): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <label for="nik">NIK</label>
            <input id="nik" name="nik" value="<?= $nik ?>" inputmode="numeric" maxlength="16" required>

            <?php if ($isRegister): ?>
                <fieldset>
                    <legend>Data akun</legend>

                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input id="nama_lengkap" name="nama_lengkap" value="<?= $nama ?>" required>

                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= $email ?>">

                    <label for="no_hp">No HP</label>
                    <input id="no_hp" name="no_hp" value="<?= $noHp ?>" placeholder="08xxxxxxxxxx">

                    <label for="no_kk">No KK</label>
                    <input id="no_kk" name="no_kk" value="<?= $noKk ?>" inputmode="numeric" maxlength="16">
                </fieldset>

                <fieldset>
                    <legend>Alamat warga</legend>

                    <div class="grid">
                        <div>
                            <label for="no_rt">No RT</label>
                            <input id="no_rt" name="no_rt" value="<?= $noRt ?>" required>
                        </div>
                        <div>
                            <label for="no_rw">No RW</label>
                            <input id="no_rw" name="no_rw" value="<?= $noRw ?>" required>
                        </div>
                    </div>

                    <label for="alamat_lengkap">Alamat Lengkap</label>
                    <textarea id="alamat_lengkap" name="alamat_lengkap" required><?= $alamat ?></textarea>

                    <div class="grid">
                        <div>
                            <label for="kelurahan">Kelurahan</label>
                            <input id="kelurahan" name="kelurahan" value="<?= $kelurahan ?>">
                        </div>
                        <div>
                            <label for="kecamatan">Kecamatan</label>
                            <input id="kecamatan" name="kecamatan" value="<?= $kecamatan ?>">
                        </div>
                        <div class="full">
                            <label for="kota_kabupaten">Kota/Kabupaten</label>
                            <input id="kota_kabupaten" name="kota_kabupaten" value="<?= $kotaKabupaten ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Data pribadi tambahan</legend>

                    <div class="grid">
                        <div>
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input id="tempat_lahir" name="tempat_lahir" value="<?= $tempatLahir ?>">
                        </div>
                        <div>
                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="<?= $tanggalLahir ?>">
                        </div>
                        <div>
                            <label for="jenis_kelamin">Jenis Kelamin</label>
                            <select id="jenis_kelamin" name="jenis_kelamin">
                                <option value="">Pilih</option>
                                <option value="L" <?= selected('jenis_kelamin', 'L') ?>>Laki-laki</option>
                                <option value="P" <?= selected('jenis_kelamin', 'P') ?>>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="agama">Agama</label>
                            <input id="agama" name="agama" value="<?= $agama ?>">
                        </div>
                        <div>
                            <label for="status_perkawinan">Status Perkawinan</label>
                            <select id="status_perkawinan" name="status_perkawinan">
                                <option value="">Pilih</option>
                                <option value="belum_kawin" <?= selected('status_perkawinan', 'belum_kawin') ?>>Belum kawin</option>
                                <option value="kawin" <?= selected('status_perkawinan', 'kawin') ?>>Kawin</option>
                                <option value="cerai_hidup" <?= selected('status_perkawinan', 'cerai_hidup') ?>>Cerai hidup</option>
                                <option value="cerai_mati" <?= selected('status_perkawinan', 'cerai_mati') ?>>Cerai mati</option>
                            </select>
                        </div>
                        <div>
                            <label for="pekerjaan">Pekerjaan</label>
                            <input id="pekerjaan" name="pekerjaan" value="<?= $pekerjaan ?>">
                        </div>
                        <div>
                            <label for="status_tinggal">Status Tinggal</label>
                            <select id="status_tinggal" name="status_tinggal">
                                <option value="tetap" <?= selected('status_tinggal', 'tetap', 'tetap') ?>>Tetap</option>
                                <option value="kontrak" <?= selected('status_tinggal', 'kontrak', 'tetap') ?>>Kontrak</option>
                                <option value="kost" <?= selected('status_tinggal', 'kost', 'tetap') ?>>Kost</option>
                                <option value="numpang" <?= selected('status_tinggal', 'numpang', 'tetap') ?>>Numpang</option>
                            </select>
                        </div>
                        <div>
                            <label for="tanggal_pindah_masuk">Tanggal Pindah Masuk</label>
                            <input id="tanggal_pindah_masuk" name="tanggal_pindah_masuk" type="date" value="<?= $tanggalPindahMasuk ?>">
                        </div>
                    </div>
                </fieldset>
            <?php endif; ?>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <?php if ($isRegister): ?>
                <label for="password_confirm">Konfirmasi Password</label>
                <input id="password_confirm" name="password_confirm" type="password" required>
            <?php endif; ?>

            <button type="submit"><?= $isRegister ? 'Daftar' : 'Masuk' ?></button>
        </form>

        <div class="hint">
            <?= $isRegister ? 'Setelah daftar, admin perlu mengaktifkan akun dulu.' : 'Belum punya akun? Buka halaman register.' ?>
        </div>
    </main>
</body>
</html>
<?php
}

function renderDashboardPage(): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Laporin RT</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f7fb; color: #172033; padding: 24px; }
        .panel { max-width: 720px; margin: 40px auto; background: #fff; border: 1px solid #dce4f0; border-radius: 8px; padding: 28px; }
        h1 { margin-top: 0; }
        dl { display: grid; grid-template-columns: 150px 1fr; gap: 10px; }
        dt { font-weight: 700; }
        button { border: 0; border-radius: 6px; padding: 10px 14px; background: #b91c1c; color: #fff; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Login Berhasil</h1>
        <p>Selamat datang, <?= e((string)$user['nama_lengkap']) ?>.</p>
        <dl>
            <dt>NIK</dt><dd><?= e((string)$user['nik']) ?></dd>
            <dt>Role</dt><dd><?= e((string)$user['role']) ?></dd>
            <dt>Status</dt><dd><?= e((string)$user['status_akun']) ?></dd>
            <dt>Kode User</dt><dd><?= e((string)($user['kode_user'] ?? '-')) ?></dd>
        </dl>
        <form method="post" action="<?= e(urlFor('/logout')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>
<?php
}

function redirectTo(string $path): void
{
    header('Location: ' . urlFor($path), true, 302);
    exit;
}

function urlFor(string $path): string
{
    $base = basePath();
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function basePath(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
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
