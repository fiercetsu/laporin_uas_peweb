<?php
declare(strict_types=1);

namespace App\Controllers\Petugas;

use App\Services\Petugas\DashboardService;
use App\Services\Petugas\TugasService;

class PetugasController
{
    public function dashboard(): void { (new DashboardService())->handle(); }
    public function tugas(): void { (new TugasService())->list(); }
    public function show(int $id): void { (new TugasService())->show($id); }
    public function mulai(int $id): void { (new TugasService())->mulai($id); }
    public function progress(int $id): void { (new TugasService())->progress($id, $_POST, $_FILES); }
    public function selesai(int $id): void { (new TugasService())->selesai($id, $_POST, $_FILES); }
    public function tindakLanjut(int $id): void { (new TugasService())->tindakLanjut($id, $_POST); }
}
