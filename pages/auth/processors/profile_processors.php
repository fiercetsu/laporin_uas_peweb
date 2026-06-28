<?php
declare(strict_types=1);

function processProfileForm(): array
{
    $user = $_SESSION['auth_user'] ?? [];
    if ($user === []) {
        redirectTo('/login');
    }

    $action = (string)($_POST['action'] ?? 'profile');
    if ($action === 'password') {
        return processProfilePasswordForm((int)$user['id']);
    }

    return processProfileDataForm($user);
}

function processProfileDataForm(array $user): array
{
    $userId = (int)$user['id'];
    $role = (string)($user['role'] ?? '');
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $noHp = preg_replace('/\D+/', '', (string)($_POST['no_hp'] ?? '')) ?? '';
    $nik = preg_replace('/\D+/', '', (string)($_POST['nik'] ?? '')) ?? '';

    if ($nama === '' || strlen($nama) > 150) {
        return [['Nama lengkap wajib diisi maksimal 150 karakter.'], ''];
    }
    if ($nik === '' || strlen($nik) !== 16) {
        return [['NIK wajib 16 digit.'], ''];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [['Format email tidak valid.'], ''];
    }
    if ($noHp !== '' && (strlen($noHp) < 10 || strlen($noHp) > 15)) {
        return [['Nomor HP harus 10-15 digit.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    if ($db->query("SELECT id FROM users WHERE nik = ? AND id <> ? LIMIT 1", [$nik, $userId])->fetch()) {
        return [['NIK sudah dipakai akun lain.'], ''];
    }
    if ($email !== '' && $db->query("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1", [$email, $userId])->fetch()) {
        return [['Email sudah dipakai akun lain.'], ''];
    }

    $db->query(
        "UPDATE users SET nik = ?, nama_lengkap = ?, email = NULLIF(?, ''), no_hp = NULLIF(?, ''), updated_at = NOW() WHERE id = ?",
        [$nik, $nama, $email, $noHp, $userId]
    );

    if ($role === 'warga') {
        upsertWargaProfile($db, $userId);
    }

    $_SESSION['auth_user'] = array_merge($user, [
        'nik' => $nik,
        'nama_lengkap' => $nama,
        'email' => $email,
        'no_hp' => $noHp,
    ]);

    return [[], 'Profil berhasil diperbarui.'];
}

function upsertWargaProfile(\App\Db\Database $db, int $userId): void
{
    $input = [
        'no_kk' => preg_replace('/\D+/', '', (string)($_POST['no_kk'] ?? '')) ?? '',
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

    $exists = $db->query("SELECT id FROM profil_warga WHERE user_id = ? LIMIT 1", [$userId])->fetch();
    $params = [
        $input['no_kk'] !== '' ? $input['no_kk'] : null,
        $input['no_rt'] !== '' ? $input['no_rt'] : '000',
        $input['no_rw'] !== '' ? $input['no_rw'] : '000',
        $input['alamat_lengkap'] !== '' ? $input['alamat_lengkap'] : '-',
        $input['kelurahan'] !== '' ? $input['kelurahan'] : null,
        $input['kecamatan'] !== '' ? $input['kecamatan'] : null,
        $input['kota_kabupaten'] !== '' ? $input['kota_kabupaten'] : null,
        $input['tempat_lahir'] !== '' ? $input['tempat_lahir'] : null,
        $input['tanggal_lahir'] !== '' ? $input['tanggal_lahir'] : null,
        in_array($input['jenis_kelamin'], ['L', 'P'], true) ? $input['jenis_kelamin'] : null,
        $input['agama'] !== '' ? $input['agama'] : null,
        in_array($input['status_perkawinan'], ['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'], true) ? $input['status_perkawinan'] : null,
        $input['pekerjaan'] !== '' ? $input['pekerjaan'] : null,
        in_array($input['status_tinggal'], ['tetap', 'kontrak', 'kost', 'numpang'], true) ? $input['status_tinggal'] : 'tetap',
        $input['tanggal_pindah_masuk'] !== '' ? $input['tanggal_pindah_masuk'] : null,
    ];

    if ($exists) {
        $db->query(
            "UPDATE profil_warga SET no_kk = ?, no_rt = ?, no_rw = ?, alamat_lengkap = ?, kelurahan = ?,
                    kecamatan = ?, kota_kabupaten = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?,
                    agama = ?, status_perkawinan = ?, pekerjaan = ?, status_tinggal = ?, tanggal_pindah_masuk = ?
             WHERE user_id = ?",
            [...$params, $userId]
        );
        return;
    }

    $db->query(
        "INSERT INTO profil_warga
            (no_kk, no_rt, no_rw, alamat_lengkap, kelurahan, kecamatan, kota_kabupaten,
             tempat_lahir, tanggal_lahir, jenis_kelamin, agama, status_perkawinan, pekerjaan,
             status_tinggal, tanggal_pindah_masuk, user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [...$params, $userId]
    );
}

function processProfilePasswordForm(int $userId): array
{
    $current = (string)($_POST['password_lama'] ?? '');
    $new = (string)($_POST['password_baru'] ?? '');
    $confirm = (string)($_POST['password_konfirmasi'] ?? '');

    if (strlen($new) < 8) {
        return [['Password baru minimal 8 karakter.'], ''];
    }
    if ($new !== $confirm) {
        return [['Konfirmasi password baru tidak sama.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    $row = $db->query("SELECT password_hash FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();
    if (!$row || !password_verify($current, (string)$row['password_hash'])) {
        return [['Password lama salah.'], ''];
    }

    $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
    $db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $userId]);

    return [[], 'Password berhasil diperbarui.'];
}
