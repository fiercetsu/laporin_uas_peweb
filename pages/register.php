<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Masyarakat - Sistem Layanan Publik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #00409c;
            --primary-hover: #0056cc;
            --surface: #ffffff;
            --surface-soft: #f0eded;
            --outline: #c3c6d6;
            --text: #1b1c1c;
            --muted: #424654;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Source Sans 3", Arial, sans-serif;
            color: var(--text);
            background: var(--primary);
            position: relative;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 380px;
            height: 380px;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(48px);
            opacity: .22;
            z-index: 0;
        }

        body::before {
            top: -120px;
            left: -120px;
            background: #0075d6;
        }

        body::after {
            right: -160px;
            bottom: -160px;
            width: 560px;
            height: 560px;
            background: #325e9c;
        }

        .register-shell {
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .register-card {
            max-width: 720px;
            border: 1px solid var(--outline);
            border-radius: .5rem;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .15);
        }

        .register-icon {
            width: 64px;
            height: 64px;
            color: var(--primary);
            background: rgba(0, 86, 204, .1);
        }

        .form-label {
            font-size: .82rem;
            font-weight: 700;
            color: var(--text);
        }

        .form-control,
        .form-select {
            border-color: var(--outline);
            border-radius: .25rem;
            padding-top: .68rem;
            padding-bottom: .68rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 .15rem rgba(0, 64, 156, .18);
        }

        .section-box {
            border: 1px solid #e4e2e1;
            border-radius: .5rem;
            padding: 1rem;
        }

        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: .85rem;
        }

        .btn-primary {
            --bs-btn-bg: var(--primary);
            --bs-btn-border-color: var(--primary);
            --bs-btn-hover-bg: var(--primary-hover);
            --bs-btn-hover-border-color: var(--primary-hover);
            --bs-btn-active-bg: var(--primary-hover);
            --bs-btn-active-border-color: var(--primary-hover);
        }

        .footer-strip {
            background: var(--surface-soft);
            border-top: 1px solid #e4e2e1;
        }
    </style>
