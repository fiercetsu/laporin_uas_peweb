<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Utils\JwtHelper;
use App\Utils\Response;
use App\Utils\Validator;

class LoginService
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
        $v = new Validator($input);
        $v->required('nik', 'NIK')->nik()->required('password', 'Password');
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }

        $user = $this->db->query(
            "SELECT id, kode_user, nik, nama_lengkap, password_hash, role, status_akun, foto_profil
             FROM users WHERE nik = ? LIMIT 1",
            [$v->get('nik')]
        )->fetch();

        if (!$user || !password_verify((string)($input['password'] ?? ''), $user['password_hash'])) {
            Response::unauthorized('NIK atau password salah.');
        }
        if ($user['status_akun'] !== 'aktif') {
            Response::forbidden('Akun belum aktif atau sedang dinonaktifkan.');
        }

        $payload = [
            'sub' => (int)$user['id'],
            'nik' => $user['nik'],
            'nama' => $user['nama_lengkap'],
            'role' => $user['role'],
            'kode_user' => $user['kode_user'],
        ];
        $access = $this->jwt->generateAccessToken($payload);
        $refresh = $this->jwt->generateRefreshToken((int)$user['id']);

        $this->db->query("DELETE FROM user_sessions WHERE user_id = ?", [$user['id']]);
        $this->db->query(
            "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expired_at)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))",
            [
                $user['id'], hash('sha256', $refresh), $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                (int)($_ENV['JWT_REFRESH_EXPIRE_DAYS'] ?? 7),
            ]
        );

        unset($user['password_hash']);
        Response::success([
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => (int)($_ENV['JWT_EXPIRE_MINUTES'] ?? 60) * 60,
            'user' => $user,
        ], 'Login berhasil.');
    }
}
