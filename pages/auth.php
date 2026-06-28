<?php
declare(strict_types=1);

function isWebAuthPage(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base !== '' && $base !== '/' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    $path = normalizeWebAuthPath($path);
    return in_array($path, ['/', '/login', '/register', '/dashboard', '/logout', '/laporan', '/laporan-saya', '/edit-laporan', '/hapus-laporan', '/admin-users', '/admin-laporan', '/rt-darurat', '/rt-monitoring', '/petugas-tugas', '/petugas-riwayat'], true);
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

    if ($path === '/hapus-laporan' && $method !== 'POST') {
        redirectTo('/laporan-saya');
    }

    if ($path === '/dashboard') {
        renderDashboardPage();
        return;
    }

    if ($path === '/laporan') {
        renderLaporanPage($errors, $success);
        return;
    }

    if ($path === '/laporan-saya') {
        renderLaporanSayaPage();
        return;
    }

    if ($path === '/edit-laporan') {
        renderEditLaporanPage($errors, $success);
        return;
    }

    if ($path === '/admin-users') {
        renderAdminUsersPage($errors, $success);
        return;
    }

    if ($path === '/admin-laporan') {
        renderAdminLaporanPage($errors, $success);
        return;
    }

    if ($path === '/rt-darurat') {
        renderRtDaruratPage();
        return;
    }

    if ($path === '/rt-monitoring') {
        renderRtMonitoringPage($errors, $success);
        return;
    }

    if ($path === '/petugas-tugas') {
        renderPetugasTugasPage($errors, $success);
        return;
    }

    if ($path === '/petugas-riwayat') {
        renderPetugasRiwayatPage();
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
        '/laporan.php' => '/laporan',
        '/laporan-saya.php' => '/laporan-saya',
        '/edit-laporan.php' => '/edit-laporan',
        '/hapus-laporan.php' => '/hapus-laporan',
        '/admin-users.php' => '/admin-users',
        '/admin-laporan.php' => '/admin-laporan',
        '/rt-darurat.php' => '/rt-darurat',
        '/rt-monitoring.php' => '/rt-monitoring',
        '/petugas-tugas.php' => '/petugas-tugas',
        '/petugas-riwayat.php' => '/petugas-riwayat',
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
        'terms' => (string)($_POST['terms'] ?? ''),
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

    if (($input['terms'] ?? '') !== '1') {
        $errors[] = 'Syarat dan ketentuan wajib disetujui.';
    }

    return $errors;
}

function nullableInput(string $value): ?string
{
    return $value !== '' ? $value : null;
}

function processLaporanForm(): array
{
    $user = $_SESSION['auth_user'] ?? [];
    if (($user['role'] ?? '') !== 'warga') {
        return [['Hanya user warga yang bisa membuat laporan.'], ''];
    }

    $input = [
        'kategori_id' => trim((string)($_POST['kategori_id'] ?? '')),
        'judul' => trim((string)($_POST['judul'] ?? '')),
        'deskripsi' => trim((string)($_POST['deskripsi'] ?? '')),
        'lokasi_detail' => trim((string)($_POST['lokasi_detail'] ?? '')),
        'latitude' => trim((string)($_POST['latitude'] ?? '')),
        'longitude' => trim((string)($_POST['longitude'] ?? '')),
        'akurasi_gps_meter' => trim((string)($_POST['akurasi_gps_meter'] ?? '')),
        'maps_url' => trim((string)($_POST['maps_url'] ?? '')),
        'tingkat_prioritas' => trim((string)($_POST['tingkat_prioritas'] ?? 'sedang')),
    ];

    $errors = validateLaporanInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    $db = \App\Db\Database::getInstance();
    if (!$db->query("SELECT id FROM kategori_laporan WHERE id = ? AND is_active = 1 LIMIT 1", [(int)$input['kategori_id']])->fetch()) {
        return [['Kategori laporan tidak valid.'], ''];
    }

    $db->beginTransaction();
    try {
        $code = new \App\Utils\CodeGenerator();
        $kode = $code->laporanCode();
        $pelaporId = (int)$user['id'];

        $db->query(
            "INSERT INTO laporan_kerusakan
             (kode_laporan, pelapor_id, kategori_id, judul, deskripsi, lokasi_detail, latitude, longitude, akurasi_gps_meter, maps_url, tingkat_prioritas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $kode,
                $pelaporId,
                (int)$input['kategori_id'],
                $input['judul'],
                $input['deskripsi'],
                $input['lokasi_detail'],
                nullableInput($input['latitude']),
                nullableInput($input['longitude']),
                nullableInput($input['akurasi_gps_meter']),
                nullableInput($input['maps_url']),
                $input['tingkat_prioritas'] !== '' ? $input['tingkat_prioritas'] : 'sedang',
            ]
        );

        $laporanId = (int)$db->lastInsertId();
        $db->query(
            "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan)
             VALUES (?, ?, NULL, 'menunggu_verifikasi', 'Laporan dibuat warga melalui halaman web.')",
            [$laporanId, $pelaporId]
        );

        saveLaporanPhotos($db, $laporanId, $pelaporId, $_FILES);
        $db->commit();
        $_POST = [];

        return [[], "Laporan berhasil dikirim dengan kode {$kode}. Menunggu verifikasi admin."];
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function processEditLaporanForm(): array
{
    $user = $_SESSION['auth_user'] ?? [];
    if (($user['role'] ?? '') !== 'warga') {
        return [['Hanya user warga yang bisa mengedit laporan.'], ''];
    }

    $reportId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $existing = getOwnedReport($reportId, (int)$user['id']);
    if (!$existing) {
        return [['Laporan tidak ditemukan atau bukan milik akun ini.'], ''];
    }

    $input = [
        'kategori_id' => trim((string)($_POST['kategori_id'] ?? '')),
        'judul' => trim((string)($_POST['judul'] ?? '')),
        'deskripsi' => trim((string)($_POST['deskripsi'] ?? '')),
        'lokasi_detail' => trim((string)($_POST['lokasi_detail'] ?? '')),
        'latitude' => trim((string)($_POST['latitude'] ?? '')),
        'longitude' => trim((string)($_POST['longitude'] ?? '')),
        'akurasi_gps_meter' => trim((string)($_POST['akurasi_gps_meter'] ?? '')),
        'maps_url' => trim((string)($_POST['maps_url'] ?? '')),
        'tingkat_prioritas' => trim((string)($_POST['tingkat_prioritas'] ?? 'sedang')),
    ];

    $errors = validateLaporanInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    $db = \App\Db\Database::getInstance();
    if (!$db->query("SELECT id FROM kategori_laporan WHERE id = ? AND is_active = 1 LIMIT 1", [(int)$input['kategori_id']])->fetch()) {
        return [['Kategori laporan tidak valid.'], ''];
    }

    $db->beginTransaction();
    try {
        $db->query(
            "UPDATE laporan_kerusakan
             SET kategori_id = ?, judul = ?, deskripsi = ?, lokasi_detail = ?, latitude = ?, longitude = ?,
                 akurasi_gps_meter = ?, maps_url = ?, tingkat_prioritas = ?
             WHERE id = ? AND pelapor_id = ?",
            [
                (int)$input['kategori_id'],
                $input['judul'],
                $input['deskripsi'],
                $input['lokasi_detail'],
                nullableInput($input['latitude']),
                nullableInput($input['longitude']),
                nullableInput($input['akurasi_gps_meter']),
                nullableInput($input['maps_url']),
                $input['tingkat_prioritas'] !== '' ? $input['tingkat_prioritas'] : 'sedang',
                $reportId,
                (int)$user['id'],
            ]
        );

        $db->query(
            "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan)
             VALUES (?, ?, ?, ?, 'Laporan diedit oleh warga melalui halaman web.')",
            [$reportId, (int)$user['id'], $existing['status'], $existing['status']]
        );

        saveLaporanPhotos($db, $reportId, (int)$user['id'], $_FILES);
        $db->commit();

        return [[], 'Laporan berhasil diperbarui.'];
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function processDeleteLaporanForm(): void
{
    $user = $_SESSION['auth_user'] ?? [];
    if (($user['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    $reportId = (int)($_POST['id'] ?? 0);
    $existing = getOwnedReport($reportId, (int)$user['id']);
    if (!$existing) {
        redirectTo('/laporan-saya');
    }

    $db = \App\Db\Database::getInstance();
    $photos = $db->query("SELECT path_file FROM foto_laporan WHERE laporan_id = ?", [$reportId])->fetchAll() ?: [];

    $db->beginTransaction();
    try {
        $db->query("DELETE FROM laporan_kerusakan WHERE id = ? AND pelapor_id = ?", [$reportId, (int)$user['id']]);
        $db->commit();
        deleteReportFiles($photos);
        redirectTo('/laporan-saya');
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function processAdminUserStatusForm(): array
{
    $admin = requireAdminWeb();
    $userId = (int)($_POST['user_id'] ?? 0);
    $status = (string)($_POST['status_akun'] ?? '');

    if ($userId < 1 || !in_array($status, ['aktif', 'nonaktif', 'pending'], true)) {
        return [['Data status user tidak valid.'], ''];
    }

    if ($userId === (int)$admin['id']) {
        return [['Tidak bisa mengubah status akun sendiri.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    $target = $db->query("SELECT id, role FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();
    if (!$target) {
        return [['User tidak ditemukan.'], ''];
    }

    $db->query("UPDATE users SET status_akun = ?, updated_at = NOW() WHERE id = ?", [$status, $userId]);
    if ($status !== 'aktif') {
        $db->query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$userId]);
    }

    return [[], 'Status user berhasil diperbarui.'];
}

function processAdminLaporanForm(): array
{
    $admin = requireAdminWeb();
    $reportId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($reportId < 1 || !in_array($action, ['verify', 'reject', 'assign'], true)) {
        return [['Aksi laporan tidak valid.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    $report = $db->query("SELECT id, status FROM laporan_kerusakan WHERE id = ? LIMIT 1", [$reportId])->fetch();
    if (!$report) {
        return [['Laporan tidak ditemukan.'], ''];
    }

    if ($action === 'verify') {
        adminChangeReportStatus($db, $report, (int)$admin['id'], 'diverifikasi', trim((string)($_POST['catatan_admin'] ?? 'Laporan diverifikasi admin.')));
        return [[], 'Laporan berhasil diverifikasi.'];
    }

    if ($action === 'reject') {
        $reason = trim((string)($_POST['alasan_penolakan'] ?? ''));
        if ($reason === '') {
            return [['Alasan penolakan wajib diisi.'], ''];
        }
        adminChangeReportStatus($db, $report, (int)$admin['id'], 'ditolak', $reason, ['alasan_penolakan' => $reason]);
        return [[], 'Laporan berhasil ditolak.'];
    }

    $petugasId = (int)($_POST['petugas_id'] ?? 0);
    $petugas = $db->query("SELECT id FROM users WHERE id = ? AND role = 'petugas' AND status_akun = 'aktif' LIMIT 1", [$petugasId])->fetch();
    if (!$petugas) {
        return [['Petugas tidak valid atau belum aktif.'], ''];
    }

    adminChangeReportStatus($db, $report, (int)$admin['id'], 'ditugaskan', trim((string)($_POST['catatan_admin'] ?? 'Laporan ditugaskan ke petugas.')), [
        'petugas_id' => $petugasId,
        'tanggal_target_selesai' => nullableInput(trim((string)($_POST['tanggal_target_selesai'] ?? ''))),
    ]);

    return [[], 'Laporan berhasil ditugaskan ke petugas.'];
}

function processPetugasTaskForm(): array
{
    $petugas = requirePetugasWeb();
    $taskId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($taskId < 1 || !in_array($action, ['mulai', 'progress', 'tindak_lanjut', 'selesai'], true)) {
        return [['Aksi tugas tidak valid.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    $task = $db->query("SELECT * FROM laporan_kerusakan WHERE id = ? AND petugas_id = ? LIMIT 1", [$taskId, (int)$petugas['id']])->fetch();
    if (!$task) {
        return [['Tugas tidak ditemukan atau bukan milik akun ini.'], ''];
    }

    $note = trim((string)($_POST['catatan_petugas'] ?? ''));

    if ($action === 'mulai') {
        if (!in_array($task['status'], ['diverifikasi', 'ditugaskan', 'perlu_tindak_lanjut'], true)) {
            return [['Tugas tidak dalam status yang bisa mulai dikerjakan.'], ''];
        }

        $db->query(
            "UPDATE laporan_kerusakan SET status = 'dalam_pengerjaan', tanggal_mulai_kerjakan = COALESCE(tanggal_mulai_kerjakan, NOW()), updated_at = NOW() WHERE id = ?",
            [$taskId]
        );
        insertTaskHistory($db, $taskId, (int)$petugas['id'], $task['status'], 'dalam_pengerjaan', 'Petugas mulai mengerjakan laporan.');
        return [[], 'Tugas berhasil dimulai.'];
    }

    if ($note === '') {
        return [['Catatan petugas wajib diisi.'], ''];
    }

    if ($action === 'progress') {
        $db->query("UPDATE laporan_kerusakan SET status = 'dalam_pengerjaan', catatan_petugas = ?, updated_at = NOW() WHERE id = ?", [$note, $taskId]);
        $db->query(
            "INSERT INTO respons_laporan (laporan_id, direspons_oleh, isi_respons, tipe_respons, is_internal) VALUES (?, ?, ?, 'update_status', 0)",
            [$taskId, (int)$petugas['id'], $note]
        );
        if ($task['status'] !== 'dalam_pengerjaan') {
            insertTaskHistory($db, $taskId, (int)$petugas['id'], $task['status'], 'dalam_pengerjaan', $note);
        }
        savePetugasTaskPhotos($db, $taskId, (int)$petugas['id'], 'proses', $_FILES);
        return [[], 'Progress tugas berhasil disimpan.'];
    }

    if ($action === 'tindak_lanjut') {
        $db->query("UPDATE laporan_kerusakan SET status = 'perlu_tindak_lanjut', catatan_petugas = ?, updated_at = NOW() WHERE id = ?", [$note, $taskId]);
        insertTaskHistory($db, $taskId, (int)$petugas['id'], $task['status'], 'perlu_tindak_lanjut', $note);
        return [[], 'Tugas ditandai perlu tindak lanjut.'];
    }

    $db->query("UPDATE laporan_kerusakan SET status = 'selesai', catatan_petugas = ?, tanggal_selesai = NOW(), updated_at = NOW() WHERE id = ?", [$note, $taskId]);
    $db->query("INSERT INTO respons_laporan (laporan_id, direspons_oleh, isi_respons, tipe_respons) VALUES (?, ?, ?, 'penyelesaian')", [$taskId, (int)$petugas['id'], $note]);
    insertTaskHistory($db, $taskId, (int)$petugas['id'], $task['status'], 'selesai', $note);
    savePetugasTaskPhotos($db, $taskId, (int)$petugas['id'], 'bukti_selesai', $_FILES);

    return [[], 'Tugas berhasil ditandai selesai.'];
}

function insertTaskHistory(\App\Db\Database $db, int $taskId, int $userId, string $oldStatus, string $newStatus, string $note): void
{
    $db->query(
        "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, ?, ?)",
        [$taskId, $userId, $oldStatus, $newStatus, $note]
    );
}

function savePetugasTaskPhotos(\App\Db\Database $db, int $taskId, int $userId, string $type, array $files): void
{
    if (empty($files['fotos']['name'])) {
        return;
    }

    $names = is_array($files['fotos']['name']) ? $files['fotos']['name'] : [$files['fotos']['name']];
    $upload = new \App\Utils\FileUpload();

    foreach ($names as $index => $name) {
        if ($name === '') {
            continue;
        }

        $file = is_array($files['fotos']['name'])
            ? [
                'name' => $name,
                'type' => $files['fotos']['type'][$index] ?? '',
                'tmp_name' => $files['fotos']['tmp_name'][$index] ?? '',
                'error' => $files['fotos']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['fotos']['size'][$index] ?? 0,
            ]
            : $files['fotos'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $saved = $upload->fotoLaporan($file, 'laporan');
        $db->query(
            "INSERT INTO foto_laporan (laporan_id, nama_file, path_file, ukuran_file, tipe_mime, tipe_foto, diunggah_oleh)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$taskId, $saved['nama_file'], $saved['path_file'], $saved['ukuran_file'], $saved['tipe_mime'], $type, $userId]
        );
    }
}

function adminChangeReportStatus(\App\Db\Database $db, array $report, int $adminId, string $newStatus, string $note, array $extra = []): void
{
    $sets = ['status = ?', 'updated_at = NOW()'];
    $params = [$newStatus];

    if (in_array($newStatus, ['diverifikasi', 'ditolak', 'ditugaskan'], true)) {
        $sets[] = 'diverifikasi_oleh = ?';
        $params[] = $adminId;
    }

    foreach ($extra as $field => $value) {
        if (in_array($field, ['petugas_id', 'tanggal_target_selesai', 'alasan_penolakan'], true)) {
            $sets[] = "{$field} = ?";
            $params[] = $value;
        }
    }

    $params[] = (int)$report['id'];
    $db->query("UPDATE laporan_kerusakan SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    $db->query(
        "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan)
         VALUES (?, ?, ?, ?, ?)",
        [(int)$report['id'], $adminId, $report['status'], $newStatus, $note !== '' ? $note : $newStatus]
    );
}

function validateLaporanInput(array $input): array
{
    $errors = [];

    if (!ctype_digit($input['kategori_id'])) {
        $errors[] = 'Kategori wajib dipilih.';
    }

    if (mb_strlen($input['judul']) < 5 || mb_strlen($input['judul']) > 200) {
        $errors[] = 'Judul harus 5 sampai 200 karakter.';
    }

    if (mb_strlen($input['deskripsi']) < 10) {
        $errors[] = 'Deskripsi minimal 10 karakter.';
    }

    if ($input['lokasi_detail'] === '' || mb_strlen($input['lokasi_detail']) > 255) {
        $errors[] = 'Lokasi detail wajib diisi dan maksimal 255 karakter.';
    }

    if ($input['latitude'] !== '' && !preg_match('/^-?([0-8]?\d(\.\d+)?|90(\.0+)?)$/', $input['latitude'])) {
        $errors[] = 'Latitude tidak valid.';
    }

    if ($input['longitude'] !== '' && !preg_match('/^-?((1[0-7]\d|\d{1,2})(\.\d+)?|180(\.0+)?)$/', $input['longitude'])) {
        $errors[] = 'Longitude tidak valid.';
    }

    if ($input['akurasi_gps_meter'] !== '' && !is_numeric($input['akurasi_gps_meter'])) {
        $errors[] = 'Akurasi GPS harus angka.';
    }

    if ($input['maps_url'] !== '' && !filter_var($input['maps_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'URL Maps tidak valid.';
    }

    if (!in_array($input['tingkat_prioritas'], ['rendah', 'sedang', 'tinggi', 'darurat'], true)) {
        $errors[] = 'Prioritas tidak valid.';
    }

    return $errors;
}

function saveLaporanPhotos(\App\Db\Database $db, int $laporanId, int $userId, array $files): void
{
    if (empty($files['fotos']['name'])) {
        return;
    }

    $names = is_array($files['fotos']['name']) ? $files['fotos']['name'] : [$files['fotos']['name']];
    $upload = new \App\Utils\FileUpload();

    foreach ($names as $index => $name) {
        if ($name === '') {
            continue;
        }

        $file = is_array($files['fotos']['name'])
            ? [
                'name' => $name,
                'type' => $files['fotos']['type'][$index] ?? '',
                'tmp_name' => $files['fotos']['tmp_name'][$index] ?? '',
                'error' => $files['fotos']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['fotos']['size'][$index] ?? 0,
            ]
            : $files['fotos'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $saved = $upload->fotoLaporan($file, 'laporan');
        $db->query(
            "INSERT INTO foto_laporan (laporan_id, nama_file, path_file, ukuran_file, tipe_mime, tipe_foto, diunggah_oleh)
             VALUES (?, ?, ?, ?, ?, 'bukti_awal', ?)",
            [$laporanId, $saved['nama_file'], $saved['path_file'], $saved['ukuran_file'], $saved['tipe_mime'], $userId]
        );
    }
}

function deleteReportFiles(array $photos): void
{
    $uploadRoot = realpath(__DIR__ . '/../backend/uploads');
    if ($uploadRoot === false) {
        return;
    }

    foreach ($photos as $photo) {
        $relative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string)($photo['path_file'] ?? ''));
        $path = realpath(__DIR__ . '/../backend/' . $relative);
        if ($path !== false && strpos($path, $uploadRoot) === 0 && is_file($path)) {
            @unlink($path);
        }
    }
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
    $action = urlFor($isRegister ? '/register' : '/login');
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

    require __DIR__ . ($isRegister ? '/register.php' : '/login.php');
}

function renderDashboardPage(): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $dashboard = buildDashboardData($user);

    require __DIR__ . '/dashboard.php';
}

function renderLaporanPage(array $errors = [], string $success = ''): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $categories = getActiveCategories();
    $csrf = e(csrfToken());
    $action = urlFor('/laporan');

    require __DIR__ . '/laporan.php';
}

function renderLaporanSayaPage(): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $reports = getMyReports((int)$user['id']);

    require __DIR__ . '/users/laporan_saya.php';
}

function renderEditLaporanPage(array $errors = [], string $success = ''): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    $reportId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $report = getOwnedReport($reportId, (int)$_SESSION['auth_user']['id']);
    if (!$report) {
        redirectTo('/laporan-saya');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $categories = getActiveCategories();
    $csrf = e(csrfToken());
    $action = urlFor('/edit-laporan') . '?id=' . $reportId;

    require __DIR__ . '/users/laporan_edit.php';
}

function renderAdminUsersPage(array $errors = [], string $success = ''): void
{
    $admin = requireAdminWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $users = getAdminUsers();
    $csrf = e(csrfToken());

    require __DIR__ . '/admin/admin_users.php';
}

function renderAdminLaporanPage(array $errors = [], string $success = ''): void
{
    $admin = requireAdminWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $reports = getAdminReports();
    $petugas = getActivePetugas();
    $csrf = e(csrfToken());

    require __DIR__ . '/admin/admin_laporan.php';
}

function renderRtDaruratPage(): void
{
    $rt = requireRtWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $reports = getRtEmergencyReports();
    $summary = getRtEmergencySummary();

    require __DIR__ . '/rt/rt_darurat.php';
}

function renderRtMonitoringPage(array $errors = [], string $success = ''): void
{
    $rt = requireRtWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $officers = getRtOfficerMonitoring();
    $activeTasks = getRtActiveTasks();

    $belumDikerjakan = getRtBelumDikerjakanTasks();
    $sedangDikerjakan = getRtSedangDikerjakanTasks();
    $selesaiTasks = getRtSelesaiTasks();

    $allReportIds = array_merge(
        array_column($belumDikerjakan, 'id'),
        array_column($sedangDikerjakan, 'id'),
        array_column($selesaiTasks, 'id')
    );

    $photosByReport = [];
    if ($allReportIds !== []) {
        $rawPhotos = getReportsPhotos($allReportIds);
        foreach ($rawPhotos as $p) {
            $photosByReport[(int)$p['laporan_id']][] = $p;
        }
    }

    $petugas = getActivePetugas();
    $csrf = e(csrfToken());

    require __DIR__ . '/rt/rt_monitoring.php';
}

function renderPetugasTugasPage(array $errors = [], string $success = ''): void
{
    $petugas = requirePetugasWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $tasks = getPetugasActiveTasks((int)$petugas['id']);
    $csrf = e(csrfToken());

    require __DIR__ . '/petugas/petugas_tugas.php';
}

function renderPetugasRiwayatPage(): void
{
    $petugas = requirePetugasWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $tasks = getPetugasHistoryTasks((int)$petugas['id']);

    require __DIR__ . '/petugas/petugas_riwayat.php';
}

function requireAdminWeb(): array
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    $user = $_SESSION['auth_user'];
    if (($user['role'] ?? '') !== 'admin') {
        redirectTo('/dashboard');
    }

    return $user;
}

function requireRtWeb(): array
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    $user = $_SESSION['auth_user'];
    if (($user['role'] ?? '') !== 'rt') {
        redirectTo('/dashboard');
    }

    return $user;
}

function requirePetugasWeb(): array
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    $user = $_SESSION['auth_user'];
    if (($user['role'] ?? '') !== 'petugas') {
        redirectTo('/dashboard');
    }

    return $user;
}

function getActiveCategories(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query("SELECT id, nama_kategori, deskripsi FROM kategori_laporan WHERE is_active = 1 ORDER BY nama_kategori")
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getMyReports(int $userId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_kategori, label_status, tingkat_prioritas, lokasi_detail, created_at, tanggal_selesai, rating_warga
                 FROM v_laporan_ringkasan WHERE pelapor_id = ? ORDER BY created_at DESC",
                [$userId]
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getOwnedReport(int $reportId, int $userId): ?array
{
    if ($reportId < 1) {
        return null;
    }

    try {
        $report = \App\Db\Database::getInstance()
            ->query(
                "SELECT lk.*, kl.nama_kategori,
                        CASE lk.status
                            WHEN 'menunggu_verifikasi' THEN 'Menunggu Verifikasi'
                            WHEN 'diverifikasi' THEN 'Sudah Diverifikasi'
                            WHEN 'ditugaskan' THEN 'Sudah Ditugaskan ke Petugas'
                            WHEN 'dalam_pengerjaan' THEN 'Sedang Dikerjakan'
                            WHEN 'perlu_tindak_lanjut' THEN 'Perlu Tindak Lanjut'
                            WHEN 'selesai' THEN 'Selesai'
                            WHEN 'ditolak' THEN 'Ditolak'
                            WHEN 'dibatalkan' THEN 'Dibatalkan'
                            ELSE lk.status
                        END AS label_status
                 FROM laporan_kerusakan lk
                 JOIN kategori_laporan kl ON kl.id = lk.kategori_id
                 WHERE lk.id = ? AND lk.pelapor_id = ?
                 LIMIT 1",
                [$reportId, $userId]
            )
            ->fetch();

        return $report ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function getAdminUsers(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT u.id, u.kode_user, u.nik, u.nama_lengkap, u.email, u.no_hp, u.role, u.status_akun, u.created_at,
                        pw.no_rt, pw.no_rw, pw.alamat_lengkap
                 FROM users u
                 LEFT JOIN profil_warga pw ON pw.user_id = u.id
                 ORDER BY FIELD(u.status_akun, 'pending', 'aktif', 'nonaktif'), u.created_at DESC"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getAdminReports(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, nama_petugas, created_at, alasan_penolakan
                 FROM v_laporan_ringkasan
                 ORDER BY FIELD(status, 'menunggu_verifikasi', 'diverifikasi', 'ditugaskan', 'dalam_pengerjaan', 'perlu_tindak_lanjut', 'selesai', 'ditolak', 'dibatalkan'), created_at DESC
                 LIMIT 100"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getActivePetugas(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query("SELECT id, kode_user, nama_lengkap FROM users WHERE role = 'petugas' AND status_akun = 'aktif' ORDER BY nama_lengkap")
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getRtEmergencyReports(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, latitude, longitude, akurasi_gps_meter, nama_petugas, created_at
                 FROM v_laporan_ringkasan
                 WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan')
                 ORDER BY created_at ASC"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getRtEmergencySummary(): array
{
    try {
        $db = \App\Db\Database::getInstance();
        return [
            'total' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan')"),
            'menunggu' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE tingkat_prioritas = 'darurat' AND status = 'menunggu_verifikasi'"),
            'diproses' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE tingkat_prioritas = 'darurat' AND status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')"),
        ];
    } catch (Throwable $e) {
        return ['total' => 0, 'menunggu' => 0, 'diproses' => 0];
    }
}

function getRtOfficerMonitoring(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query("SELECT * FROM v_monitoring_petugas ORDER BY jml_aktif DESC, nama_petugas")
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getRtActiveTasks(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT kode_laporan, judul, label_status, tingkat_prioritas, lokasi_detail, nama_petugas, created_at
                 FROM v_laporan_ringkasan
                 WHERE status IN ('ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')
                 ORDER BY tingkat_prioritas DESC, created_at ASC
                 LIMIT 30"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getPetugasActiveTasks(int $petugasId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, latitude, longitude, created_at, tanggal_target_selesai, catatan_petugas
                 FROM v_laporan_ringkasan
                 WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?)
                   AND status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')
                 ORDER BY tingkat_prioritas DESC, created_at ASC",
                [$petugasId]
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getPetugasHistoryTasks(int $petugasId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, created_at, tanggal_mulai_kerjakan, tanggal_selesai, durasi_hari, rating_warga
                 FROM v_laporan_ringkasan
                 WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?)
                   AND status IN ('selesai','ditolak','dibatalkan')
                 ORDER BY COALESCE(tanggal_selesai, created_at) DESC
                 LIMIT 100",
                [$petugasId]
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function buildDashboardData(array $user): array
{
    try {
        $db = \App\Db\Database::getInstance();
        $role = (string)($user['role'] ?? 'warga');
        $userId = (int)($user['id'] ?? 0);

        if ($role === 'admin') {
            return buildAdminDashboard($db);
        }

        if ($role === 'petugas') {
            return buildPetugasDashboard($db, $userId);
        }

        if ($role === 'rt') {
            return buildRtDashboard($db);
        }

        return buildWargaDashboard($db, $userId);
    } catch (Throwable $e) {
        return [
            'stats' => [],
            'rows' => [],
            'secondary_rows' => [],
            'error' => 'Data dashboard belum bisa dimuat: ' . $e->getMessage(),
        ];
    }
}

function buildWargaDashboard(\App\Db\Database $db, int $userId): array
{
    return [
        'stats' => [
            ['label' => 'Total Laporan', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE pelapor_id = ?", [$userId]), 'tone' => 'primary'],
            ['label' => 'Menunggu', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE pelapor_id = ? AND status = 'menunggu_verifikasi'", [$userId]), 'tone' => 'warning'],
            ['label' => 'Diproses', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE pelapor_id = ? AND status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')", [$userId]), 'tone' => 'info'],
            ['label' => 'Selesai', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE pelapor_id = ? AND status = 'selesai'", [$userId]), 'tone' => 'success'],
        ],
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, label_status, tingkat_prioritas, created_at FROM v_laporan_ringkasan WHERE pelapor_id = ? ORDER BY created_at DESC LIMIT 6", [$userId]),
        'secondary_rows' => dashboardRows($db, "SELECT judul, pesan, created_at FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]),
        'primary_title' => 'Laporan Terbaru Saya',
        'secondary_title' => 'Notifikasi Terbaru',
    ];
}

function buildAdminDashboard(\App\Db\Database $db): array
{
    return [
        'stats' => [
            ['label' => 'Total Laporan', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan"), 'tone' => 'primary'],
            ['label' => 'Menunggu Verifikasi', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE status = 'menunggu_verifikasi'"), 'tone' => 'warning'],
            ['label' => 'Akun Pending', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM users WHERE status_akun = 'pending'"), 'tone' => 'danger'],
            ['label' => 'Total User', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM users"), 'tone' => 'success'],
        ],
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, tingkat_prioritas, created_at FROM v_laporan_ringkasan ORDER BY created_at DESC LIMIT 7"),
        'secondary_rows' => dashboardRows($db, "SELECT role, COUNT(*) jumlah FROM users GROUP BY role ORDER BY role"),
        'primary_title' => 'Laporan Masuk Terbaru',
        'secondary_title' => 'Jumlah Akun per Role',
    ];
}

function buildPetugasDashboard(\App\Db\Database $db, int $userId): array
{
    return [
        'stats' => [
            ['label' => 'Total Tugas', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE petugas_id = ?", [$userId]), 'tone' => 'primary'],
            ['label' => 'Ditugaskan', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE petugas_id = ? AND status IN ('diverifikasi','ditugaskan')", [$userId]), 'tone' => 'warning'],
            ['label' => 'Dikerjakan', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE petugas_id = ? AND status IN ('dalam_pengerjaan','perlu_tindak_lanjut')", [$userId]), 'tone' => 'info'],
            ['label' => 'Selesai', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE petugas_id = ? AND status = 'selesai'", [$userId]), 'tone' => 'success'],
        ],
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, tingkat_prioritas, lokasi_detail, created_at FROM v_laporan_ringkasan WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?) AND status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut') ORDER BY tingkat_prioritas DESC, created_at ASC LIMIT 8", [$userId]),
        'secondary_rows' => dashboardRows($db, "SELECT kode_laporan, judul, label_status, tanggal_selesai FROM v_laporan_ringkasan WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?) AND status = 'selesai' ORDER BY tanggal_selesai DESC LIMIT 5", [$userId]),
        'primary_title' => 'Tugas Aktif',
        'secondary_title' => 'Tugas Selesai Terbaru',
    ];
}

function buildRtDashboard(\App\Db\Database $db): array
{
    return [
        'stats' => [
            ['label' => 'Total Laporan', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan"), 'tone' => 'primary'],
            ['label' => 'Darurat Aktif', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan')"), 'tone' => 'danger'],
            ['label' => 'Sedang Diproses', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM laporan_kerusakan WHERE status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')"), 'tone' => 'info'],
            ['label' => 'Petugas Aktif', 'value' => dashboardCount($db, "SELECT COUNT(*) FROM users WHERE role = 'petugas' AND status_akun = 'aktif'"), 'tone' => 'success'],
        ],
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, lokasi_detail, created_at FROM v_laporan_ringkasan WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan') ORDER BY created_at ASC LIMIT 8"),
        'secondary_rows' => dashboardRows($db, "SELECT kode_petugas, nama_petugas, jml_aktif, jml_selesai, terakhir_aktif FROM v_monitoring_petugas ORDER BY jml_aktif DESC, nama_petugas LIMIT 8"),
        'primary_title' => 'Laporan Darurat Aktif',
        'secondary_title' => 'Monitoring Petugas',
    ];
}

function dashboardCount(\App\Db\Database $db, string $sql, array $params = []): int
{
    return (int)$db->query($sql, $params)->fetchColumn();
}

function dashboardRows(\App\Db\Database $db, string $sql, array $params = []): array
{
    return $db->query($sql, $params)->fetchAll() ?: [];
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

function getRtBelumDikerjakanTasks(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, created_at, latitude, longitude
                 FROM v_laporan_ringkasan
                 WHERE status IN ('menunggu_verifikasi', 'diverifikasi')
                 ORDER BY tingkat_prioritas DESC, created_at DESC"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getRtSedangDikerjakanTasks(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, nama_petugas, created_at, tanggal_mulai_kerjakan, tanggal_target_selesai, latitude, longitude
                 FROM v_laporan_ringkasan
                 WHERE status IN ('ditugaskan', 'dalam_pengerjaan', 'perlu_tindak_lanjut')
                 ORDER BY tingkat_prioritas DESC, created_at DESC"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getRtSelesaiTasks(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, nama_petugas, created_at, tanggal_mulai_kerjakan, tanggal_selesai, rating_warga, ulasan_warga, latitude, longitude
                 FROM v_laporan_ringkasan
                 WHERE status IN ('selesai', 'ditolak', 'dibatalkan')
                 ORDER BY tanggal_selesai DESC, created_at DESC"
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getReportsPhotos(array $reportIds): array
{
    if ($reportIds === []) {
        return [];
    }
    try {
        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
        return \App\Db\Database::getInstance()
            ->query("SELECT id, laporan_id, path_file, tipe_foto, created_at FROM foto_laporan WHERE laporan_id IN ($placeholders) ORDER BY created_at ASC", $reportIds)
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function processRtMonitoringForm(): array
{
    $rt = requireRtWeb();
    $reportId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($reportId < 1 || !in_array($action, ['assign'], true)) {
        return [['Aksi laporan tidak valid.'], ''];
    }

    $db = \App\Db\Database::getInstance();
    $report = $db->query("SELECT id, status FROM laporan_kerusakan WHERE id = ? LIMIT 1", [$reportId])->fetch();
    if (!$report) {
        return [['Laporan tidak ditemukan.'], ''];
    }

    if (in_array($report['status'], ['selesai', 'ditolak', 'dibatalkan'], true)) {
        return [['Laporan sudah selesai atau ditutup, tidak bisa ditugaskan.'], ''];
    }

    $petugasId = (int)($_POST['petugas_id'] ?? 0);
    $petugas = $db->query("SELECT id FROM users WHERE id = ? AND role = 'petugas' AND status_akun = 'aktif' LIMIT 1", [$petugasId])->fetch();
    if (!$petugas) {
        return [['Petugas tidak valid atau belum aktif.'], ''];
    }

    $targetDate = nullableInput(trim((string)($_POST['tanggal_target_selesai'] ?? '')));
    $note = trim((string)($_POST['catatan_rt'] ?? 'Laporan ditugaskan oleh RT.'));

    $db->beginTransaction();
    try {
        $newStatus = 'ditugaskan';
        $sets = ['status = ?', 'updated_at = NOW()', 'petugas_id = ?'];
        $params = [$newStatus, $petugasId];

        if ($report['status'] === 'menunggu_verifikasi') {
            $sets[] = 'diverifikasi_oleh = ?';
            $params[] = (int)$rt['id'];
        }

        if ($targetDate !== null) {
            $sets[] = 'tanggal_target_selesai = ?';
            $params[] = $targetDate;
        }

        $sets[] = 'catatan_admin = ?';
        $params[] = $note;

        $params[] = $reportId;

        $db->query("UPDATE laporan_kerusakan SET " . implode(', ', $sets) . " WHERE id = ?", $params);

        $db->query(
            "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan)
             VALUES (?, ?, ?, ?, ?)",
            [$reportId, (int)$rt['id'], $report['status'], $newStatus, $note]
        );

        $db->commit();
        return [[], 'Laporan berhasil ditugaskan ke petugas.'];
    } catch (Throwable $e) {
        $db->rollback();
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}
