<?php
declare(strict_types=1);

// ── Auth Guard ──────────────────────────────────────────────────────
if (empty($_SESSION['auth_user'])) {
    redirectTo('/login');
}

// ── Handle POST (PRG) ───────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verifyCsrfToken();
        [$errors, $success] = processProfileForm();
    } catch (Throwable $e) {
        $errors = ['Gagal memproses: ' . $e->getMessage()];
        $success = '';
    }
    $_SESSION['flash'] = ['errors' => $errors, 'success' => $success];
    redirectTo('/profil');
}

// ── Read flash ──────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
$errors = $flash['errors'] ?? [];
$success = $flash['success'] ?? '';

// ── Ambil data untuk tampilan ───────────────────────────────────────
$user = $_SESSION['auth_user'];
$profile = getProfileData((int)$user['id']);
$csrf = e(csrfToken());

$role = (string)($user['role'] ?? 'warga');
$profile = $profile ?: $user;
$value = static fn(string $key): string => e((string)($_POST[$key] ?? $profile[$key] ?? ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil - Laporin RT</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="flex min-h-screen bg-[#f5f5f5] text-[#1b1c1c]">
<header class="lg:hidden bg-[#00409c] text-white flex justify-between items-center w-full px-4 h-16 shadow-sm fixed top-0 left-0 z-50">
    <div class="text-xl font-bold">Laporin RT</div>
    <form method="post" action="<?= e(urlFor('/logout')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <button class="p-2 rounded-full" type="submit" aria-label="Keluar"><span class="material-symbols-outlined">logout</span></button>
    </form>
</header>

<?php renderAppSidebar($user, 'profile'); ?>

<main class="min-h-screen w-full flex-1 px-4 py-20 sm:px-6 lg:ml-[280px] lg:px-8 lg:py-6">
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-[#00409c]" href="<?= e(urlFor('/dashboard')) ?>">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke dashboard
            </a>
            <h1 class="mt-2 text-2xl font-bold">Profil Saya</h1>
            <p class="text-sm text-[#5d6673]">Edit data akun dan reset password untuk role <?= e($role) ?>.</p>
        </div>
    </header>

    <?php if ($errors !== []): ?>
        <div class="mb-4 rounded-lg border border-[#f2b8b5] bg-[#ffdad6] p-4 text-[#93000a]"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="mb-4 rounded-lg border border-[#b8e6c9] bg-[#dcfce7] p-4 text-[#166534]"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="mb-4 flex flex-wrap gap-2 border-b border-[#d7dce2]" data-profile-tabs>
        <button class="border-b-2 border-[#00409c] px-4 py-3 font-semibold text-[#00409c]" type="button" data-tab-target="data">Data Profil</button>
        <button class="border-b-2 border-transparent px-4 py-3 font-semibold text-[#5d6673]" type="button" data-tab-target="password">Reset Password</button>
    </div>

    <section data-tab-panel="data">
        <form class="rounded-lg border border-[#d7dce2] bg-white" method="post" action="<?= e(urlFor('/profil')) ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="profile">
            <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-12">
                <label class="lg:col-span-4">
                    <span class="mb-1 block text-sm font-semibold">NIK</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="nik" maxlength="16" value="<?= $value('nik') ?>" required>
                </label>
                <label class="lg:col-span-8">
                    <span class="mb-1 block text-sm font-semibold">Nama Lengkap</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="nama_lengkap" maxlength="150" value="<?= $value('nama_lengkap') ?>" required>
                </label>
                <label class="lg:col-span-6">
                    <span class="mb-1 block text-sm font-semibold">Email</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="email" type="email" value="<?= $value('email') ?>">
                </label>
                <label class="lg:col-span-6">
                    <span class="mb-1 block text-sm font-semibold">Nomor HP</span>
                    <input class="w-full rounded-lg border-[#c8ced8]" name="no_hp" maxlength="15" value="<?= $value('no_hp') ?>">
                </label>

                <?php if ($role === 'warga'): ?>
                    <label class="lg:col-span-4">
                        <span class="mb-1 block text-sm font-semibold">Nomor KK</span>
                        <input class="w-full rounded-lg border-[#c8ced8]" name="no_kk" maxlength="16" value="<?= $value('no_kk') ?>">
                    </label>
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-sm font-semibold">RT</span>
                        <input class="w-full rounded-lg border-[#c8ced8]" name="no_rt" value="<?= $value('no_rt') ?>">
                    </label>
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-sm font-semibold">RW</span>
                        <input class="w-full rounded-lg border-[#c8ced8]" name="no_rw" value="<?= $value('no_rw') ?>">
                    </label>
                    <label class="lg:col-span-4">
                        <span class="mb-1 block text-sm font-semibold">Status Tinggal</span>
                        <select class="w-full rounded-lg border-[#c8ced8]" name="status_tinggal">
                            <?php foreach (['tetap' => 'Tetap', 'kontrak' => 'Kontrak', 'kost' => 'Kost', 'numpang' => 'Numpang'] as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= ((string)($profile['status_tinggal'] ?? 'tetap') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="lg:col-span-12">
                        <span class="mb-1 block text-sm font-semibold">Alamat Lengkap</span>
                        <textarea class="w-full rounded-lg border-[#c8ced8]" name="alamat_lengkap" rows="3"><?= $value('alamat_lengkap') ?></textarea>
                    </label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Kelurahan</span><input class="w-full rounded-lg border-[#c8ced8]" name="kelurahan" value="<?= $value('kelurahan') ?>"></label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Kecamatan</span><input class="w-full rounded-lg border-[#c8ced8]" name="kecamatan" value="<?= $value('kecamatan') ?>"></label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Kota/Kabupaten</span><input class="w-full rounded-lg border-[#c8ced8]" name="kota_kabupaten" value="<?= $value('kota_kabupaten') ?>"></label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Tempat Lahir</span><input class="w-full rounded-lg border-[#c8ced8]" name="tempat_lahir" value="<?= $value('tempat_lahir') ?>"></label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Tanggal Lahir</span><input class="w-full rounded-lg border-[#c8ced8]" name="tanggal_lahir" type="date" value="<?= $value('tanggal_lahir') ?>"></label>
                    <label class="lg:col-span-4">
                        <span class="mb-1 block text-sm font-semibold">Jenis Kelamin</span>
                        <select class="w-full rounded-lg border-[#c8ced8]" name="jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="L" <?= ((string)($profile['jenis_kelamin'] ?? '') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= ((string)($profile['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Agama</span><input class="w-full rounded-lg border-[#c8ced8]" name="agama" value="<?= $value('agama') ?>"></label>
                    <label class="lg:col-span-4">
                        <span class="mb-1 block text-sm font-semibold">Status Perkawinan</span>
                        <select class="w-full rounded-lg border-[#c8ced8]" name="status_perkawinan">
                            <option value="">Pilih</option>
                            <?php foreach (['belum_kawin' => 'Belum Kawin', 'kawin' => 'Kawin', 'cerai_hidup' => 'Cerai Hidup', 'cerai_mati' => 'Cerai Mati'] as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= ((string)($profile['status_perkawinan'] ?? '') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Pekerjaan</span><input class="w-full rounded-lg border-[#c8ced8]" name="pekerjaan" value="<?= $value('pekerjaan') ?>"></label>
                    <label class="lg:col-span-4"><span class="mb-1 block text-sm font-semibold">Tanggal Pindah Masuk</span><input class="w-full rounded-lg border-[#c8ced8]" name="tanggal_pindah_masuk" type="date" value="<?= $value('tanggal_pindah_masuk') ?>"></label>
                <?php endif; ?>
            </div>
            <div class="flex justify-end border-t border-[#d7dce2] px-4 py-4 sm:px-6">
                <button class="rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">Simpan Profil</button>
            </div>
        </form>
    </section>

    <section class="hidden" data-tab-panel="password">
        <form class="max-w-xl rounded-lg border border-[#d7dce2] bg-white p-4 sm:p-6" method="post" action="<?= e(urlFor('/profil')) ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="password">
            <label class="mb-4 block"><span class="mb-1 block text-sm font-semibold">Password Lama</span><input class="w-full rounded-lg border-[#c8ced8]" name="password_lama" type="password" required></label>
            <label class="mb-4 block"><span class="mb-1 block text-sm font-semibold">Password Baru</span><input class="w-full rounded-lg border-[#c8ced8]" name="password_baru" type="password" minlength="8" required></label>
            <label class="mb-4 block"><span class="mb-1 block text-sm font-semibold">Konfirmasi Password Baru</span><input class="w-full rounded-lg border-[#c8ced8]" name="password_konfirmasi" type="password" minlength="8" required></label>
            <button class="rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">Reset Password</button>
        </form>
    </section>
</main>
<script src="<?= e(urlFor('/pages/profile.js')) ?>"></script>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
