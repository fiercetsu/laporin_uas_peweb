<?php
declare(strict_types=1);

$role = (string)($user['role'] ?? 'warga');
$roleTitles = [
    'warga' => 'Dashboard Warga',
    'admin' => 'Dashboard Admin',
    'petugas' => 'Dashboard Petugas',
    'rt' => 'Dashboard RT',
];
$roleDescriptions = [
    'warga' => 'Pantau laporan yang kamu kirim dan notifikasi terbaru.',
    'admin' => 'Kelola verifikasi laporan, akun warga, dan data operasional.',
    'petugas' => 'Pantau tugas aktif dan progres penanganan laporan.',
    'rt' => 'Lihat kondisi wilayah, laporan darurat, dan performa petugas.',
];
$stats = $dashboard['stats'] ?? [];
$rows = $dashboard['rows'] ?? [];
$secondaryRows = $dashboard['secondary_rows'] ?? [];

if ($role === 'warga' && $secondaryRows !== []) {
    $secondaryIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $secondaryRows)));
    $photosByReport = [];
    if ($secondaryIds !== []) {
        foreach (getReportsPhotos($secondaryIds) as $photo) {
            $photosByReport[(int)$photo['laporan_id']][] = [
                'url' => urlFor('/backend/' . ltrim((string)$photo['path_file'], '/')),
                'type' => str_replace('_', ' ', (string)$photo['tipe_foto']),
                'date' => formatDashboardDate((string)$photo['created_at']),
            ];
        }
    }

    foreach ($secondaryRows as $index => $row) {
        $secondaryRows[$index]['photos_json'] = json_encode($photosByReport[(int)($row['id'] ?? 0)] ?? []);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= e($roleTitles[$role] ?? 'Dashboard') ?> - Sistem Layanan Publik</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary": "#00409c",
                        "on-surface": "#1b1c1c",
                        "surface-tint": "#0357cd",
                        "on-primary-fixed-variant": "#00419e",
                        "surface-container-high": "#eae8e7",
                        "on-tertiary-fixed": "#001b3d",
                        "inverse-surface": "#303030",
                        "secondary-fixed-dim": "#a5c8ff",
                        "inverse-on-surface": "#f2f0f0",
                        "tertiary-container": "#325e9c",
                        "on-secondary-container": "#fefcff",
                        "primary-container": "#0056cc",
                        "tertiary-fixed": "#d6e3ff",
                        "surface-bright": "#fbf9f8",
                        "on-error-container": "#93000a",
                        "error-container": "#ffdad6",
                        "outline": "#737785",
                        "surface-dim": "#dcd9d9",
                        "tertiary-fixed-dim": "#a8c8ff",
                        "error": "#ba1a1a",
                        "surface-container-lowest": "#ffffff",
                        "surface": "#fbf9f8",
                        "primary-fixed-dim": "#b1c5ff",
                        "on-surface-variant": "#424654",
                        "surface-container": "#f0eded",
                        "on-secondary-fixed-variant": "#004786",
                        "on-secondary-fixed": "#001c3a",
                        "on-secondary": "#ffffff",
                        "primary-fixed": "#dae2ff",
                        "on-tertiary": "#ffffff",
                        "on-tertiary-fixed-variant": "#134684",
                        "background": "#fbf9f8",
                        "tertiary": "#124683",
                        "secondary": "#005cab",
                        "surface-variant": "#e4e2e1",
                        "secondary-fixed": "#d4e3ff",
                        "inverse-primary": "#b1c5ff",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#c9d6ff",
                        "on-primary-fixed": "#001946",
                        "on-tertiary-container": "#c4d8ff",
                        "outline-variant": "#c3c6d6",
                        "surface-container-low": "#f5f3f3",
                        "surface-container-highest": "#e4e2e1",
                        "secondary-container": "#0075d6",
                        "on-background": "#1b1c1c"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem"
                    },
                    "spacing": {
                        "xs": "4px",
                        "md": "16px",
                        "container-padding": "24px",
                        "lg": "24px",
                        "base": "4px",
                        "xl": "32px",
                        "sm": "8px",
                        "gutter": "16px"
                    },
                    "fontFamily": {
                        "headline-lg-mobile": ["\"Source Sans 3\""],
                        "title-lg": ["\"Source Sans 3\""],
                        "body-md": ["\"Source Sans 3\""],
                        "code": ["\"Source Sans 3\""],
                        "label-md": ["\"Source Sans 3\""],
                        "headline-lg": ["\"Source Sans 3\""],
                        "body-lg": ["\"Source Sans 3\""],
                        "display-lg": ["\"Source Sans 3\""],
                        "headline-md": ["\"Source Sans 3\""]
                    },
                    "fontSize": {
                        "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "title-lg": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "code": ["13px", { "lineHeight": "18px", "fontWeight": "400" }],
                        "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; background-color: #F5F5F5; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body class="flex min-h-screen text-on-surface bg-[#F5F5F5]">

<!-- TopNavBar (Mobile Only) -->
<header class="lg:hidden bg-primary text-on-primary flex justify-between items-center w-full px-md h-16 shadow-sm fixed top-0 left-0 z-50">
    <div class="text-title-lg font-title-lg font-bold">Laporin RT</div>
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm uppercase">
            <?= substr(e((string)$user['nama_lengkap']), 0, 1) ?>
        </div>
        <form method="post" action="<?= e(urlFor('/logout')) ?>" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button class="hover:bg-primary-container/20 p-2 rounded-full transition-colors flex items-center justify-center" type="submit" aria-label="Keluar">
                <span class="material-symbols-outlined text-white">logout</span>
            </button>
        </form>
    </div>
</header>

<?php renderAppSidebar($user, 'dashboard'); ?>

<!-- Main Content Area -->
<main class="flex-1 w-full lg:ml-[280px] pt-20 lg:pt-0 p-md lg:p-lg lg:max-w-[1440px] mx-auto min-h-screen flex flex-col gap-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-headline-md font-headline-md text-on-surface"><?= e($roleTitles[$role] ?? 'Dashboard') ?></h1>
            <p class="text-body-md font-body-md text-on-surface-variant mt-1"><?= e($roleDescriptions[$role] ?? 'Ringkasan aktivitas akun.') ?></p>
        </div>
    </div>

    <?php if (!empty($dashboard['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-body-md" role="alert">
            <?= e((string)$dashboard['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php 
        $iconMap = [
            'total laporan' => 'inbox',
            'menunggu' => 'hourglass_empty',
            'menunggu verifikasi' => 'hourglass_empty',
            'diproses' => 'pending_actions',
            'sedang diproses' => 'pending_actions',
            'dikerjakan' => 'build',
            'selesai' => 'check_circle',
            'darurat aktif' => 'priority_high',
            'petugas aktif' => 'engineering',
            'akun pending' => 'person_add',
            'total user' => 'group',
            'total tugas' => 'assignment_late',
            'ditugaskan' => 'assignment_ind',
        ];
        $colorMap = [
            'primary' => 'bg-primary-container/10 text-primary-container',
            'warning' => 'bg-amber-100 text-amber-800',
            'info' => 'bg-sky-100 text-sky-800',
            'success' => 'bg-emerald-100 text-emerald-800',
            'danger' => 'bg-rose-100 text-rose-800',
            'red' => 'bg-rose-100 text-rose-800',
        ];
        ?>
        <?php foreach ($stats as $stat): ?>
            <?php 
            $labelLower = strtolower(trim($stat['label']));
            $icon = $iconMap[$labelLower] ?? 'analytics';
            $tone = $stat['tone'] ?? 'primary';
            $colorClasses = $colorMap[$tone] ?? 'bg-primary-container/10 text-primary-container';
            ?>
            <div class="bg-white border border-[#BDBDBD] rounded-xl p-4 flex items-center gap-4 shadow-sm">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl <?= $colorClasses ?>">
                    <span class="material-symbols-outlined"><?= $icon ?></span>
                </div>
                <div>
                    <div class="text-label-md font-label-md text-on-surface-variant uppercase tracking-wider"><?= e((string)$stat['label']) ?></div>
                    <div class="text-headline-md font-headline-md text-on-surface"><?= e((string)$stat['value']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Data Table & Side Cards Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Table Card -->
        <div class="lg:col-span-2 bg-white border border-[#BDBDBD] rounded-lg shadow-sm flex flex-col overflow-hidden">
            <div class="bg-[#E6F2FF] px-md py-4 border-b border-[#BDBDBD] flex justify-between items-center">
                <h2 class="text-title-lg font-title-lg text-on-surface"><?= e((string)($dashboard['primary_title'] ?? 'Data Utama')) ?></h2>
                <button onclick="window.location.reload();" class="text-primary hover:bg-primary/10 px-3 py-1 rounded text-body-md font-body-md transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">refresh</span> Refresh
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead class="bg-surface border-b border-[#BDBDBD]">
                        <tr>
                            <th class="px-md py-3 text-label-md font-label-md text-on-surface-variant uppercase tracking-wider">Kode</th>
                            <th class="px-md py-3 text-label-md font-label-md text-on-surface-variant uppercase tracking-wider">Laporan / Pelapor</th>
                            <th class="px-md py-3 text-label-md font-label-md text-on-surface-variant uppercase tracking-wider">Status</th>
                            <th class="px-md py-3 text-label-md font-label-md text-on-surface-variant uppercase tracking-wider">Prioritas</th>
                            <th class="px-md py-3 text-label-md font-label-md text-on-surface-variant uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#BDBDBD] text-body-md font-body-md text-on-surface">
                        <?php if ($rows === []): ?>
                            <tr>
                                <td class="px-md py-8 text-center text-on-surface-variant" colspan="5">Belum ada data laporan terbaru.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $status = $row['status'] ?? '';
                            $badgeColor = 'bg-gray-100 text-gray-800';
                            if (in_array($status, ['menunggu_verifikasi', 'pending'], true)) {
                                $badgeColor = 'bg-gray-200 text-gray-700 border border-[#BDBDBD]';
                            } elseif (in_array($status, ['diverifikasi', 'ditugaskan'], true)) {
                                $badgeColor = 'bg-sky-100 text-sky-800';
                            } elseif ($status === 'dalam_pengerjaan') {
                                $badgeColor = 'bg-blue-100 text-blue-800';
                            } elseif ($status === 'selesai') {
                                $badgeColor = 'bg-emerald-100 text-emerald-800';
                            } elseif ($status === 'ditolak' || $status === 'dibatalkan') {
                                $badgeColor = 'bg-rose-100 text-rose-800';
                            }
                            ?>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-md py-4 font-mono text-xs font-semibold"><?= e((string)($row['kode_laporan'] ?? '-')) ?></td>
                                <td class="px-md py-4">
                                    <div class="font-semibold text-on-surface"><?= e((string)($row['judul'] ?? '-')) ?></div>
                                    <div class="text-xs text-on-surface-variant"><?= e((string)($row['nama_pelapor'] ?? $row['lokasi_detail'] ?? '')) ?></div>
                                </td>
                                <td class="px-md py-4">
                                    <span class="px-2 py-1 rounded text-xs font-semibold inline-block <?= $badgeColor ?>">
                                        <?= e((string)($row['label_status'] ?? '-')) ?>
                                    </span>
                                </td>
                                <td class="px-md py-4">
                                    <span class="text-xs capitalize font-semibold"><?= e((string)($row['tingkat_prioritas'] ?? '-')) ?></span>
                                </td>
                                <td class="px-md py-4 text-xs text-on-surface-variant whitespace-nowrap">
                                    <?= e(formatDashboardDate((string)($row['created_at'] ?? $row['tanggal_selesai'] ?? ''))) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Secondary Info Card -->
        <div class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm flex flex-col overflow-hidden h-fit">
            <div class="bg-surface px-md py-4 border-b border-[#BDBDBD] flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-title-lg font-title-lg text-on-surface"><?= e((string)($dashboard['secondary_title'] ?? 'Ringkasan')) ?></h2>
                <?php if ($role === 'warga'): ?>
                    <button class="inline-flex w-fit items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-container" type="button" data-open-report-modal>
                        <span class="material-symbols-outlined text-lg">rate_review</span>
                        Buat Laporan
                    </button>
                <?php endif; ?>
            </div>
            <div class="divide-y divide-[#BDBDBD]">
                <?php if ($secondaryRows === []): ?>
                    <div class="px-md py-6 text-center text-on-surface-variant text-body-md">Belum ada data ringkasan.</div>
                <?php endif; ?>
                <?php foreach ($secondaryRows as $row): ?>
                    <div class="p-4 hover:bg-surface-container-low transition-colors text-body-md">
                        <?php if (isset($row['role'])): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-capitalize font-semibold text-on-surface"><?= e((string)$row['role']) ?></span>
                                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-bold"><?= e((string)$row['jumlah']) ?></span>
                            </div>
                        <?php elseif (isset($row['kode_petugas'])): ?>
                            <div class="font-semibold text-on-surface"><?= e((string)$row['nama_petugas']) ?></div>
                            <div class="text-xs text-on-surface-variant mt-1">
                                Kode: <?= e((string)$row['kode_petugas']) ?>
                            </div>
                            <div class="flex gap-4 mt-2">
                                <span class="text-xs bg-sky-50 text-sky-700 px-2 py-0.5 rounded">Aktif: <strong class="font-bold"><?= e((string)$row['jml_aktif']) ?></strong></span>
                                <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">Selesai: <strong class="font-bold"><?= e((string)$row['jml_selesai']) ?></strong></span>
                            </div>
                        <?php elseif ($role === 'warga' && isset($row['kode_laporan'])): ?>
                            <?php
                            $status = (string)($row['status'] ?? '');
                            $statusBadge = $status === 'selesai'
                                ? 'bg-emerald-100 text-emerald-800'
                                : ($status === 'perlu_tindak_lanjut' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800');
                            ?>
                            <div class="flex flex-col gap-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-on-surface truncate"><?= e((string)$row['judul']) ?></div>
                                        <div class="text-xs text-on-surface-variant mt-1"><?= e((string)$row['kode_laporan']) ?><?= !empty($row['nama_petugas']) ? ' | Petugas: ' . e((string)$row['nama_petugas']) : '' ?></div>
                                    </div>
                                    <span class="shrink-0 rounded px-2 py-1 text-xs font-semibold <?= $statusBadge ?>"><?= e((string)$row['label_status']) ?></span>
                                </div>
                                <?php if (!empty($row['catatan_petugas'])): ?>
                                    <div class="rounded-lg bg-surface-container-low px-3 py-2 text-xs text-on-surface-variant">
                                        <?= e((string)$row['catatan_petugas']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-[11px] text-on-surface-variant">
                                        <?= e(formatDashboardDate((string)($row['tanggal_selesai'] ?? $row['tanggal_mulai_kerjakan'] ?? $row['created_at'] ?? ''))) ?>
                                    </div>
                                    <button class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary/10" type="button" data-dashboard-photos="<?= e((string)($row['photos_json'] ?? '[]')) ?>">
                                        Lihat Bukti (<?= e((string)($row['jumlah_foto'] ?? 0)) ?>)
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="font-semibold text-on-surface truncate max-w-[280px]"><?= e((string)($row['judul'] ?? $row['kode_laporan'] ?? '-')) ?></div>
                            <div class="text-xs text-on-surface-variant mt-1"><?= e((string)($row['pesan'] ?? $row['label_status'] ?? '')) ?></div>
                            <div class="text-[11px] text-on-surface-variant mt-1">
                                <?= e(formatDashboardDate((string)($row['created_at'] ?? $row['tanggal_selesai'] ?? ''))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- BottomNavBar (Mobile Only) -->
<nav class="lg:hidden fixed bottom-0 w-full z-50 flex justify-around items-center px-gutter py-sm bg-surface text-primary border-t border-outline-variant shadow-lg rounded-t-xl">
    <a class="flex flex-col items-center justify-center text-primary font-bold scale-90 transition-transform" href="<?= e(urlFor('/dashboard')) ?>">
        <span class="material-symbols-outlined filled-icon">home</span>
        <span class="text-label-md font-label-md mt-1">Home</span>
    </a>
    <?php if ($role === 'warga'): ?>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/laporan-saya')) ?>">
            <span class="material-symbols-outlined">description</span>
            <span class="text-label-md font-label-md mt-1">Saya</span>
        </a>
    <?php elseif ($role === 'admin'): ?>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/admin-users')) ?>">
            <span class="material-symbols-outlined">group</span>
            <span class="text-label-md font-label-md mt-1">User</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/admin-laporan')) ?>">
            <span class="material-symbols-outlined">assignment_turned_in</span>
            <span class="text-label-md font-label-md mt-1">Verifikasi</span>
        </a>
    <?php elseif ($role === 'petugas'): ?>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/petugas-tugas')) ?>">
            <span class="material-symbols-outlined">build</span>
            <span class="text-label-md font-label-md mt-1">Tugas</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/petugas-riwayat')) ?>">
            <span class="material-symbols-outlined">history</span>
            <span class="text-label-md font-label-md mt-1">Riwayat</span>
        </a>
    <?php elseif ($role === 'rt'): ?>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/rt-darurat')) ?>">
            <span class="material-symbols-outlined">emergency</span>
            <span class="text-label-md font-label-md mt-1">Darurat</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/rt-monitoring')) ?>">
            <span class="material-symbols-outlined">person_search</span>
            <span class="text-label-md font-label-md mt-1">Monitor</span>
        </a>
    <?php endif; ?>
    <a class="flex flex-col items-center justify-center text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded" href="<?= e(urlFor('/profil')) ?>">
        <span class="material-symbols-outlined">account_circle</span>
        <span class="text-label-md font-label-md mt-1">Profil</span>
    </a>
</nav>

<?php if ($role === 'warga'): ?>
<div id="dashboard-photo-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-[#BDBDBD] px-4 py-3">
            <h3 class="text-title-lg font-title-lg text-on-surface">Bukti Laporan</h3>
            <button class="text-on-surface-variant hover:text-on-surface" type="button" data-dashboard-photo-close aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="dashboard-photo-content" class="grid max-h-[75vh] gap-4 overflow-y-auto p-4"></div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('dashboard-photo-modal');
    var content = document.getElementById('dashboard-photo-content');
    if (!modal || !content) return;

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('[data-dashboard-photos]').forEach(function (button) {
        button.addEventListener('click', function () {
            var photos = [];
            try {
                photos = JSON.parse(button.getAttribute('data-dashboard-photos') || '[]');
            } catch (error) {
                photos = [];
            }

            content.innerHTML = '';
            if (photos.length === 0) {
                content.innerHTML = '<div class="rounded-lg border border-dashed border-[#BDBDBD] px-4 py-8 text-center text-on-surface-variant">Belum ada bukti foto untuk laporan ini.</div>';
            } else {
                photos.forEach(function (photo) {
                    var item = document.createElement('figure');
                    item.className = 'overflow-hidden rounded-lg border border-[#BDBDBD] bg-white';
                    var image = document.createElement('img');
                    image.className = 'max-h-[460px] w-full object-contain bg-surface';
                    image.src = photo.url || '';
                    image.alt = 'Bukti laporan';
                    var caption = document.createElement('figcaption');
                    caption.className = 'bg-surface px-3 py-2 text-xs text-on-surface-variant';
                    var type = document.createElement('span');
                    type.className = 'font-semibold capitalize';
                    type.textContent = photo.type || 'foto';
                    caption.appendChild(type);
                    caption.appendChild(document.createTextNode(' | ' + (photo.date || '-')));
                    item.appendChild(image);
                    item.appendChild(caption);
                    content.appendChild(item);
                });
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-dashboard-photo-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>
<?php endif; ?>

<?php if ($role === 'warga'): ?>
    <?php renderCreateReportModal($categories ?? [], $reportAction ?? urlFor('/laporan'), $reportCsrf ?? e(csrfToken())); ?>
    <script src="<?= e(urlFor('/pages/laporan.js')) ?>"></script>
    <?php renderCreateReportModalScript(); ?>
<?php endif; ?>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
