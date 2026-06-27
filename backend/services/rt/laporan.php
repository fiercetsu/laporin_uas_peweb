<?php
declare(strict_types=1);

namespace App\Services\Rt;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
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
        $this->auth->requireRt();
        Response::success($this->db->query("SELECT * FROM v_laporan_ringkasan ORDER BY created_at DESC LIMIT 200")->fetchAll());
    }
}
