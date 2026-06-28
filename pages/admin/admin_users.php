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
            <p class="text-sm text-[#5d6673]">Tambah, hapus, aktifkan, pending, atau nonaktifkan akun pengguna.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="button" data-open-user-modal>
                <span class="material-symbols-outlined text-lg">person_add</span>
                Tambah User
            </button>
            <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" href="<?= e(urlFor('/admin-laporan')) ?>">
                <span class="material-symbols-outlined text-lg">fact_check</span>
                Verifikasi Laporan
            </a>
        </div>
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
                            <div class="flex justify-end gap-2">
                            <?php if ((int)$row['id'] === (int)$admin['id']): ?>
                                <span class="rounded-lg border border-[#c8ced8] px-3 py-2 text-sm font-semibold text-[#5d6673]">Akun sendiri</span>
                            <?php else: ?>
                                <form class="flex gap-2" method="post" action="<?= e(urlFor('/admin-users')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="user_id" value="<?= e((string)$row['id']) ?>">
                                    <select class="w-24 rounded-lg border-[#c8ced8] text-xs py-1 px-2" name="role" required>
                                        <option value="warga" <?= $row['role'] === 'warga' ? 'selected' : '' ?>>warga</option>
                                        <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                                        <option value="petugas" <?= $row['role'] === 'petugas' ? 'selected' : '' ?>>petugas</option>
                                        <option value="rt" <?= $row['role'] === 'rt' ? 'selected' : '' ?>>rt</option>
                                    </select>
                                    <select class="w-24 rounded-lg border-[#c8ced8] text-xs py-1 px-2" name="status_akun" required>
                                        <option value="aktif" <?= $row['status_akun'] === 'aktif' ? 'selected' : '' ?>>aktif</option>
                                        <option value="pending" <?= $row['status_akun'] === 'pending' ? 'selected' : '' ?>>pending</option>
                                        <option value="nonaktif" <?= $row['status_akun'] === 'nonaktif' ? 'selected' : '' ?>>nonaktif</option>
                                    </select>
                                    <button class="rounded-lg bg-[#00409c] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#0056cc]" type="submit">Simpan</button>
                                </form>
                                <form method="post" action="<?= e(urlFor('/admin-users')) ?>" onsubmit="return confirm('apakah user mau dihapus?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= e((string)$row['id']) ?>">
                                    <button class="rounded-lg border border-rose-500 px-3 py-2 font-semibold text-rose-600 hover:bg-rose-50" type="submit">Hapus</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div id="userModal" class="<?= !empty($openCreateModal) ? 'flex' : 'hidden' ?> fixed inset-0 z-[80] items-center justify-center bg-black/60 p-4">
    <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-xl">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[#d7dce2] bg-white px-5 py-4">
            <div>
                <h2 class="text-2xl font-bold">Tambah User</h2>
                <p class="text-sm text-[#5d6673]">Buat akun baru untuk warga, petugas, RT, atau admin.</p>
            </div>
            <button id="closeUserModal" class="rounded p-2 text-[#5d6673] hover:bg-[#eef2f7]" type="button" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="post" action="<?= e(urlFor('/admin-users')) ?>" class="grid gap-4 p-5 sm:grid-cols-12">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create_user">

            <label class="sm:col-span-6">
                <span class="mb-1 block text-sm font-semibold">Nama Lengkap</span>
                <input class="w-full rounded-lg border-[#c8ced8]" name="nama_lengkap" value="<?= e((string)($_POST['nama_lengkap'] ?? '')) ?>" required>
            </label>
            <label class="sm:col-span-6">
                <span class="mb-1 block text-sm font-semibold">NIK</span>
                <input class="w-full rounded-lg border-[#c8ced8]" name="nik" maxlength="16" value="<?= e((string)($_POST['nik'] ?? '')) ?>" required>
            </label>
            <label class="sm:col-span-6">
                <span class="mb-1 block text-sm font-semibold">Email</span>
                <input class="w-full rounded-lg border-[#c8ced8]" name="email" type="email" value="<?= e((string)($_POST['email'] ?? '')) ?>">
            </label>
            <label class="sm:col-span-6">
                <span class="mb-1 block text-sm font-semibold">Nomor HP</span>
                <input class="w-full rounded-lg border-[#c8ced8]" name="no_hp" value="<?= e((string)($_POST['no_hp'] ?? '')) ?>">
            </label>
            <label class="sm:col-span-4">
                <span class="mb-1 block text-sm font-semibold">Role</span>
                <?php $postedRole = (string)($_POST['role'] ?? 'warga'); ?>
                <select id="newUserRole" class="w-full rounded-lg border-[#c8ced8]" name="role">
                    <option value="warga" <?= $postedRole === 'warga' ? 'selected' : '' ?>>Warga</option>
                    <option value="petugas" <?= $postedRole === 'petugas' ? 'selected' : '' ?>>Petugas</option>
                    <option value="rt" <?= $postedRole === 'rt' ? 'selected' : '' ?>>RT</option>
                    <option value="admin" <?= $postedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </label>
            <label class="sm:col-span-4">
                <span class="mb-1 block text-sm font-semibold">Status</span>
                <?php $postedStatus = (string)($_POST['status_akun'] ?? 'aktif'); ?>
                <select class="w-full rounded-lg border-[#c8ced8]" name="status_akun">
                    <option value="aktif" <?= $postedStatus === 'aktif' ? 'selected' : '' ?>>aktif</option>
                    <option value="pending" <?= $postedStatus === 'pending' ? 'selected' : '' ?>>pending</option>
                    <option value="nonaktif" <?= $postedStatus === 'nonaktif' ? 'selected' : '' ?>>nonaktif</option>
                </select>
            </label>
            <label class="sm:col-span-4">
                <span class="mb-1 block text-sm font-semibold">Password</span>
                <input class="w-full rounded-lg border-[#c8ced8]" name="password" type="password" minlength="8" required>
            </label>

            <div id="wargaFields" class="grid gap-4 sm:col-span-12 sm:grid-cols-12">
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-semibold">RT</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="no_rt" value="<?= e((string)($_POST['no_rt'] ?? '')) ?>">
                </label>
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-semibold">RW</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="no_rw" value="<?= e((string)($_POST['no_rw'] ?? '')) ?>">
                </label>
                <label class="sm:col-span-8">
                    <span class="mb-1 block text-sm font-semibold">Alamat Lengkap</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="alamat_lengkap" value="<?= e((string)($_POST['alamat_lengkap'] ?? '')) ?>">
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-[#d7dce2] pt-4 sm:col-span-12">
                <button id="cancelUserModal" class="rounded-lg border border-[#c8ced8] bg-white px-4 py-2 font-semibold text-[#00409c] hover:bg-[#eef5ff]" type="button">Batal</button>
                <button class="rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('userModal');
    var openButton = document.querySelector('[data-open-user-modal]');
    var closeButton = document.getElementById('closeUserModal');
    var cancelButton = document.getElementById('cancelUserModal');
    var roleSelect = document.getElementById('newUserRole');
    var wargaFields = document.getElementById('wargaFields');

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function syncRoleFields() {
        wargaFields.classList.toggle('hidden', roleSelect.value !== 'warga');
    }

    if (openButton) openButton.addEventListener('click', openModal);
    if (closeButton) closeButton.addEventListener('click', closeModal);
    if (cancelButton) cancelButton.addEventListener('click', closeModal);
    if (roleSelect) roleSelect.addEventListener('change', syncRoleFields);
    if (modal) modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    syncRoleFields();
});
</script>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
