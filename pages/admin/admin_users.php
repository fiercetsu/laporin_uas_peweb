<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola User - Admin</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body class="bg-[#f7f9fc] font-['Inter',sans-serif] text-[#181c20]">
<?php renderAppSidebar($admin, 'admin-users'); ?>
<main class="min-h-screen px-4 py-6 sm:px-6 lg:ml-[280px] lg:px-8">
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/dashboard')) ?>">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke dashboard
            </a>
            <h1 class="mt-2 text-2xl font-bold">Kelola User</h1>
            <p class="text-sm text-[#5d6673]">Aktifkan, pending, atau nonaktifkan akun warga dan petugas.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/admin-laporan')) ?>">
            <span class="material-symbols-outlined text-lg">fact_check</span>
            Verifikasi Laporan
        </a>
    </header>

    <?php if ($errors !== []): ?>
        <div class="mb-4 rounded-lg border border-[#f2b8b5] bg-[#ffdad6] p-4 text-[#93000a]"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="mb-4 rounded-lg border border-[#b8e6c9] bg-[#dcfce7] p-4 text-[#166534]"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-lg border border-[#d7dce2] bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-[#d7dce2] bg-[#f7f9fc] text-xs font-bold uppercase tracking-wide text-[#4b5563]">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf0f4]">
                <?php if ($users === []): ?>
                    <tr><td class="px-4 py-12 text-center text-[#5d6673]" colspan="7">Belum ada user.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $row): ?>
                    <?php
                    $statusClass = $row['status_akun'] === 'aktif'
                        ? 'bg-[#dcfce7] text-[#166534]'
                        : ($row['status_akun'] === 'pending' ? 'bg-[#fef3c7] text-[#92400e]' : 'bg-[#e5e7eb] text-[#374151]');
                    ?>
                    <tr class="hover:bg-[#f7f9fc]">
                        <td class="px-4 py-3">
                            <div class="font-semibold"><?= e((string)$row['nama_lengkap']) ?></div>
                            <div class="text-xs text-[#5d6673]"><?= e((string)($row['kode_user'] ?? '-')) ?></div>
                        </td>
                        <td class="px-4 py-3"><?= e((string)$row['nik']) ?></td>
                        <td class="px-4 py-3">
                            <div><?= e((string)($row['email'] ?? '-')) ?></div>
                            <div class="text-xs text-[#5d6673]"><?= e((string)($row['no_hp'] ?? '-')) ?></div>
                        </td>
                        <td class="px-4 py-3"><span class="rounded bg-[#eef5ff] px-2 py-1 text-xs font-semibold uppercase text-[#00409c]"><?= e((string)$row['role']) ?></span></td>
                        <td class="px-4 py-3">
                            <?= e((string)($row['alamat_lengkap'] ?? '-')) ?>
                            <?php if (!empty($row['no_rt']) || !empty($row['no_rw'])): ?>
                                <div class="text-xs text-[#5d6673]">RT <?= e((string)($row['no_rt'] ?? '-')) ?> / RW <?= e((string)($row['no_rw'] ?? '-')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-semibold <?= $statusClass ?>"><?= e((string)$row['status_akun']) ?></span></td>
                        <td class="px-4 py-3">
                            <form class="flex justify-end gap-2" method="post" action="<?= e(urlFor('/admin-users')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="user_id" value="<?= e((string)$row['id']) ?>">
                                <select class="w-32 rounded-lg border-[#c8ced8] text-sm" name="status_akun">
                                    <option value="aktif" <?= $row['status_akun'] === 'aktif' ? 'selected' : '' ?>>aktif</option>
                                    <option value="pending" <?= $row['status_akun'] === 'pending' ? 'selected' : '' ?>>pending</option>
                                    <option value="nonaktif" <?= $row['status_akun'] === 'nonaktif' ? 'selected' : '' ?>>nonaktif</option>
                                </select>
                                <button class="rounded-lg bg-[#00409c] px-3 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">Simpan</button>
                            </form>
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
