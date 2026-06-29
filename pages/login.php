<?php
declare(strict_types=1);

// ── Handle POST ─────────────────────────────────────────────────────
$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verifyCsrfToken();
        unset($_SESSION['session_error']);
        [$errors, $success] = processLoginForm();
    } catch (Throwable $e) {
        $errors = ['Gagal memproses login: ' . $e->getMessage()];
    }
}

if (!empty($_SESSION['session_error'])) {
    unset($_SESSION['session_error']);
}

// ── Setup variabel untuk tampilan ───────────────────────────────────
$action = urlFor('/login');
$csrf = e(csrfToken());
$email = e((string)($_POST['email'] ?? ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Layanan Publik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: #00409c;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            max-width: 460px;
            width: 100%;
            border: 1px solid #c3c6d6;
            border-radius: .5rem;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .15);
        }
        .login-icon {
            width: 64px;
            height: 64px;
            color: #00409c;
            background: rgba(0, 86, 204, .1);
        }
        .btn-primary {
            --bs-btn-bg: #00409c;
            --bs-btn-border-color: #00409c;
            --bs-btn-hover-bg: #0056cc;
            --bs-btn-hover-border-color: #0056cc;
        }
    </style>
</head>
<body>
<main class="login-card bg-white">
    <header class="text-center px-4 pt-5 pb-4 border-bottom">
        <div class="login-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
            <i class="bi bi-box-arrow-in-right fs-2"></i>
        </div>
        <h1 class="h3 fw-bold mb-1">Login</h1>
        <p class="mb-0 text-secondary">Masuk memakai email dan password akun kamu.</p>
    </header>
    <div class="p-4">
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="post" action="<?= e($action) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="mb-3">
                <label class="form-label fw-bold" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= $email ?>" placeholder="Masukkan email terdaftar" required>
                <div class="invalid-feedback">Email wajib diisi dengan format yang benar.</div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold mb-0" for="password">Password</label>
                    <a class="text-decoration-none text-small small" href="<?= e(urlFor('/reset-password')) ?>">Lupa Password?</a>
                </div>
                <div class="input-group">
                    <input class="form-control" id="password" name="password" type="password" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" id="togglePassword" type="button" aria-label="Tampilkan password">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">Password wajib diisi.</div>
                </div>
            </div>
            <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">Masuk</button>
        </form>
    </div>
    <footer class="text-center px-4 py-3 bg-light border-top">
        <span class="text-secondary">Belum punya akun?</span>
        <a class="fw-bold text-decoration-none" href="<?= e(urlFor('/register')) ?>">Daftar di sini</a>
    </footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(urlFor('/pages/login.js')) ?>"></script>
</body>
</html>
