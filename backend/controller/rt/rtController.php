<?php
declare(strict_types=1);

namespace App\Controllers\Rt;

use App\Services\Rt\DashboardService;
use App\Services\Rt\LaporanService;
use App\Services\Rt\PengumumanService;
use App\Services\Rt\PetugasService;

class RtController
{
    public function dashboard(): void { (new DashboardService())->handle(); }
    public function laporan(): void { (new LaporanService())->list(); }
    public function petugas(): void { (new PetugasService())->list(); }
    public function publishPengumuman(int $id): void { (new PengumumanService())->publish($id); }
    public function unpublishPengumuman(int $id): void { (new PengumumanService())->unpublish($id); }
}
