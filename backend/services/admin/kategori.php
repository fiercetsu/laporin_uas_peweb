<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Validator;

class KategoriService
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
        Response::success($this->db->query("SELECT * FROM kategori_laporan ORDER BY id")->fetchAll());
    }

    public function create(array $input): void
    {
        $this->auth->requireAdmin();
        $v = new Validator($input);
        $v->required('nama_kategori', 'Nama kategori')->maxLength('nama_kategori', 100);
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }
        $this->db->query(
            "INSERT INTO kategori_laporan (nama_kategori, deskripsi, ikon, is_active) VALUES (?, ?, ?, ?)",
            [$v->get('nama_kategori'), $v->get('deskripsi') ?: null, $v->get('ikon') ?: null, (int)($input['is_active'] ?? 1)]
        );
        Response::created(['id' => (int)$this->db->lastInsertId()], 'Kategori berhasil dibuat.');
    }

    public function update(int $id, array $input): void
    {
        $this->auth->requireAdmin();
        $this->db->query(
            "UPDATE kategori_laporan SET nama_kategori = COALESCE(NULLIF(?, ''), nama_kategori), deskripsi = ?, ikon = ?, is_active = ? WHERE id = ?",
            [$input['nama_kategori'] ?? '', $input['deskripsi'] ?? null, $input['ikon'] ?? null, (int)($input['is_active'] ?? 1), $id]
        );
        Response::success(null, 'Kategori berhasil diperbarui.');
    }
}
