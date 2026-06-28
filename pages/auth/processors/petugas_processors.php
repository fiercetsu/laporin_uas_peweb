<?php
declare(strict_types=1);

function processPetugasTaskForm(): array
{
    $petugas = requirePetugasWeb();
    $taskId = (int)($_POST['laporan_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($taskId < 1 || !in_array($action, ['mulai', 'progress', 'tindak_lanjut', 'selesai'], true)) {
        return [['Aksi tugas tidak valid.'], ''];
    }

    try {
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
    } catch (Throwable $e) {
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
    }
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
