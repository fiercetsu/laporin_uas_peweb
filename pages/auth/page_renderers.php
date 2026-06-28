<?php
declare(strict_types=1);

function renderAuthPage(string $mode, array $errors = [], string $success = ''): void
{
    header('Content-Type: text/html; charset=UTF-8');

    $isRegister = $mode === 'register';
    $title = $isRegister ? 'Register Warga' : 'Login';
    $action = urlFor($isRegister ? '/register' : '/login');
    $csrf = e(csrfToken());
    $nik = e((string)($_POST['nik'] ?? ''));
    $nama = e((string)($_POST['nama_lengkap'] ?? ''));
    $email = e((string)($_POST['email'] ?? ''));
    $noHp = e((string)($_POST['no_hp'] ?? ''));
    $noKk = e((string)($_POST['no_kk'] ?? ''));
    $noRt = e((string)($_POST['no_rt'] ?? ''));
    $noRw = e((string)($_POST['no_rw'] ?? ''));
    $alamat = e((string)($_POST['alamat_lengkap'] ?? ''));
    $kelurahan = e((string)($_POST['kelurahan'] ?? ''));
    $kecamatan = e((string)($_POST['kecamatan'] ?? ''));
    $kotaKabupaten = e((string)($_POST['kota_kabupaten'] ?? ''));
    $tempatLahir = e((string)($_POST['tempat_lahir'] ?? ''));
    $tanggalLahir = e((string)($_POST['tanggal_lahir'] ?? ''));
    $agama = e((string)($_POST['agama'] ?? ''));
    $pekerjaan = e((string)($_POST['pekerjaan'] ?? ''));
    $tanggalPindahMasuk = e((string)($_POST['tanggal_pindah_masuk'] ?? ''));

    require __DIR__ . ($isRegister ? '/../register.php' : '/../login.php');
}

function renderResetPasswordPage(array $errors = [], string $success = ''): void
{
    header('Content-Type: text/html; charset=UTF-8');
    $action = urlFor('/reset-password');
    $csrf = e(csrfToken());
    $email = e((string)($_POST['email'] ?? ''));
    $nik = e((string)($_POST['nik'] ?? ''));

    require __DIR__ . '/../reset_password.php';
}

function renderDashboardPage(): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $dashboard = buildDashboardData($user);

    require __DIR__ . '/../dashboard.php';
}

function renderLaporanPage(array $errors = [], string $success = ''): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $categories = getActiveCategories();
    $csrf = e(csrfToken());
    $action = urlFor('/laporan');

    require __DIR__ . '/../laporan.php';
}

function renderLaporanSayaPage(): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $reports = getMyReports((int)$user['id']);

    require __DIR__ . '/../users/laporan_saya.php';
}

function renderEditLaporanPage(array $errors = [], string $success = ''): void
{
    if (empty($_SESSION['auth_user'])) {
        redirectTo('/login');
    }

    if (($_SESSION['auth_user']['role'] ?? '') !== 'warga') {
        redirectTo('/dashboard');
    }

    $reportId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $report = getOwnedReport($reportId, (int)$_SESSION['auth_user']['id']);
    if (!$report) {
        redirectTo('/laporan-saya');
    }

    header('Content-Type: text/html; charset=UTF-8');
    $user = $_SESSION['auth_user'];
    $categories = getActiveCategories();
    $csrf = e(csrfToken());
    $action = urlFor('/edit-laporan') . '?id=' . $reportId;

    require __DIR__ . '/../users/laporan_edit.php';
}

function renderAdminUsersPage(array $errors = [], string $success = ''): void
{
    $admin = requireAdminWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $users = getAdminUsers();
    $csrf = e(csrfToken());

    require __DIR__ . '/../admin/admin_users.php';
}

function renderAdminLaporanPage(array $errors = [], string $success = ''): void
{
    $admin = requireAdminWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $reports = getAdminReports();
    $petugas = getActivePetugas();
    $csrf = e(csrfToken());

    require __DIR__ . '/../admin/admin_laporan.php';
}

function renderRtDaruratPage(): void
{
    $rt = requireRtWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $reports = getRtEmergencyReports();
    $summary = getRtEmergencySummary();

    require __DIR__ . '/../rt/rt_darurat.php';
}

function renderRtMonitoringPage(array $errors = [], string $success = ''): void
{
    $rt = requireRtWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $officers = getRtOfficerMonitoring();
    $activeTasks = getRtActiveTasks();

    $belumDikerjakan = getRtBelumDikerjakanTasks();
    $sedangDikerjakan = getRtSedangDikerjakanTasks();
    $selesaiTasks = getRtSelesaiTasks();

    $allReportIds = array_merge(
        array_column($belumDikerjakan, 'id'),
        array_column($sedangDikerjakan, 'id'),
        array_column($selesaiTasks, 'id')
    );

    $photosByReport = [];
    if ($allReportIds !== []) {
        $rawPhotos = getReportsPhotos($allReportIds);
        foreach ($rawPhotos as $p) {
            $photosByReport[(int)$p['laporan_id']][] = $p;
        }
    }

    $petugas = getActivePetugas();
    $csrf = e(csrfToken());

    require __DIR__ . '/../rt/rt_monitoring.php';
}

function renderPetugasTugasPage(array $errors = [], string $success = ''): void
{
    $petugas = requirePetugasWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $tasks = getPetugasActiveTasks((int)$petugas['id']);
    $csrf = e(csrfToken());

    require __DIR__ . '/../petugas/petugas_tugas.php';
}

function renderPetugasRiwayatPage(): void
{
    $petugas = requirePetugasWeb();

    header('Content-Type: text/html; charset=UTF-8');
    $tasks = getPetugasHistoryTasks((int)$petugas['id']);

    require __DIR__ . '/../petugas/petugas_riwayat.php';
}
