<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Validator;

class PengumumanService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
    }

    public function create(array $input): void
    {
        $payload = $this->auth->requireAdmin();
        $v = new Validator($input);
        $v->required('judul', 'Judul')->required('isi', 'Isi')->inList('tipe', ['umum', 'darurat', 'kegiatan', 'informasi']);
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }
        $this->db->query(
            "INSERT INTO pengumuman (dibuat_oleh, judul, isi, tipe, is_pinned, is_published, tanggal_publish, tanggal_expired)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [(int)$payload['sub'], $v->get('judul'), $v->get('isi'), $v->get('tipe') ?: 'umum', (int)($input['is_pinned'] ?? 0), (int)($input['is_published'] ?? 0), $input['tanggal_publish'] ?? null, $input['tanggal_expired'] ?? null]
        );
        Response::created(['id' => (int)$this->db->lastInsertId()], 'Pengumuman berhasil dibuat.');
    }
}
