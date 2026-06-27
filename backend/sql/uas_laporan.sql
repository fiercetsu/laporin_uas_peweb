-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 27, 2026 at 05:57 AM
-- Server version: 8.0.44
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uas_laporan`
--

-- --------------------------------------------------------

--
-- Table structure for table `foto_laporan`
--

CREATE TABLE `foto_laporan` (
  `id` int UNSIGNED NOT NULL,
  `laporan_id` int UNSIGNED NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_file` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path relatif dari storage root',
  `ukuran_file` int UNSIGNED DEFAULT NULL COMMENT 'Ukuran file dalam bytes',
  `tipe_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'image/jpeg | image/png | image/webp',
  `tipe_foto` enum('bukti_awal','proses','bukti_selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bukti_awal' COMMENT 'bukti_awal: dari warga | proses: saat pengerjaan | bukti_selesai: bukti perbaikan selesai dari petugas',
  `foto_latitude` decimal(10,8) DEFAULT NULL COMMENT 'GPS dari EXIF foto (jika ada)',
  `foto_longitude` decimal(11,8) DEFAULT NULL COMMENT 'GPS dari EXIF foto (jika ada)',
  `diunggah_oleh` int UNSIGNED NOT NULL COMMENT 'FK users: bisa warga, petugas, atau admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `histori_laporan`
--

CREATE TABLE `histori_laporan` (
  `id` int UNSIGNED NOT NULL,
  `laporan_id` int UNSIGNED NOT NULL,
  `diubah_oleh` int UNSIGNED NOT NULL,
  `status_lama` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_baru` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_laporan`
--

CREATE TABLE `kategori_laporan` (
  `id` int UNSIGNED NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `ikon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama class icon atau path aset ikon UI',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_laporan`
--

INSERT INTO `kategori_laporan` (`id`, `nama_kategori`, `deskripsi`, `ikon`, `is_active`, `created_at`) VALUES
(1, 'Kerusakan Jalan', 'Jalan berlubang, retak, atau rusak', 'icon-road', 1, '2026-06-27 04:55:04'),
(2, 'Lampu Penerangan', 'Lampu jalan mati atau bermasalah', 'icon-lamp', 1, '2026-06-27 04:55:04'),
(3, 'Saluran Air / Got', 'Got tersumbat, banjir, atau saluran rusak', 'icon-drain', 1, '2026-06-27 04:55:04'),
(4, 'Fasilitas Umum', 'Kerusakan pos ronda, papan pengumuman, dll', 'icon-building', 1, '2026-06-27 04:55:04'),
(5, 'Taman & Penghijauan', 'Pohon tumbang atau taman tidak terawat', 'icon-tree', 1, '2026-06-27 04:55:04'),
(6, 'Sampah', 'Penumpukan sampah atau TPS bermasalah', 'icon-trash', 1, '2026-06-27 04:55:04'),
(7, 'Keamanan Lingkungan', 'Pagar rusak, portal hilang, atau masalah keamanan', 'icon-security', 1, '2026-06-27 04:55:04'),
(8, 'Lainnya', 'Kategori lain yang belum tercakup', 'icon-other', 1, '2026-06-27 04:55:04');

-- --------------------------------------------------------

--
-- Table structure for table `konfigurasi`
--

