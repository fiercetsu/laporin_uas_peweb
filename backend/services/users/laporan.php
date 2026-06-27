<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Utils\CodeGenerator;
use App\Utils\FileUpload;
use App\Utils\Response;
use App\Utils\Validator;

class LaporanService
{
    private Database $db;
    private AuthMiddleware $auth;
    private CodeGenerator $code;
    private FileUpload $upload;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
        $this->code = new CodeGenerator();
        $this->upload = new FileUpload();
    }

    public function index(): void
    {
        $payload = $this->auth->requireAuth();
        $query = SecurityMiddleware::sanitizeQuery();
        $where = ['1=1'];
        $params = [];

        if ($payload['role'] === 'warga') {
            $where[] = 'pelapor_id = ?';
            $params[] = $payload['sub'];
        }
        foreach (['status', 'tingkat_prioritas'] as $field) {
            if (!empty($query[$field])) {
                $where[] = "{$field} = ?";
                $params[] = $query[$field];
            }
        }
        if (!empty($query['kategori_id']) && ctype_digit((string)$query['kategori_id'])) {
            $where[] = 'kategori_id = ?';
            $params[] = (int)$query['kategori_id'];
        }

        $page = max(1, (int)($query['page'] ?? 1));
        $limit = min(50, max(1, (int)($query['limit'] ?? 10)));
        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query("SELECT COUNT(*) FROM v_laporan_ringkasan WHERE {$whereSql}", $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT * FROM v_laporan_ringkasan WHERE {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, ($page - 1) * $limit])
        )->fetchAll();

        Response::success(['data' => $data, 'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit]]);
    }

    public function show(int $id): void
    {
        $payload = $this->auth->requireAuth();
        $laporan = $this->db->query("SELECT * FROM v_laporan_ringkasan WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$laporan) {
            Response::notFound('Laporan tidak ditemukan.');
        }
        if ($payload['role'] === 'warga' && (int)$laporan['pelapor_id'] !== (int)$payload['sub']) {
            Response::forbidden();
        }

        $responsSql = "SELECT r.id, r.isi_respons, r.tipe_respons, r.is_internal, r.created_at, u.nama_lengkap, u.role
                       FROM respons_laporan r JOIN users u ON u.id = r.direspons_oleh
                       WHERE r.laporan_id = ?";
        if ($payload['role'] === 'warga') {
            $responsSql .= " AND r.is_internal = 0";
        }
        Response::success([
            'laporan' => $laporan,
            'foto' => $this->db->query("SELECT id, nama_file, path_file, tipe_foto, created_at FROM foto_laporan WHERE laporan_id = ? ORDER BY created_at", [$id])->fetchAll(),
            'respons' => $this->db->query($responsSql . " ORDER BY r.created_at", [$id])->fetchAll(),
            'histori' => $this->db->query("SELECT h.*, u.nama_lengkap FROM histori_laporan h JOIN users u ON u.id = h.diubah_oleh WHERE laporan_id = ? ORDER BY h.created_at", [$id])->fetchAll(),
        ]);
    }

    public function create(array $input, array $files): void
    {
        $payload = $this->auth->requireWarga();
        $v = new Validator($input);
        $v->required('kategori_id', 'Kategori')->numeric('kategori_id')
            ->required('judul', 'Judul')->minLength('judul', 5)->maxLength('judul', 200)
            ->required('deskripsi', 'Deskripsi')->minLength('deskripsi', 10)
            ->required('lokasi_detail', 'Lokasi')
            ->inList('tingkat_prioritas', ['rendah', 'sedang', 'tinggi', 'darurat'])
            ->latitude()->longitude()
            ->numeric('akurasi_gps_meter', 'Akurasi GPS');
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }

        if (!$this->db->query("SELECT id FROM kategori_laporan WHERE id = ? AND is_active = 1", [(int)$v->get('kategori_id')])->fetch()) {
            Response::error('Kategori tidak valid.', 422);
        }

        $this->db->beginTransaction();
        try {
            $kode = $this->code->laporanCode();
            $this->db->query(
                "INSERT INTO laporan_kerusakan
                 (kode_laporan, pelapor_id, kategori_id, judul, deskripsi, lokasi_detail, latitude, longitude, akurasi_gps_meter, maps_url, tingkat_prioritas)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $kode, $payload['sub'], (int)$v->get('kategori_id'), $v->get('judul'), $v->get('deskripsi'),
                    $v->get('lokasi_detail'), $v->get('latitude') ?: null, $v->get('longitude') ?: null,
                    $v->get('akurasi_gps_meter') ?: null, $v->get('maps_url') ?: null, $v->get('tingkat_prioritas') ?: 'sedang',
                ]
            );
            $laporanId = (int)$this->db->lastInsertId();
            $this->insertHistory($laporanId, (int)$payload['sub'], null, 'menunggu_verifikasi', 'Laporan dibuat warga.');
            $this->savePhotos($laporanId, (int)$payload['sub'], 'bukti_awal', $files);
            $this->db->commit();
            Response::created(['id' => $laporanId, 'kode_laporan' => $kode], 'Laporan berhasil dibuat.');
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function cancel(int $id): void
    {
        $payload = $this->auth->requireWarga();
        $laporan = $this->db->query("SELECT pelapor_id, status FROM laporan_kerusakan WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$laporan) {
            Response::notFound('Laporan tidak ditemukan.');
        }
        if ((int)$laporan['pelapor_id'] !== (int)$payload['sub']) {
            Response::forbidden();
        }
        if (!in_array($laporan['status'], ['menunggu_verifikasi', 'diverifikasi'], true)) {
            Response::error('Laporan sudah diproses dan tidak bisa dibatalkan.', 422);
        }
        $this->db->query("UPDATE laporan_kerusakan SET status = 'dibatalkan' WHERE id = ?", [$id]);
        $this->insertHistory($id, (int)$payload['sub'], $laporan['status'], 'dibatalkan', 'Dibatalkan oleh warga.');
        Response::success(null, 'Laporan berhasil dibatalkan.');
    }

    public function rating(int $id, array $input): void
    {
        $payload = $this->auth->requireWarga();
        $rating = (int)($input['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            Response::error('Rating harus 1 sampai 5.', 422);
        }
        $laporan = $this->db->query("SELECT pelapor_id, status FROM laporan_kerusakan WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$laporan) {
            Response::notFound('Laporan tidak ditemukan.');
        }
        if ((int)$laporan['pelapor_id'] !== (int)$payload['sub'] || $laporan['status'] !== 'selesai') {
            Response::forbidden('Rating hanya bisa diberikan oleh pelapor untuk laporan selesai.');
        }
        $this->db->query("UPDATE laporan_kerusakan SET rating_warga = ?, ulasan_warga = ? WHERE id = ?", [$rating, $input['ulasan'] ?? null, $id]);
        Response::success(null, 'Terima kasih, rating berhasil disimpan.');
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

    private function insertHistory(int $laporanId, int $userId, ?string $old, string $new, string $note): void
    {
        $this->db->query(
            "INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, ?, ?)",
            [$laporanId, $userId, $old, $new, $note]
        );
    }
}
