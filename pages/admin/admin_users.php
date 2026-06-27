<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola User - Admin</title>
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
            <h1 class="h3 mb-1 mt-2">Kelola User</h1>
            <p class="text-secondary mb-0">Aktifkan, pending, atau nonaktifkan akun warga dan petugas.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(urlFor('/admin-laporan')) ?>">
            <i class="bi bi-clipboard-check me-1"></i>Verifikasi Laporan
        </a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="card table-card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>NIK</th>
                        <th>Kontak</th>
                        <th>Role</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($users === []): ?>
                    <tr><td class="text-center text-secondary py-5" colspan="7">Belum ada user.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string)$row['nama_lengkap']) ?></div>
                            <div class="text-secondary text-small"><?= e((string)($row['kode_user'] ?? '-')) ?></div>
                        </td>
                        <td><?= e((string)$row['nik']) ?></td>
                        <td>
                            <div><?= e((string)($row['email'] ?? '-')) ?></div>
                            <div class="text-secondary text-small"><?= e((string)($row['no_hp'] ?? '-')) ?></div>
                        </td>
                        <td><span class="badge text-bg-light text-uppercase"><?= e((string)$row['role']) ?></span></td>
                        <td class="text-small">
                            <?= e((string)($row['alamat_lengkap'] ?? '-')) ?>
                            <?php if (!empty($row['no_rt']) || !empty($row['no_rw'])): ?>
                                <div class="text-secondary">RT <?= e((string)($row['no_rt'] ?? '-')) ?> / RW <?= e((string)($row['no_rw'] ?? '-')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge text-bg-<?= $row['status_akun'] === 'aktif' ? 'success' : ($row['status_akun'] === 'pending' ? 'warning' : 'secondary') ?>"><?= e((string)$row['status_akun']) ?></span></td>
                        <td class="text-end">
                            <form class="d-flex justify-content-end gap-2" method="post" action="<?= e(urlFor('/admin-users')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="user_id" value="<?= e((string)$row['id']) ?>">
                                <select class="form-select form-select-sm" name="status_akun" style="max-width: 130px;">
                                    <option value="aktif" <?= $row['status_akun'] === 'aktif' ? 'selected' : '' ?>>aktif</option>
                                    <option value="pending" <?= $row['status_akun'] === 'pending' ? 'selected' : '' ?>>pending</option>
                                    <option value="nonaktif" <?= $row['status_akun'] === 'nonaktif' ? 'selected' : '' ?>>nonaktif</option>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                            </form>
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