</head>
<body>
<main class="register-shell d-flex align-items-center justify-content-center p-3 p-md-4">
    <section class="register-card bg-white w-100">
        <header class="text-center px-4 pt-5 pb-4 border-bottom">
            <div class="register-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                <i class="bi bi-person-plus-fill fs-2"></i>
            </div>
            <h1 class="h3 fw-bold mb-1">Registrasi Akun</h1>
            <p class="mb-0 text-secondary">Bergabung dengan Sistem Layanan Publik untuk kemudahan akses layanan terpadu.</p>
        </header>

        <div class="p-4 p-md-5">
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-primary" role="alert"><?= e($success) ?></div>
            <?php endif; ?>

            <form id="registerForm" method="post" action="<?= e($action) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="section-box mb-3">
                    <div class="section-title">Data akun</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= $nama ?>" placeholder="Masukkan nama lengkap sesuai KTP" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="nik">NIK</label>
                            <input class="form-control" id="nik" name="nik" value="<?= $nik ?>" inputmode="numeric" maxlength="16" placeholder="16 digit NIK" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="no_kk">No KK</label>
                            <input class="form-control" id="no_kk" name="no_kk" value="<?= $noKk ?>" inputmode="numeric" maxlength="16" placeholder="16 digit No KK">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input class="form-control" id="email" name="email" type="email" value="<?= $email ?>" placeholder="contoh@email.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="no_hp">Nomor HP</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input class="form-control" id="no_hp" name="no_hp" value="<?= $noHp ?>" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-box mb-3">
                    <div class="section-title">Alamat warga</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="no_rt">No RT</label>
                            <input class="form-control" id="no_rt" name="no_rt" value="<?= $noRt ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="no_rw">No RW</label>
                            <input class="form-control" id="no_rw" name="no_rw" value="<?= $noRw ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="alamat_lengkap">Alamat Domisili</label>
                            <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3" placeholder="Masukkan alamat lengkap" required><?= $alamat ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kelurahan">Kelurahan</label>
                            <input class="form-control" id="kelurahan" name="kelurahan" value="<?= $kelurahan ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kecamatan">Kecamatan</label>
                            <input class="form-control" id="kecamatan" name="kecamatan" value="<?= $kecamatan ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="kota_kabupaten">Kota/Kabupaten</label>
                            <input class="form-control" id="kota_kabupaten" name="kota_kabupaten" value="<?= $kotaKabupaten ?>">
                        </div>
                    </div>
                </div>

                <div class="section-box mb-3">
                    <div class="section-title">Data pribadi tambahan</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
                            <input class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= $tempatLahir ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                            <input class="form-control" id="tanggal_lahir" name="tanggal_lahir" type="date" value="<?= $tanggalLahir ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                                <option value="">Pilih</option>
                                <option value="L" <?= selected('jenis_kelamin', 'L') ?>>Laki-laki</option>
                                <option value="P" <?= selected('jenis_kelamin', 'P') ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="agama">Agama</label>
                            <input class="form-control" id="agama" name="agama" value="<?= $agama ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status_perkawinan">Status Perkawinan</label>
                            <select class="form-select" id="status_perkawinan" name="status_perkawinan">
                                <option value="">Pilih</option>
                                <option value="belum_kawin" <?= selected('status_perkawinan', 'belum_kawin') ?>>Belum kawin</option>
                                <option value="kawin" <?= selected('status_perkawinan', 'kawin') ?>>Kawin</option>
                                <option value="cerai_hidup" <?= selected('status_perkawinan', 'cerai_hidup') ?>>Cerai hidup</option>
                                <option value="cerai_mati" <?= selected('status_perkawinan', 'cerai_mati') ?>>Cerai mati</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pekerjaan">Pekerjaan</label>
                            <input class="form-control" id="pekerjaan" name="pekerjaan" value="<?= $pekerjaan ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status_tinggal">Status Tinggal</label>
                            <select class="form-select" id="status_tinggal" name="status_tinggal">
                                <option value="tetap" <?= selected('status_tinggal', 'tetap', 'tetap') ?>>Tetap</option>
                                <option value="kontrak" <?= selected('status_tinggal', 'kontrak', 'tetap') ?>>Kontrak</option>
                                <option value="kost" <?= selected('status_tinggal', 'kost', 'tetap') ?>>Kost</option>
                                <option value="numpang" <?= selected('status_tinggal', 'numpang', 'tetap') ?>>Numpang</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tanggal_pindah_masuk">Tanggal Pindah Masuk</label>
                            <input class="form-control" id="tanggal_pindah_masuk" name="tanggal_pindah_masuk" type="date" value="<?= $tanggalPindahMasuk ?>">
                        </div>
                    </div>
                </div>

                <div class="section-box mb-3">
                    <div class="section-title">Keamanan akun</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input class="form-control" id="password" name="password" type="password" placeholder="Minimal 8 karakter" required>
                            </div>
                            <div class="form-text">Gunakan minimal 8 karakter, huruf kapital, dan angka.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input class="form-control" id="password_confirm" name="password_confirm" type="password" placeholder="Ulangi password" required>
                            </div>
                            <div class="invalid-feedback d-block" id="passwordMatchMessage"></div>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" id="terms" name="terms" value="1" type="checkbox" required>
                    <label class="form-check-label text-secondary" for="terms">
                        Saya menyetujui syarat dan ketentuan yang berlaku.
                    </label>
                </div>

                <button class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" type="submit">
                    <span>Daftar Sekarang</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>

        <footer class="footer-strip text-center px-4 py-3">
            <span class="text-secondary">Sudah memiliki akun?</span>
            <a class="fw-bold text-decoration-none" href="<?= e(urlFor('/login')) ?>">Masuk di sini</a>
        </footer>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(urlFor('/pages/register.js')) ?>"></script>
</body>
</html>
