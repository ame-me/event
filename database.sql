CREATE DATABASE IF NOT EXISTS `event_kms`;
USE `event_kms`;

DROP TABLE IF EXISTS `forum_comments`;
DROP TABLE IF EXISTS `forum_diskusi`;
DROP TABLE IF EXISTS `evaluasi`;
DROP TABLE IF EXISTS `lesson_learned`;
DROP TABLE IF EXISTS `knowledge_docs`;
DROP TABLE IF EXISTS `kegiatan`;
DROP TABLE IF EXISTS `templates`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL
);

INSERT INTO `roles` (`id`, `role_name`) VALUES 
(1, 'Admin Sistem'), 
(2, 'Manajemen'), 
(3, 'Panitia'), 
(4, 'Yayasan');

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
);

INSERT INTO `users` (`username`, `password`, `role_id`, `nama_lengkap`, `email`) VALUES 
('admin@smasanmar.sch.id', '$2y$12$U3HbUpQOKVcTy69QhtzP/uo60c6cA3aJ9LdVtw06gCVAKKlQGL9xm', 1, 'Admin Sistem', 'admin@smasanmar.sch.id'),
('manajemen@smasanmar.sch.id', '$2y$12$pSXjRzVSyY8pEws/8GouluYrAtc3coQnjkDoe1cUb55KYcSBLjXw6', 2, 'Manajer (Pelaksana)', 'manajemen@smasanmar.sch.id'),
('panitia@smasanmar.sch.id', '$2y$12$I0reum5UXrYMqTHlFVLvV.vF32c5JrbWHphMrPSpo2trBFnzyplei', 3, 'Panitia (Evaluator)', 'panitia@smasanmar.sch.id'),
('yayasan@smasanmar.sch.id', '$2y$12$jP12osqlOpIESFZwgo.F/.Fkgehu/lFQTfGG2ZCSPNpdyln.p.sQm', 4, 'Bpk. Yayasan', 'yayasan@smasanmar.sch.id');

CREATE TABLE `kegiatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kegiatan` VARCHAR(255) NOT NULL,
  `jenis_kegiatan` VARCHAR(100),
  `tahun` VARCHAR(10),
  `deskripsi` TEXT,
  `status` ENUM('Perencanaan', 'Berjalan', 'Selesai') DEFAULT 'Perencanaan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `knowledge_docs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT,
  `judul` VARCHAR(255) NOT NULL,
  `tipe_dokumen` VARCHAR(50),
  `file_path` VARCHAR(255),
  `versi` INT DEFAULT 1,
  `uploader_id` INT,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan`(`id`),
  FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `lesson_learned` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT,
  `user_id` INT,
  `kendala` TEXT,
  `solusi` TEXT,
  `rekomendasi` TEXT,
  `is_best_practice` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- Note: Password for admin is 'password' (bcrypt hash as default Laravel/PHP)
CREATE TABLE `evaluasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT,
  `user_id` INT,
  `skor_rating` INT,
  `keberhasilan` TEXT,
  `kekurangan` TEXT,
  `saran` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `forum_diskusi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `user_id` INT,
  `status_aktif` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `forum_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `diskusi_id` INT,
  `user_id` INT,
  `content` TEXT NOT NULL,
  `likes` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`diskusi_id`) REFERENCES `forum_diskusi`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_template` VARCHAR(255) NOT NULL,
  `tipe` VARCHAR(50),
  `file_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DUMMY DATA KEGIATAN
INSERT INTO `kegiatan` (`id`, `nama_kegiatan`, `jenis_kegiatan`, `tahun`, `deskripsi`, `status`) VALUES 
(1, 'Konser Amal Sanmar 2024', 'Seni & Budaya', '2024', 'Konser tahunan untuk penggalangan dana panti asuhan.', 'Selesai'),
(2, 'LDKS Pengurus OSIS', 'Kepemimpinan', '2024', 'Latihan Dasar Kepemimpinan Siswa di Bedengan.', 'Berjalan'),
(3, 'Wisuda Kelulusan Angkatan 70', 'Seremonial', '2025', 'Acara pelepasan siswa kelas XII.', 'Perencanaan');

