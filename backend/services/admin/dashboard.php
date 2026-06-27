<?php
declare(strict_types=1);

namespace App\Services\Admin;

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
        $this->auth->requireAdmin();
        Response::success([
            'rekap_status' => $this->db->query("SELECT * FROM v_rekap_status_laporan")->fetch(),
            'kategori' => $this->db->query(
                "SELECT kl.nama_kategori, COUNT(lk.id) jumlah
                 FROM kategori_laporan kl LEFT JOIN laporan_kerusakan lk ON lk.kategori_id = kl.id
                 GROUP BY kl.id, kl.nama_kategori ORDER BY jumlah DESC"
            )->fetchAll(),
            'akun' => $this->db->query(
                "SELECT COUNT(*) total, SUM(role='warga') warga, SUM(role='petugas') petugas,
                        SUM(role='admin') admin, SUM(role='rt') rt, SUM(status_akun='pending') pending
                 FROM users"
            )->fetch(),
            'terbaru' => $this->db->query("SELECT * FROM v_laporan_ringkasan ORDER BY created_at DESC LIMIT 8")->fetchAll(),
        ]);
    }
}
