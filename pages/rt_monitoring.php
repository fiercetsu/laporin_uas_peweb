<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring Petugas - RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .page-card { border: 0; border-radius: 8px; }
        .text-small { font-size: .875rem; }
    </style>
</head>
<body>
<main class="container-fluid py-4 px-3 px-lg-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a class="text-decoration-none text-secondary text-small" href="<?= e(urlFor('/dashboard')) ?>">
                <i class="bi bi-arrow-left"></i> Kembali ke dashboard
            </a>
            <h1 class="h3 mb-1 mt-2">Monitoring Petugas</h1>
            <p class="text-secondary mb-0">Pantau beban tugas aktif, total selesai, dan aktivitas petugas.</p>
        </div>
        <a class="btn btn-outline-danger" href="<?= e(urlFor('/rt-darurat')) ?>">
            <i class="bi bi-exclamation-triangle me-1"></i>Laporan Darurat
        </a>
    </div>

    <section class="row g-3 mb-4">
        <?php if ($officers === []): ?>
            <div class="col-12"><div class="card page-card shadow-sm"><div class="card-body text-center text-secondary py-5">Belum ada data petugas.</div></div></div>
        <?php endif; ?>
        <?php foreach ($officers as $row): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card page-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold"><?= e((string)$row['nama_petugas']) ?></div>
                                <div class="text-secondary text-small"><?= e((string)$row['kode_petugas']) ?> | <?= e((string)($row['hp_petugas'] ?? '-')) ?></div>
                            </div>
                            <span class="badge align-self-start text-bg-<?= $row['status_akun'] === 'aktif' ? 'success' : 'secondary' ?>"><?= e((string)$row['status_akun']) ?></span>
                        </div>
                        <div class="row text-center mt-4">
                            <div class="col-4"><div class="text-secondary text-small">Total</div><strong class="h4"><?= e((string)$row['total_ditugaskan']) ?></strong></div>
                            <div class="col-4"><div class="text-secondary text-small">Aktif</div><strong class="h4"><?= e((string)$row['jml_aktif']) ?></strong></div>
                            <div class="col-4"><div class="text-secondary text-small">Selesai</div><strong class="h4"><?= e((string)$row['jml_selesai']) ?></strong></div>
                        </div>
                        <div class="text-secondary text-small mt-3">Rata-rata selesai: <?= e((string)($row['rata_hari_selesai'] ?? '-')) ?> hari</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card page-card shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Tugas Aktif Petugas</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Laporan</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($activeTasks === []): ?>
                    <tr><td class="text-center text-secondary py-5" colspan="6">Belum ada tugas aktif.</td></tr>
                <?php endif; ?>
                <?php foreach ($activeTasks as $task): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string)$task['kode_laporan']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string)$task['judul']) ?></div>
                            <div class="text-secondary text-small"><?= e((string)$task['tingkat_prioritas']) ?></div>
                        </td>
                        <td><?= e((string)$task['lokasi_detail']) ?></td>
                        <td><span class="badge text-bg-light"><?= e((string)$task['label_status']) ?></span></td>
                        <td><?= e((string)($task['nama_petugas'] ?? '-')) ?></td>
                        <td class="text-secondary text-small"><?= e(formatDashboardDate((string)$task['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
