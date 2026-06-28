<?php declare(strict_types=1); ?>
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
    <style>
        .modal-bg { background: rgba(0,0,0,.5); }
        .prio-rendah  { background:#dcfce7; color:#166534; }
        .prio-sedang  { background:#dbeafe; color:#1e40af; }
        .prio-tinggi  { background:#fef3c7; color:#92400e; }
        .prio-darurat { background:#fee2e2; color:#991b1b; }
    </style>
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
            <p class="text-sm text-[#5d6673]">Klik <strong>Detail</strong> untuk melihat dan mengelola laporan.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/admin-users')) ?>">
            <span class="material-symbols-outlined text-lg">group</span>
            Kelola User
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
            <h2 class="text-lg font-semibold">Daftar Laporan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[860px]">
                <thead class="bg-[#f7f9fc] border-b border-[#d7dce2]">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Kode</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Laporan</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Pelapor</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Prioritas</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-bold text-[#5d6673] uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e8edf2]">
                    <?php if ($reports === []): ?>
                        <tr><td colspan="7" class="px-4 py-12 text-center text-[#5d6673]">Belum ada laporan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $row):
                        $s = $row['status'];
                        $statusClass = $s === 'menunggu_verifikasi' ? 'bg-amber-100 text-amber-800'
                            : ($s === 'ditolak'    ? 'bg-rose-100 text-rose-700'
                            : ($s === 'selesai'    ? 'bg-emerald-100 text-emerald-700'
                            : ($s === 'dibatalkan' ? 'bg-gray-100 text-gray-500'
                            : 'bg-sky-100 text-sky-800')));
                        $prioClass = 'prio-' . ($row['tingkat_prioritas'] ?? 'sedang');
                        $photos    = $photosByReport[(int)$row['id']] ?? [];
                        $rowJson   = e(json_encode($row));
                        $photosJson = e(json_encode($photos));
                    ?>
                        <tr class="hover:bg-[#f7f9fc]">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-[#5d6673] whitespace-nowrap"><?= e((string)$row['kode_laporan']) ?></td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-sm"><?= e((string)$row['judul']) ?></div>
                                <div class="text-xs text-[#5d6673]"><?= e((string)$row['nama_kategori']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div><?= e((string)$row['nama_pelapor']) ?></div>
                                <?php if (!empty($row['hp_pelapor'])): ?>
                                    <div class="text-xs text-[#5d6673]"><?= e((string)$row['hp_pelapor']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded px-2 py-1 text-xs font-semibold <?= $statusClass ?>"><?= e((string)$row['label_status']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded px-2 py-1 text-xs font-semibold capitalize <?= $prioClass ?>"><?= e((string)($row['tingkat_prioritas'] ?? '-')) ?></span>
                            </td>
                            <td class="px-4 py-3 text-xs text-[#5d6673] whitespace-nowrap"><?= e(formatDashboardDate((string)$row['created_at'])) ?></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button"
                                    class="inline-flex items-center gap-1 rounded-lg bg-[#00409c] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#0056cc] transition-colors"
                                    data-row="<?= $rowJson ?>"
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:92vh">

        <!-- Header -->
        <div class="flex items-start justify-between px-6 pt-5 pb-4 border-b border-[#e8edf2] flex-shrink-0">
            <div>
                <span id="mKode" class="font-mono text-xs font-bold text-[#5d6673] tracking-wide"></span>
                <h2 id="mJudul" class="text-xl font-bold text-[#181c20] mt-1 leading-snug"></h2>
            </div>
            <button onclick="closeModal()" class="ml-3 mt-0.5 flex-shrink-0 rounded-full w-8 h-8 flex items-center justify-center hover:bg-[#f0f3f7] transition-colors">
                <span class="material-symbols-outlined text-[#5d6673]" style="font-size:20px">close</span>
            </button>
        </div>

        <!-- Scrollable Body -->
        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

            <!-- Status & Priority Badges -->
            <div class="flex flex-wrap gap-2">
                <span id="mStatus" class="rounded-full px-3 py-1 text-xs font-semibold"></span>
                <span id="mPrio" class="rounded-full px-3 py-1 text-xs font-semibold capitalize"></span>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-y-2 gap-x-6 text-sm">
                <div><span class="font-semibold text-[#181c20]">Pelapor: </span><span id="mPelapor" class="text-[#424654]"></span></div>
                <div><span class="font-semibold text-[#181c20]">HP: </span><span id="mHp" class="text-[#424654]"></span></div>
                <div><span class="font-semibold text-[#181c20]">Kategori: </span><span id="mKategori" class="text-[#424654]"></span></div>
                <div><span class="font-semibold text-[#181c20]">Petugas: </span><span id="mPetugas" class="text-[#424654]"></span></div>
                <div class="col-span-2"><span class="font-semibold text-[#181c20]">Lokasi: </span><span id="mLokasi" class="text-[#424654]"></span></div>
            </div>

            <!-- Alasan Penolakan -->
            <div id="mPenolakanBox" class="hidden">
                <p class="text-xs font-bold uppercase tracking-widest text-rose-500 mb-2">Alasan Penolakan</p>
                <p id="mPenolakan" class="text-sm bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-rose-800"></p>
            </div>

            <!-- Deskripsi -->
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#5d6673] mb-2">Deskripsi</p>
                <p id="mDeskripsi" class="text-sm bg-[#f7f9fc] border border-[#d7dce2] rounded-xl px-4 py-3 whitespace-pre-wrap text-[#181c20] leading-relaxed"></p>
            </div>

            <!-- Foto Bukti -->
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#5d6673] mb-3">Foto Bukti Aduan</p>
                <div id="mFotos" class="flex flex-wrap gap-3"></div>
            </div>

            <hr class="border-[#e8edf2]">

            <!-- Aksi Admin -->
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#5d6673] mb-3">Aksi Admin</p>

                <!-- Tugaskan Form -->
                <form id="fAssign" method="post" action="<?= e(urlFor('/admin-laporan')) ?>" class="space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" id="aId" name="laporan_id">

                    <div>
                        <label class="block text-xs font-semibold text-[#5d6673] mb-1">Pilih Petugas</label>
                        <select class="w-full rounded-xl border-[#d7dce2] text-sm py-2" id="aPetugas" name="petugas_id" required>
                            <option value="">Pilih petugas</option>
                            <?php foreach ($petugas as $p): ?>
                                <option value="<?= e((string)$p['id']) ?>"><?= e((string)$p['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#5d6673] mb-1">Ubah Prioritas (opsional)</label>
                        <select class="w-full rounded-xl border-[#d7dce2] text-sm py-2" id="aPrio" name="tingkat_prioritas">
                            <option value="">Tidak diubah</option>
                            <option value="rendah">Rendah</option>
                            <option value="sedang">Sedang</option>
                            <option value="tinggi">Tinggi</option>
                            <option value="darurat">🔴 Darurat</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-[#5d6673] mb-1">Target Selesai</label>
                            <input class="w-full rounded-xl border-[#d7dce2] text-sm py-2" name="tanggal_target_selesai" type="date">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#5d6673] mb-1">Catatan (opsional)</label>
                            <input class="w-full rounded-xl border-[#d7dce2] text-sm py-2" name="catatan_admin" placeholder="Catatan penugasan">
                        </div>
                    </div>

                    <button class="w-full rounded-xl bg-[#00409c] py-2.5 font-semibold text-white hover:bg-[#0056cc] transition-colors" type="submit">
                        Tugaskan ke Petugas
                    </button>
                </form>

                <!-- Verifikasi & Tolak -->
                <div class="flex gap-2 mt-2">
                    <form method="post" action="<?= e(urlFor('/admin-laporan')) ?>" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" class="vId" name="laporan_id">
                        <button class="w-full rounded-xl bg-emerald-600 py-2.5 font-semibold text-white hover:bg-emerald-700 transition-colors" type="submit">✓ Verifikasi</button>
                    </form>
                    <form method="post" action="<?= e(urlFor('/admin-laporan')) ?>" class="flex gap-2 flex-1">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" class="rId" name="laporan_id">
                        <input class="min-w-0 flex-1 rounded-xl border-[#d7dce2] text-sm px-3" name="alasan_penolakan" placeholder="Alasan tolak" required>
                        <button class="rounded-xl border border-rose-300 px-4 py-2.5 font-semibold text-rose-700 hover:bg-rose-50 transition-colors whitespace-nowrap" type="submit">Tolak</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>


<?php renderIdleLogoutScript(); ?>
<script>
const statusMap = {
    menunggu_verifikasi: 'bg-amber-100 text-amber-800',
    ditolak:             'bg-rose-100 text-rose-700',
    selesai:             'bg-emerald-100 text-emerald-700',
    dibatalkan:          'bg-gray-100 text-gray-500',
};

function openModal(btn) {
    const row    = JSON.parse(btn.dataset.row);
    const photos = JSON.parse(btn.dataset.photos);

    document.getElementById('mKode').textContent     = row.kode_laporan || '';
    document.getElementById('mJudul').textContent    = row.judul || '';
    document.getElementById('mDeskripsi').textContent = row.deskripsi || '-';
    document.getElementById('mPelapor').textContent  = row.nama_pelapor || '-';
    document.getElementById('mHp').textContent       = row.hp_pelapor || '-';
    document.getElementById('mKategori').textContent = row.nama_kategori || '-';
    document.getElementById('mPetugas').textContent  = row.nama_petugas || 'Belum ditugaskan';
    document.getElementById('mLokasi').textContent   = row.lokasi_detail || '-';

    const sc = statusMap[row.status] || 'bg-sky-100 text-sky-800';
    document.getElementById('mStatus').className = 'rounded px-2 py-1 text-xs font-semibold ' + sc;
    document.getElementById('mStatus').textContent = row.label_status || row.status;

    const pc = 'prio-' + (row.tingkat_prioritas || 'sedang');
    document.getElementById('mPrio').className = 'rounded px-2 py-1 text-xs font-semibold capitalize ' + pc;
    document.getElementById('mPrio').textContent = row.tingkat_prioritas || '-';

    const penolakanBox = document.getElementById('mPenolakanBox');
    if (row.alasan_penolakan) {
        document.getElementById('mPenolakan').textContent = row.alasan_penolakan;
        penolakanBox.classList.remove('hidden');
    } else {
        penolakanBox.classList.add('hidden');
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

    // Set IDs on all forms
    document.getElementById('aId').value = row.id;
    document.querySelectorAll('.vId, .rId').forEach(el => el.value = row.id);

    // Pre-select priority
    const prioSel = document.getElementById('aPrio');
    Array.from(prioSel.options).forEach(o => { o.selected = o.value === row.tingkat_prioritas; });

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
