<?php
declare(strict_types=1);

namespace App\Services\Petugas;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class DashboardService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
    }

    public function handle(): void
    {
        $payload = $this->auth->requirePetugas();
        Response::success([
            'ringkasan' => $this->db->query(
                "SELECT COUNT(*) total,
                        SUM(status = 'ditugaskan') ditugaskan,
                        SUM(status = 'dalam_pengerjaan') dalam_pengerjaan,
                        SUM(status = 'perlu_tindak_lanjut') perlu_tindak_lanjut,
                        SUM(status = 'selesai') selesai
                 FROM laporan_kerusakan WHERE petugas_id = ?",
                [$payload['sub']]
            )->fetch(),
            'tugas_aktif' => $this->db->query(
                "SELECT * FROM v_laporan_ringkasan
                 WHERE kode_petugas = (SELECT kode_user FROM users WHERE id = ?)
                   AND status IN ('ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')
                 ORDER BY tingkat_prioritas DESC, created_at ASC",
                [$payload['sub']]
            )->fetchAll(),
        ]);
    }
}
