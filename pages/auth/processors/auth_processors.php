<?php
declare(strict_types=1);

function processLoginForm(): array
{
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $errors = [];

    if ($email === '') {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    }

    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        $user = $db->query(
            "SELECT id, kode_user, nik, nama_lengkap, email, password_hash, role, status_akun
             FROM users WHERE email = ? LIMIT 1",
            [$email]
        )->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [['Email atau password salah.'], ''];
        }

        if ($user['status_akun'] !== 'aktif') {
            return [['Akun belum aktif atau sedang dinonaktifkan.'], ''];
        }

        unset($user['password_hash']);
        $_SESSION['auth_user'] = $user;

        // Prevent multiple logins: set previous sessions for this user to inactive
        $db->query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$user['id']]);

        // Register the new web session ID
        $sessionId = session_id();
        $db->query(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expired_at, is_active)
             VALUES (?, ?, ?, ?, ?, 1)",
            [
                $user['id'],
                $sessionId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                date('Y-m-d H:i:s', strtotime('+1 day')),
            ]
        );

        redirectTo('/dashboard');
        return [[], ''];
    } catch (Throwable $e) {
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
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
        'terms' => (string)($_POST['terms'] ?? ''),
    ];

    $errors = validateRegisterInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        if ($db->query("SELECT id FROM users WHERE nik = ? LIMIT 1", [$input['nik']])->fetch()) {
            return [['NIK sudah terdaftar.'], ''];
        }
        if ($input['email'] !== '' && $db->query("SELECT id FROM users WHERE email = ? LIMIT 1", [$input['email']])->fetch()) {
            return [['Email sudah terdaftar.'], ''];
        }

        $db->beginTransaction();
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
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}

function processResetPasswordForm(): array
{
    $email = trim((string)($_POST['email'] ?? ''));
    $nik = trim((string)($_POST['nik'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    $errors = [];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }
    if (!preg_match('/^\d{16}$/', $nik)) {
        $errors[] = 'NIK harus 16 digit angka.';
    }
    if (mb_strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password baru minimal 8 karakter, mengandung huruf kapital dan angka.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        $user = $db->query("SELECT id FROM users WHERE email = ? AND nik = ? LIMIT 1", [$email, $nik])->fetch();

        if (!$user) {
            return [['Data email dan NIK tidak cocok dengan akun manapun.'], ''];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
        $db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $user['id']]);

        $_POST = [];
        return [[], 'Password Anda berhasil disetel ulang. Silakan masuk kembali.'];
    } catch (Throwable $e) {
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
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

    if (($input['terms'] ?? '') !== '1') {
        $errors[] = 'Syarat dan ketentuan wajib disetujui.';
    }

    return $errors;
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
