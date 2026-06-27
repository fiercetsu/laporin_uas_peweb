<?php
declare(strict_types=1);

namespace App\Services\Rt;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class PengumumanService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
    }

    public function publish(int $id): void
    {
        $this->auth->requireRt();
        $this->db->query("UPDATE pengumuman SET is_published = 1, tanggal_publish = COALESCE(tanggal_publish, NOW()) WHERE id = ?", [$id]);
        Response::success(null, 'Pengumuman dipublikasikan.');
    }

    public function unpublish(int $id): void
    {
        $this->auth->requireRt();
        $this->db->query("UPDATE pengumuman SET is_published = 0 WHERE id = ?", [$id]);
        Response::success(null, 'Pengumuman disembunyikan.');
    }
}
