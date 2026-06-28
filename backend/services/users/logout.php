<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Utils\Response;

class LogoutService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function handle(array $input): void
    {
        $refresh = (string)($input['refresh_token'] ?? '');
        if ($refresh !== '') {
            $this->db->query("DELETE FROM user_sessions WHERE session_token = ?", [hash('sha256', $refresh)]);
        }
        Response::success(null, 'Logout berhasil.');
    }
}
