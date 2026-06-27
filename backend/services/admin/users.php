<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Utils\CodeGenerator;
use App\Utils\Response;
use App\Utils\Validator;

class UsersService
{
    private Database $db;
    private AuthMiddleware $auth;
    private CodeGenerator $code;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
        $this->code = new CodeGenerator();
    }

    public function list(): void
    {
        $this->auth->requireAdmin();
        $q = SecurityMiddleware::sanitizeQuery();
        $where = ['1=1'];
        $params = [];
        if (!empty($q['role']) && in_array($q['role'], ['warga', 'petugas', 'admin', 'rt'], true)) {
            $where[] = 'u.role = ?';
            $params[] = $q['role'];
        }
        if (!empty($q['status']) && in_array($q['status'], ['aktif', 'nonaktif', 'pending'], true)) {
            $where[] = 'u.status_akun = ?';
            $params[] = $q['status'];
        }
        if (!empty($q['search'])) {
            $s = '%' . str_replace(['%', '_'], ['\%', '\_'], $q['search']) . '%';
            $where[] = '(u.nik LIKE ? OR u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.kode_user LIKE ?)';
            array_push($params, $s, $s, $s, $s);
        }

        $page = max(1, (int)($q['page'] ?? 1));
        $limit = min(50, max(1, (int)($q['limit'] ?? 20)));
        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params)->fetchColumn();
        $rows = $this->db->query(
            "SELECT u.id, u.kode_user, u.nik, u.nama_lengkap, u.email, u.no_hp, u.role, u.status_akun, u.created_at,
                    pw.no_rt, pw.no_rw, pw.alamat_lengkap
            FROM users u LEFT JOIN profil_warga pw ON pw.user_id = u.id
            WHERE {$whereSql} ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, ($page - 1) * $limit])
        )->fetchAll();

        Response::success(['data' => $rows, 'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit]]);
    }

    public function updateStatus(int $id, array $input): void
    {
        $payload = $this->auth->requireAdmin();
        if ($id === (int)$payload['sub']) {
            Response::error('Tidak bisa mengubah status akun sendiri.', 422);
        }
        $v = new Validator($input);
        $v->required('status', 'Status')->inList('status', ['aktif', 'nonaktif', 'pending']);
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }
        $target = $this->db->query("SELECT id, role FROM users WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$target) {
            Response::notFound('User tidak ditemukan.');
        }
        if ($target['role'] === 'rt' && $payload['role'] !== 'rt') {
            Response::forbidden('Hanya RT yang bisa mengubah akun RT.');
        }
        $this->db->query("UPDATE users SET status_akun = ?, updated_at = NOW() WHERE id = ?", [$v->get('status'), $id]);
        if ($v->get('status') !== 'aktif') {
            $this->db->query("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?", [$id]);
        }
        Response::success(null, 'Status akun berhasil diperbarui.');
    }

    public function createPetugas(array $input): void
    {
        $this->auth->requireAdmin();
        $v = new Validator($input);
        $v->required('nik', 'NIK')->nik()
            ->required('nama_lengkap', 'Nama')->minLength('nama_lengkap', 3)
            ->required('password', 'Password')->password()
            ->email('email')->noHp('no_hp');
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }
        if ($this->db->query("SELECT id FROM users WHERE nik = ? LIMIT 1", [$v->get('nik')])->fetch()) {
            Response::error('NIK sudah terdaftar.', 409);
        }
        $kode = $this->code->petugasCode();
        $hash = password_hash((string)($input['password'] ?? ''), PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
        $this->db->query(
            "INSERT INTO users (kode_user, nik, nama_lengkap, email, no_hp, password_hash, role, status_akun)
             VALUES (?, ?, ?, ?, ?, ?, 'petugas', 'aktif')",
            [$kode, $v->get('nik'), $v->get('nama_lengkap'), $v->get('email') ?: null, $v->get('no_hp') ?: null, $hash]
        );
        Response::created(['kode_user' => $kode], 'Petugas berhasil dibuat.');
    }
}
