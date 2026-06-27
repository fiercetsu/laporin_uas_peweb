<?php
declare(strict_types=1);

$role = (string)($user['role'] ?? 'warga');
$roleTitles = [
    'warga' => 'Dashboard User',
    'admin' => 'Dashboard Admin',
    'petugas' => 'Dashboard Petugas',
    'rt' => 'Dashboard RT',
];
$roleDescriptions = [
    'warga' => 'Pantau laporan yang kamu kirim dan notifikasi terbaru.',
    'admin' => 'Kelola verifikasi laporan, akun warga, dan data operasional.',
    'petugas' => 'Pantau tugas aktif dan progres penanganan laporan.',
    'rt' => 'Lihat kondisi wilayah, laporan darurat, dan performa petugas.',
];
$badgeClass = [
    'warga' => 'text-bg-primary',
    'admin' => 'text-bg-dark',
    'petugas' => 'text-bg-success',
    'rt' => 'text-bg-warning',
][$role] ?? 'text-bg-primary';
$stats = $dashboard['stats'] ?? [];
$rows = $dashboard['rows'] ?? [];
$secondaryRows = $dashboard['secondary_rows'] ?? [];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($roleTitles[$role] ?? 'Dashboard') ?> - Sistem Layanan Publik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .app-shell { min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #0f172a;
            color: #fff;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.72);
            border-radius: 8px;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .content { flex: 1; min-width: 0; }
        .stat-card { border: 0; border-radius: 8px; }
        .table-card { border: 0; border-radius: 8px; overflow: hidden; }
        .text-small { font-size: .875rem; }
        @media (max-width: 991px) {
            .app-shell { flex-direction: column; }
            .sidebar { width: 100%; }
        }
    </style>
</head>
<body>
<div class="app-shell d-flex">
    <aside class="sidebar p-3">
        <div class="d-flex align-items-center gap-2 mb-4">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-2" style="width: 40px; height: 40px;">
                <i class="bi bi-megaphone-fill"></i>
            </span>
            <div>
                <div class="fw-bold">Laporin RT</div>
                <div class="text-white-50 text-small">Sistem Layanan Publik</div>
            </div>
        </div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link active" href="<?= e(urlFor('/dashboard')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <?php if ($role === 'warga'): ?>
                <a class="nav-link" href="<?= e(urlFor('/laporan')) ?>"><i class="bi bi-file-earmark-plus me-2"></i>Buat Laporan</a>
                <a class="nav-link" href="<?= e(urlFor('/laporan-saya')) ?>"><i class="bi bi-list-check me-2"></i>Laporan Saya</a>
            <?php elseif ($role === 'admin'): ?>
                <a class="nav-link" href="<?= e(urlFor('/admin-users')) ?>"><i class="bi bi-people me-2"></i>Kelola User</a>
                <a class="nav-link" href="<?= e(urlFor('/admin-laporan')) ?>"><i class="bi bi-clipboard-check me-2"></i>Verifikasi Laporan</a>
            <?php elseif ($role === 'petugas'): ?>
                <a class="nav-link" href="<?= e(urlFor('/petugas-tugas')) ?>"><i class="bi bi-tools me-2"></i>Tugas Aktif</a>
                <a class="nav-link" href="<?= e(urlFor('/petugas-riwayat')) ?>"><i class="bi bi-clock-history me-2"></i>Riwayat Tugas</a>
            <?php elseif ($role === 'rt'): ?>
                <a class="nav-link" href="<?= e(urlFor('/rt-darurat')) ?>"><i class="bi bi-exclamation-triangle me-2"></i>Laporan Darurat</a>
                <a class="nav-link" href="<?= e(urlFor('/rt-monitoring')) ?>"><i class="bi bi-person-workspace me-2"></i>Monitoring Petugas</a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="content p-3 p-lg-4">
        <header class="bg-white rounded-2 shadow-sm p-3 p-lg-4 mb-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="mb-2">
                    <span class="badge <?= e($badgeClass) ?> text-uppercase"><?= e($role) ?></span>
                    <span class="badge text-bg-light"><?= e((string)($user['status_akun'] ?? '-')) ?></span>
                </div>
                <h1 class="h3 mb-1"><?= e($roleTitles[$role] ?? 'Dashboard') ?></h1>
                <p class="text-secondary mb-0"><?= e($roleDescriptions[$role] ?? 'Ringkasan aktivitas akun.') ?></p>
            </div>
            <div class="d-flex align-items-start gap-3">
                <div class="text-end">
                    <div class="fw-bold"><?= e((string)$user['nama_lengkap']) ?></div>
                    <div class="text-secondary text-small"><?= e((string)$user['nik']) ?></div>
                    <div class="text-secondary text-small"><?= e((string)($user['kode_user'] ?? 'Warga')) ?></div>
                </div>
                <form method="post" action="<?= e(urlFor('/logout')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="btn btn-outline-danger btn-sm" type="submit">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </form>
            </div>
        </header>

        <?php if (!empty($dashboard['error'])): ?>
            <div class="alert alert-warning"><?= e((string)$dashboard['error']) ?></div>
        <?php endif; ?>

        <section class="row g-3 mb-4">
            <?php foreach ($stats as $stat): ?>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-secondary text-small"><?= e((string)$stat['label']) ?></div>
                            <div class="d-flex align-items-end justify-content-between mt-2">
                                <strong class="display-6 lh-1"><?= e((string)$stat['value']) ?></strong>
                                <span class="badge text-bg-<?= e((string)$stat['tone']) ?>">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="row g-4">
            <div class="col-xl-8">
                <div class="card table-card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h2 class="h5 mb-0"><?= e((string)($dashboard['primary_title'] ?? 'Data Utama')) ?></h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Prioritas</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($rows === []): ?>
                                <tr><td class="text-center text-secondary py-4" colspan="5">Belum ada data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string)($row['kode_laporan'] ?? '-')) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e((string)($row['judul'] ?? '-')) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)($row['nama_pelapor'] ?? $row['lokasi_detail'] ?? '')) ?></div>
                                    </td>
                                    <td><span class="badge text-bg-light"><?= e((string)($row['label_status'] ?? '-')) ?></span></td>
                                    <td><?= e((string)($row['tingkat_prioritas'] ?? '-')) ?></td>
                                    <td class="text-secondary text-small"><?= e(formatDashboardDate((string)($row['created_at'] ?? $row['tanggal_selesai'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card table-card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h2 class="h5 mb-0"><?= e((string)($dashboard['secondary_title'] ?? 'Ringkasan')) ?></h2>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if ($secondaryRows === []): ?>
                            <div class="list-group-item text-secondary py-4">Belum ada data.</div>
                        <?php endif; ?>
                        <?php foreach ($secondaryRows as $row): ?>
                            <div class="list-group-item py-3">
                                <?php if (isset($row['role'])): ?>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-capitalize"><?= e((string)$row['role']) ?></span>
                                        <strong><?= e((string)$row['jumlah']) ?></strong>
                                    </div>
                                <?php elseif (isset($row['kode_petugas'])): ?>
                                    <div class="fw-semibold"><?= e((string)$row['nama_petugas']) ?></div>
                                    <div class="text-secondary text-small"><?= e((string)$row['kode_petugas']) ?> | Aktif: <?= e((string)$row['jml_aktif']) ?> | Selesai: <?= e((string)$row['jml_selesai']) ?></div>
                                <?php else: ?>
                                    <div class="fw-semibold"><?= e((string)($row['judul'] ?? $row['kode_laporan'] ?? '-')) ?></div>
                                    <div class="text-secondary text-small"><?= e((string)($row['pesan'] ?? $row['label_status'] ?? '')) ?></div>
                                    <div class="text-secondary text-small"><?= e(formatDashboardDate((string)($row['created_at'] ?? $row['tanggal_selesai'] ?? ''))) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
