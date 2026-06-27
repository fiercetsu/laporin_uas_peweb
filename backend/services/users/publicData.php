<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Utils\Response;

class PublicDataService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function categories(): void
    {
        $rows = $this->db->query("SELECT id, nama_kategori, deskripsi, ikon FROM kategori_laporan WHERE is_active = 1 ORDER BY nama_kategori")->fetchAll();
        Response::success($rows);
    }

    public function announcements(): void
    {
        $rows = $this->db->query(
            "SELECT id, judul, isi, tipe, is_pinned, tanggal_publish, tanggal_expired
             FROM pengumuman
             WHERE is_published = 1 AND (tanggal_publish IS NULL OR tanggal_publish <= NOW())
               AND (tanggal_expired IS NULL OR tanggal_expired >= NOW())
             ORDER BY is_pinned DESC, tanggal_publish DESC, created_at DESC"
        )->fetchAll();
        Response::success($rows);
    }
}
