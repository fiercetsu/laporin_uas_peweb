<?php
declare(strict_types=1);

// Format photo arrays for JS presentation
$formatPhotos = function(array $reportPhotos) {
    return array_map(function($p) {
        return [
            'path_file_url' => urlFor('/backend/' . $p['path_file']),
            'tipe_foto' => $p['tipe_foto'],
            'created_at_fmt' => formatDashboardDate($p['created_at'])
        ];
    }, $reportPhotos);
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring & Penugasan Laporan - RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background: #f1f5f9; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }
        .page-card { 
            border: 0; 
            border-radius: 12px; 
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        }
        .nav-pills .nav-link {
            color: #64748b;
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 16px;
            transition: all 0.2s ease;
        }
        .nav-pills .nav-link:hover {
            background-color: #e2e8f0;
            color: #334155;
        }
        .nav-pills .nav-link.active {
            background-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
        }
        .badge-prioritas {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .prioritas-rendah { background-color: #f1f5f9; color: #475569; }
        .prioritas-sedang { background-color: #e0f2fe; color: #0369a1; }
        .prioritas-tinggi { background-color: #ffedd5; color: #c2410c; }
        .prioritas-darurat { background-color: #fee2e2; color: #b91c1c; }
        
        .badge-status {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .status-menunggu_verifikasi { background-color: #fef3c7; color: #d97706; }
        .status-diverifikasi { background-color: #e0e7ff; color: #4338ca; }
        .status-ditugaskan { background-color: #ecfdf5; color: #047857; }
        .status-dalam_pengerjaan { background-color: #eff6ff; color: #1d4ed8; }
        .status-perlu_tindak_lanjut { background-color: #faf5ff; color: #6b21a8; }
        .status-selesai { background-color: #d1fae5; color: #065f46; }
        .status-ditolak { background-color: #fee2e2; color: #991b1b; }
        .status-dibatalkan { background-color: #f1f5f9; color: #334155; }
        
        .officer-card {
            border-left: 4px solid #cbd5e1;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .officer-card.status-aktif { border-left-color: #10b981; }
        .officer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        .text-small { font-size: 0.8125rem; }
        .table > :not(caption) > * > * {
            padding: 12px 16px;
        }
        .btn-action {
            border-radius: 6px;
            font-weight: 500;
            padding: 6px 12px;
            font-size: 0.8125rem;
        }
    </style>
</head>
<body>
<main class="container-fluid py-4 px-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a class="text-decoration-none text-secondary text-small d-inline-flex align-items-center gap-1" href="<?= e(urlFor('/dashboard')) ?>">
                <i class="bi bi-arrow-left-short fs-5"></i> Kembali ke dashboard
            </a>
            <h1 class="h3 mb-1 mt-2 fw-bold text-slate-800">Monitoring & Penugasan Laporan</h1>
            <p class="text-secondary mb-0">Kelola penugasan, pantau petugas, dan pantau status pengerjaan laporan di wilayah RT.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-danger d-inline-flex align-items-center gap-2" href="<?= e(urlFor('/rt-darurat')) ?>">
                <i class="bi bi-exclamation-triangle"></i> Laporan Darurat
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
            <?php foreach ($errors as $error): ?>
                <div><i class="bi bi-x-circle me-2"></i><?= e($error) ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
            <div><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="mb-4">
        <ul class="nav nav-pills gap-2" id="rtTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="petugas-tab" data-bs-toggle="tab" data-bs-target="#tab-petugas" type="button" role="tab" aria-controls="tab-petugas" aria-selected="true">
                    <i class="bi bi-people me-2"></i>Beban Petugas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="belum-tab" data-bs-toggle="tab" data-bs-target="#tab-belum" type="button" role="tab" aria-controls="tab-belum" aria-selected="false">
                    <i class="bi bi-clipboard me-2"></i>Belum Dikerjakan 
                    <span class="badge bg-warning text-dark ms-1"><?= count($belumDikerjakan) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sedang-tab" data-bs-toggle="tab" data-bs-target="#tab-sedang" type="button" role="tab" aria-controls="tab-sedang" aria-selected="false">
                    <i class="bi bi-tools me-2"></i>Sedang Dikerjakan 
                    <span class="badge bg-primary ms-1"><?= count($sedangDikerjakan) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button" role="tab" aria-controls="tab-riwayat" aria-selected="false">
                    <i class="bi bi-clock-history me-2"></i>Selesai & Riwayat
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="rtTabContent">
        
        <!-- Tab 1: Beban Petugas -->
        <div class="tab-pane fade show active" id="tab-petugas" role="tabpanel" aria-labelledby="petugas-tab">
            <div class="row g-3">
                <?php if ($officers === []): ?>
                    <div class="col-12">
                        <div class="card page-card"><div class="card-body text-center text-secondary py-5">Belum ada data petugas aktif.</div></div>
                    </div>
                <?php endif; ?>
                <?php foreach ($officers as $row): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card page-card officer-card <?= $row['status_akun'] === 'aktif' ? 'status-aktif' : '' ?> h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-bold fs-5 text-slate-800"><?= e((string)$row['nama_petugas']) ?></div>
                                        <div class="text-secondary text-small"><i class="bi bi-hash me-1"></i><?= e((string)($row['kode_petugas'] ?? '')) ?></div>
                                        <div class="text-secondary text-small mt-1"><i class="bi bi-telephone me-1"></i><?= e((string)($row['hp_petugas'] ?? '-')) ?></div>
                                    </div>
                                    <span class="badge align-self-start text-bg-<?= $row['status_akun'] === 'aktif' ? 'success' : 'secondary' ?>"><?= e((string)$row['status_akun']) ?></span>
                                </div>
                                <hr class="text-slate-200">
                                <div class="row text-center mt-3 g-2">
                                    <div class="col-4">
                                        <div class="text-secondary text-small">Total Tugas</div>
                                        <strong class="h4 text-slate-800 fw-bold"><?= e((string)$row['total_ditugaskan']) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-secondary text-small">Tugas Aktif</div>
                                        <strong class="h4 text-primary fw-bold"><?= e((string)$row['jml_aktif']) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-secondary text-small">Selesai</div>
                                        <strong class="h4 text-success fw-bold"><?= e((string)$row['jml_selesai']) ?></strong>
                                    </div>
                                </div>
                                <div class="text-secondary text-small mt-3 d-flex justify-content-between">
                                    <span>Rerata pengerjaan:</span>
                                    <strong><?= e((string)($row['rata_hari_selesai'] ?? '-')) ?> hari</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab 2: Belum Dikerjakan -->
        <div class="tab-pane fade" id="tab-belum" role="tabpanel" aria-labelledby="belum-tab">
            <div class="card page-card table-card shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-secondary text-small text-uppercase">
                            <tr>
                                <th>Kode</th>
                                <th>Laporan</th>
                                <th>Pelapor</th>
                                <th>Lokasi & Prioritas</th>
                                <th>Foto</th>
                                <th style="min-width: 320px;">Tugaskan Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($belumDikerjakan === []): ?>
                                <tr>
                                    <td class="text-center text-secondary py-5" colspan="6">Tidak ada laporan yang belum dikerjakan.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($belumDikerjakan as $row): ?>
                                <tr>
                                    <td class="fw-semibold text-slate-800"><?= e((string)$row['kode_laporan']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= e((string)$row['judul']) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)$row['nama_kategori']) ?></div>
                                        <div class="text-secondary text-small mt-1"><i class="bi bi-calendar-event me-1"></i>Dibuat: <?= e(formatDashboardDate((string)$row['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div><?= e((string)$row['nama_pelapor']) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)($row['hp_pelapor'] ?? '-')) ?></div>
                                    </td>
                                    <td>
                                        <div class="mb-1 text-slate-700"><?= e((string)$row['lokasi_detail']) ?></div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge-prioritas prioritas-<?= $row['tingkat_prioritas'] ?> text-capitalize"><?= e((string)$row['tingkat_prioritas']) ?></span>
                                            <span class="badge-status status-<?= $row['status'] ?>"><?= e((string)$row['label_status']) ?></span>
                                            <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                                <a class="text-small text-decoration-none" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$row['latitude']) ?>,<?= e((string)$row['longitude']) ?>">
                                                    <i class="bi bi-geo-alt-fill"></i> Peta
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action d-inline-flex align-items-center gap-1" 
                                                type="button"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#photoModal" 
                                                data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">
                                            <i class="bi bi-image"></i> Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)
                                        </button>
                                    </td>
                                    <td>
                                        <form class="bg-light p-2 rounded border" method="post" action="<?= e(urlFor('/rt-monitoring')) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                            <input type="hidden" name="action" value="assign">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <select class="form-select form-select-sm" name="petugas_id" required>
                                                        <option value="">Pilih petugas</option>
                                                        <?php foreach ($petugas as $p): ?>
                                                            <option value="<?= e((string)$p['id']) ?>"><?= e((string)$p['nama_lengkap']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <input class="form-control form-control-sm" name="tanggal_target_selesai" type="date">
                                                </div>
                                                <div class="col-12">
                                                    <input class="form-control form-control-sm" name="catatan_rt" placeholder="Catatan penugasan opsional">
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-sm btn-primary w-100 btn-action" type="submit">
                                                        <i class="bi bi-send-fill me-1"></i>Tugaskan
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Sedang Dikerjakan -->
        <div class="tab-pane fade" id="tab-sedang" role="tabpanel" aria-labelledby="sedang-tab">
            <div class="card page-card table-card shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-secondary text-small text-uppercase">
                            <tr>
                                <th>Kode</th>
                                <th>Laporan</th>
                                <th>Pelapor</th>
                                <th>Detail Petugas & Waktu</th>
                                <th>Foto</th>
                                <th style="min-width: 320px;">Alihkan Penugasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sedangDikerjakan === []): ?>
                                <tr>
                                    <td class="text-center text-secondary py-5" colspan="6">Tidak ada laporan yang sedang dikerjakan.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($sedangDikerjakan as $row): ?>
                                <tr>
                                    <td class="fw-semibold text-slate-800"><?= e((string)$row['kode_laporan']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= e((string)$row['judul']) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)$row['nama_kategori']) ?></div>
                                        <span class="badge-status status-<?= $row['status'] ?> text-capitalize"><?= e((string)$row['label_status']) ?></span>
                                    </td>
                                    <td>
                                        <div><?= e((string)$row['nama_pelapor']) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)($row['hp_pelapor'] ?? '-')) ?></div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><strong>Dikerjakan oleh:</strong> <span class="text-primary fw-semibold"><?= e((string)($row['nama_petugas'] ?? '-')) ?></span></div>
                                        <div class="text-secondary text-small"><i class="bi bi-calendar-event me-1"></i>Tanggal Lapor: <?= e(formatDashboardDate((string)$row['created_at'])) ?></div>
                                        <div class="text-secondary text-small"><i class="bi bi-play-circle me-1"></i>Mulai Dikerjakan: <?= e(formatDashboardDate((string)($row['tanggal_mulai_kerjakan'] ?? ''))) ?></div>
                                        <?php if (!empty($row['tanggal_target_selesai'])): ?>
                                            <div class="text-secondary text-small"><i class="bi bi-alarm-fill me-1"></i>Target Selesai: <?= e(formatDashboardDate((string)$row['tanggal_target_selesai'] . ' 00:00')) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action d-inline-flex align-items-center gap-1" 
                                                type="button"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#photoModal" 
                                                data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">
                                            <i class="bi bi-image"></i> Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)
                                        </button>
                                    </td>
                                    <td>
                                        <form class="bg-light p-2 rounded border" method="post" action="<?= e(urlFor('/rt-monitoring')) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                            <input type="hidden" name="action" value="assign">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <select class="form-select form-select-sm" name="petugas_id" required>
                                                        <option value="">Pilih petugas baru</option>
                                                        <?php foreach ($petugas as $p): ?>
                                                            <option value="<?= e((string)$p['id']) ?>"><?= e((string)$p['nama_lengkap']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <input class="form-control form-control-sm" name="tanggal_target_selesai" type="date">
                                                </div>
                                                <div class="col-12">
                                                    <input class="form-control form-control-sm" name="catatan_rt" placeholder="Catatan pengalihan opsional">
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-sm btn-outline-primary w-100 btn-action" type="submit">
                                                        <i class="bi bi-arrow-left-right me-1"></i>Alihkan Tugas
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Selesai & Riwayat -->
        <div class="tab-pane fade" id="tab-riwayat" role="tabpanel" aria-labelledby="riwayat-tab">
            <div class="card page-card table-card shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-secondary text-small text-uppercase">
                            <tr>
                                <th>Kode</th>
                                <th>Laporan</th>
                                <th>Pelapor</th>
                                <th>Petugas & Durasi</th>
                                <th>Ulasan / Feedback</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($selesaiTasks === []): ?>
                                <tr>
                                    <td class="text-center text-secondary py-5" colspan="6">Belum ada riwayat laporan selesai.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($selesaiTasks as $row): ?>
                                <tr>
                                    <td class="fw-semibold text-slate-800"><?= e((string)$row['kode_laporan']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= e((string)$row['judul']) ?></div>
                                        <div class="text-secondary text-small mb-1"><?= e((string)$row['nama_kategori']) ?></div>
                                        <span class="badge-status status-<?= $row['status'] ?> text-capitalize"><?= e((string)$row['label_status']) ?></span>
                                    </td>
                                    <td>
                                        <div><?= e((string)$row['nama_pelapor']) ?></div>
                                        <div class="text-secondary text-small"><?= e((string)($row['hp_pelapor'] ?? '-')) ?></div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><strong>Oleh:</strong> <?= e((string)($row['nama_petugas'] ?? '-')) ?></div>
                                        <div class="text-secondary text-small"><i class="bi bi-calendar-event me-1"></i>Dilapor: <?= e(formatDashboardDate((string)$row['created_at'])) ?></div>
                                        <div class="text-secondary text-small"><i class="bi bi-calendar-check me-1"></i>Selesai: <?= e(formatDashboardDate((string)($row['tanggal_selesai'] ?? ''))) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['rating_warga'])): ?>
                                            <div class="text-warning mb-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= (int)$row['rating_warga'] ? '-fill' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="fst-italic text-secondary text-small">"<?= e((string)($row['ulasan_warga'] ?? 'Tidak ada ulasan')) ?>"</div>
                                        <?php else: ?>
                                            <span class="text-muted text-small">- Belum diulas warga -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action d-inline-flex align-items-center gap-1" 
                                                type="button"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#photoModal" 
                                                data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">
                                            <i class="bi bi-image"></i> Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Lightbox / Photo Viewer Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-0 bg-light p-3">
                <h5 class="modal-title fw-bold text-slate-800" id="photoModalLabel"><i class="bi bi-images me-2 text-primary"></i>Foto Bukti Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4 bg-slate-900" style="min-height: 250px; background-color: #0f172a !important;">
                <div id="photoCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner" id="modalPhotoContainer">
                        <!-- Loaded dynamically via JS -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#photoCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                        <span class="visually-hidden">Sebelumnya</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#photoCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                        <span class="visually-hidden">Selanjutnya</span>
                    </button>
                </div>
                <div id="noPhotosAlert" class="alert alert-info d-none mb-0">Belum ada foto yang diunggah untuk laporan ini.</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const rawPhotos = button.getAttribute('data-photos');
            const photos = JSON.parse(rawPhotos || '[]');
            const container = document.getElementById('modalPhotoContainer');
            const noPhotos = document.getElementById('noPhotosAlert');
            const prevBtn = photoModal.querySelector('.carousel-control-prev');
            const nextBtn = photoModal.querySelector('.carousel-control-next');
            
            container.innerHTML = '';
            
            if (photos.length === 0) {
                noPhotos.classList.remove('d-none');
                prevBtn.classList.add('d-none');
                nextBtn.classList.add('d-none');
            } else {
                noPhotos.classList.add('d-none');
                if (photos.length > 1) {
                    prevBtn.classList.remove('d-none');
                    nextBtn.classList.remove('d-none');
                } else {
                    prevBtn.classList.add('d-none');
                    nextBtn.classList.add('d-none');
                }
                
                photos.forEach((photo, idx) => {
                    const activeClass = idx === 0 ? 'active' : '';
                    let typeLabel = 'Bukti Awal';
                    let badgeColor = 'primary';
                    if (photo.tipe_foto === 'proses') {
                        typeLabel = 'Proses Pengerjaan';
                        badgeColor = 'warning';
                    } else if (photo.tipe_foto === 'bukti_selesai') {
                        typeLabel = 'Bukti Selesai';
                        badgeColor = 'success';
                    }
                    
                    const slide = document.createElement('div');
                    slide.className = `carousel-item ${activeClass}`;
                    slide.innerHTML = `
                        <div class="badge bg-${badgeColor} mb-3 fs-6 px-3 py-2 rounded-pill">${typeLabel}</div>
                        <div class="bg-dark p-2 rounded border border-secondary mb-3 d-flex align-items-center justify-content-center" style="background-color: #0f172a !important;">
                            <img src="${photo.path_file_url}" class="img-fluid rounded" style="max-height: 420px; object-fit: contain;" alt="Foto Laporan">
                        </div>
                        <div class="text-white-50 text-small"><i class="bi bi-clock me-1"></i>Diunggah pada: ${photo.created_at_fmt}</div>
                    `;
                    container.appendChild(slide);
                });
            }
        });
    }
});
</script>
</body>
</html>
