<?php
declare(strict_types=1);

$user = $rt;
$role = 'rt';
$stats = [
    ['label' => 'Darurat Aktif', 'value' => $summary['total'] ?? 0, 'icon' => 'priority_high', 'tone' => 'danger'],
    ['label' => 'Menunggu Verifikasi', 'value' => $summary['menunggu'] ?? 0, 'icon' => 'hourglass_empty', 'tone' => 'warning'],
    ['label' => 'Sedang Diproses', 'value' => $summary['diproses'] ?? 0, 'icon' => 'pending_actions', 'tone' => 'info'],
    ['label' => 'Total Data', 'value' => count($reports), 'icon' => 'inbox', 'tone' => 'primary'],
];
$toneMap = [
    'primary' => 'bg-blue-50 text-blue-700',
    'warning' => 'bg-amber-100 text-amber-800',
    'info' => 'bg-sky-100 text-sky-800',
    'danger' => 'bg-rose-100 text-rose-800',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Laporan Darurat - RT</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; background: #f5f5f5; }
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

<?php renderAppSidebar($user, 'rt-darurat'); ?>

<main class="flex-1 w-full lg:ml-[280px] pt-20 lg:pt-0 p-4 lg:p-6 lg:max-w-[1440px] mx-auto min-h-screen flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-semibold">Laporan Darurat</h1>
            <p class="text-[#424654] mt-1">Pantau laporan prioritas darurat yang belum selesai.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($stats as $stat): ?>
            <div class="bg-white border border-[#BDBDBD] rounded-lg p-4 flex items-center gap-4 shadow-sm">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center <?= $toneMap[$stat['tone']] ?? $toneMap['primary'] ?>">
                    <span class="material-symbols-outlined"><?= e($stat['icon']) ?></span>
                </div>
                <div>
                    <div class="text-xs font-bold text-[#424654] uppercase tracking-wide"><?= e($stat['label']) ?></div>
                    <div class="text-3xl font-semibold"><?= e((string)$stat['value']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
        <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD] flex justify-between items-center">
            <h2 class="text-2xl font-semibold">Laporan Darurat Aktif</h2>
            <button onclick="window.location.reload();" class="text-[#00409c] hover:bg-[#00409c]/10 px-3 py-1 rounded flex items-center gap-1">
                <span class="material-symbols-outlined text-base">refresh</span> Refresh
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[820px]">
                <thead class="bg-[#fbf9f8] border-b border-[#BDBDBD]">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Laporan / Pelapor</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Petugas</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Lokasi</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#424654] uppercase tracking-wide">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#BDBDBD]">
                    <?php if ($reports === []): ?>
                        <tr><td class="px-4 py-12 text-center text-[#424654]" colspan="6">Tidak ada laporan darurat aktif.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $row): ?>
                        <tr class="hover:bg-[#f5f3f3]">
                            <td class="px-4 py-4 font-mono text-xs font-semibold"><?= e((string)$row['kode_laporan']) ?></td>
                            <td class="px-4 py-4">
                                <div class="font-semibold"><?= e((string)$row['judul']) ?></div>
                                <div class="text-xs text-[#424654]"><?= e((string)$row['nama_pelapor']) ?><?= !empty($row['hp_pelapor']) ? ' | ' . e((string)$row['hp_pelapor']) : '' ?></div>
                                <div class="text-xs text-[#424654]"><?= e((string)$row['nama_kategori']) ?></div>
                                <?php
                                $photos = getReportsPhotos([(int)$row['id']]);
                                if ($photos !== []):
                                ?>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <?php foreach ($photos as $photo): ?>
                                            <a href="<?= e(urlFor($photo['path_file'])) ?>" target="_blank" class="inline-block border border-[#c8ced8] rounded overflow-hidden p-0.5 hover:border-[#00409c] transition-all bg-white">
                                                <img src="<?= e(urlFor($photo['path_file'])) ?>" alt="Foto Bukti" class="h-8 w-8 object-cover rounded-sm">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4"><span class="px-2 py-1 rounded text-xs font-semibold bg-rose-100 text-rose-800"><?= e((string)$row['label_status']) ?></span></td>
                            <td class="px-4 py-4"><?= e((string)($row['nama_petugas'] ?? '-')) ?></td>
                            <td class="px-4 py-4 text-sm">
                                <div><?= e((string)$row['lokasi_detail']) ?></div>
                                <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                    <a class="text-[#00409c] text-xs" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$row['latitude']) ?>,<?= e((string)$row['longitude']) ?>">Buka Maps</a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-xs text-[#424654] whitespace-nowrap"><?= e(formatDashboardDate((string)$row['created_at'])) ?></td>
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
