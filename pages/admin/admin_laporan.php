<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Laporan - Admin</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<?php renderAppSidebar($admin, 'admin-laporan'); ?>
<main class="min-h-screen px-4 py-6 sm:px-6 lg:ml-[280px] lg:px-8">
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/dashboard')) ?>">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke dashboard
            </a>
            <h1 class="mt-2 text-2xl font-bold">Verifikasi Laporan</h1>
            <p class="text-sm text-[#5d6673]">Verifikasi, tolak, atau tugaskan laporan ke petugas aktif.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/admin-users')) ?>">
            <span class="material-symbols-outlined text-lg">group</span>
            Kelola User
        </a>
    </header>

    <?php if ($errors !== []): ?>
        <div class="mb-4 rounded-lg border border-[#f2b8b5] bg-[#ffdad6] p-4 text-[#93000a]"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="mb-4 rounded-lg border border-[#b8e6c9] bg-[#dcfce7] p-4 text-[#166534]"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php if ($reports === []): ?>
            <div class="rounded-lg border border-[#d7dce2] bg-white py-12 text-center text-[#5d6673]">Belum ada laporan.</div>
        <?php endif; ?>
        <?php foreach ($reports as $row): ?>
            <?php
            $statusClass = $row['status'] === 'menunggu_verifikasi'
                ? 'bg-[#fef3c7] text-[#92400e]'
                : ($row['status'] === 'ditolak' ? 'bg-[#ffdad6] text-[#93000a]' : 'bg-[#dcfce7] text-[#166534]');
            ?>
            <section class="rounded-lg border border-[#d7dce2] bg-white p-4 sm:p-6">
                <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <div>
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="rounded bg-[#f0f3f7] px-2 py-1 text-xs font-semibold"><?= e((string)$row['kode_laporan']) ?></span>
                            <span class="rounded px-2 py-1 text-xs font-semibold <?= $statusClass ?>"><?= e((string)$row['label_status']) ?></span>
                            <span class="rounded bg-[#eef5ff] px-2 py-1 text-xs font-semibold text-[#00409c]"><?= e((string)$row['tingkat_prioritas']) ?></span>
                        </div>
                        <h2 class="text-lg font-bold"><?= e((string)$row['judul']) ?></h2>
                        <p class="mb-3 text-sm text-[#5d6673]">
                            Pelapor: <?= e((string)$row['nama_pelapor']) ?><?= !empty($row['hp_pelapor']) ? ' | HP: ' . e((string)$row['hp_pelapor']) : '' ?>
                        </p>
                        <div class="grid gap-2 text-sm md:grid-cols-3">
                            <div><span class="font-semibold">Kategori:</span> <?= e((string)$row['nama_kategori']) ?></div>
                            <div><span class="font-semibold">Lokasi:</span> <?= e((string)$row['lokasi_detail']) ?></div>
                            <div><span class="font-semibold">Petugas:</span> <?= e((string)($row['nama_petugas'] ?? '-')) ?></div>
                        </div>

                        <?php
                        $photos = getReportsPhotos([(int)$row['id']]);
                        if ($photos !== []):
                        ?>
                            <div class="mt-4">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-[#5d6673] mb-2">Foto Bukti Aduan:</span>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($photos as $photo): ?>
                                        <a href="<?= e(urlFor($photo['path_file'])) ?>" target="_blank" class="group relative inline-block border border-[#c8ced8] rounded-lg overflow-hidden hover:border-[#00409c] transition-all bg-white p-1">
                                            <img src="<?= e(urlFor($photo['path_file'])) ?>" alt="Foto Bukti" class="h-16 w-16 sm:h-20 sm:w-20 object-cover rounded-md group-hover:scale-105 transition-transform duration-200">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <form class="rounded-lg border border-[#d7dce2] bg-[#f7f9fc] p-3" method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                            <input type="hidden" name="action" value="assign">
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                                <select class="rounded-lg border-[#c8ced8] text-sm" name="petugas_id" required>
                                    <option value="">Pilih petugas</option>
                                    <?php foreach ($petugas as $person): ?>
                                        <option value="<?= e((string)$person['id']) ?>"><?= e((string)$person['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input class="rounded-lg border-[#c8ced8] text-sm" name="tanggal_target_selesai" type="date">
                                <input class="rounded-lg border-[#c8ced8] text-sm sm:col-span-2 xl:col-span-1 2xl:col-span-2" name="catatan_admin" placeholder="Catatan penugasan opsional">
                                <button class="rounded-lg bg-[#00409c] px-3 py-2 font-semibold text-white hover:bg-[#0056cc] sm:col-span-2 xl:col-span-1 2xl:col-span-2" type="submit">Tugaskan</button>
                            </div>
                        </form>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <form method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                <input type="hidden" name="action" value="verify">
                                <button class="w-full rounded-lg bg-[#16803a] px-3 py-2 font-semibold text-white hover:bg-[#126c31]" type="submit">Verifikasi</button>
                            </form>
                            <form class="flex flex-1 gap-2" method="post" action="<?= e(urlFor('/admin-laporan')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                <input type="hidden" name="action" value="reject">
                                <input class="min-w-0 flex-1 rounded-lg border-[#c8ced8] text-sm" name="alasan_penolakan" placeholder="Alasan tolak" required>
                                <button class="rounded-lg border border-[#f2b8b5] px-3 py-2 font-semibold text-[#93000a] hover:bg-[#ffdad6]" type="submit">Tolak</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
