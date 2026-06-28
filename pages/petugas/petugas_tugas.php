<?php declare(strict_types=1); ?>
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
    <style>
        .modal-bg { background: rgba(0,0,0,.5); }
        .prio-rendah  { background:#dcfce7; color:#166534; }
        .prio-sedang  { background:#dbeafe; color:#1e40af; }
        .prio-tinggi  { background:#fef3c7; color:#92400e; }
        .prio-darurat { background:#fee2e2; color:#991b1b; }
    </style>
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
            <p class="text-sm text-[#5d6673]">Klik <strong>Detail</strong> untuk mengupdate progress atau menyelesaikan tugas.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/petugas-riwayat')) ?>">
            <span class="material-symbols-outlined text-lg">history</span>
            Riwayat Tugas
        </a>
    </header>

    <?php if ($errors !== []): ?>
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="bg-white border border-[#d7dce2] rounded-lg shadow-sm overflow-hidden">
        <div class="bg-[#E6F2FF] px-4 py-4 border-b border-[#d7dce2]">
            <h2 class="text-lg font-semibold">Daftar Tugas Aktif</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[800px]">
                <thead class="bg-[#f7f9fc] border-b border-[#d7dce2]">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Laporan</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Lokasi</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Prioritas</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Target</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e8edf2]">
                    <?php if ($tasks === []): ?>
                        <tr><td colspan="7" class="px-4 py-12 text-center text-[#5d6673]">Belum ada tugas aktif.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tasks as $task):
                        $ts = $task['status'];
                        $statusClass = $ts === 'ditugaskan'          ? 'bg-amber-100 text-amber-800'
                            : ($ts === 'diverifikasi'        ? 'bg-sky-100 text-sky-800'
                            : ($ts === 'dalam_pengerjaan'    ? 'bg-blue-100 text-blue-800'
                            : ($ts === 'perlu_tindak_lanjut' ? 'bg-orange-100 text-orange-800'
                            : 'bg-gray-100 text-gray-700')));
                        $prioClass = 'prio-' . ($task['tingkat_prioritas'] ?? 'sedang');
                        $photos = $photosByReport[(int)$task['id']] ?? [];
                        $taskJson = e(json_encode($task));
                        $photosJson = e(json_encode($photos));
                    ?>
                        <tr class="hover:bg-[#f7f9fc]">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-[#5d6673] whitespace-nowrap"><?= e((string)$task['kode_laporan']) ?></td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-sm"><?= e((string)$task['judul']) ?></div>
                                <div class="text-xs text-[#5d6673]"><?= e((string)$task['nama_kategori']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#5d6673]"><?= e((string)$task['lokasi_detail']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $statusClass ?>"><?= e((string)$task['label_status']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded px-2 py-1 text-xs font-semibold capitalize <?= $prioClass ?>"><?= e((string)($task['tingkat_prioritas'] ?? '-')) ?></span>
                            </td>
                            <td class="px-4 py-3 text-xs text-[#5d6673] whitespace-nowrap"><?= e((string)($task['tanggal_target_selesai'] ?? '-')) ?></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button"
                                    class="inline-flex items-center gap-1 rounded-lg bg-[#00409c] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#0056cc] transition-colors"
                                    data-task="<?= $taskJson ?>"
                                    data-photos="<?= $photosJson ?>"
                                    onclick="openModal(this)">
                                    <span class="material-symbols-outlined" style="font-size:14px">open_in_new</span>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- ===== DETAIL MODAL ===== -->
<div id="detailModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl flex flex-col" style="max-height:90vh">

        <!-- Header -->
        <div class="flex items-start justify-between border-b border-[#d7dce2] px-5 py-4 flex-shrink-0">
            <div>
                <span id="mKode" class="font-mono text-xs font-semibold text-[#5d6673]"></span>
                <h2 id="mJudul" class="text-lg font-bold mt-0.5"></h2>
            </div>
            <button onclick="closeModal()" class="ml-3 rounded-full p-1 hover:bg-[#f0f3f7] flex-shrink-0">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Scrollable body -->
        <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

            <!-- Badges -->
            <div class="flex flex-wrap gap-2">
                <span id="mStatus" class="rounded px-2 py-1 text-xs font-semibold"></span>
                <span id="mPrio" class="rounded px-2 py-1 text-xs font-semibold capitalize"></span>
            </div>

            <!-- Info -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><span class="font-semibold text-[#5d6673]">Pelapor:</span> <span id="mPelapor"></span></div>
                <div><span class="font-semibold text-[#5d6673]">HP:</span> <span id="mHp"></span></div>
                <div><span class="font-semibold text-[#5d6673]">Kategori:</span> <span id="mKategori"></span></div>
                <div><span class="font-semibold text-[#5d6673]">Target:</span> <span id="mTarget"></span></div>
                <div class="col-span-2"><span class="font-semibold text-[#5d6673]">Lokasi:</span> <span id="mLokasi"></span></div>
            </div>

            <!-- Deskripsi -->
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5d6673] mb-1">Deskripsi</p>
                <p id="mDeskripsi" class="text-sm bg-[#f7f9fc] border border-[#d7dce2] rounded-lg px-3 py-2 whitespace-pre-wrap"></p>
            </div>

            <!-- Maps link -->
            <div id="mMapsBox" class="hidden">
                <a id="mMaps" href="#" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c] hover:underline">
                    <span class="material-symbols-outlined text-base">location_on</span>
                    Buka lokasi di Google Maps
                </a>
            </div>

            <!-- Foto Bukti -->
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5d6673] mb-2">Foto Bukti Aduan</p>
                <div id="mFotos" class="flex flex-wrap gap-2"></div>
            </div>

            <hr class="border-[#d7dce2]">

            <!-- Update Form -->
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5d6673] mb-2">Update Tugas</p>
                <form id="fUpdate" method="post" action="<?= e(urlFor('/petugas-tugas')) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" id="tId" name="laporan_id">

                    <!-- Prioritas -->
                    <div class="mb-2">
                        <label class="block text-xs font-semibold text-[#5d6673] mb-1">Ubah Prioritas (opsional)</label>
                        <select class="w-full rounded-lg border-[#c8ced8] text-sm" id="tPrio" name="tingkat_prioritas">
                            <option value="">Tidak diubah</option>
                            <option value="rendah">Rendah</option>
                            <option value="sedang">Sedang</option>
                            <option value="tinggi">Tinggi</option>
                            <option value="darurat">🔴 Darurat</option>
                        </select>
                    </div>

                    <!-- Catatan -->
                    <textarea class="mb-2 w-full rounded-lg border-[#c8ced8] text-sm" name="catatan_petugas" rows="3" placeholder="Catatan progress atau penyelesaian (wajib kecuali aksi Mulai)"></textarea>

                    <!-- Upload Foto -->
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-[#5d6673] mb-1">Upload Foto (opsional)</label>
                        <input class="w-full rounded-lg border border-[#c8ced8] text-sm px-2 py-1.5" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <button class="rounded-lg border border-[#c8ced8] bg-white px-3 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" name="action" value="mulai" type="submit">Mulai</button>
                        <button class="rounded-lg bg-[#00409c] px-3 py-2 font-semibold text-white hover:bg-[#0056cc]" name="action" value="progress" type="submit">Update Progress</button>
                        <button class="rounded-lg border border-amber-300 px-3 py-2 font-semibold text-amber-800 hover:bg-amber-50" name="action" value="tindak_lanjut" type="submit">Tindak Lanjut</button>
                        <button class="rounded-lg bg-emerald-600 px-3 py-2 font-semibold text-white hover:bg-emerald-700" name="action" value="selesai" type="submit">✓ Selesai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderIdleLogoutScript(); ?>
<script>
const statusMap = {
    ditugaskan:          'bg-amber-100 text-amber-800',
    diverifikasi:        'bg-sky-100 text-sky-800',
    dalam_pengerjaan:    'bg-blue-100 text-blue-800',
    perlu_tindak_lanjut: 'bg-orange-100 text-orange-800',
};

function openModal(btn) {
    const task   = JSON.parse(btn.dataset.task);
    const photos = JSON.parse(btn.dataset.photos);

    document.getElementById('mKode').textContent     = task.kode_laporan || '';
    document.getElementById('mJudul').textContent    = task.judul || '';
    document.getElementById('mDeskripsi').textContent = task.deskripsi || '-';
    document.getElementById('mPelapor').textContent  = task.nama_pelapor || '-';
    document.getElementById('mHp').textContent       = task.hp_pelapor || '-';
    document.getElementById('mKategori').textContent = task.nama_kategori || '-';
    document.getElementById('mTarget').textContent   = task.tanggal_target_selesai || '-';
    document.getElementById('mLokasi').textContent   = task.lokasi_detail || '-';

    const sc = statusMap[task.status] || 'bg-gray-100 text-gray-700';
    document.getElementById('mStatus').className = 'rounded px-2 py-1 text-xs font-semibold ' + sc;
    document.getElementById('mStatus').textContent = task.label_status || task.status;

    const pc = 'prio-' + (task.tingkat_prioritas || 'sedang');
    document.getElementById('mPrio').className = 'rounded px-2 py-1 text-xs font-semibold capitalize ' + pc;
    document.getElementById('mPrio').textContent = task.tingkat_prioritas || '-';

    // Maps
    const mapsBox = document.getElementById('mMapsBox');
    if (task.latitude && task.longitude) {
        document.getElementById('mMaps').href = `https://www.google.com/maps?q=${task.latitude},${task.longitude}`;
        mapsBox.classList.remove('hidden');
    } else {
        mapsBox.classList.add('hidden');
    }

    // Photos
    const fc = document.getElementById('mFotos');
    if (photos && photos.length > 0) {
        fc.innerHTML = photos.map(p =>
            `<a href="${p.url}" target="_blank" class="block border border-[#c8ced8] rounded-lg overflow-hidden hover:border-[#00409c] transition-colors">
                <img src="${p.url}" alt="Foto" class="h-20 w-20 object-cover">
            </a>`
        ).join('');
    } else {
        fc.innerHTML = '<span class="text-xs text-[#5d6673]">Tidak ada foto bukti.</span>';
    }

    // Set task ID & pre-select priority
    document.getElementById('tId').value = task.id;
    const prioSel = document.getElementById('tPrio');
    Array.from(prioSel.options).forEach(o => { o.selected = o.value === task.tingkat_prioritas; });

    document.getElementById('detailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('detailModal').addEventListener('click', e => {
    if (e.target === document.getElementById('detailModal')) closeModal();
});
</script>
</body>
</html>