-- DUMMY DATA REPOSITORY
INSERT INTO `knowledge_docs` (`kegiatan_id`, `judul`, `tipe_dokumen`, `file_path`, `uploader_id`) VALUES 
(1, 'Proposal Konser Amal 2024', 'Proposal', 'docs/proposal_konser.pdf', 2),
(1, 'Laporan Keuangan Konser', 'Laporan', 'docs/lpj_keuangan.pdf', 2),
(2, 'Rundown LDKS Day 1', 'Timeline', 'docs/rundown_ldks.pdf', 2);

-- DUMMY DATA LESSON LEARNED
INSERT INTO `lesson_learned` (`kegiatan_id`, `user_id`, `kendala`, `solusi`, `rekomendasi`) VALUES 
(1, 2, 'Vendor telat datang 2 jam dari jadwal.', 'Langsung hubungi vendor cadangan di area terdekat.', 'Selalu buat kontrak dengan denda keterlambatan H-4 jam.'),
(1, 2, 'Banyak nasi kotak yang basi.', 'Simpan di ruangan ber-AC.', 'Distribusi maksimal 2 jam setelah matang.');

-- DUMMY DATA EVALUASI
INSERT INTO `evaluasi` (`kegiatan_id`, `user_id`, `skor_rating`, `keberhasilan`, `kekurangan`, `saran`) VALUES 
(1, 3, 5, 'Target dana tercapai 120%.', 'Area parkir kurang luas.', 'Tahun depan perlu kerjasama dengan lahan parkir gereja.');

-- DUMMY DATA FORUM
INSERT INTO `forum_diskusi` (`id`, `kegiatan_id`, `judul`, `user_id`) VALUES 
(1, 1, 'Cara handle kerumunan di depan gate', 2),
(2, 2, 'Rekomendasi tempat LDKS yang aman', 3),
(3, 1, 'Evaluasi Vendor Sound System - Konser Amal', 2),
(4, 3, 'Persiapan Rundown Wisuda Kelulusan', 2);

-- DATA KEGIATAN TAMBAHAN (2023 - 2025)
INSERT INTO `kegiatan` (`id`, `nama_kegiatan`, `jenis_kegiatan`, `tahun`, `deskripsi`, `status`) VALUES 
(4, 'Pentas Seni Sanmar 2023', 'Seni & Budaya', '2023', 'Pagelaran seni siswa akhir tahun.', 'Selesai'),
(5, 'Open House & PPDB', 'Promosi', '2024', 'Penerimaan siswa baru tahun ajaran 2024/2025.', 'Selesai'),
(6, 'Retret Kelas XI', 'Spiritual', '2024', 'Pembinaan iman siswa di Lawang.', 'Selesai'),
(7, 'Bazaar Kewirausahaan', 'Ekonomi', '2024', 'Praktek wirausaha siswa kelas X.', 'Selesai'),
(8, 'Class Meeting Semester Ganjil', 'Olahraga', '2024', 'Lomba antar kelas setelah SAS.', 'Berjalan'),
(9, 'Perayaan Natal Bersama', 'Spiritual', '2024', 'Ibadah dan perayaan natal keluarga besar sekolah.', 'Perencanaan'),
(10, 'Study Tour Yogyakarta', 'Edukasi', '2023', 'Kunjungan edukasi ke candi dan museum.', 'Selesai'),
(11, 'Lomba Debat Bahasa Inggris', 'Akademik', '2024', 'Kompetisi internal antar sekolah.', 'Berjalan');

-- DATA REPOSITORY TAMBAHAN
INSERT INTO `knowledge_docs` (`kegiatan_id`, `judul`, `tipe_dokumen`, `file_path`, `uploader_id`) VALUES 
(4, 'Laporan Akhir Pensi 2023', 'Laporan', 'docs/lpj_pensi.pdf', 2),
(5, 'Flyer Promosi PPDB 2024', 'Promosi', 'docs/flyer_ppdb.png', 2),
(6, 'Panduan Doa Retret', 'Materi', 'docs/doa_retret.pdf', 2),
(7, 'Layout Tenant Bazaar', 'Layout', 'docs/layout_bazaar.pdf', 2),
(10, 'Itinerary Study Tour Jogja', 'Timeline', 'docs/itinerary_jogja.pdf', 2);

