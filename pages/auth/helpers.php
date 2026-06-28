<?php
declare(strict_types=1);

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        throw new RuntimeException('Token form tidak valid. Silakan refresh halaman.');
    }
}

function webSessionIdleSeconds(): int
{
    return webSessionIdleMinutes() * 60;
}

function webSessionIdleMinutes(): int
{
    return max(1, (int)($_ENV['WEB_SESSION_IDLE_MINUTES'] ?? 30));
}

function webSessionExpiresAt(): string
{
    return date('Y-m-d H:i:s', time() + webSessionIdleSeconds());
}

function clearCurrentWebSession(string $message): void
{
    unset($_SESSION['auth_user']);
    $_SESSION['session_error'] = $message;
    redirectTo('/login');
}

function renderIdleLogoutScript(): void
{
    $timeoutMs = webSessionIdleSeconds() * 1000;
    $logoutUrl = e(urlFor('/logout'));
    $csrf = e(csrfToken());
    ?>
    <script>
    (function () {
        var timeoutMs = <?= (int)$timeoutMs ?>;
        var logoutUrl = '<?= $logoutUrl ?>';
        var pingUrl = '<?= e(urlFor('/session-ping')) ?>';
        var csrfToken = '<?= $csrf ?>';
        var timer = null;
        var loggedOut = false;
        var lastPingAt = Date.now();

        function logoutBecauseIdle() {
            if (loggedOut) {
                return;
            }
            loggedOut = true;

            var form = document.createElement('form');
            form.method = 'post';
            form.action = logoutUrl;
            form.style.display = 'none';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = csrfToken;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        function pingSession() {
            var now = Date.now();
            var pingInterval = Math.min(Math.max(timeoutMs / 3, 30000), 120000);
            if (now - lastPingAt < pingInterval) {
                return;
            }

            lastPingAt = now;
            fetch(pingUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (response.status === 401) {
                    window.location.href = logoutUrl;
                }
            }).catch(function () {});
        }

        function resetTimer() {
            if (loggedOut) {
                return;
            }
            clearTimeout(timer);
            pingSession();
            timer = setTimeout(logoutBecauseIdle, timeoutMs);
        }

        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (eventName) {
            window.addEventListener(eventName, resetTimer, { passive: true });
        });

        resetTimer();
    })();
    </script>
    <?php
}

