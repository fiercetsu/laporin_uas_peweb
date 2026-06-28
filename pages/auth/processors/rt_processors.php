<?php
declare(strict_types=1);

function processRtMonitoringForm(): array
{
    $rt = requireRtWeb();
    $reportId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($reportId < 1 || !in_array($action, ['assign'], true)) {
        return [['Aksi laporan tidak valid.'], ''];
    }

    try {
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
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}
