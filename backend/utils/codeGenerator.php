<?php
declare(strict_types=1);

namespace App\Utils;

use App\Db\Database;

class CodeGenerator
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function laporanCode(): string
    {
        $prefix = 'LPR-' . date('Ym') . '-';
        return $prefix . str_pad((string)$this->nextCounter('counter_laporan'), 5, '0', STR_PAD_LEFT);
    }

    public function petugasCode(): string
    {
        return 'PTG-' . str_pad((string)$this->nextCounter('counter_petugas'), 5, '0', STR_PAD_LEFT);
    }

    public function adminCode(): string
    {
        return 'DRT-' . str_pad((string)$this->nextCounter('counter_admin'), 5, '0', STR_PAD_LEFT);
    }

    private function nextCounter(string $key): int
    {
        $this->db->query("UPDATE konfigurasi SET nilai = CAST(COALESCE(nilai, '0') AS UNSIGNED) + 1 WHERE kunci = ?", [$key]);
        $value = $this->db->query("SELECT nilai FROM konfigurasi WHERE kunci = ? LIMIT 1", [$key])->fetchColumn();
        return max(1, (int)$value);
    }
}
