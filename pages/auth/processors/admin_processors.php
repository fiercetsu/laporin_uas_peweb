<?php
declare(strict_types=1);

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

    try {
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
    } catch (Throwable $e) {
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}

function processAdminLaporanForm(): array
{
    $admin = requireAdminWeb();
    $reportId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($reportId < 1 || !in_array($action, ['verify', 'reject', 'assign'], true)) {
        return [['Aksi laporan tidak valid.'], ''];
    }

    try {
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
    } catch (Throwable $e) {
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
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
