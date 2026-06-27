<?php
declare(strict_types=1);

namespace App\Services\Rt;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class PetugasService
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
        Response::success($this->db->query("SELECT * FROM v_monitoring_petugas ORDER BY nama_petugas")->fetchAll());
    }
}
