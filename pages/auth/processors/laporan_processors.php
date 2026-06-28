<?php
declare(strict_types=1);

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
        'tingkat_prioritas' => 'sedang', // Prioritas hanya bisa diubah oleh petugas/RT/admin
    ];

    $errors = validateLaporanInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        if (!$db->query("SELECT id FROM kategori_laporan WHERE id = ? AND is_active = 1 LIMIT 1", [(int)$input['kategori_id']])->fetch()) {
            return [['Kategori laporan tidak valid.'], ''];
        }

        $db->beginTransaction();
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
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
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

    // Laporan selesai tidak bisa diedit oleh warga
    if ($existing['status'] === 'selesai') {
        return [['Laporan yang sudah selesai tidak dapat diedit.'], ''];
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
        'tingkat_prioritas' => 'sedang', // Diabaikan — prioritas tidak bisa diubah oleh warga
    ];

    $errors = validateLaporanInput($input);
    if ($errors !== []) {
        return [$errors, ''];
    }

    try {
        $db = \App\Db\Database::getInstance();
        if (!$db->query("SELECT id FROM kategori_laporan WHERE id = ? AND is_active = 1 LIMIT 1", [(int)$input['kategori_id']])->fetch()) {
            return [['Kategori laporan tidak valid.'], ''];
        }

        // Jika laporan ditolak dan diedit ulang, reset status ke menunggu_verifikasi
        $wasDitolak = $existing['status'] === 'ditolak';
        $newStatus = $wasDitolak ? 'menunggu_verifikasi' : $existing['status'];
        $statusSetClause = $wasDitolak ? ', status = ?, alasan_penolakan = NULL' : '';

        $params = [
            (int)$input['kategori_id'],
            $input['judul'],
            $input['deskripsi'],
            $input['lokasi_detail'],
            nullableInput($input['latitude']),
            nullableInput($input['longitude']),
            nullableInput($input['akurasi_gps_meter']),
            nullableInput($input['maps_url']),
            $existing['tingkat_prioritas'], // Warga tidak bisa mengubah prioritas
        ];
        if ($wasDitolak) {
            $params[] = 'menunggu_verifikasi';
        }
        $params[] = $reportId;
        $params[] = (int)$user['id'];

        $db->beginTransaction();
        $db->query(
            "UPDATE laporan_kerusakan
             SET kategori_id = ?, judul = ?, deskripsi = ?, lokasi_detail = ?, latitude = ?, longitude = ?,
                 akurasi_gps_meter = ?, maps_url = ?, tingkat_prioritas = ?{$statusSetClause}
             WHERE id = ? AND pelapor_id = ?",
            $params
        );

        $keterangan = $wasDitolak
            ? 'Laporan diperbarui dan diajukan ulang oleh warga.'
            : 'Laporan diedit oleh warga melalui halaman web.';
        $db->query(
            "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan)
             VALUES (?, ?, ?, ?, ?)",
            [$reportId, (int)$user['id'], $existing['status'], $newStatus, $keterangan]
        );

        saveLaporanPhotos($db, $reportId, (int)$user['id'], $_FILES);
        $db->commit();

        $msg = $wasDitolak
            ? 'Laporan berhasil diperbarui dan diajukan ulang untuk verifikasi.'
            : 'Laporan berhasil diperbarui.';
        return [[], $msg];
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        return [['Terjadi kesalahan: ' . $e->getMessage()], ''];
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

    // Laporan selesai tidak bisa dihapus oleh warga
    if ($existing['status'] === 'selesai') {
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

    if ($input['lokasi_detail'] === '') {
        $errors[] = 'Lokasi detail wajib diisi.';
    }

    if ($input['latitude'] !== '' && !preg_match('/^\-?\d+(\.\d+)?$/', $input['latitude'])) {
        $errors[] = 'Latitude tidak valid.';
    }

    if ($input['longitude'] !== '' && !preg_match('/^\-?\d+(\.\d+)?$/', $input['longitude'])) {
        $errors[] = 'Longitude tidak valid.';
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


