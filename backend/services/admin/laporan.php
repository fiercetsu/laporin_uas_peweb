<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Utils\Response;

class LaporanService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
    }

    public function list(): void
    {
        $this->auth->requireAdmin();
        $q = SecurityMiddleware::sanitizeQuery();
        $where = ['1=1'];
        $params = [];
        foreach (['status', 'tingkat_prioritas'] as $field) {
            if (!empty($q[$field])) {
                $where[] = "{$field} = ?";
                $params[] = $q[$field];
            }
        }
        if (!empty($q['search'])) {
            $s = '%' . str_replace(['%', '_'], ['\%', '\_'], $q['search']) . '%';
            $where[] = '(kode_laporan LIKE ? OR judul LIKE ? OR nama_pelapor LIKE ? OR lokasi_detail LIKE ?)';
            array_push($params, $s, $s, $s, $s);
        }
        $page = max(1, (int)($q['page'] ?? 1));
        $limit = min(50, max(1, (int)($q['limit'] ?? 20)));
        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query("SELECT COUNT(*) FROM v_laporan_ringkasan WHERE {$whereSql}", $params)->fetchColumn();
        $rows = $this->db->query("SELECT * FROM v_laporan_ringkasan WHERE {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$limit, ($page - 1) * $limit]))->fetchAll();
        Response::success(['data' => $rows, 'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit]]);
    }

    public function verify(int $id, array $input): void
    {
        $payload = $this->auth->requireAdmin();
        $this->changeStatus($id, (int)$payload['sub'], 'diverifikasi', $input['catatan_admin'] ?? 'Laporan diverifikasi.');
    }

    public function reject(int $id, array $input): void
    {
        $payload = $this->auth->requireAdmin();
        $reason = trim((string)($input['alasan_penolakan'] ?? ''));
        if ($reason === '') {
            Response::error('Alasan penolakan wajib diisi.', 422);
        }
        $this->changeStatus($id, (int)$payload['sub'], 'ditolak', $reason, ['alasan_penolakan' => $reason]);
    }

    public function assign(int $id, array $input): void
    {
        $payload = $this->auth->requireAdmin();
        $petugasId = (int)($input['petugas_id'] ?? 0);
        $petugas = $this->db->query("SELECT id FROM users WHERE id = ? AND role = 'petugas' AND status_akun = 'aktif'", [$petugasId])->fetch();
        if (!$petugas) {
            Response::error('Petugas tidak valid.', 422);
        }
        $this->changeStatus($id, (int)$payload['sub'], 'ditugaskan', $input['catatan_admin'] ?? 'Laporan ditugaskan ke petugas.', [
            'petugas_id' => $petugasId,
            'tanggal_target_selesai' => $input['tanggal_target_selesai'] ?? null,
        ]);
    }

    private function changeStatus(int $id, int $userId, string $newStatus, string $note, array $extra = []): void
    {
        $laporan = $this->db->query("SELECT status FROM laporan_kerusakan WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$laporan) {
            Response::notFound('Laporan tidak ditemukan.');
        }
        $sets = ['status = ?', 'updated_at = NOW()'];
        $params = [$newStatus];
        if (in_array($newStatus, ['diverifikasi', 'ditolak', 'ditugaskan'], true)) {
            $sets[] = 'diverifikasi_oleh = ?';
            $params[] = $userId;
        }
        foreach ($extra as $field => $value) {
            if (in_array($field, ['petugas_id', 'tanggal_target_selesai', 'alasan_penolakan'], true)) {
                $sets[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        $params[] = $id;
        $this->db->query("UPDATE laporan_kerusakan SET " . implode(', ', $sets) . " WHERE id = ?", $params);
        $this->db->query("INSERT INTO histori_laporan (laporan_id, diubah_oleh, status_lama, status_baru, keterangan) VALUES (?, ?, ?, ?, ?)", [$id, $userId, $laporan['status'], $newStatus, $note]);
        Response::success(null, 'Status laporan berhasil diperbarui.');
    }
}
