<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Laporan - Laporin RT</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<main class="min-h-screen">
    <div class="flex min-h-screen">
        <?php renderAppSidebar($user, 'laporan-saya'); ?>

        <section class="flex min-w-0 flex-1 flex-col lg:ml-[280px]">
            <header class="border-b border-[#d7dce2] bg-white px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/laporan-saya')) ?>">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Kembali ke Laporan Saya
                        </a>
                        <h1 class="mt-2 text-2xl font-bold"><?= $report['status'] === 'ditolak' ? 'Update & Ajukan Ulang Laporan' : 'Edit Laporan' ?></h1>
                        <p class="text-sm text-[#5d6673]">
                            <?= e((string)$report['kode_laporan']) ?> | Status: <?= e((string)$report['label_status']) ?>
                        </p>
                    </div>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/laporan-saya')) ?>">
                        <span class="material-symbols-outlined text-lg">list_alt</span>
                        Laporan Saya
                    </a>
                </div>
            </header>

            <div class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <?php if ($errors !== []): ?>
                    <div class="mb-4 rounded-lg border border-[#f2b8b5] bg-[#ffdad6] p-4 text-[#93000a]">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="mb-4 rounded-lg border border-[#b8e6c9] bg-[#dcfce7] p-4 text-[#166534]"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($report['status'] === 'ditolak' && !empty($report['alasan_penolakan'])): ?>
                    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-800">
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-lg mt-0.5">warning</span>
                            <div>
                                <div class="font-semibold">Laporan ini ditolak</div>
                                <div class="text-sm mt-1">Alasan: <?= e((string)$report['alasan_penolakan']) ?></div>
                                <div class="text-sm mt-1 text-amber-600">Silakan perbarui laporan dan ajukan ulang.</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="rounded-lg border border-[#d7dce2] bg-white" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                    <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-12">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= e((string)$report['id']) ?>">
                        <input type="hidden" id="latitude" name="latitude" value="<?= old('latitude', (string)($report['latitude'] ?? '')) ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?= old('longitude', (string)($report['longitude'] ?? '')) ?>">
                        <input type="hidden" id="akurasi_gps_meter" name="akurasi_gps_meter" value="<?= old('akurasi_gps_meter', (string)($report['akurasi_gps_meter'] ?? '')) ?>">

                        <label class="lg:col-span-7">
                            <span class="mb-1 block text-sm font-semibold">Judul Laporan</span>
                            <input class="w-full rounded-lg border-[#c8ced8]" id="judul" name="judul" value="<?= old('judul', (string)$report['judul']) ?>" maxlength="200" required>
                        </label>
                        <label class="lg:col-span-5">
                            <span class="mb-1 block text-sm font-semibold">Kategori</span>
                            <select class="w-full rounded-lg border-[#c8ced8]" id="kategori_id" name="kategori_id" required>
                                <option value="">Pilih kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string)$category['id']) ?>" <?= selected('kategori_id', (string)$category['id'], (string)$report['kategori_id']) ?>>
                                        <?= e((string)$category['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="lg:col-span-12">
                            <span class="mb-1 block text-sm font-semibold">Deskripsi</span>
                            <textarea class="w-full rounded-lg border-[#c8ced8]" id="deskripsi" name="deskripsi" rows="4" required><?= old('deskripsi', (string)$report['deskripsi']) ?></textarea>
                        </label>
                        <label class="lg:col-span-8">
                            <span class="mb-1 block text-sm font-semibold">Lokasi Detail</span>
                            <input class="w-full rounded-lg border-[#c8ced8]" id="lokasi_detail" name="lokasi_detail" value="<?= old('lokasi_detail', (string)$report['lokasi_detail']) ?>" maxlength="255" required>
                        </label>
                        <label class="lg:col-span-4">
                            <span class="mb-1 block text-sm font-semibold">Prioritas</span>
                            <select class="w-full rounded-lg border-[#c8ced8]" id="tingkat_prioritas" name="tingkat_prioritas">
                                <option value="rendah" <?= selected('tingkat_prioritas', 'rendah', (string)$report['tingkat_prioritas']) ?>>Rendah</option>
                                <option value="sedang" <?= selected('tingkat_prioritas', 'sedang', (string)$report['tingkat_prioritas']) ?>>Sedang</option>
                                <option value="tinggi" <?= selected('tingkat_prioritas', 'tinggi', (string)$report['tingkat_prioritas']) ?>>Tinggi</option>
                                <option value="darurat" <?= selected('tingkat_prioritas', 'darurat', (string)$report['tingkat_prioritas']) ?>>Darurat</option>
                            </select>
                        </label>
                        <label class="lg:col-span-8">
                            <span class="mb-1 block text-sm font-semibold">Link Google Maps</span>
                            <input class="w-full rounded-lg border-[#c8ced8]" id="maps_url" name="maps_url" value="<?= old('maps_url', (string)($report['maps_url'] ?? '')) ?>">
                        </label>
                        <label class="lg:col-span-4">
                            <span class="mb-1 block text-sm font-semibold">Tambah Foto Bukti</span>
                            <input class="w-full rounded-lg border-[#c8ced8]" id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        </label>

                        <div class="lg:col-span-12 rounded-lg border border-[#d7dce2] bg-[#f7f9fc] p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold">Lokasi GPS realtime</div>
                                    <div class="text-sm text-[#5d6673]" id="gpsStatus">
                                        <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                                            Lokasi tersimpan: <?= e((string)$report['latitude']) ?>, <?= e((string)$report['longitude']) ?>
                                        <?php else: ?>
                                            Belum ada lokasi GPS.
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" id="gpsButton" type="button">
                                    <span class="material-symbols-outlined text-lg">my_location</span>
                                    Perbarui Lokasi
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-[#d7dce2] px-4 py-4 sm:px-6">
                        <a class="rounded-lg px-4 py-2 font-semibold text-[#5d6673] hover:bg-[#f0f3f7]" href="<?= e(urlFor('/laporan-saya')) ?>">Batal</a>
                        <button class="inline-flex items-center gap-2 rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">
                            <span class="material-symbols-outlined text-lg"><?= $report['status'] === 'ditolak' ? 'send' : 'save' ?></span>
                            <?= $report['status'] === 'ditolak' ? 'Ajukan Ulang' : 'Simpan Perubahan' ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
<script src="<?= e(urlFor('/pages/laporan.js')) ?>"></script>
<script>
(function() {
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Menyimpan...';
            }
        });
    }
})();
</script>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
