CREATE DATABASE IF NOT EXISTS db_absensi
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_absensi;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS karyawan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL UNIQUE,
  nip VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  jabatan VARCHAR(80) NOT NULL,
  bagian VARCHAR(80) NOT NULL,
  tanggal_masuk DATE NULL,
  status ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  CONSTRAINT fk_karyawan_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS absensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  karyawan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jam_masuk TIME NULL,
  jam_pulang TIME NULL,
  keterangan VARCHAR(100) DEFAULT 'Hadir',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unik_absensi_harian (karyawan_id, tanggal),
  CONSTRAINT fk_absensi_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
    ON DELETE CASCADE
);

-- Akun admin awal dibuat melalui setup_admin.php agar password tersimpan dengan hash.