function renderAppSidebar(array $user, string $active): void
{
    $role = (string)($user['role'] ?? 'warga');
    $roleLabel = strtoupper($role);
    $name = (string)($user['nama_lengkap'] ?? 'User');
    $initial = strtoupper(substr($name, 0, 1));
    $items = [
        'warga' => [
            ['key' => 'dashboard', 'url' => '/dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['key' => 'laporan-saya', 'url' => '/laporan-saya', 'icon' => 'description', 'label' => 'Laporan Saya'],
        ],
        'admin' => [
            ['key' => 'dashboard', 'url' => '/dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['key' => 'admin-users', 'url' => '/admin-users', 'icon' => 'group', 'label' => 'Kelola User'],
            ['key' => 'admin-laporan', 'url' => '/admin-laporan', 'icon' => 'assignment_turned_in', 'label' => 'Verifikasi Laporan'],
        ],
        'petugas' => [
            ['key' => 'dashboard', 'url' => '/dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['key' => 'petugas-tugas', 'url' => '/petugas-tugas', 'icon' => 'build', 'label' => 'Tugas Aktif'],
            ['key' => 'petugas-riwayat', 'url' => '/petugas-riwayat', 'icon' => 'history', 'label' => 'Riwayat Tugas'],
        ],
        'rt' => [
            ['key' => 'dashboard', 'url' => '/dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['key' => 'rt-darurat', 'url' => '/rt-darurat', 'icon' => 'emergency', 'label' => 'Laporan Darurat'],
            ['key' => 'rt-monitoring', 'url' => '/rt-monitoring', 'icon' => 'person_search', 'label' => 'Monitoring Petugas'],
        ],
    ];

    $activeClass = 'bg-[#325e9c] text-[#c4d8ff] rounded-lg mx-2 px-4 py-3 flex items-center gap-3 transition-transform translate-x-1';
    $idleClass = 'text-[#a8c8ff] hover:text-white hover:bg-white/10 mx-2 px-4 py-3 rounded-lg flex items-center gap-3 transition-colors';
    ?>
    <aside class="hidden lg:flex flex-col h-screen fixed left-0 top-0 py-6 w-[280px] bg-[#003D7A] text-white z-40">
        <div class="px-6 mb-8 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-xl uppercase shrink-0">
                <?= e($initial) ?>
            </div>
            <div>
                <div class="text-xl font-bold truncate max-w-[180px]" title="<?= e($name) ?>"><?= e($name) ?></div>
                <div class="text-xs font-bold text-[#a8c8ff] uppercase tracking-wide"><?= e($roleLabel) ?></div>
            </div>
        </div>
        <nav class="flex-1 flex flex-col gap-2 px-2">
            <?php foreach (($items[$role] ?? $items['warga']) as $item): ?>
                <?php $isActive = $active === $item['key']; ?>
                <a class="<?= $isActive ? $activeClass : $idleClass ?>" href="<?= e(urlFor($item['url'])) ?>">
                    <span class="material-symbols-outlined <?= $isActive ? 'filled-icon' : '' ?>"><?= e($item['icon']) ?></span>
                    <span class="text-xs font-bold tracking-wide"><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="px-2 mt-auto">
            <?php $isProfileActive = $active === 'profile'; ?>
            <a class="<?= $isProfileActive ? $activeClass . ' mb-2' : 'text-[#a8c8ff] hover:text-white hover:bg-white/10 mx-2 mb-2 px-4 py-3 rounded-lg flex items-center gap-3 transition-colors' ?>" href="<?= e(urlFor('/profil')) ?>">
                <span class="material-symbols-outlined <?= $isProfileActive ? 'filled-icon' : '' ?>">account_circle</span>
                <span class="text-xs font-bold tracking-wide">Profil</span>
            </a>
            <form method="post" action="<?= e(urlFor('/logout')) ?>" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button class="w-full text-left text-[#a8c8ff] hover:text-white hover:bg-white/10 mx-2 px-4 py-3 rounded-lg flex items-center gap-3 transition-colors" type="submit">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="text-xs font-bold tracking-wide">Keluar</span>
                </button>
            </form>
        </div>
    </aside>
    <?php
}

function renderCreateReportModal(array $categories, string $action, string $csrf, array $errors = [], string $success = '', bool $open = false): void
{
    ?>
    <div id="createReportModal" class="<?= $open ? 'flex' : 'hidden' ?> fixed inset-0 z-[80] items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[#BDBDBD] bg-white px-5 py-4">
                <div>
                    <h2 class="text-2xl font-semibold text-[#1b1c1c]">Buat Laporan Kerusakan</h2>
                    <p class="text-sm text-[#424654]">Isi detail laporan, lokasi, prioritas, dan foto bukti awal.</p>
                </div>
                <button id="closeReportModal" class="rounded p-2 text-[#424654] hover:bg-[#f0eded] hover:text-[#1b1c1c]" type="button" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="px-5 pt-4">
                <?php if ($errors !== []): ?>
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= e($success) ?></div>
                <?php endif; ?>
            </div>

            <form class="grid gap-4 px-5 pb-5" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" id="latitude" name="latitude" value="<?= old('latitude') ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?= old('longitude') ?>">
                <input type="hidden" id="akurasi_gps_meter" name="akurasi_gps_meter" value="<?= old('akurasi_gps_meter') ?>">

                <div class="grid gap-4 md:grid-cols-12">
                    <label class="grid gap-1 md:col-span-7">
                        <span class="text-sm font-semibold">Judul Laporan</span>
                        <input class="rounded-lg border-[#BDBDBD]" name="judul" value="<?= old('judul') ?>" maxlength="200" placeholder="Contoh: Lampu jalan mati di depan pos ronda" required>
                    </label>
                    <label class="grid gap-1 md:col-span-5">
                        <span class="text-sm font-semibold">Kategori</span>
                        <select class="rounded-lg border-[#BDBDBD]" name="kategori_id" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e((string)$category['id']) ?>" <?= selected('kategori_id', (string)$category['id']) ?>>
                                    <?= e((string)$category['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="grid gap-1 md:col-span-12">
                        <span class="text-sm font-semibold">Deskripsi</span>
                        <textarea class="rounded-lg border-[#BDBDBD]" name="deskripsi" rows="4" placeholder="Jelaskan kerusakan dan kondisi sekitar lokasi." required><?= old('deskripsi') ?></textarea>
                    </label>
                    <label class="grid gap-1 md:col-span-12">
                        <span class="text-sm font-semibold">Lokasi Detail</span>
                        <input class="rounded-lg border-[#BDBDBD]" name="lokasi_detail" value="<?= old('lokasi_detail') ?>" maxlength="255" placeholder="Contoh: Jl. Melati RT 01 dekat mushola" required>
                    </label>
                    <label class="grid gap-1 md:col-span-8">
                        <span class="text-sm font-semibold">Link Google Maps</span>
                        <input class="rounded-lg border-[#BDBDBD]" name="maps_url" value="<?= old('maps_url') ?>" placeholder="Opsional, tempel link maps jika ada">
                    </label>
                    <label class="grid gap-1 md:col-span-4">
                        <span class="text-sm font-semibold">Foto Bukti</span>
                        <input class="rounded-lg border border-[#BDBDBD] text-sm" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    </label>
                </div>

                <div class="rounded-lg border border-[#BDBDBD] bg-[#f5f3f3] p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-semibold">Lokasi GPS realtime</div>
                            <div class="text-sm text-[#424654]" id="gpsStatus">Belum mengambil lokasi.</div>
                        </div>
                        <button class="rounded-lg border border-[#00409c] px-4 py-2 font-semibold text-[#00409c] hover:bg-[#00409c]/10" id="gpsButton" type="button">
                            Ambil Lokasi Saya
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-[#BDBDBD] pt-4">
                    <button class="rounded-lg bg-[#f0eded] px-4 py-2 font-semibold text-[#424654] hover:bg-[#e4e2e1]" id="cancelReportModal" type="button">Batal</button>
                    <button class="rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="submit">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function renderCreateReportModalScript(bool $open = false): void
{
    ?>
    <script>
    (function () {
        var modal = document.getElementById('createReportModal');
        var openButtons = document.querySelectorAll('[data-open-report-modal]');
        var closeButton = document.getElementById('closeReportModal');
        var cancelButton = document.getElementById('cancelReportModal');

        if (!modal) {
            return;
        }

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', openModal);
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        var form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function () {
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Mengirim...';
                }
            });
        }

        <?php if ($open): ?>
        openModal();
        <?php endif; ?>
    })();
    </script>
    <?php
}

function redirectTo(string $path): void
{
    header('Location: ' . urlFor($path), true, 302);
    exit;
}

function urlFor(string $path): string
{
    $pageAliases = [
        '/login' => '/handlers/features/login.php',
        '/register' => '/handlers/features/register.php',
        '/dashboard' => '/handlers/features/dashboard.php',
        '/logout' => '/handlers/features/logout.php',
        '/laporan' => '/handlers/features/laporan.php',
        '/laporan-saya' => '/handlers/users/laporan-saya.php',
        '/edit-laporan' => '/handlers/features/edit-laporan.php',
        '/hapus-laporan' => '/handlers/features/hapus-laporan.php',
        '/admin-users' => '/handlers/admin/admin-users.php',
        '/admin-laporan' => '/handlers/admin/admin-laporan.php',
        '/rt-darurat' => '/handlers/rt/rt-darurat.php',
        '/rt-monitoring' => '/handlers/rt/rt-monitoring.php',
        '/petugas-tugas' => '/handlers/petugas/petugas-tugas.php',
        '/petugas-riwayat' => '/handlers/petugas/petugas-riwayat.php',
        '/profil' => '/handlers/features/profil.php',
        '/laporan-pdf' => '/handlers/features/laporan-pdf.php',
        '/reset-password' => '/handlers/features/reset-password.php',
        '/session-ping' => '/handlers/features/session-ping.php',
    ];

    $path = $pageAliases[$path] ?? $path;
    $base = basePath();
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function basePath(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    
    // Jika diakses melalui script di dalam subfolder handlers,
    // kita bersihkan sub-path '/handlers' agar mendapatkan root base proyek.
    $handlersPos = strpos($base, '/handlers');
    if ($handlersPos !== false) {
        $base = substr($base, 0, $handlersPos);
    }
    
    return $base === '/' ? '' : $base;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function selected(string $field, string $value, string $default = ''): string
{
    $current = (string)($_POST[$field] ?? $default);
    return $current === $value ? 'selected' : '';
}

function old(string $field, string $default = ''): string
{
    return e((string)($_POST[$field] ?? $default));
}

function formatDashboardDate(string $value): string
{
    if ($value === '') {
        return '-';
    }

    try {
        return (new DateTime($value))->format('d M Y H:i');
    } catch (Throwable $e) {
        return $value;
    }
}

function nullableInput(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function isValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}
