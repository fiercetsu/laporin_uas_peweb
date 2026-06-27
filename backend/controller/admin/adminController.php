<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\Admin\DashboardService;
use App\Services\Admin\KategoriService;
use App\Services\Admin\KonfigurasiService;
use App\Services\Admin\LaporanService;
use App\Services\Admin\PengumumanService;
use App\Services\Admin\UsersService;

class AdminController
{
    public function dashboard(): void { (new DashboardService())->handle(); }
    public function users(): void { (new UsersService())->list(); }
    public function updateUserStatus(int $id): void { (new UsersService())->updateStatus($id, $_POST); }
    public function createPetugas(): void { (new UsersService())->createPetugas($_POST); }

    public function laporan(): void { (new LaporanService())->list(); }
    public function verifyLaporan(int $id): void { (new LaporanService())->verify($id, $_POST); }
    public function rejectLaporan(int $id): void { (new LaporanService())->reject($id, $_POST); }
    public function assignLaporan(int $id): void { (new LaporanService())->assign($id, $_POST); }

    public function konfigurasi(): void { (new KonfigurasiService())->list(); }
    public function updateKonfigurasi(): void { (new KonfigurasiService())->update($_POST); }

    public function kategori(): void { (new KategoriService())->list(); }
    public function createKategori(): void { (new KategoriService())->create($_POST); }
    public function updateKategori(int $id): void { (new KategoriService())->update($id, $_POST); }

    public function pengumuman(): void { (new PengumumanService())->create($_POST); }
}
