<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class NotificationService
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
        $payload = $this->auth->requireAuth();
        Response::success([
            'unread_count' => (int)$this->db->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0", [$payload['sub']])->fetchColumn(),
            'data' => $this->db->query("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", [$payload['sub']])->fetchAll(),
        ]);
    }

    public function markRead(int $id): void
    {
        $payload = $this->auth->requireAuth();
        $this->db->query("UPDATE notifikasi SET is_read = 1 WHERE id = ? AND user_id = ?", [$id, $payload['sub']]);
        Response::success(null, 'Notifikasi ditandai dibaca.');
    }
}
