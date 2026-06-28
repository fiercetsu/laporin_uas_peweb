<?php
declare(strict_types=1);

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

function getOwnedReport(int $id, int $userId): ?array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, deskripsi, lokasi_detail, latitude, longitude,
                        akurasi_gps_meter, maps_url, tingkat_prioritas, status, alasan_penolakan, created_at,
                        CASE status
                            WHEN 'menunggu_verifikasi' THEN 'Menunggu Verifikasi'
                            WHEN 'diverifikasi' THEN 'Sudah Diverifikasi'
                            WHEN 'ditugaskan' THEN 'Sudah Ditugaskan'
                            WHEN 'dalam_pengerjaan' THEN 'Sedang Dikerjakan'
                            WHEN 'perlu_tindak_lanjut' THEN 'Perlu Tindak Lanjut'
                            WHEN 'selesai' THEN 'Selesai'
                            WHEN 'ditolak' THEN 'Ditolak'
                            WHEN 'dibatalkan' THEN 'Dibatalkan'
                            ELSE status
                        END AS label_status
                 FROM laporan_kerusakan
                 WHERE id = ? AND pelapor_id = ? LIMIT 1",
                [$id, $userId]
                )
            ->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function getMyReports(int $userId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT id, kode_laporan, judul, nama_kategori, label_status, status, tingkat_prioritas, lokasi_detail, rating_warga, ulasan_warga, created_at
                 FROM v_laporan_ringkasan
                 WHERE pelapor_id = ?
                 ORDER BY created_at DESC",
                [$userId]
            )
            ->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getAdminUsers(): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT u.id, u.kode_user, u.nik, u.nama_lengkap, u.email, u.no_hp, u.role, u.status_akun,
                        w.alamat_lengkap, w.no_rt, w.no_rw
                 FROM users u
                 LEFT JOIN profil_warga w ON w.user_id = u.id
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
                "SELECT v.id, v.kode_laporan, v.judul, lk.deskripsi,
                        v.nama_pelapor, v.hp_pelapor, v.nama_kategori, v.label_status, v.status,
                        v.tingkat_prioritas, v.lokasi_detail, v.nama_petugas, v.created_at, v.alasan_penolakan
                 FROM v_laporan_ringkasan v
                 LEFT JOIN laporan_kerusakan lk ON lk.id = v.id
                 ORDER BY FIELD(v.status, 'menunggu_verifikasi', 'diverifikasi', 'ditugaskan', 'dalam_pengerjaan', 'perlu_tindak_lanjut', 'selesai', 'ditolak', 'dibatalkan'), v.created_at DESC
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
                "SELECT kode_laporan, judul, nama_pelapor, hp_pelapor, nama_kategori, label_status, status,
                        tingkat_prioritas, lokasi_detail, nama_petugas, created_at, latitude, longitude
                 FROM v_laporan_ringkasan
                 WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan')
                 ORDER BY created_at ASC
                 LIMIT 50"
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

