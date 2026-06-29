<?php
declare(strict_types=1);

// ── Handle POST ─────────────────────────────────────────────────────
$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verifyCsrfToken();
        [$errors, $success] = processResetPasswordForm();
    } catch (Throwable $e) {
        $errors = ['Gagal memproses reset password: ' . $e->getMessage()];
    }
}

// ── Setup variabel untuk tampilan ───────────────────────────────────
$action = urlFor('/reset-password');
$csrf = e(csrfToken());
$email = e((string)($_POST['email'] ?? ''));
$nik = e((string)($_POST['nik'] ?? ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Sistem Layanan Publik</title>
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
        .reset-card {
            max-width: 480px;
            width: 100%;
            border: 1px solid #c3c6d6;
            border-radius: .5rem;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .15);
        }
        .reset-icon {
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
<main class="reset-card bg-white">
    <header class="text-center px-4 pt-5 pb-4 border-bottom">
        <div class="reset-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
            <i class="bi bi-shield-lock-fill fs-2"></i>
        </div>
        <h1 class="h3 fw-bold mb-1">Reset Password</h1>
        <p class="mb-0 text-secondary">Verifikasi email & NIK Anda untuk menyetel ulang password.</p>
    </header>
    <div class="p-4">
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form id="resetForm" method="post" action="<?= e($action) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="mb-3">
                <label class="form-label fw-bold" for="email">Email Terdaftar</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= $email ?>" placeholder="contoh@email.com" required>
                <div class="invalid-feedback">Masukkan email dengan benar.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="nik">NIK (16 Digit)</label>
                <input class="form-control" id="nik" name="nik" value="<?= $nik ?>" inputmode="numeric" maxlength="16" placeholder="Masukkan NIK 16 digit" required>
                <div class="invalid-feedback">Masukkan NIK yang sesuai.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="password">Password Baru</label>
                <input class="form-control" id="password" name="password" type="password" placeholder="Minimal 8 karakter" required>
                <div class="invalid-feedback">Password baru minimal 8 karakter.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="password_confirm">Konfirmasi Password Baru</label>
                <input class="form-control" id="password_confirm" name="password_confirm" type="password" placeholder="Ulangi password baru" required>
                <div class="invalid-feedback">Konfirmasi password tidak cocok.</div>
            </div>
            <button class="btn btn-primary w-100 py-2 fw-bold" type="submit">Setel Ulang Password</button>
        </form>
    </div>
    <footer class="text-center px-4 py-3 bg-light border-top">
        <span class="text-secondary">Kembali ke</span>
        <a class="fw-bold text-decoration-none" href="<?= e(urlFor('/login')) ?>">Halaman Login</a>
    </footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  "use strict";
  var form = document.getElementById("resetForm");
  if (!form) return;

  var nik = document.getElementById("nik");
  if (nik) {
    nik.addEventListener("input", function () {
      nik.value = nik.value.replace(/\D/g, "").slice(0, 16);
    });
  }

  form.addEventListener("submit", function (event) {
    var p = document.getElementById("password").value;
    var pc = document.getElementById("password_confirm").value;
    if (p !== pc) {
        document.getElementById("password_confirm").setCustomValidity("Password tidak cocok");
    } else {
        document.getElementById("password_confirm").setCustomValidity("");
    }

    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add("was-validated");
  });
})();
</script>
</body>
</html>
