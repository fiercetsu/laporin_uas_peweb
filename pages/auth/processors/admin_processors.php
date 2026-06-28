<?php
declare(strict_types=1);

function processAdminUserForm(): array
{
    $action = (string)($_POST['action'] ?? 'status');

    switch ($action) {
        case 'create_user':
            return processAdminCreateUserForm();
        case 'delete_user':
            return processAdminDeleteUserForm();
        default:
            return processAdminUserStatusForm();
    }
}

function processAdminUserStatusForm(): array
{
    $admin = requireAdminWeb();
    $userId = (int)($_POST['user_id'] ?? 0);
    $status = (string)($_POST['status_akun'] ?? '');
    $role = (string)($_POST['role'] ?? '');

    if ($userId < 1) {
        return [['User tidak valid.'], ''];
    }

    if ($userId === (int)$admin['id']) {
        return [['Tidak bisa mengubah status atau role akun sendiri.'], ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        $target = $db->query("SELECT id, role, kode_user FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();
        if (!$target) {
            return [['User tidak ditemukan.'], ''];
        }

        // Initialize counters
        $db->query("INSERT INTO konfigurasi (kunci, nilai) VALUES ('counter_rt', '0') ON DUPLICATE KEY UPDATE kunci=kunci");
        $db->query("INSERT INTO konfigurasi (kunci, nilai) VALUES ('counter_warga', '0') ON DUPLICATE KEY UPDATE kunci=kunci");

        $db->beginTransaction();

        if ($status !== '' && in_array($status, ['aktif', 'nonaktif', 'pending'], true)) {
            $db->query("UPDATE users SET status_akun = ? WHERE id = ?", [$status, $userId]);
            if ($status !== 'aktif') {
                $db->query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$userId]);
            }
        }

        if ($role !== '' && in_array($role, ['warga', 'admin', 'petugas', 'rt'], true) && $role !== $target['role']) {
            $kode = $target['kode_user'];
            $codeGen = new \App\Utils\CodeGenerator();
            if ($role === 'warga' && ($kode === null || strpos((string)$kode, 'WRG-') !== 0)) {
                $kode = $codeGen->wargaCode();
            } elseif ($role === 'petugas' && ($kode === null || strpos((string)$kode, 'PTG-') !== 0)) {
                $kode = $codeGen->petugasCode();
            } elseif ($role === 'admin' && ($kode === null || strpos((string)$kode, 'DRT-') !== 0)) {
                $kode = $codeGen->adminCode();
            } elseif ($role === 'rt' && ($kode === null || strpos((string)$kode, 'RT-') !== 0)) {
                $kode = $codeGen->rtCode();
            }
            $db->query("UPDATE users SET role = ?, kode_user = ? WHERE id = ?", [$role, $kode, $userId]);
        }

        $db->query("UPDATE users SET updated_at = NOW() WHERE id = ?", [$userId]);
        $db->commit();

        return [[], 'Data user berhasil diperbarui.'];
    } catch (Throwable $e) {
        if (isset($db) && $db->pdo()->inTransaction()) {
            $db->rollback();
        }
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}

function processAdminCreateUserForm(): array
{
    requireAdminWeb();

    $nik = preg_replace('/\D+/', '', (string)($_POST['nik'] ?? ''));
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $noHp = preg_replace('/[^\d+]/', '', (string)($_POST['no_hp'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'warga');
    $status = (string)($_POST['status_akun'] ?? 'aktif');
    $noRt = trim((string)($_POST['no_rt'] ?? ''));
    $noRw = trim((string)($_POST['no_rw'] ?? ''));
    $alamat = trim((string)($_POST['alamat_lengkap'] ?? ''));
    $errors = [];

    if (!preg_match('/^\d{16}$/', $nik)) {
        $errors[] = 'NIK wajib 16 digit angka.';
    }
    if (mb_strlen($nama) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if (!in_array($role, ['warga', 'petugas', 'rt', 'admin'], true)) {
        $errors[] = 'Role user tidak valid.';
    }
    if (!in_array($status, ['aktif', 'nonaktif', 'pending'], true)) {
        $errors[] = 'Status akun tidak valid.';
    }
    if ($role === 'warga' && ($noRt === '' || $noRw === '' || $alamat === '')) {
        $errors[] = 'RT, RW, dan alamat wajib diisi untuk user warga.';
    }
    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        if ($db->query("SELECT id FROM users WHERE nik = ? LIMIT 1", [$nik])->fetch()) {
            return [['NIK sudah terdaftar.'], ''];
        }
        if ($email !== '' && $db->query("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])->fetch()) {
            return [['Email sudah digunakan.'], ''];
        }

        $code = null;
        $codeGen = new \App\Utils\CodeGenerator();
        if ($role === 'warga') {
            $code = $codeGen->wargaCode();
        } elseif ($role === 'petugas') {
            $code = $codeGen->petugasCode();
        } elseif ($role === 'admin') {
            $code = $codeGen->adminCode();
        } elseif ($role === 'rt') {
            $code = $codeGen->rtCode();
        }

        $db->beginTransaction();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
        $db->query(
            "INSERT INTO users (kode_user, nik, nama_lengkap, email, no_hp, password_hash, role, status_akun)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$code, $nik, $nama, $email !== '' ? $email : null, $noHp !== '' ? $noHp : null, $hash, $role, $status]
        );
        $userId = (int)$db->lastInsertId();

        if ($role === 'warga') {
            $db->query(
                "INSERT INTO profil_warga (user_id, no_rt, no_rw, alamat_lengkap) VALUES (?, ?, ?, ?)",
                [$userId, $noRt, $noRw, $alamat]
            );
        }

        $db->commit();
        return [[], 'User baru berhasil ditambahkan.'];
    } catch (Throwable $e) {
        if (isset($db)) {
            $db->rollback();
        }
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
}

function processAdminDeleteUserForm(): array
{
    $admin = requireAdminWeb();
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId < 1) {
        return [['User tidak valid.'], ''];
    }
    if ($userId === (int)$admin['id']) {
        return [['Tidak bisa menghapus akun sendiri.'], ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        $target = $db->query("SELECT id, nama_lengkap, status_akun FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();
        if (!$target) {
            return [['User tidak ditemukan.'], ''];
        }

        $db->beginTransaction();
        $db->query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$userId]);

        if ($target['status_akun'] === 'nonaktif') {
            $db->query("SET FOREIGN_KEY_CHECKS = 0");
            $db->query("DELETE FROM users WHERE id = ?", [$userId]);
            $db->query("DELETE FROM profil_warga WHERE user_id = ?", [$userId]);
            $db->query("SET FOREIGN_KEY_CHECKS = 1");
        } else {
            $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        }

        $db->commit();

        return [[], 'User berhasil dihapus.'];
    } catch (Throwable $e) {
        if (isset($db) && $db->pdo()->inTransaction()) {
            $db->rollback();
        }
        return [['User tidak bisa dihapus karena masih terkait data laporan atau aktivitas lain. Nonaktifkan akun jika data masih harus disimpan.'], ''];
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

        $prioritas = trim((string)($_POST['tingkat_prioritas'] ?? ''));
        $extraAssign = [
            'petugas_id' => $petugasId,
            'tanggal_target_selesai' => nullableInput(trim((string)($_POST['tanggal_target_selesai'] ?? ''))),
        ];
        if (in_array($prioritas, ['rendah', 'sedang', 'tinggi', 'darurat'], true)) {
            $extraAssign['tingkat_prioritas'] = $prioritas;
        }

        adminChangeReportStatus($db, $report, (int)$admin['id'], 'ditugaskan', trim((string)($_POST['catatan_admin'] ?? 'Laporan ditugaskan ke petugas.')), $extraAssign);

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
        if (in_array($field, ['petugas_id', 'tanggal_target_selesai', 'alasan_penolakan', 'tingkat_prioritas'], true)) {
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