function getPetugasActiveTasks(int $petugasId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT v.id, v.kode_laporan, v.judul, lk.deskripsi,
                        v.nama_pelapor, v.hp_pelapor, v.nama_kategori, v.label_status, v.status,
                        v.tingkat_prioritas, v.lokasi_detail, v.latitude, v.longitude,
                        v.created_at, v.tanggal_target_selesai, v.catatan_petugas
                 FROM v_laporan_ringkasan v
                 LEFT JOIN laporan_kerusakan lk ON lk.id = v.id
                 WHERE v.kode_petugas = (SELECT kode_user FROM users WHERE id = ?)
                   AND v.status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')
                 ORDER BY v.tingkat_prioritas DESC, v.created_at ASC",
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

function getProfileData(int $userId): array
{
    try {
        return \App\Db\Database::getInstance()
            ->query(
                "SELECT u.id, u.kode_user, u.nik, u.nama_lengkap, u.email, u.no_hp, u.role, u.status_akun,
                        w.no_kk, w.no_rt, w.no_rw, w.alamat_lengkap, w.kelurahan, w.kecamatan,
                        w.kota_kabupaten, w.tempat_lahir, w.tanggal_lahir, w.jenis_kelamin,
                        w.agama, w.status_perkawinan, w.pekerjaan, w.status_tinggal, w.tanggal_pindah_masuk
                 FROM users u
                 LEFT JOIN profil_warga w ON w.user_id = u.id
                 WHERE u.id = ? LIMIT 1",
                [$userId]
            )
            ->fetch() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getCompletedReportsForPdf(array $user): array
{
    $role = (string)($user['role'] ?? '');
    $userId = (int)($user['id'] ?? 0);

    if (!in_array($role, ['petugas', 'rt'], true)) {
        return [];
    }

    try {
        $db = \App\Db\Database::getInstance();
        $sql = "SELECT lk.id, lk.kode_laporan, lk.judul, lk.deskripsi,
                       pelapor.nama_lengkap AS nama_pelapor, pelapor.no_hp AS hp_pelapor,
                       kl.nama_kategori,
                       'Selesai' AS label_status, lk.status, lk.tingkat_prioritas, lk.lokasi_detail,
                       lk.maps_url, lk.latitude, lk.longitude,
                       petugas.nama_lengkap AS nama_petugas, petugas.no_hp AS hp_petugas,
                       lk.catatan_petugas, lk.created_at, lk.tanggal_mulai_kerjakan,
                       lk.tanggal_selesai, (TO_DAYS(COALESCE(lk.tanggal_selesai, NOW())) - TO_DAYS(lk.created_at)) AS durasi_hari,
                       lk.rating_warga, lk.ulasan_warga
                FROM laporan_kerusakan lk
                INNER JOIN users pelapor ON pelapor.id = lk.pelapor_id
                INNER JOIN kategori_laporan kl ON kl.id = lk.kategori_id
                LEFT JOIN users petugas ON petugas.id = lk.petugas_id
                WHERE lk.status = 'selesai'";
        $params = [];

        if ($role === 'petugas') {
            $sql .= " AND lk.petugas_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY COALESCE(lk.tanggal_selesai, lk.created_at) DESC LIMIT 100";
        return $db->query($sql, $params)->fetchAll() ?: [];
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
            'primary_title' => 'Data Utama',
            'secondary_title' => 'Ringkasan',
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
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, label_status, status, tingkat_prioritas, lokasi_detail, created_at FROM v_laporan_ringkasan WHERE pelapor_id = ? ORDER BY created_at DESC LIMIT 6", [$userId]),
        'secondary_rows' => dashboardRows(
            $db,
            "SELECT id, kode_laporan, judul, label_status, status, tingkat_prioritas, nama_petugas,
                    catatan_petugas, tanggal_mulai_kerjakan, tanggal_selesai, created_at,
                    (SELECT COUNT(*) FROM foto_laporan fl WHERE fl.laporan_id = v_laporan_ringkasan.id) AS jumlah_foto
             FROM v_laporan_ringkasan
             WHERE pelapor_id = ?
               AND status IN ('ditugaskan', 'dalam_pengerjaan', 'perlu_tindak_lanjut', 'selesai')
             ORDER BY FIELD(status, 'perlu_tindak_lanjut', 'dalam_pengerjaan', 'ditugaskan', 'selesai'), COALESCE(tanggal_selesai, tanggal_mulai_kerjakan, created_at) DESC
             LIMIT 8",
            [$userId]
        ),
        'primary_title' => 'Laporan Terbaru Saya',
        'secondary_title' => 'Ringkasan',
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
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, status, tingkat_prioritas, created_at FROM v_laporan_ringkasan ORDER BY created_at DESC LIMIT 7"),
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
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, status, tingkat_prioritas, lokasi_detail, created_at FROM v_laporan_ringkasan WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?) AND status IN ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut') ORDER BY tingkat_prioritas DESC, created_at ASC LIMIT 8", [$userId]),
        'secondary_rows' => dashboardRows($db, "SELECT kode_laporan, judul, label_status, status, tanggal_selesai, created_at FROM v_laporan_ringkasan WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?) AND status = 'selesai' ORDER BY tanggal_selesai DESC LIMIT 5", [$userId]),
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
        'rows' => dashboardRows($db, "SELECT kode_laporan, judul, nama_pelapor, label_status, status, tingkat_prioritas, lokasi_detail, created_at FROM v_laporan_ringkasan WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan') ORDER BY created_at ASC LIMIT 8"),
        'secondary_rows' => dashboardRows($db, "SELECT kode_petugas, nama_petugas, jml_aktif, jml_selesai, terakhir_aktif FROM v_monitoring_petugas ORDER BY jml_aktif DESC, nama_petugas LIMIT 8"),
        'primary_title' => 'Laporan Darurat Aktif',
        'secondary_title' => 'Monitoring Petugas',
    ];
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

function dashboardCount(\App\Db\Database $db, string $sql, array $params = []): int
{
    return (int)$db->query($sql, $params)->fetchColumn();
}

function dashboardRows(\App\Db\Database $db, string $sql, array $params = []): array
{
    return $db->query($sql, $params)->fetchAll() ?: [];
}

function deleteReportFiles(array $photos): void
{
    $uploadRoot = realpath(__DIR__ . '/../../backend/uploads');
    if ($uploadRoot === false) {
        return;
    }

    foreach ($photos as $photo) {
        $relative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string)($photo['path_file'] ?? ''));
        $path = realpath(__DIR__ . '/../../backend/' . $relative);
        if ($path !== false && strpos($path, $uploadRoot) === 0 && is_file($path)) {
            @unlink($path);
        }
    }
}
