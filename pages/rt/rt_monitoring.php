<?php
declare(strict_types=1);

$user = $rt;
$formatPhotos = function(array $reportPhotos): array {
    return array_map(static function(array $p): array {
        return [
            'path_file_url' => urlFor('/backend/' . $p['path_file']),
            'tipe_foto' => $p['tipe_foto'],
            'created_at_fmt' => formatDashboardDate($p['created_at']),
        ];
    }, $reportPhotos);
};
$totalActiveOfficers = 0;
$totalFinished = 0;
foreach ($officers as $officer) {
    $totalActiveOfficers += (int)($officer['jml_aktif'] ?? 0);
    $totalFinished += (int)($officer['jml_selesai'] ?? 0);
}
$stats = [
    ['label' => 'Petugas', 'value' => count($officers), 'icon' => 'engineering', 'tone' => 'success'],
    ['label' => 'Belum Dikerjakan', 'value' => count($belumDikerjakan), 'icon' => 'assignment_late', 'tone' => 'warning'],
    ['label' => 'Sedang Dikerjakan', 'value' => count($sedangDikerjakan), 'icon' => 'pending_actions', 'tone' => 'info'],
    ['label' => 'Riwayat Selesai', 'value' => count($selesaiTasks), 'icon' => 'check_circle', 'tone' => 'primary'],
];
$toneMap = [
    'primary' => 'bg-blue-50 text-blue-700',
    'warning' => 'bg-amber-100 text-amber-800',
    'info' => 'bg-sky-100 text-sky-800',
    'success' => 'bg-emerald-100 text-emerald-800',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Monitoring Petugas - RT</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; background: #f5f5f5; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
        .rt-tab-panel { display: none; }
        .rt-tab-panel.active { display: block; }
        .rt-tab.active { background: #00409c; color: #fff; }
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

<?php renderAppSidebar($user, 'rt-monitoring'); ?>

<main class="flex-1 w-full lg:ml-[280px] pt-20 lg:pt-0 p-4 lg:p-6 lg:max-w-[1440px] mx-auto min-h-screen flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-semibold">Monitoring Petugas</h1>
            <p class="text-[#424654] mt-1">Kelola penugasan dan pantau progres pengerjaan laporan di wilayah RT.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg">
            <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg"><?= e($success) ?></div>
    <?php endif; ?>

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

    <div class="flex flex-wrap gap-2">
        <button class="rt-tab active px-4 py-2 rounded-lg border border-[#BDBDBD] bg-white font-semibold" data-tab="petugas">Beban Petugas</button>
        <button class="rt-tab px-4 py-2 rounded-lg border border-[#BDBDBD] bg-white font-semibold" data-tab="belum">Belum Dikerjakan</button>
        <button class="rt-tab px-4 py-2 rounded-lg border border-[#BDBDBD] bg-white font-semibold" data-tab="sedang">Sedang Dikerjakan</button>
        <button class="rt-tab px-4 py-2 rounded-lg border border-[#BDBDBD] bg-white font-semibold" data-tab="riwayat">Selesai & Riwayat</button>
    </div>

    <section id="tab-petugas" class="rt-tab-panel active">
        <div class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
            <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD]"><h2 class="text-2xl font-semibold">Monitoring Petugas</h2></div>
            <div class="divide-y divide-[#BDBDBD]">
                <?php if ($officers === []): ?>
                    <div class="p-8 text-center text-[#424654]">Belum ada data petugas aktif.</div>
                <?php endif; ?>
                <?php foreach ($officers as $row): ?>
                    <div class="p-4 hover:bg-[#f5f3f3]">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold"><?= e((string)$row['nama_petugas']) ?></div>
                                <div class="text-sm text-[#424654]">Kode: <?= e((string)($row['kode_petugas'] ?? '-')) ?><?= !empty($row['hp_petugas']) ? ' | HP: ' . e((string)$row['hp_petugas']) : '' ?></div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="text-sm bg-blue-50 text-blue-700 px-3 py-1 rounded">Aktif: <strong><?= e((string)$row['jml_aktif']) ?></strong></span>
                                <span class="text-sm bg-emerald-50 text-emerald-700 px-3 py-1 rounded">Selesai: <strong><?= e((string)$row['jml_selesai']) ?></strong></span>
                                <span class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded">Total: <strong><?= e((string)$row['total_ditugaskan']) ?></strong></span>
                                <span class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded">Rata-rata: <strong><?= e((string)($row['rata_hari_selesai'] ?? '-')) ?> hari</strong></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="tab-belum" class="rt-tab-panel">
        <div class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
            <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD]"><h2 class="text-2xl font-semibold">Belum Dikerjakan</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[1000px]">
                    <thead class="bg-[#fbf9f8] border-b border-[#BDBDBD]">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold uppercase">Kode</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase">Laporan / Pelapor</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase">Lokasi</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase">Foto</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase">Tugaskan</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-[#BDBDBD]">
                    <?php if ($belumDikerjakan === []): ?><tr><td colspan="5" class="px-4 py-10 text-center text-[#424654]">Tidak ada laporan yang belum dikerjakan.</td></tr><?php endif; ?>
                    <?php foreach ($belumDikerjakan as $row): ?>
                        <tr class="hover:bg-[#f5f3f3]">
                            <td class="px-4 py-4 font-mono text-xs font-semibold"><?= e((string)$row['kode_laporan']) ?></td>
                            <td class="px-4 py-4">
                                <div class="font-semibold"><?= e((string)$row['judul']) ?></div>
                                <div class="text-xs text-[#424654]"><?= e((string)$row['nama_pelapor']) ?><?= !empty($row['hp_pelapor']) ? ' | ' . e((string)$row['hp_pelapor']) : '' ?></div>
                                <div class="text-xs text-[#424654]"><?= e((string)$row['nama_kategori']) ?></div>
                                <div class="mt-1"><span class="px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-800"><?= e((string)$row['label_status']) ?></span> <span class="px-2 py-1 rounded text-xs font-semibold bg-rose-100 text-rose-800"><?= e((string)$row['tingkat_prioritas']) ?></span></div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div><?= e((string)$row['lokasi_detail']) ?></div>
                                <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?><a class="text-[#00409c] text-xs" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string)$row['latitude']) ?>,<?= e((string)$row['longitude']) ?>">Buka Maps</a><?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <button class="photo-button text-[#00409c] border border-[#00409c] px-3 py-1 rounded text-sm" type="button" data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)</button>
                            </td>
                            <td class="px-4 py-4">
                                <form class="grid grid-cols-1 gap-2 min-w-[260px]" method="post" action="<?= e(urlFor('/rt-monitoring')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <select class="rounded border-[#BDBDBD] text-sm" name="petugas_id" required>
                                        <option value="">Pilih petugas</option>
                                        <?php foreach ($petugas as $p): ?><option value="<?= e((string)$p['id']) ?>"><?= e((string)$p['nama_lengkap']) ?></option><?php endforeach; ?>
                                    </select>
                                    <input class="rounded border-[#BDBDBD] text-sm" name="tanggal_target_selesai" type="date">
                                    <input class="rounded border-[#BDBDBD] text-sm" name="catatan_rt" placeholder="Catatan opsional">
                                    <button class="bg-[#00409c] text-white rounded px-3 py-2 text-sm font-semibold" type="submit">Tugaskan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="tab-sedang" class="rt-tab-panel">
        <div class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
            <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD]"><h2 class="text-2xl font-semibold">Sedang Dikerjakan</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[1000px]">
                    <thead class="bg-[#fbf9f8] border-b border-[#BDBDBD]"><tr><th class="px-4 py-3 text-xs font-bold uppercase">Kode</th><th class="px-4 py-3 text-xs font-bold uppercase">Laporan</th><th class="px-4 py-3 text-xs font-bold uppercase">Petugas & Waktu</th><th class="px-4 py-3 text-xs font-bold uppercase">Foto</th><th class="px-4 py-3 text-xs font-bold uppercase">Alihkan</th></tr></thead>
                    <tbody class="divide-y divide-[#BDBDBD]">
                    <?php if ($sedangDikerjakan === []): ?><tr><td colspan="5" class="px-4 py-10 text-center text-[#424654]">Tidak ada laporan yang sedang dikerjakan.</td></tr><?php endif; ?>
                    <?php foreach ($sedangDikerjakan as $row): ?>
                        <tr class="hover:bg-[#f5f3f3]">
                            <td class="px-4 py-4 font-mono text-xs font-semibold"><?= e((string)$row['kode_laporan']) ?></td>
                            <td class="px-4 py-4"><div class="font-semibold"><?= e((string)$row['judul']) ?></div><div class="text-xs text-[#424654]"><?= e((string)$row['nama_pelapor']) ?> | <?= e((string)$row['nama_kategori']) ?></div><span class="px-2 py-1 rounded text-xs font-semibold bg-sky-100 text-sky-800"><?= e((string)$row['label_status']) ?></span></td>
                            <td class="px-4 py-4 text-sm"><div><strong><?= e((string)($row['nama_petugas'] ?? '-')) ?></strong></div><div class="text-xs text-[#424654]">Dilapor: <?= e(formatDashboardDate((string)$row['created_at'])) ?></div><div class="text-xs text-[#424654]">Mulai: <?= e(formatDashboardDate((string)($row['tanggal_mulai_kerjakan'] ?? ''))) ?></div></td>
                            <td class="px-4 py-4"><button class="photo-button text-[#00409c] border border-[#00409c] px-3 py-1 rounded text-sm" type="button" data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)</button></td>
                            <td class="px-4 py-4">
                                <form class="grid grid-cols-1 gap-2 min-w-[260px]" method="post" action="<?= e(urlFor('/rt-monitoring')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="laporan_id" value="<?= e((string)$row['id']) ?>"><input type="hidden" name="action" value="assign">
                                    <select class="rounded border-[#BDBDBD] text-sm" name="petugas_id" required><option value="">Pilih petugas baru</option><?php foreach ($petugas as $p): ?><option value="<?= e((string)$p['id']) ?>"><?= e((string)$p['nama_lengkap']) ?></option><?php endforeach; ?></select>
                                    <input class="rounded border-[#BDBDBD] text-sm" name="tanggal_target_selesai" type="date"><input class="rounded border-[#BDBDBD] text-sm" name="catatan_rt" placeholder="Catatan pengalihan opsional">
                                    <button class="bg-white text-[#00409c] border border-[#00409c] rounded px-3 py-2 text-sm font-semibold" type="submit">Alihkan Tugas</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="tab-riwayat" class="rt-tab-panel">
        <div class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm overflow-hidden">
            <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#BDBDBD] flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold">Selesai & Riwayat</h2>
                <a class="inline-flex w-fit items-center justify-center gap-2 rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" href="<?= e(urlFor('/laporan-pdf')) ?>">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    Export PDF Laporan Selesai
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[900px]">
                    <thead class="bg-[#fbf9f8] border-b border-[#BDBDBD]"><tr><th class="px-4 py-3 text-xs font-bold uppercase">Kode</th><th class="px-4 py-3 text-xs font-bold uppercase">Laporan</th><th class="px-4 py-3 text-xs font-bold uppercase">Petugas</th><th class="px-4 py-3 text-xs font-bold uppercase">Ulasan</th><th class="px-4 py-3 text-xs font-bold uppercase">Foto</th></tr></thead>
                    <tbody class="divide-y divide-[#BDBDBD]">
                    <?php if ($selesaiTasks === []): ?><tr><td colspan="5" class="px-4 py-10 text-center text-[#424654]">Belum ada riwayat laporan selesai.</td></tr><?php endif; ?>
                    <?php foreach ($selesaiTasks as $row): ?>
                        <tr class="hover:bg-[#f5f3f3]">
                            <td class="px-4 py-4 font-mono text-xs font-semibold"><?= e((string)$row['kode_laporan']) ?></td>
                            <td class="px-4 py-4"><div class="font-semibold"><?= e((string)$row['judul']) ?></div><div class="text-xs text-[#424654]"><?= e((string)$row['nama_pelapor']) ?> | <?= e((string)$row['nama_kategori']) ?></div><span class="px-2 py-1 rounded text-xs font-semibold bg-emerald-100 text-emerald-800"><?= e((string)$row['label_status']) ?></span></td>
                            <td class="px-4 py-4 text-sm"><div><?= e((string)($row['nama_petugas'] ?? '-')) ?></div><div class="text-xs text-[#424654]">Selesai: <?= e(formatDashboardDate((string)($row['tanggal_selesai'] ?? ''))) ?></div></td>
                            <td class="px-4 py-4 text-sm"><?= !empty($row['rating_warga']) ? e((string)$row['rating_warga']) . '/5 - ' . e((string)($row['ulasan_warga'] ?? 'Tidak ada ulasan')) : '<span class="text-[#424654]">Belum diulas</span>' ?></td>
                            <td class="px-4 py-4"><button class="photo-button text-[#00409c] border border-[#00409c] px-3 py-1 rounded text-sm" type="button" data-photos="<?= e(json_encode($formatPhotos($photosByReport[(int)$row['id']] ?? []))) ?>">Lihat Foto (<?= count($photosByReport[(int)$row['id']] ?? []) ?>)</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<div id="photo-modal" class="hidden fixed inset-0 z-50 bg-black/70 items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full overflow-hidden">
        <div class="flex justify-between items-center border-b border-[#BDBDBD] px-4 py-3">
            <h3 class="text-xl font-semibold">Foto Bukti Laporan</h3>
            <button id="photo-close" class="text-[#424654] hover:text-black" type="button"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div id="photo-content" class="p-4 grid gap-4"></div>
    </div>
