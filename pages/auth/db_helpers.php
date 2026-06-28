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
                        akurasi_gps_meter, maps_url, tingkat_prioritas, status, created_at
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
                "SELECT id, kode_laporan, judul, nama_kategori, label_status, tingkat_prioritas, lokasi_detail, created_at
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
                 WHERE u.role != 'admin'
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
