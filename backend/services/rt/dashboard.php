<?php
declare(strict_types=1);

namespace App\Services\Rt;

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
        $this->auth->requireRt();
        Response::success([
            'rekap_status' => $this->db->query("SELECT * FROM v_rekap_status_laporan")->fetch(),
            'monitoring_petugas' => $this->db->query("SELECT * FROM v_monitoring_petugas ORDER BY jml_aktif DESC, nama_petugas")->fetchAll(),
            'darurat' => $this->db->query("SELECT * FROM v_laporan_ringkasan WHERE tingkat_prioritas = 'darurat' AND status NOT IN ('selesai','ditolak','dibatalkan') ORDER BY created_at ASC")->fetchAll(),
            'peta' => $this->db->query("SELECT * FROM v_laporan_peta ORDER BY created_at DESC LIMIT 200")->fetchAll(),
        ]);
    }
}
