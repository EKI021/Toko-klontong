-- =========================================================
-- SKEMA DATABASE — SISTEM STOK TOKO KELONTONG MULTI-CABANG
-- =========================================================
-- Cara pakai: import file ini lewat phpMyAdmin, atau lewat terminal:
--   mysql -u root -p toko_kelontong < schema.sql
-- (Buat databasenya dulu: CREATE DATABASE toko_kelontong;)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- CABANG (setiap toko/cabang yang pakai sistem ini)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS cabang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_cabang VARCHAR(150) NOT NULL,
  alamat VARCHAR(255) DEFAULT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- KARYAWAN (akun login)
-- Peran: super_admin (semua cabang), admin (1 cabang), gudang, kasir
-- cabang_id NULL berarti super_admin (bisa akses semua cabang)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS karyawan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cabang_id INT DEFAULT NULL,
  nama VARCHAR(150) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  peran ENUM('super_admin','admin','gudang','kasir') NOT NULL DEFAULT 'kasir',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_karyawan_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- BARANG (katalog produk — dipakai bersama semua cabang)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(40) NOT NULL UNIQUE,
  nama VARCHAR(200) NOT NULL,
  kategori VARCHAR(100) DEFAULT NULL,
  satuan VARCHAR(30) DEFAULT 'pcs',
  harga_beli DECIMAL(14,2) NOT NULL DEFAULT 0,
  harga_jual DECIMAL(14,2) NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 0,
  barcode VARCHAR(60) DEFAULT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_barcode (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- STOK (jumlah stok barang ini di cabang tertentu)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS stok (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cabang_id INT NOT NULL,
  barang_id INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 0,
  diubah_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_stok_cabang_barang (cabang_id, barang_id),
  CONSTRAINT fk_stok_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
  CONSTRAINT fk_stok_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- TRANSAKSI (riwayat stok masuk/keluar & checkout per cabang)
-- harga_beli_saat / harga_jual_saat = snapshot harga waktu transaksi,
-- supaya laporan laba-rugi lama tidak berubah kalau harga di-update nanti
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cabang_id INT NOT NULL,
  barang_id INT NOT NULL,
  karyawan_id INT DEFAULT NULL,
  jenis ENUM('masuk','keluar') NOT NULL,
  jumlah INT NOT NULL,
  catatan VARCHAR(255) DEFAULT NULL,
  harga_beli_saat DECIMAL(14,2) DEFAULT NULL,
  harga_jual_saat DECIMAL(14,2) DEFAULT NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transaksi_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
  CONSTRAINT fk_transaksi_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_transaksi_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE SET NULL,
  INDEX idx_transaksi_cabang_waktu (cabang_id, waktu),
  INDEX idx_transaksi_barang (barang_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- LOG AKTIVITAS (audit trail karyawan)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS log_aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cabang_id INT DEFAULT NULL,
  karyawan_id INT DEFAULT NULL,
  nama_karyawan_saat VARCHAR(150) DEFAULT NULL,
  aksi VARCHAR(60) NOT NULL,
  detail VARCHAR(500) DEFAULT NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_cabang FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE SET NULL,
  CONSTRAINT fk_log_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE SET NULL,
  INDEX idx_log_waktu (waktu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- SESI LOGIN (token API sederhana, dipakai backend PHP)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS sesi_login (
  token VARCHAR(64) PRIMARY KEY,
  karyawan_id INT NOT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  kadaluarsa_pada DATETIME NOT NULL,
  CONSTRAINT fk_sesi_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- DATA AWAL: satu cabang contoh (silakan ganti/tambah)
-- Akun super_admin dibuat lewat setup_admin.php, BUKAN di sini,
-- supaya password-nya di-hash dengan aman oleh PHP.
-- ---------------------------------------------------------
INSERT INTO cabang (nama_cabang, alamat) VALUES ('Cabang Pusat', 'Isi alamat cabang di sini')
  ON DUPLICATE KEY UPDATE nama_cabang = nama_cabang;