-- DATA LESSON LEARNED TAMBAHAN
INSERT INTO `lesson_learned` (`kegiatan_id`, `user_id`, `kendala`, `solusi`, `rekomendasi`, `is_best_practice`) VALUES 
(4, 2, 'Panggung roboh terkena angin kencang.', 'Gunakan vendor panggung yang bersertifikat standar keamanan.', 'Wajib ada inspeksi struktur H-1 jam.', 1),
(5, 2, 'Orang tua bingung alur pendaftaran offline.', 'Siapkan standing banner alur di setiap pojok ruangan.', 'Buat video tutorial pendaftaran di YouTube.', 1),
(10, 2, 'Siswa ada yang tertinggal di area wisata.', 'Gunakan sistem presensi per bus setiap akan berangkat.', 'Wajibkan siswa memakai ID Card dengan nomor HP guru.', 0);

-- DATA EVALUASI TAMBAHAN
INSERT INTO `evaluasi` (`kegiatan_id`, `user_id`, `skor_rating`, `keberhasilan`, `kekurangan`, `saran`) VALUES 
(4, 3, 4, 'Acara sangat meriah dan tiket terjual habis.', 'Sound system sering feedback.', 'Cari vendor sound yang lebih mahal tapi berkualitas.'),
(5, 3, 5, 'Jumlah pendaftar meningkat 20% dari tahun lalu.', 'Antrean validasi dokumen sangat panjang.', 'Tahun depan validasi dokumen dilakukan online h-1.'),
(6, 3, 4, 'Siswa sangat tenang dan meresapi sesi retret.', 'Makanan kurang variasi.', 'Menu makanan perlu ditanyakan ke siswa dulu.'),
(7, 3, 3, 'Penghasilan bazaar lumayan besar.', 'Sampah menumpuk di area lapangan.', 'Sediakan lebih banyak tempat sampah besar.'),
(10, 3, 5, 'Semua objek wisata dikunjungi tepat waktu.', 'Bus AC-nya kurang dingin.', 'Pastikan cek armada bus 3 hari sebelum berangkat.');

-- DATA FORUM TAMBAHAN
INSERT INTO `forum_diskusi` (`id`, `kegiatan_id`, `judul`, `user_id`) VALUES 
(5, 5, 'Cara rekrut relawan siswa untuk Open House', 2),
(6, 7, 'Keamanan parkir saat Bazaar', 3);

-- DATA FORUM COMMENTS TAMBAHAN
INSERT INTO `forum_comments` (`diskusi_id`, `user_id`, `content`) VALUES 
(5, 2, 'Bagaimana kriteria siswa yang cocok jadi guide Open House?'),
(5, 3, 'Pilih yang komunikatif dan hafal struktur gedung sekolah.'),
(5, 4, 'Setuju, beri mereka seragam khusus agar mudah dikenali.'),
(6, 3, 'Perlu koordinasi dengan kepolisian atau cukup satpam internal?'),
(6, 1, 'Satpam internal cukup, tapi perlu bantuan karang taruna untuk area luar sekolah.');

-- TABLE TEMPLATES (Modul 6)
CREATE TABLE IF NOT EXISTS `templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_template` varchar(255) NOT NULL,
  `tipe` enum('Proposal','Laporan','SOP','Rundown') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DATA DUMMY TEMPLATES
INSERT INTO `templates` (`nama_template`, `tipe`, `file_path`) VALUES 
('Template Proposal Kegiatan Umum', 'Proposal', 'uploads/templates/proposal_v1.docx'),
('Template Laporan Pertanggungjawaban', 'Laporan', 'uploads/templates/lpj_v1.docx'),
('SOP Keamanan & Alur Parkir', 'SOP', 'uploads/templates/sop_keamanan.pdf'),
('Draft Rundown & Timeline Acara', 'Rundown', 'uploads/templates/rundown_v1.xlsx');

