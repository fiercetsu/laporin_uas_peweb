<?php
declare(strict_types=1);

$openModal = $errors !== [] || $success !== '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Buat Laporan - Laporin RT</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Source Sans 3', sans-serif; background-color: #f5f5f5; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="flex min-h-screen text-[#1b1c1c] bg-[#f5f5f5]">
<header class="lg:hidden bg-[#00409c] text-white flex justify-between items-center w-full px-4 h-16 shadow-sm fixed top-0 left-0 z-50">
    <div class="text-xl font-bold">Laporin RT</div>
    <form method="post" action="<?= e(urlFor('/logout')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <button class="p-2 rounded-full" type="submit" aria-label="Keluar"><span class="material-symbols-outlined">logout</span></button>
    </form>
</header>

<?php renderAppSidebar($user, ''); ?>

<main class="flex-1 w-full lg:ml-[280px] pt-20 lg:pt-0 p-4 lg:p-6 lg:max-w-[1440px] mx-auto min-h-screen">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold">Buat Laporan</h1>
                <p class="text-[#424654] mt-1">Klik tombol untuk membuka dialog pengisian laporan.</p>
            </div>
        </div>

        <section class="bg-white border border-[#BDBDBD] rounded-lg shadow-sm p-6">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-semibold">Laporkan kerusakan lingkungan</h2>
                <p class="text-[#424654] mt-2">Form laporan sekarang dibuka sebagai dialog agar kamu tetap berada di halaman yang sama.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <button class="rounded-lg bg-[#00409c] px-4 py-2 font-semibold text-white hover:bg-[#0056cc]" type="button" data-open-report-modal>
                        Buat Laporan
                    </button>
                    <a class="rounded-lg border border-[#00409c] px-4 py-2 font-semibold text-[#00409c] hover:bg-[#00409c]/10" href="<?= e(urlFor('/laporan-saya')) ?>">Laporan Saya</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php renderCreateReportModal($categories, $action, $csrf, $errors, $success, $openModal); ?>
<script src="<?= e(urlFor('/pages/laporan.js')) ?>"></script>
<?php renderCreateReportModalScript($openModal); ?>
<?php renderIdleLogoutScript(); ?>
</body>
</html>
