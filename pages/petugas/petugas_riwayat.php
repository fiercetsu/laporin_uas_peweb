<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Tugas - Petugas</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<?php renderAppSidebar($petugas, 'petugas-riwayat'); ?>
<main class="min-h-screen px-4 py-6 sm:px-6 lg:ml-[280px] lg:px-8">
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/dashboard')) ?>">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke dashboard
            </a>
            <h1 class="mt-2 text-2xl font-bold">Riwayat Tugas</h1>
            <p class="text-sm text-[#5d6673]">Daftar tugas selesai atau ditutup yang pernah ditangani.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" href="<?= e(urlFor('/laporan-pdf')) ?>">
                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                Export PDF Laporan Selesai
            </a>
            <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/petugas-tugas')) ?>">
                <span class="material-symbols-outlined text-lg">construction</span>
                Tugas Aktif
            </a>
        </div>
    </header>

    <section class="overflow-hidden rounded-lg border border-[#d7dce2] bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-[#d7dce2] bg-[#f7f9fc] text-xs font-bold uppercase tracking-wide text-[#4b5563]">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Laporan</th>
                        <th class="px-4 py-3">Pelapor</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Durasi</th>
                        <th class="px-4 py-3">Selesai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf0f4]">
                <?php if ($tasks === []): ?>
                    <tr><td class="px-4 py-12 text-center text-[#5d6673]" colspan="7">Belum ada riwayat tugas.</td></tr>
                <?php endif; ?>
                <?php foreach ($tasks as $task): ?>
                    <tr class="hover:bg-[#f7f9fc]">
                        <td class="px-4 py-3 font-semibold"><?= e((string)$task['kode_laporan']) ?></td>
                        <td class="px-4 py-3">
                            <div class="font-semibold"><?= e((string)$task['judul']) ?></div>
                            <div class="text-xs text-[#5d6673]"><?= e((string)$task['nama_kategori']) ?> | <?= e((string)$task['tingkat_prioritas']) ?></div>
                            <?php
                            $photos = getReportsPhotos([(int)$task['id']]);
                            if ($photos !== []):
                            ?>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <?php foreach ($photos as $photo): ?>
                                        <a href="<?= e(urlFor('/backend/' . $photo['path_file'])) ?>" target="_blank" class="inline-block border border-[#c8ced8] rounded overflow-hidden p-0.5 hover:border-[#00409c] transition-all bg-white">
                                            <img src="<?= e(urlFor('/backend/' . $photo['path_file'])) ?>" alt="Foto Bukti" class="h-8 w-8 object-cover rounded-sm">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= e((string)$task['nama_pelapor']) ?></td>
                        <td class="px-4 py-3"><?= e((string)$task['lokasi_detail']) ?></td>
                        <td class="px-4 py-3"><span class="rounded bg-[#eef5ff] px-2 py-1 text-xs font-semibold text-[#00409c]"><?= e((string)$task['label_status']) ?></span></td>
                        <td class="px-4 py-3"><?= e((string)($task['durasi_hari'] ?? '-')) ?> hari</td>
                        <td class="px-4 py-3 text-xs text-[#5d6673]"><?= e(formatDashboardDate((string)($task['tanggal_selesai'] ?? $task['created_at'] ?? ''))) ?></td>
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
