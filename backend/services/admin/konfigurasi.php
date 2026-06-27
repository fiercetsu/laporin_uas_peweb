<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class KonfigurasiService
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
        Response::success($this->db->query("SELECT kunci, nilai, keterangan FROM konfigurasi ORDER BY id")->fetchAll());
    }

    public function update(array $input): void
    {
        $this->auth->requireAdmin();
        foreach ($input as $key => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', (string)$key)) {
                $this->db->query("UPDATE konfigurasi SET nilai = ? WHERE kunci = ?", [(string)$value, (string)$key]);
            }
        }
        Response::success(null, 'Konfigurasi berhasil diperbarui.');
    }
}
