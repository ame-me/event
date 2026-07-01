CREATE TABLE IF NOT EXISTS `evaluasi` (
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

CREATE TABLE IF NOT EXISTS `forum_diskusi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `user_id` INT,
  `status_aktif` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `forum_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `diskusi_id` INT,
  `user_id` INT,
  `content` TEXT NOT NULL,
  `likes` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`diskusi_id`) REFERENCES `forum_diskusi`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_template` VARCHAR(255) NOT NULL,
  `tipe` VARCHAR(50),
  `file_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
