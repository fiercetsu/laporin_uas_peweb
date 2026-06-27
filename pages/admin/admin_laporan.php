<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Laporan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .report-card { border: 0; border-radius: 8px; }
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
            <h1 class="h3 mb-1 mt-2">Verifikasi Laporan</h1>
            <p class="text-secondary mb-0">Verifikasi, tolak, atau tugaskan laporan ke petugas aktif.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/admin-users')) ?>">
            <i class="bi bi-people me-1"></i>Kelola User
        </a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <?php if ($reports === []): ?>
            <div class="col-12"><div class="card report-card shadow-sm"><div class="card-body text-center text-secondary py-5">Belum ada laporan.</div></div></div>
        <?php endif; ?>
        <?php foreach ($reports as $row): ?>
            <div class="col-12">
                <section class="card report-card shadow-sm">
                    <div class="card-body p-3 p-lg-4">
                        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge text-bg-light"><?= e((string)$row['kode_laporan']) ?></span>
                                    <span class="badge text-bg-<?= $row['status'] === 'menunggu_verifikasi' ? 'warning' : ($row['status'] === 'ditolak' ? 'danger' : 'success') ?>"><?= e((string)$row['label_status']) ?></span>
                                    <span class="badge text-bg-secondary"><?= e((string)$row['tingkat_prioritas']) ?></span>
                                </div>
                                <h2 class="h5 mb-1"><?= e((string)$row['judul']) ?></h2>
                                <div class="text-secondary text-small mb-2">
                                    Pelapor: <?= e((string)$row['nama_pelapor']) ?><?= !empty($row['hp_pelapor']) ? ' | HP: ' . e((string)$row['hp_pelapor']) : '' ?>
                                </div>
                                <div class="row g-2 text-small">
                                    <div class="col-md-4"><strong>Kategori:</strong> <?= e((string)$row['nama_kategori']) ?></div>
                                    <div class="col-md-4"><strong>Lokasi:</strong> <?= e((string)$row['lokasi_detail']) ?></div>
                                    <div class="col-md-4"><strong>Petugas:</strong> <?= e((string)($row['nama_petugas'] ?? '-')) ?></div>
                                </div>
                            </div>
                            <div style="min-width: 360px;">
                                <form class="border rounded-2 p-3 bg-light mb-2" method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <select class="form-select form-select-sm" name="petugas_id" required>
                                                <option value="">Pilih petugas</option>
                                                <?php foreach ($petugas as $person): ?>
                                                    <option value="<?= e((string)$person['id']) ?>"><?= e((string)$person['nama_lengkap']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-5">
                                            <input class="form-control form-control-sm" name="tanggal_target_selesai" type="date">
                                        </div>
                                        <div class="col-12">
                                            <input class="form-control form-control-sm" name="catatan_admin" placeholder="Catatan penugasan opsional">
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-sm btn-primary w-100" type="submit">Tugaskan</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="d-flex gap-2">
                                    <form method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button class="btn btn-sm btn-success" type="submit">Verifikasi</button>
                                    </form>
                                    <form class="d-flex gap-2 flex-grow-1" method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input class="form-control form-control-sm" name="alasan_penolakan" placeholder="Alasan tolak" required>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Tolak</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
