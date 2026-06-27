<?php
declare(strict_types=1);

namespace App\Services\Petugas;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Utils\FileUpload;
use App\Utils\Response;

class TugasService
{
    private Database $db;
    private AuthMiddleware $auth;
    private FileUpload $upload;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
        $this->upload = new FileUpload();
    }

    public function list(): void
    {
        $payload = $this->auth->requirePetugas();
        $q = SecurityMiddleware::sanitizeQuery();
        $where = ["kode_petugas = (SELECT kode_user FROM users WHERE id = ?)"];
        $params = [$payload['sub']];
        if (!empty($q['status'])) {
            $where[] = 'status = ?';
            $params[] = $q['status'];
        }
        $rows = $this->db->query(
            "SELECT * FROM v_laporan_ringkasan WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT 100",
            $params
        )->fetchAll();
        Response::success($rows);
    }

    public function show(int $id): void
    {
        $payload = $this->auth->requirePetugas();
        $laporan = $this->ownedLaporan($id, (int)$payload['sub']);
        Response::success([
            'laporan' => $laporan,
            'foto' => $this->db->query("SELECT * FROM foto_laporan WHERE laporan_id = ? ORDER BY created_at", [$id])->fetchAll(),
            'respons' => $this->db->query(
                "SELECT r.*, u.nama_lengkap, u.role FROM respons_laporan r JOIN users u ON u.id = r.direspons_oleh WHERE r.laporan_id = ? ORDER BY r.created_at",
                [$id]
            )->fetchAll(),
            'histori' => $this->db->query("SELECT * FROM histori_laporan WHERE laporan_id = ? ORDER BY created_at", [$id])->fetchAll(),
        ]);
    }

    public function mulai(int $id): void
    {
        $payload = $this->auth->requirePetugas();
        $laporan = $this->ownedLaporan($id, (int)$payload['sub']);
        if (!in_array($laporan['status'], ['ditugaskan', 'perlu_tindak_lanjut'], true)) {
            Response::error('Laporan tidak dalam status yang bisa mulai dikerjakan.', 422);
        }
        $this->updateStatus($id, (int)$payload['sub'], $laporan['status'], 'dalam_pengerjaan', 'Petugas mulai mengerjakan laporan.', ['tanggal_mulai_kerjakan = COALESCE(tanggal_mulai_kerjakan, NOW())']);
    }

    public function progress(int $id, array $input, array $files): void
    {
        $payload = $this->auth->requirePetugas();
        $laporan = $this->ownedLaporan($id, (int)$payload['sub']);
        $note = trim((string)($input['catatan_petugas'] ?? $input['isi_respons'] ?? 'Update progress dari petugas.'));
        $this->db->query("UPDATE laporan_kerusakan SET catatan_petugas = ?, status = 'dalam_pengerjaan', updated_at = NOW() WHERE id = ?", [$note, $id]);
        $this->db->query(
            "INSERT INTO respons_laporan (laporan_id, direspons_oleh, isi_respons, tipe_respons, is_internal) VALUES (?, ?, ?, 'update_status', ?)",
            [$id, $payload['sub'], $note, (int)($input['is_internal'] ?? 0)]
        );
        if ($laporan['status'] !== 'dalam_pengerjaan') {
            $this->db->query("INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, 'dalam_pengerjaan', ?)", [$id, $payload['sub'], $laporan['status'], $note]);
        }
        $this->savePhotos($id, (int)$payload['sub'], 'proses', $files);
        Response::success(null, 'Progress berhasil disimpan.');
    }

    public function selesai(int $id, array $input, array $files): void
    {
        $payload = $this->auth->requirePetugas();
        $laporan = $this->ownedLaporan($id, (int)$payload['sub']);
        $note = trim((string)($input['catatan_petugas'] ?? 'Laporan sudah diselesaikan oleh petugas.'));
        $this->db->query(
            "UPDATE laporan_kerusakan SET status = 'selesai', catatan_petugas = ?, tanggal_selesai = NOW(), updated_at = NOW() WHERE id = ?",
            [$note, $id]
        );
        $this->db->query("INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, 'selesai', ?)", [$id, $payload['sub'], $laporan['status'], $note]);
        $this->db->query("INSERT INTO respons_laporan (laporan_id, direspons_oleh, isi_respons, tipe_respons) VALUES (?, ?, ?, 'penyelesaian')", [$id, $payload['sub'], $note]);
        $this->savePhotos($id, (int)$payload['sub'], 'bukti_selesai', $files);
        Response::success(null, 'Laporan ditandai selesai.');
    }

    public function tindakLanjut(int $id, array $input): void
    {
        $payload = $this->auth->requirePetugas();
        $laporan = $this->ownedLaporan($id, (int)$payload['sub']);
        $note = trim((string)($input['catatan_petugas'] ?? 'Laporan membutuhkan tindak lanjut.'));
        $this->updateStatus($id, (int)$payload['sub'], $laporan['status'], 'perlu_tindak_lanjut', $note);
    }

    private function ownedLaporan(int $id, int $petugasId): array
    {
        $laporan = $this->db->query("SELECT * FROM laporan_kerusakan WHERE id = ? AND petugas_id = ? LIMIT 1", [$id, $petugasId])->fetch();
        if (!$laporan) {
            Response::notFound('Tugas laporan tidak ditemukan.');
        }
        return $laporan;
    }

    private function updateStatus(int $id, int $userId, string $oldStatus, string $newStatus, string $note, array $extraSet = []): void
    {
        $sets = array_merge(['status = ?', 'updated_at = NOW()'], $extraSet);
        $this->db->query("UPDATE laporan_kerusakan SET " . implode(', ', $sets) . " WHERE id = ?", array_merge([$newStatus], [$id]));
        $this->db->query("INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, ?, ?)", [$id, $userId, $oldStatus, $newStatus, $note]);
        Response::success(null, 'Status tugas berhasil diperbarui.');
    }

    private function savePhotos(int $laporanId, int $userId, string $type, array $files): void
    {
        if (empty($files['fotos']['name'])) {
            return;
        }
        $names = is_array($files['fotos']['name']) ? $files['fotos']['name'] : [$files['fotos']['name']];
        foreach ($names as $i => $name) {
            $file = is_array($files['fotos']['name'])
                ? ['name' => $name, 'type' => $files['fotos']['type'][$i], 'tmp_name' => $files['fotos']['tmp_name'][$i], 'error' => $files['fotos']['error'][$i], 'size' => $files['fotos']['size'][$i]]
                : $files['fotos'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $uploaded = $this->upload->fotoLaporan($file, 'laporan');
            $this->db->query(
                "INSERT INTO foto_laporan (laporan_id, nama_file, path_file, ukuran_file, tipe_mime, tipe_foto, diunggah_oleh)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$laporanId, $uploaded['nama_file'], $uploaded['path_file'], $uploaded['ukuran_file'], $uploaded['tipe_mime'], $type, $userId]
            );
        }
    }
}
