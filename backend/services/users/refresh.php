<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Utils\JwtHelper;
use App\Utils\Response;

class RefreshService
{
    private Database $db;
    private JwtHelper $jwt;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwt = new JwtHelper();
    }

    public function handle(array $input): void
    {
        $refresh = (string)($input['refresh_token'] ?? '');
        if ($refresh === '') {
            Response::error('Refresh token wajib diisi.', 400);
        }

        try {
            $claims = $this->jwt->verify($refresh, 'refresh');
        } catch (\Throwable $e) {
            Response::unauthorized('Refresh token tidak valid.');
        }

        $session = $this->db->query(
            "SELECT s.user_id, s.expired_at, u.nik, u.nama_lengkap, u.role, u.kode_user, u.status_akun
             FROM user_sessions s JOIN users u ON u.id = s.user_id
             WHERE s.session_token = ? AND s.is_active = 1 LIMIT 1",
            [hash('sha256', $refresh)]
        )->fetch();

        if ($session && strtotime($session['expired_at']) < time()) {
            $this->db->query("DELETE FROM user_sessions WHERE session_token = ?", [hash('sha256', $refresh)]);
        }

        if (!$session || strtotime($session['expired_at']) < time() || $session['status_akun'] !== 'aktif' || (int)$claims['sub'] !== (int)$session['user_id']) {
            Response::unauthorized('Sesi tidak aktif. Silakan login ulang.');
        }

        Response::success([
            'access_token' => $this->jwt->generateAccessToken([
                'sub' => (int)$session['user_id'],
                'nik' => $session['nik'],
                'nama' => $session['nama_lengkap'],
                'role' => $session['role'],
                'kode_user' => $session['kode_user'],
            ]),
            'token_type' => 'Bearer',
        ]);
    }
}
