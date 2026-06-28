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
        do {
            $code = 'PTG-' . str_pad((string)$this->nextCounter('counter_petugas'), 5, '0', STR_PAD_LEFT);
            $exists = $this->db->query("SELECT id FROM users WHERE kode_user = ? LIMIT 1", [$code])->fetch();
        } while ($exists);
        return $code;
    }

    public function rtCode(): string
    {
        do {
            $code = 'RT-' . str_pad((string)$this->nextCounter('counter_rt'), 3, '0', STR_PAD_LEFT);
            $exists = $this->db->query("SELECT id FROM users WHERE kode_user = ? LIMIT 1", [$code])->fetch();
        } while ($exists);
        return $code;
    }

    public function wargaCode(): string
    {
        do {
            $code = 'WRG-' . str_pad((string)$this->nextCounter('counter_warga'), 3, '0', STR_PAD_LEFT);
            $exists = $this->db->query("SELECT id FROM users WHERE kode_user = ? LIMIT 1", [$code])->fetch();
        } while ($exists);
        return $code;
    }

    public function adminCode(): string
    {
        do {
            $code = 'DRT-' . str_pad((string)$this->nextCounter('counter_admin'), 5, '0', STR_PAD_LEFT);
            $exists = $this->db->query("SELECT id FROM users WHERE kode_user = ? LIMIT 1", [$code])->fetch();
        } while ($exists);
        return $code;
    }

    private function nextCounter(string $key): int
    {
        $this->db->query("UPDATE konfigurasi SET nilai = CAST(COALESCE(nilai, '0') AS UNSIGNED) + 1 WHERE kunci = ?", [$key]);
        $value = $this->db->query("SELECT nilai FROM konfigurasi WHERE kunci = ? LIMIT 1", [$key])->fetchColumn();
        return max(1, (int)$value);
    }
}
