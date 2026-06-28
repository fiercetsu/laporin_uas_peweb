<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tugas Aktif - Petugas</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<?php renderAppSidebar($petugas, 'petugas-tugas'); ?>
<main class="min-h-screen px-4 py-6 sm:px-6 lg:ml-[280px] lg:px-8">
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/dashboard')) ?>">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke dashboard
            </a>
            <h1 class="mt-2 text-2xl font-bold">Tugas Aktif</h1>
            <p class="text-sm text-[#5d6673]">Mulai pekerjaan, kirim progress, tandai tindak lanjut, atau selesaikan tugas.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/petugas-riwayat')) ?>">
            <span class="material-symbols-outlined text-lg">history</span>
            Riwayat Tugas
        </a>
    </header>

    <?php if ($errors !== []): ?>
        <div class="mb-4 rounded-lg border border-[#f2b8b5] bg-[#ffdad6] p-4 text-[#93000a]"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="mb-4 rounded-lg border border-[#b8e6c9] bg-[#dcfce7] p-4 text-[#166534]"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php if ($tasks === []): ?>
            <div class="rounded-lg border border-[#d7dce2] bg-white py-12 text-center text-[#5d6673]">Belum ada tugas aktif.</div>
        <?php endif; ?>
        <?php foreach ($tasks as $task): ?>
            <section class="rounded-lg border border-[#d7dce2] bg-white p-4 sm:p-6">
                <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <div>
                        <div class="mb-3 flex flex-wrap gap-2">
                            <span class="rounded bg-[#f0f3f7] px-2 py-1 text-xs font-semibold"><?= e((string)$task['kode_laporan']) ?></span>
                            <span class="rounded bg-[#dff3ff] px-2 py-1 text-xs font-semibold text-[#00658a]"><?= e((string)$task['label_status']) ?></span>
                            <span class="rounded bg-[#eef5ff] px-2 py-1 text-xs font-semibold text-[#00409c]"><?= e((string)$task['tingkat_prioritas']) ?></span>
                        </div>
                        <h2 class="text-lg font-bold"><?= e((string)$task['judul']) ?></h2>
                        <p class="mb-3 text-sm text-[#5d6673]">Pelapor: <?= e((string)$task['nama_pelapor']) ?><?= !empty($task['hp_pelapor']) ? ' | HP: ' . e((string)$task['hp_pelapor']) : '' ?></p>
                        <div class="grid gap-2 text-sm md:grid-cols-3">
                            <div><span class="font-semibold">Kategori:</span> <?= e((string)$task['nama_kategori']) ?></div>
                            <div><span class="font-semibold">Lokasi:</span> <?= e((string)$task['lokasi_detail']) ?></div>
                            <div><span class="font-semibold">Target:</span> <?= e((string)($task['tanggal_target_selesai'] ?? '-')) ?></div>
                        </div>
                        <?php if (!empty($task['latitude']) && !empty($task['longitude'])): ?>
                            <a class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$task['latitude']) ?>,<?= e((string)$task['longitude']) ?>">
                                <span class="material-symbols-outlined text-base">location_on</span>
                                Buka lokasi di Maps
                            </a>
                        <?php endif; ?>

                        <?php
                        $photos = getReportsPhotos([(int)$task['id']]);
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
                    <form class="rounded-lg border border-[#d7dce2] bg-[#f7f9fc] p-3" method="post" action="<?= e(urlFor('/petugas-tugas')) ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="laporan_id" value="<?= e((string)$task['id']) ?>">
                        <textarea class="mb-2 w-full rounded-lg border-[#c8ced8] text-sm" name="catatan_petugas" rows="2" placeholder="Catatan progress atau penyelesaian"></textarea>
                        <input class="mb-3 w-full rounded-lg border-[#c8ced8] text-sm" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="rounded-lg border border-[#c8ced8] bg-white px-3 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" name="action" value="mulai" type="submit">Mulai</button>
                            <button class="rounded-lg bg-[#00409c] px-3 py-2 font-semibold text-white hover:bg-[#0056cc]" name="action" value="progress" type="submit">Update</button>
                            <button class="rounded-lg border border-[#f3d182] px-3 py-2 font-semibold text-[#7c5800] hover:bg-[#fff4cc]" name="action" value="tindak_lanjut" type="submit">Tindak Lanjut</button>
                            <button class="rounded-lg bg-[#16803a] px-3 py-2 font-semibold text-white hover:bg-[#126c31]" name="action" value="selesai" type="submit">Selesai</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
