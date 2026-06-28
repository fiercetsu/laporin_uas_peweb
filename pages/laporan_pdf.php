<?php
declare(strict_types=1);

$role = (string)($user['role'] ?? '');
$title = $role === 'rt' ? 'Rekap Laporan Selesai RT' : 'Rekap Laporan Selesai Petugas';
$printedAt = date('d/m/Y H:i');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Laporin RT</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .report-page { break-inside: avoid; page-break-inside: avoid; }
            img { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<main class="mx-auto min-h-screen max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="no-print mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor($role === 'rt' ? '/rt-monitoring' : '/petugas-riwayat')) ?>">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Kembali
        </a>
        <div class="flex gap-2">
            <button class="inline-flex items-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" type="button" data-pdf-back>
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Kembali
            </button>
            <button class="inline-flex items-center gap-2 rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="button" data-pdf-print>
                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <header class="mb-6 rounded-lg border border-[#d7dce2] bg-white p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold"><?= e($title) ?></h1>
                <p class="text-sm text-[#5d6673]">Berisi laporan dengan status selesai dan bukti foto yang tersimpan di sistem.</p>
            </div>
            <div class="text-sm text-[#5d6673] sm:text-right">
                <div>Dicetak: <?= e($printedAt) ?></div>
                <div>Oleh: <?= e((string)($user['nama_lengkap'] ?? '-')) ?> (<?= e($role) ?>)</div>
            </div>
        </div>
    </header>

    <?php if ($reports === []): ?>
        <section class="rounded-lg border border-[#d7dce2] bg-white py-12 text-center text-[#5d6673]">
            Belum ada laporan selesai untuk dibuat PDF.
        </section>
    <?php endif; ?>

    <div class="space-y-5">
        <?php foreach ($reports as $report): ?>
            <?php $photos = $photosByReport[(int)$report['id']] ?? []; ?>
            <article class="report-page rounded-lg border border-[#d7dce2] bg-white p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="mb-2 flex flex-wrap gap-2">
                            <span class="rounded bg-[#f0f3f7] px-2 py-1 text-xs font-semibold"><?= e((string)$report['kode_laporan']) ?></span>
                            <span class="rounded bg-[#eef5ff] px-2 py-1 text-xs font-semibold text-[#00409c]"><?= e((string)$report['tingkat_prioritas']) ?></span>
                        </div>
                        <h2 class="text-xl font-bold"><?= e((string)$report['judul']) ?></h2>
                        <p class="text-sm text-[#5d6673]"><?= e((string)$report['nama_kategori']) ?> | Pelapor: <?= e((string)$report['nama_pelapor']) ?></p>
                    </div>
                    <div class="text-sm text-[#5d6673] sm:text-right">
                        <div>Masuk: <?= e(formatDashboardDate((string)$report['created_at'])) ?></div>
                        <div>Selesai: <?= e(formatDashboardDate((string)($report['tanggal_selesai'] ?? ''))) ?></div>
                    </div>
                </div>

                <div class="grid gap-3 text-sm md:grid-cols-2">
                    <div><span class="font-semibold">Lokasi:</span> <?= e((string)$report['lokasi_detail']) ?></div>
                    <div><span class="font-semibold">Petugas:</span> <?= e((string)($report['nama_petugas'] ?? '-')) ?></div>
                    <div><span class="font-semibold">Durasi:</span> <?= e((string)($report['durasi_hari'] ?? '-')) ?> hari</div>
                    <div class="md:col-span-2"><span class="font-semibold">Deskripsi:</span> <?= e((string)($report['deskripsi'] ?? '-')) ?></div>
                    <div class="md:col-span-2"><span class="font-semibold">Catatan petugas:</span> <?= e((string)($report['catatan_petugas'] ?? '-')) ?></div>
                </div>

                <div class="mt-5">
                    <h3 class="mb-3 font-bold">Bukti Foto</h3>
                    <?php if ($photos === []): ?>
                        <div class="rounded-lg border border-dashed border-[#c8ced8] p-4 text-sm text-[#5d6673]">Belum ada foto bukti.</div>
                    <?php else: ?>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($photos as $photo): ?>
                                <figure class="overflow-hidden rounded-lg border border-[#d7dce2]">
                                    <img class="h-48 w-full object-cover" src="<?= e(urlFor('/backend/' . ltrim((string)$photo['path_file'], '/'))) ?>" alt="Bukti <?= e((string)$photo['tipe_foto']) ?>">
                                    <figcaption class="bg-[#f7f9fc] px-3 py-2 text-xs font-semibold text-[#5d6673]">
                                        <?= e(str_replace('_', ' ', (string)$photo['tipe_foto'])) ?> | <?= e(formatDashboardDate((string)$photo['created_at'])) ?>
                                    </figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>
<script src="<?= e(urlFor('/pages/laporan_pdf.js')) ?>"></script>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
