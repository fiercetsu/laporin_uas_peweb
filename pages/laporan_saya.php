<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Saya - Laporin RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .table-card { border: 0; border-radius: 8px; overflow: hidden; }
        .text-small { font-size: .875rem; }
    </style>
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a class="text-decoration-none text-secondary text-small" href="<?= e(urlFor('/dashboard')) ?>">
                <i class="bi bi-arrow-left"></i> Kembali ke dashboard
            </a>
            <h1 class="h3 mb-1 mt-2">Laporan Saya</h1>
            <p class="text-secondary mb-0">Daftar laporan yang pernah kamu kirim.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(urlFor('/laporan')) ?>">
            <i class="bi bi-file-earmark-plus me-1"></i>Buat Laporan
        </a>
    </div>

    <section class="card table-card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Laporan</th>
                        <th>Lokasi</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Prioritas</th>
                        <th>Tanggal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports === []): ?>
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="8">Belum ada laporan.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string)$report['kode_laporan']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string)$report['judul']) ?></div>
                            </td>
                            <td><?= e((string)$report['lokasi_detail']) ?></td>
                            <td><?= e((string)$report['nama_kategori']) ?></td>
                            <td><span class="badge text-bg-light"><?= e((string)$report['label_status']) ?></span></td>
                            <td><?= e((string)$report['tingkat_prioritas']) ?></td>
                            <td class="text-secondary text-small"><?= e(formatDashboardDate((string)$report['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(urlFor('/edit-laporan') . '?id=' . (int)$report['id']) ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form method="post" action="<?= e(urlFor('/hapus-laporan')) ?>" onsubmit="return confirm('Hapus laporan ini? Data yang dihapus tidak bisa dikembalikan.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= e((string)$report['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
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
