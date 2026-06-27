<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Darurat - RT</title>
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
            <h1 class="h3 mb-1 mt-2">Laporan Darurat</h1>
            <p class="text-secondary mb-0">Pantau laporan prioritas darurat yang belum selesai.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/rt-monitoring')) ?>">
            <i class="bi bi-person-workspace me-1"></i>Monitoring Petugas
        </a>
    </div>

    <section class="row g-3 mb-4">
        <div class="col-md-4"><div class="card page-card shadow-sm"><div class="card-body"><div class="text-secondary text-small">Darurat Aktif</div><strong class="display-6"><?= e((string)$summary['total']) ?></strong></div></div></div>
        <div class="col-md-4"><div class="card page-card shadow-sm"><div class="card-body"><div class="text-secondary text-small">Menunggu Verifikasi</div><strong class="display-6"><?= e((string)$summary['menunggu']) ?></strong></div></div></div>
        <div class="col-md-4"><div class="card page-card shadow-sm"><div class="card-body"><div class="text-secondary text-small">Sedang Diproses</div><strong class="display-6"><?= e((string)$summary['diproses']) ?></strong></div></div></div>
    </section>

    <section class="card page-card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Laporan</th>
                        <th>Pelapor</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($reports === []): ?>
                    <tr><td class="text-center text-secondary py-5" colspan="7">Tidak ada laporan darurat aktif.</td></tr>
                <?php endif; ?>
                <?php foreach ($reports as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string)$row['kode_laporan']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string)$row['judul']) ?></div>
                            <div class="text-secondary text-small"><?= e((string)$row['nama_kategori']) ?></div>
                        </td>
                        <td>
                            <div><?= e((string)$row['nama_pelapor']) ?></div>
                            <div class="text-secondary text-small"><?= e((string)($row['hp_pelapor'] ?? '-')) ?></div>
                        </td>
                        <td>
                            <div><?= e((string)$row['lokasi_detail']) ?></div>
                            <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                <a class="text-small" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$row['latitude']) ?>,<?= e((string)$row['longitude']) ?>">Buka Maps</a>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge text-bg-warning"><?= e((string)$row['label_status']) ?></span></td>
                        <td><?= e((string)($row['nama_petugas'] ?? '-')) ?></td>
                        <td class="text-secondary text-small"><?= e(formatDashboardDate((string)$row['created_at'])) ?></td>
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
