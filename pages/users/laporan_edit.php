<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Laporan - Laporin RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .page-card { border: 0; border-radius: 8px; }
        .text-small { font-size: .875rem; }
    </style>
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a class="text-decoration-none text-secondary text-small" href="<?= e(urlFor('/laporan-saya')) ?>">
                <i class="bi bi-arrow-left"></i> Kembali ke Laporan Saya
            </a>
            <h1 class="h3 mb-1 mt-2">Edit Laporan</h1>
            <p class="text-secondary mb-0">
                <?= e((string)$report['kode_laporan']) ?> | Status: <?= e((string)$report['label_status']) ?>
            </p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/laporan-saya')) ?>">
            <i class="bi bi-list-check me-1"></i>Laporan Saya
        </a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form class="card page-card shadow-sm" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
        <div class="card-body p-3 p-lg-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="<?= e((string)$report['id']) ?>">
            <input type="hidden" id="latitude" name="latitude" value="<?= old('latitude', (string)($report['latitude'] ?? '')) ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?= old('longitude', (string)($report['longitude'] ?? '')) ?>">
            <input type="hidden" id="akurasi_gps_meter" name="akurasi_gps_meter" value="<?= old('akurasi_gps_meter', (string)($report['akurasi_gps_meter'] ?? '')) ?>">

            <div class="row g-3">
                <div class="col-lg-7">
                    <label class="form-label" for="judul">Judul Laporan</label>
                    <input class="form-control" id="judul" name="judul" value="<?= old('judul', (string)$report['judul']) ?>" maxlength="200" required>
                </div>
                <div class="col-lg-5">
                    <label class="form-label" for="kategori_id">Kategori</label>
                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string)$category['id']) ?>" <?= selected('kategori_id', (string)$category['id'], (string)$report['kategori_id']) ?>>
                                <?= e((string)$category['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="deskripsi">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?= old('deskripsi', (string)$report['deskripsi']) ?></textarea>
                </div>
                <div class="col-lg-8">
                    <label class="form-label" for="lokasi_detail">Lokasi Detail</label>
                    <input class="form-control" id="lokasi_detail" name="lokasi_detail" value="<?= old('lokasi_detail', (string)$report['lokasi_detail']) ?>" maxlength="255" required>
                </div>
                <div class="col-lg-4">
                    <label class="form-label" for="tingkat_prioritas">Prioritas</label>
                    <select class="form-select" id="tingkat_prioritas" name="tingkat_prioritas">
                        <option value="rendah" <?= selected('tingkat_prioritas', 'rendah', (string)$report['tingkat_prioritas']) ?>>Rendah</option>
                        <option value="sedang" <?= selected('tingkat_prioritas', 'sedang', (string)$report['tingkat_prioritas']) ?>>Sedang</option>
                        <option value="tinggi" <?= selected('tingkat_prioritas', 'tinggi', (string)$report['tingkat_prioritas']) ?>>Tinggi</option>
                        <option value="darurat" <?= selected('tingkat_prioritas', 'darurat', (string)$report['tingkat_prioritas']) ?>>Darurat</option>
                    </select>
                </div>
                <div class="col-lg-8">
                    <label class="form-label" for="maps_url">Link Google Maps</label>
                    <input class="form-control" id="maps_url" name="maps_url" value="<?= old('maps_url', (string)($report['maps_url'] ?? '')) ?>">
                </div>
                <div class="col-lg-4">
                    <label class="form-label" for="fotos">Tambah Foto Bukti</label>
                    <input class="form-control" id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                </div>
            </div>

            <div class="border rounded-2 bg-light p-3 mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">Lokasi GPS realtime</div>
                        <div class="text-secondary text-small" id="gpsStatus">
                            <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                                Lokasi tersimpan: <?= e((string)$report['latitude']) ?>, <?= e((string)$report['longitude']) ?>
                            <?php else: ?>
                                Belum ada lokasi GPS.
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary" id="gpsButton" type="button">
                        <i class="bi bi-geo-alt me-1"></i>Perbarui Lokasi
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-light" href="<?= e(urlFor('/laporan-saya')) ?>">Batal</a>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(urlFor('/pages/laporan.js')) ?>"></script>
</body>
</html>
