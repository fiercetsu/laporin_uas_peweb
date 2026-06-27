<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Tugas - Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .table-card { border: 0; border-radius: 8px; overflow: hidden; }
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
            <h1 class="h3 mb-1 mt-2">Riwayat Tugas</h1>
            <p class="text-secondary mb-0">Daftar tugas selesai atau ditutup yang pernah ditangani.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/petugas-tugas')) ?>">
            <i class="bi bi-tools me-1"></i>Tugas Aktif
        </a>
    </div>

    <section class="card table-card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Laporan</th>
                        <th>Pelapor</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Selesai</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($tasks === []): ?>
                    <tr><td class="text-center text-secondary py-5" colspan="7">Belum ada riwayat tugas.</td></tr>
                <?php endif; ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string)$task['kode_laporan']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string)$task['judul']) ?></div>
                            <div class="text-secondary text-small"><?= e((string)$task['nama_kategori']) ?> | <?= e((string)$task['tingkat_prioritas']) ?></div>
                        </td>
                        <td><?= e((string)$task['nama_pelapor']) ?></td>
                        <td><?= e((string)$task['lokasi_detail']) ?></td>
                        <td><span class="badge text-bg-light"><?= e((string)$task['label_status']) ?></span></td>
                        <td><?= e((string)($task['durasi_hari'] ?? '-')) ?> hari</td>
                        <td class="text-secondary text-small"><?= e(formatDashboardDate((string)($task['tanggal_selesai'] ?? $task['created_at'] ?? ''))) ?></td>
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
