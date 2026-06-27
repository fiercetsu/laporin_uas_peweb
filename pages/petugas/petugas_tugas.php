<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tugas Aktif - Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .task-card { border: 0; border-radius: 8px; }
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
            <h1 class="h3 mb-1 mt-2">Tugas Aktif</h1>
            <p class="text-secondary mb-0">Mulai pekerjaan, kirim progress, tandai tindak lanjut, atau selesaikan tugas.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/petugas-riwayat')) ?>">
            <i class="bi bi-clock-history me-1"></i>Riwayat Tugas
        </a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <?php if ($tasks === []): ?>
            <div class="col-12"><div class="card task-card shadow-sm"><div class="card-body text-center text-secondary py-5">Belum ada tugas aktif.</div></div></div>
        <?php endif; ?>
        <?php foreach ($tasks as $task): ?>
            <div class="col-12">
                <section class="card task-card shadow-sm">
                    <div class="card-body p-3 p-lg-4">
                        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge text-bg-light"><?= e((string)$task['kode_laporan']) ?></span>
                                    <span class="badge text-bg-info"><?= e((string)$task['label_status']) ?></span>
                                    <span class="badge text-bg-secondary"><?= e((string)$task['tingkat_prioritas']) ?></span>
                                </div>
                                <h2 class="h5 mb-1"><?= e((string)$task['judul']) ?></h2>
                                <div class="text-secondary text-small mb-2">Pelapor: <?= e((string)$task['nama_pelapor']) ?><?= !empty($task['hp_pelapor']) ? ' | HP: ' . e((string)$task['hp_pelapor']) : '' ?></div>
                                <div class="row g-2 text-small">
                                    <div class="col-md-4"><strong>Kategori:</strong> <?= e((string)$task['nama_kategori']) ?></div>
                                    <div class="col-md-4"><strong>Lokasi:</strong> <?= e((string)$task['lokasi_detail']) ?></div>
                                    <div class="col-md-4"><strong>Target:</strong> <?= e((string)($task['tanggal_target_selesai'] ?? '-')) ?></div>
                                </div>
                                <?php if (!empty($task['latitude']) && !empty($task['longitude'])): ?>
                                    <a class="text-small" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$task['latitude']) ?>,<?= e((string)$task['longitude']) ?>">Buka lokasi di Maps</a>
                                <?php endif; ?>
                            </div>
                            <div style="min-width: 360px;">
                                <form method="post" action="<?= e(urlFor('/petugas-tugas')) ?>" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="laporan_id" value="<?= e((string)$task['id']) ?>">
                                    <textarea class="form-control form-control-sm mb-2" name="catatan_petugas" rows="2" placeholder="Catatan progress atau penyelesaian"></textarea>
                                    <input class="form-control form-control-sm mb-2" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary" name="action" value="mulai" type="submit">Mulai</button>
                                        <button class="btn btn-sm btn-primary" name="action" value="progress" type="submit">Update Progress</button>
                                        <button class="btn btn-sm btn-outline-warning" name="action" value="tindak_lanjut" type="submit">Tindak Lanjut</button>
                                        <button class="btn btn-sm btn-success" name="action" value="selesai" type="submit">Selesai</button>
                                    </div>
                                </form>
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