CREATE TABLE `konfigurasi` (
  `id` int UNSIGNED NOT NULL,
  `kunci` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` text COLLATE utf8mb4_unicode_ci,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `konfigurasi`
--

INSERT INTO `konfigurasi` (`id`, `kunci`, `nilai`, `keterangan`, `updated_at`) VALUES
(1, 'nama_rt', '01', 'Nomor RT', '2026-06-27 04:55:05'),
(2, 'nama_rw', '05', 'Nomor RW', '2026-06-27 04:55:05'),
(3, 'nama_kelurahan', 'Nama Kelurahan', 'Nama kelurahan/desa', '2026-06-27 04:55:05'),
(4, 'nama_kecamatan', 'Nama Kecamatan', 'Nama kecamatan', '2026-06-27 04:55:05'),
(5, 'nama_kota', 'Nama Kota', 'Nama kota/kabupaten', '2026-06-27 04:55:05'),
(6, 'nama_ketua_rt', 'Nama Ketua RT', 'Nama ketua RT aktif', '2026-06-27 04:55:05'),
(7, 'kontak_rt', '08xxxxxxxxx', 'Nomor HP ketua RT', '2026-06-27 04:55:05'),
(8, 'logo_rt', NULL, 'Path logo RT', '2026-06-27 04:55:05'),
(9, 'max_foto_laporan', '5', 'Maksimal foto bukti awal per laporan dari warga', '2026-06-27 04:55:05'),
(10, 'max_foto_bukti_petugas', '5', 'Maksimal foto bukti selesai dari petugas', '2026-06-27 04:55:05'),
(11, 'max_ukuran_file_mb', '5', 'Batas ukuran per file upload (MB)', '2026-06-27 04:55:05'),
(12, 'counter_laporan', '0', 'Counter kode LPR — di-reset tiap bulan oleh app', '2026-06-27 04:55:05'),
(13, 'counter_petugas', '1', 'Counter kode PTG — tidak di-reset', '2026-06-27 04:55:05'),
(14, 'counter_admin', '1', 'Counter kode DRT — tidak di-reset', '2026-06-27 04:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_kerusakan`
--

CREATE TABLE `laporan_kerusakan` (
  `id` int UNSIGNED NOT NULL,
  `kode_laporan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: LPR-YYYYMM-XXXXX',
  `pelapor_id` int UNSIGNED NOT NULL COMMENT 'FK users (role: warga)',
  `kategori_id` int UNSIGNED NOT NULL,
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi_detail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Deskripsi lokasi teks dari warga (wajib)',
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Koordinat GPS dari modul GPS perangkat (opsional)',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Koordinat GPS dari modul GPS perangkat (opsional)',
  `akurasi_gps_meter` float DEFAULT NULL COMMENT 'Akurasi GPS dalam meter, dari device API',
  `maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Google Maps share URL jika warga share link manual',
  `tingkat_prioritas` enum('rendah','sedang','tinggi','darurat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sedang',
  `status` enum('menunggu_verifikasi','diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut','selesai','ditolak','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu_verifikasi',
  `diverifikasi_oleh` int UNSIGNED DEFAULT NULL COMMENT 'FK users: admin/petugas yang verifikasi',
  `petugas_id` int UNSIGNED DEFAULT NULL COMMENT 'FK users (role: petugas) yang ditugaskan',
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `catatan_petugas` text COLLATE utf8mb4_unicode_ci,
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_target_selesai` date DEFAULT NULL,
  `tanggal_mulai_kerjakan` timestamp NULL DEFAULT NULL,
  `tanggal_selesai` timestamp NULL DEFAULT NULL,
  `rating_warga` tinyint UNSIGNED DEFAULT NULL COMMENT 'Kepuasan warga 1–5 setelah laporan selesai',
  `ulasan_warga` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'Penerima notifikasi',
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('laporan','sistem','pengumuman') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sistem',
  `referensi_id` int UNSIGNED DEFAULT NULL COMMENT 'ID laporan terkait (opsional)',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int UNSIGNED NOT NULL,
  `dibuat_oleh` int UNSIGNED NOT NULL COMMENT 'FK users (role: admin)',
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('umum','darurat','kegiatan','informasi') COLLATE utf8mb4_unicode_ci DEFAULT 'umum',
  `is_pinned` tinyint(1) DEFAULT '0',
  `is_published` tinyint(1) DEFAULT '0',
  `tanggal_publish` timestamp NULL DEFAULT NULL,
  `tanggal_expired` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profil_warga`
--

CREATE TABLE `profil_warga` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `no_kk` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomor Kartu Keluarga',
  `no_rt` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_rw` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_lengkap` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelurahan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota_kabupaten` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agama` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_perkawinan` enum('belum_kawin','kawin','cerai_hidup','cerai_mati') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_tinggal` enum('tetap','kontrak','kost','numpang') COLLATE utf8mb4_unicode_ci DEFAULT 'tetap',
  `tanggal_pindah_masuk` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `respons_laporan`
--

CREATE TABLE `respons_laporan` (
  `id` int UNSIGNED NOT NULL,
  `laporan_id` int UNSIGNED NOT NULL,
  `direspons_oleh` int UNSIGNED NOT NULL,
  `isi_respons` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_respons` enum('komentar','update_status','penugasan','eskalasi','penyelesaian') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'komentar',
  `is_internal` tinyint(1) DEFAULT '0' COMMENT '0 = warga bisa lihat | 1 = hanya admin/petugas',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `kode_user` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PTG-XXXXX | DRT-XXXXX | RT-001. NULL untuk warga biasa',
  `nik` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nomor Induk Kependudukan',
  `nama_lengkap` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('warga','petugas','admin','rt') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warga',
  `status_akun` enum('aktif','nonaktif','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending = belum diverifikasi admin',
  `foto_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `kode_user`, `nik`, `nama_lengkap`, `email`, `no_hp`, `password_hash`, `role`, `status_akun`, `foto_profil`, `created_at`, `updated_at`) VALUES
(1, 'RT-001', '0000000000000001', 'Bapak Ketua RT', 'rt@rt.local', '081111111111', '$2y$10$PLACEHOLDER_GANTI_HASH_INI_DENGAN_BCRYPT_ASLI_DARI_PHP_1', 'rt', 'aktif', NULL, '2026-06-27 04:55:05', '2026-06-27 04:55:05'),
(2, 'DRT-00001', '0000000000000002', 'Administrator RT', 'admin@rt.local', '082222222222', '$2y$10$PLACEHOLDER_GANTI_HASH_INI_DENGAN_BCRYPT_ASLI_DARI_PHP_2', 'admin', 'aktif', NULL, '2026-06-27 04:55:05', '2026-06-27 04:55:05'),
(3, 'PTG-00001', '0000000000000003', 'Petugas Satu', 'petugas1@rt.local', '083333333333', '$2y$10$PLACEHOLDER_GANTI_HASH_INI_DENGAN_BCRYPT_ASLI_DARI_PHP_3', 'petugas', 'aktif', NULL, '2026-06-27 04:55:05', '2026-06-27 04:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` timestamp NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_peta`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_peta` (
`akurasi_gps_meter` float
,`created_at` timestamp
,`id` int unsigned
,`judul` varchar(200)
,`kategori_ikon` varchar(100)
,`kode_laporan` varchar(30)
,`latitude` decimal(10,8)
,`lokasi_detail` varchar(255)
,`longitude` decimal(11,8)
,`nama_kategori` varchar(100)
,`status` enum('menunggu_verifikasi','diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut','selesai','ditolak','dibatalkan')
,`tingkat_prioritas` enum('rendah','sedang','tinggi','darurat')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_ringkasan`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_ringkasan` (
`akurasi_gps_meter` float
,`alasan_penolakan` text
,`catatan_petugas` text
,`created_at` timestamp
,`durasi_hari` int
,`hp_pelapor` varchar(15)
,`hp_petugas` varchar(15)
,`id` int unsigned
,`jml_foto_awal` bigint
,`jml_foto_selesai` bigint
,`jml_respons` bigint
,`judul` varchar(200)
,`kategori_id` int unsigned
,`kategori_ikon` varchar(100)
,`kelompok_status` varchar(15)
,`kode_laporan` varchar(30)
,`kode_petugas` varchar(20)
,`kode_verifikator` varchar(20)
,`label_status` varchar(27)
,`latitude` decimal(10,8)
,`lokasi_detail` varchar(255)
,`longitude` decimal(11,8)
,`nama_kategori` varchar(100)
,`nama_pelapor` varchar(150)
,`nama_petugas` varchar(150)
,`nama_verifikator` varchar(150)
,`pelapor_id` int unsigned
,`rating_warga` tinyint unsigned
,`role_verifikator` enum('warga','petugas','admin','rt')
,`status` enum('menunggu_verifikasi','diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut','selesai','ditolak','dibatalkan')
,`tanggal_mulai_kerjakan` timestamp
,`tanggal_selesai` timestamp
,`tanggal_target_selesai` date
,`tingkat_prioritas` enum('rendah','sedang','tinggi','darurat')
,`ulasan_warga` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monitoring_petugas`
-- (See below for the actual view)
--
CREATE TABLE `v_monitoring_petugas` (
`hp_petugas` varchar(15)
,`jml_aktif` decimal(23,0)
,`jml_selesai` decimal(23,0)
,`kode_petugas` varchar(20)
,`nama_petugas` varchar(150)
,`petugas_id` int unsigned
,`rata_hari_selesai` decimal(10,1)
,`status_akun` enum('aktif','nonaktif','pending')
,`terakhir_aktif` timestamp
,`total_ditugaskan` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_rekap_status_laporan`
-- (See below for the actual view)
--
CREATE TABLE `v_rekap_status_laporan` (
`belum_diproses` decimal(23,0)
,`darurat_belum_selesai` decimal(23,0)
,`ditutup` decimal(23,0)
,`jml_dalam_pengerjaan` decimal(23,0)
,`jml_dibatalkan` decimal(23,0)
,`jml_ditolak` decimal(23,0)
,`jml_ditugaskan` decimal(23,0)
,`jml_diverifikasi` decimal(23,0)
,`jml_menunggu_verifikasi` decimal(23,0)
,`jml_perlu_tindak_lanjut` decimal(23,0)
,`jml_selesai` decimal(23,0)
,`rata_hari_penyelesaian` decimal(10,1)
,`sedang_diproses` decimal(23,0)
,`selesai` decimal(23,0)
,`total` bigint
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `foto_laporan`
--
ALTER TABLE `foto_laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `diunggah_oleh` (`diunggah_oleh`),
  ADD KEY `idx_laporan` (`laporan_id`),
  ADD KEY `idx_tipe` (`tipe_foto`);

--
-- Indexes for table `histori_laporan`
--
ALTER TABLE `histori_laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `diubah_oleh` (`diubah_oleh`),
  ADD KEY `idx_laporan` (`laporan_id`);

--
-- Indexes for table `kategori_laporan`
--
ALTER TABLE `kategori_laporan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `konfigurasi`
--
ALTER TABLE `konfigurasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kunci` (`kunci`);

--
-- Indexes for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_laporan` (`kode_laporan`),
  ADD KEY `diverifikasi_oleh` (`diverifikasi_oleh`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pelapor` (`pelapor_id`),
  ADD KEY `idx_petugas` (`petugas_id`),
  ADD KEY `idx_prioritas` (`tingkat_prioritas`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_kategori` (`kategori_id`),
  ADD KEY `idx_koordinat` (`latitude`,`longitude`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `idx_published` (`is_published`,`tanggal_publish`);

--
-- Indexes for table `profil_warga`
--
ALTER TABLE `profil_warga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_nokk` (`no_kk`);

--
-- Indexes for table `respons_laporan`
--
ALTER TABLE `respons_laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direspons_oleh` (`direspons_oleh`),
  ADD KEY `idx_laporan` (`laporan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `kode_user` (`kode_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status_akun`),
  ADD KEY `idx_kode` (`kode_user`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_token` (`session_token`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `foto_laporan`
--
ALTER TABLE `foto_laporan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `histori_laporan`
--
ALTER TABLE `histori_laporan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_laporan`
--
ALTER TABLE `kategori_laporan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `konfigurasi`
--
ALTER TABLE `konfigurasi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profil_warga`
--
ALTER TABLE `profil_warga`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `respons_laporan`
--
ALTER TABLE `respons_laporan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_peta`
--
DROP TABLE IF EXISTS `v_laporan_peta`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_peta`  AS SELECT `lk`.`id` AS `id`, `lk`.`kode_laporan` AS `kode_laporan`, `lk`.`judul` AS `judul`, `lk`.`status` AS `status`, `lk`.`tingkat_prioritas` AS `tingkat_prioritas`, `lk`.`latitude` AS `latitude`, `lk`.`longitude` AS `longitude`, `lk`.`akurasi_gps_meter` AS `akurasi_gps_meter`, `lk`.`lokasi_detail` AS `lokasi_detail`, `lk`.`created_at` AS `created_at`, `kl`.`nama_kategori` AS `nama_kategori`, `kl`.`ikon` AS `kategori_ikon` FROM (`laporan_kerusakan` `lk` join `kategori_laporan` `kl` on((`kl`.`id` = `lk`.`kategori_id`))) WHERE ((`lk`.`latitude` is not null) AND (`lk`.`longitude` is not null)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_ringkasan`
--
DROP TABLE IF EXISTS `v_laporan_ringkasan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_ringkasan`  AS SELECT `lk`.`id` AS `id`, `lk`.`kode_laporan` AS `kode_laporan`, `lk`.`judul` AS `judul`, `lk`.`status` AS `status`, (case `lk`.`status` when 'menunggu_verifikasi' then 'Menunggu Verifikasi' when 'diverifikasi' then 'Sudah Diverifikasi' when 'ditugaskan' then 'Sudah Ditugaskan ke Petugas' when 'dalam_pengerjaan' then 'Sedang Dikerjakan' when 'perlu_tindak_lanjut' then 'Perlu Tindak Lanjut' when 'selesai' then 'Selesai' when 'ditolak' then 'Ditolak' when 'dibatalkan' then 'Dibatalkan' else `lk`.`status` end) AS `label_status`, (case when (`lk`.`status` = 'menunggu_verifikasi') then 'belum_diproses' when (`lk`.`status` in ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')) then 'sedang_diproses' when (`lk`.`status` = 'selesai') then 'selesai' else 'ditutup' end) AS `kelompok_status`, `lk`.`tingkat_prioritas` AS `tingkat_prioritas`, `lk`.`lokasi_detail` AS `lokasi_detail`, `lk`.`latitude` AS `latitude`, `lk`.`longitude` AS `longitude`, `lk`.`akurasi_gps_meter` AS `akurasi_gps_meter`, `lk`.`created_at` AS `created_at`, `lk`.`tanggal_target_selesai` AS `tanggal_target_selesai`, `lk`.`tanggal_mulai_kerjakan` AS `tanggal_mulai_kerjakan`, `lk`.`tanggal_selesai` AS `tanggal_selesai`, `lk`.`catatan_petugas` AS `catatan_petugas`, `lk`.`alasan_penolakan` AS `alasan_penolakan`, `lk`.`rating_warga` AS `rating_warga`, `lk`.`ulasan_warga` AS `ulasan_warga`, (to_days(coalesce(`lk`.`tanggal_selesai`,now())) - to_days(`lk`.`created_at`)) AS `durasi_hari`, `u_lapor`.`id` AS `pelapor_id`, `u_lapor`.`nama_lengkap` AS `nama_pelapor`, `u_lapor`.`no_hp` AS `hp_pelapor`, `kl`.`id` AS `kategori_id`, `kl`.`nama_kategori` AS `nama_kategori`, `kl`.`ikon` AS `kategori_ikon`, `u_verif`.`kode_user` AS `kode_verifikator`, `u_verif`.`nama_lengkap` AS `nama_verifikator`, `u_verif`.`role` AS `role_verifikator`, `u_ptg`.`kode_user` AS `kode_petugas`, `u_ptg`.`nama_lengkap` AS `nama_petugas`, `u_ptg`.`no_hp` AS `hp_petugas`, (select count(0) from `foto_laporan` `fl` where ((`fl`.`laporan_id` = `lk`.`id`) and (`fl`.`tipe_foto` = 'bukti_awal'))) AS `jml_foto_awal`, (select count(0) from `foto_laporan` `fl` where ((`fl`.`laporan_id` = `lk`.`id`) and (`fl`.`tipe_foto` = 'bukti_selesai'))) AS `jml_foto_selesai`, (select count(0) from `respons_laporan` `rl` where ((`rl`.`laporan_id` = `lk`.`id`) and (`rl`.`is_internal` = 0))) AS `jml_respons` FROM ((((`laporan_kerusakan` `lk` join `users` `u_lapor` on((`u_lapor`.`id` = `lk`.`pelapor_id`))) join `kategori_laporan` `kl` on((`kl`.`id` = `lk`.`kategori_id`))) left join `users` `u_verif` on((`u_verif`.`id` = `lk`.`diverifikasi_oleh`))) left join `users` `u_ptg` on((`u_ptg`.`id` = `lk`.`petugas_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_monitoring_petugas`
--
DROP TABLE IF EXISTS `v_monitoring_petugas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monitoring_petugas`  AS SELECT `u`.`id` AS `petugas_id`, `u`.`kode_user` AS `kode_petugas`, `u`.`nama_lengkap` AS `nama_petugas`, `u`.`no_hp` AS `hp_petugas`, `u`.`status_akun` AS `status_akun`, count(`lk`.`id`) AS `total_ditugaskan`, sum((case when (`lk`.`status` in ('ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')) then 1 else 0 end)) AS `jml_aktif`, sum((case when (`lk`.`status` = 'selesai') then 1 else 0 end)) AS `jml_selesai`, round(avg((case when ((`lk`.`status` = 'selesai') and (`lk`.`tanggal_selesai` is not null)) then (to_days(`lk`.`tanggal_selesai`) - to_days(`lk`.`tanggal_mulai_kerjakan`)) end)),1) AS `rata_hari_selesai`, max(`lk`.`updated_at`) AS `terakhir_aktif` FROM (`users` `u` left join `laporan_kerusakan` `lk` on((`lk`.`petugas_id` = `u`.`id`))) WHERE (`u`.`role` = 'petugas') GROUP BY `u`.`id`, `u`.`kode_user`, `u`.`nama_lengkap`, `u`.`no_hp`, `u`.`status_akun` ;

-- --------------------------------------------------------

--
-- Structure for view `v_rekap_status_laporan`
--
DROP TABLE IF EXISTS `v_rekap_status_laporan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekap_status_laporan`  AS SELECT sum((case when (`laporan_kerusakan`.`status` = 'menunggu_verifikasi') then 1 else 0 end)) AS `belum_diproses`, sum((case when (`laporan_kerusakan`.`status` in ('diverifikasi','ditugaskan','dalam_pengerjaan','perlu_tindak_lanjut')) then 1 else 0 end)) AS `sedang_diproses`, sum((case when (`laporan_kerusakan`.`status` = 'selesai') then 1 else 0 end)) AS `selesai`, sum((case when (`laporan_kerusakan`.`status` in ('ditolak','dibatalkan')) then 1 else 0 end)) AS `ditutup`, count(0) AS `total`, sum((case when (`laporan_kerusakan`.`status` = 'menunggu_verifikasi') then 1 else 0 end)) AS `jml_menunggu_verifikasi`, sum((case when (`laporan_kerusakan`.`status` = 'diverifikasi') then 1 else 0 end)) AS `jml_diverifikasi`, sum((case when (`laporan_kerusakan`.`status` = 'ditugaskan') then 1 else 0 end)) AS `jml_ditugaskan`, sum((case when (`laporan_kerusakan`.`status` = 'dalam_pengerjaan') then 1 else 0 end)) AS `jml_dalam_pengerjaan`, sum((case when (`laporan_kerusakan`.`status` = 'perlu_tindak_lanjut') then 1 else 0 end)) AS `jml_perlu_tindak_lanjut`, sum((case when (`laporan_kerusakan`.`status` = 'selesai') then 1 else 0 end)) AS `jml_selesai`, sum((case when (`laporan_kerusakan`.`status` = 'ditolak') then 1 else 0 end)) AS `jml_ditolak`, sum((case when (`laporan_kerusakan`.`status` = 'dibatalkan') then 1 else 0 end)) AS `jml_dibatalkan`, sum((case when ((`laporan_kerusakan`.`tingkat_prioritas` = 'darurat') and (`laporan_kerusakan`.`status` not in ('selesai','ditolak','dibatalkan'))) then 1 else 0 end)) AS `darurat_belum_selesai`, round(avg((case when ((`laporan_kerusakan`.`status` = 'selesai') and (`laporan_kerusakan`.`tanggal_selesai` is not null)) then (to_days(`laporan_kerusakan`.`tanggal_selesai`) - to_days(`laporan_kerusakan`.`created_at`)) end)),1) AS `rata_hari_penyelesaian` FROM `laporan_kerusakan` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `foto_laporan`
--
ALTER TABLE `foto_laporan`
  ADD CONSTRAINT `foto_laporan_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_kerusakan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `foto_laporan_ibfk_2` FOREIGN KEY (`diunggah_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `histori_laporan`
--
ALTER TABLE `histori_laporan`
  ADD CONSTRAINT `histori_laporan_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_kerusakan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `histori_laporan_ibfk_2` FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  ADD CONSTRAINT `laporan_kerusakan_ibfk_1` FOREIGN KEY (`pelapor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `laporan_kerusakan_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_laporan` (`id`),
  ADD CONSTRAINT `laporan_kerusakan_ibfk_3` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `laporan_kerusakan_ibfk_4` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `profil_warga`
--
ALTER TABLE `profil_warga`
  ADD CONSTRAINT `profil_warga_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `respons_laporan`
--
ALTER TABLE `respons_laporan`
  ADD CONSTRAINT `respons_laporan_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_kerusakan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respons_laporan_ibfk_2` FOREIGN KEY (`direspons_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