</div>

<script>
document.querySelectorAll('.rt-tab').forEach(function (button) {
    button.addEventListener('click', function () {
        document.querySelectorAll('.rt-tab').forEach(function (item) { item.classList.remove('active'); });
        document.querySelectorAll('.rt-tab-panel').forEach(function (panel) { panel.classList.remove('active'); });
        button.classList.add('active');
        document.getElementById('tab-' + button.dataset.tab).classList.add('active');
    });
});

var modal = document.getElementById('photo-modal');
var content = document.getElementById('photo-content');
document.querySelectorAll('.photo-button').forEach(function (button) {
    button.addEventListener('click', function () {
        var photos = JSON.parse(button.dataset.photos || '[]');
        content.innerHTML = '';
        if (photos.length === 0) {
            content.innerHTML = '<div class="text-center text-[#424654] py-8">Belum ada foto untuk laporan ini.</div>';
        } else {
            photos.forEach(function (photo) {
                var label = photo.tipe_foto === 'proses' ? 'Proses Pengerjaan' : (photo.tipe_foto === 'bukti_selesai' ? 'Bukti Selesai' : 'Bukti Awal');
                var item = document.createElement('div');
                item.className = 'border border-[#BDBDBD] rounded-lg p-3';
                item.innerHTML = '<div class="text-sm font-semibold mb-2">' + label + '</div><img class="max-h-[420px] mx-auto rounded object-contain" src="' + photo.path_file_url + '" alt="Foto Laporan"><div class="text-xs text-[#424654] mt-2">' + photo.created_at_fmt + '</div>';
                content.appendChild(item);
            });
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
});
document.getElementById('photo-close').addEventListener('click', function () {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
});
modal.addEventListener('click', function (event) {
    if (event.target === modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
});
</script>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
