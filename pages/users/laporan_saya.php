<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Laporan Saya - Laporin RT</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; background-color: #f5f5f5; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="flex min-h-screen text-[#1b1c1c] bg-[#f5f5f5]">
<header class="lg:hidden bg-[#00409c] text-white flex justify-between items-center w-full px-4 h-16 shadow-sm fixed top-0 left-0 z-50">
    <div class="text-xl font-bold">Laporin RT</div>
    <form method="post" action="<?= e(urlFor('/logout')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <button class="p-2 rounded-full" type="submit" aria-label="Keluar"><span class="material-symbols-outlined">logout</span></button>
    </form>
</header>

<?php renderAppSidebar($user, 'laporan-saya'); ?>

<main class="flex-1 w-full lg:ml-[280px] pt-20 lg:pt-0 p-4 lg:p-6 lg:max-w-[1440px] mx-auto min-h-screen flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-semibold">Laporan Saya</h1>
            <p class="text-[#424654] mt-1">Daftar laporan yang pernah kamu kirim.</p>
        </div>
    </div>

    <section class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
        <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD]">
            <h2 class="text-2xl font-semibold">Riwayat Laporan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[900px]">
                <thead class="bg-[#fbf9f8] border-b border-[#BDBDBD]">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Laporan</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Lokasi</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Prioritas</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#BDBDBD]">
                    <?php if ($reports === []): ?>
                        <tr><td class="px-4 py-12 text-center text-[#424654]" colspan="7">Belum ada laporan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $report): ?>
                        <tr class="hover:bg-[#f5f3f3]">
                            <td class="px-4 py-4 font-mono text-xs font-semibold"><?= e((string)$report['kode_laporan']) ?></td>
                            <td class="px-4 py-4">
                                <div class="font-semibold"><?= e((string)$report['judul']) ?></div>
                                <div class="text-xs text-[#424654]"><?= e((string)$report['nama_kategori']) ?></div>
                            </td>
                            <td class="px-4 py-4 text-sm"><?= e((string)$report['lokasi_detail']) ?></td>
                            <td class="px-4 py-4"><span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800"><?= e((string)$report['label_status']) ?></span></td>
                            <td class="px-4 py-4 text-sm capitalize"><?= e((string)$report['tingkat_prioritas']) ?></td>
                            <td class="px-4 py-4 text-xs text-[#424654] whitespace-nowrap"><?= e(formatDashboardDate((string)$report['created_at'])) ?></td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a class="rounded border border-[#00409c] px-3 py-1 text-sm font-semibold text-[#00409c] hover:bg-[#00409c]/10" href="<?= e(urlFor('/edit-laporan') . '?id=' . (int)$report['id']) ?>">Edit</a>
                                    <form method="post" action="<?= e(urlFor('/hapus-laporan')) ?>" onsubmit="return confirm('Hapus laporan ini? Data yang dihapus tidak bisa dikembalikan.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= e((string)$report['id']) ?>">
                                        <button class="rounded border border-rose-600 px-3 py-1 text-sm font-semibold text-rose-700 hover:bg-rose-50" type="submit">Hapus</button>
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

<?php renderIdleLogoutScript(); ?>
</body>
</html>
