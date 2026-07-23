# Panduan Setup Backend — Sistem Stok Toko Kelontong

Panduan ini untuk pemula, dijelaskan langkah demi langkah dari nol.
Backend ini sudah diuji dan berfungsi (login, tambah barang, stok masuk/keluar,
checkout, laporan laba-rugi, dashboard gudang & monitoring, karyawan, log aktivitas).

---

## Bagian 1 — Siapkan "Server" di Komputer Kantor

Server di sini artinya komputer yang akan menyalakan program supaya bisa diakses
komputer/HP lain. Kita pakai **XAMPP** karena paling mudah untuk pemula (sudah
termasuk Apache, PHP, dan MySQL/MariaDB sekaligus).

### Langkah 1.1 — Install XAMPP
1. Unduh XAMPP di **https://www.apachefriends.org** (pilih versi Windows kalau
   komputer kantor pakai Windows).
2. Install seperti biasa (Next, Next, Finish). Centang komponen **Apache** dan
   **MySQL** saat instalasi (biasanya sudah tercentang secara default).
3. Buka **XAMPP Control Panel**, klik tombol **Start** di baris **Apache** dan
   baris **MySQL**. Kalau warnanya jadi hijau, berarti sudah jalan.

### Langkah 1.2 — Salin folder backend ini
1. Cari folder instalasi XAMPP, biasanya di `C:\xampp\htdocs`.
2. Salin seluruh folder `toko-backend` (yang saya berikan) ke dalam
   `C:\xampp\htdocs\`. Hasil akhirnya: `C:\xampp\htdocs\toko-backend\...`

### Langkah 1.3 — Buat database
1. Buka browser, masuk ke **http://localhost/phpmyadmin**
2. Klik menu **Databases** di bagian atas.
3. Di kolom "Create database", ketik `toko_kelontong`, lalu klik **Create**.
4. Klik nama database `toko_kelontong` yang baru muncul di sebelah kiri.
5. Klik tab **Import** di bagian atas.
6. Klik **Choose File**, pilih file `toko-backend/database/schema.sql`.
7. Scroll ke bawah, klik tombol **Go/Import**.
8. Kalau berhasil akan muncul tulisan hijau dan beberapa tabel baru muncul di
   sebelah kiri (`barang`, `cabang`, `karyawan`, `stok`, `transaksi`, dst).

### Langkah 1.4 — Cek konfigurasi koneksi database
Buka file `toko-backend/config/database.php`. Untuk instalasi XAMPP default,
isian ini **sudah benar dan tidak perlu diubah**:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'toko_kelontong');
define('DB_USER', 'root');
define('DB_PASS', '');
```
(Kalau MySQL XAMPP kamu diberi password root, isi `DB_PASS` sesuai itu.)

### Langkah 1.5 — Buat akun Super Admin pertama
1. Buka browser: **http://localhost/toko-backend/setup_admin.php**
2. Isi Nama, Username, dan Password (minimal 4 karakter), klik **Buat Akun
   Super Admin**.
3. Kalau muncul pesan hijau "berhasil dibuat" — **SEKARANG HAPUS file
   `setup_admin.php`** dari folder (lewat File Explorer). Ini penting supaya
   orang lain tidak bisa membuat akun Super Admin baru lewat halaman itu.

Sampai sini, backend sudah siap dan sudah punya 1 akun Super Admin + 1 cabang
contoh bernama "Cabang Pusat" (bisa diganti/ditambah lewat API cabang nanti).

---

## Bagian 2 — Coba Backend-nya (opsional tapi disarankan)

Backend ini berbentuk **API** (bukan tampilan web biasa) — artinya dipanggil
lewat kode, bukan diklik-klik di browser. Untuk mengecek backend sudah jalan,
kamu bisa pakai aplikasi seperti **Postman** (gratis, unduh di postman.com),
atau langsung ke Bagian 3 untuk sambungkan ke aplikasi toko yang sudah ada.

Contoh test login pakai Postman:
- Method: `POST`
- URL: `http://localhost/toko-backend/api/auth/login.php`
- Body (raw, JSON):
  ```json
  { "username": "USERNAME_KAMU", "password": "PASSWORD_KAMU" }
  ```
- Kalau berhasil, responsnya berisi `"token": "...."` — token inilah yang
  dipakai aplikasi untuk semua permintaan berikutnya (dikirim lewat header
  `Authorization: Bearer <token>`).

---

## Bagian 3 — Pasang Aplikasi Toko (Frontend)

Frontend sudah jadi (`index.html`) dan **sudah diuji langsung di browser sungguhan**
lewat semua alur utamanya: login, tambah barang, checkout, laporan laba-rugi,
kelola karyawan, dan kelola cabang — semuanya terhubung ke backend PHP + database
di atas, bukan lagi `window.storage` Claude.

### Langkah 3.1 — Taruh file di tempat yang benar
Struktur folder di `htdocs` harus seperti ini (frontend **sejajar** dengan folder
`toko-backend`, bukan di dalamnya):
```
C:\xampp\htdocs\
├── index.html          ← file frontend
└── toko-backend\        ← folder backend yang sudah kamu salin di Bagian 1
    ├── api\
    ├── config\
    └── ...
```
Kalau kamu taruh `index.html` di lokasi lain, buka file itu dengan editor teks,
cari baris berikut di bagian paling atas `<script>`:
```javascript
const API_BASE = '../toko-backend/api';
```
Ubah `'../toko-backend/api'` menjadi path yang sesuai lokasi folder backend kamu
relatif terhadap file frontend ini (atau alamat lengkap seperti
`'http://localhost/toko-backend/api'`).

### Langkah 3.2 — Buka aplikasinya
Buka browser: **http://localhost/**
(atau **http://localhost/index.html** kalau tidak otomatis terbuka)

Login pakai akun Super Admin yang dibuat di Langkah 1.5.

### Yang berbeda dari versi Claude sebelumnya
- **Login** sekarang pakai username + password sungguhan (bukan pilih nama dari
  daftar + PIN), dan password disimpan terenkripsi di server.
- **Multi-cabang**: kalau login sebagai Super Admin, akan muncul pemilih cabang
  di pojok kiri atas — pilih cabang mana yang mau dikelola. Peran lain (Admin,
  Gudang, Kasir) otomatis terkunci ke cabang mereka masing-masing.
- **Data tersimpan permanen di database sendiri** — bukan lagi tersimpan lewat
  Claude, jadi bisa diakses dari komputer/HP mana pun yang tersambung ke server
  (lihat Bagian 4 untuk opsi multi-cabang).
- Semua fitur lama tetap ada: scan barcode, cetak struk & label, dashboard
  gudang & monitoring, laporan laba-rugi, log aktivitas karyawan.


---

## Bagian 4 — Supaya Bisa Diakses dari Banyak Cabang

Ini bagian yang perlu dipikirkan matang-matang karena menyangkut jaringan dan
keamanan. Tiga opsi, dari yang paling sederhana sampai paling andal:

### Opsi A — Semua cabang lewat internet ke komputer kantor (paling murah, tapi butuh perhatian ekstra)
- Perlu **IP publik** dari internet kantor (tanya provider internet, atau
  pakai layanan Dynamic DNS seperti **No-IP** / **DuckDNS** kalau IP-nya
  berubah-ubah).
- Perlu **port forwarding** di router kantor supaya port 80 (atau 443 untuk
  HTTPS) diarahkan ke komputer server.
- **Risiko:** komputer kantor jadi bisa diakses dari internet luar, jadi wajib
  pasang HTTPS (SSL) dan jaga keamanan komputer itu (update Windows, antivirus,
  jangan sembarangan install program lain di situ).
- Cocok untuk: 2–5 cabang, anggaran terbatas, ada yang bisa maintain.

### Opsi B — VPN antar cabang (lebih aman, sedikit lebih ribet setahap)
- Pasang VPN (misalnya **WireGuard** atau **Tailscale** — Tailscale jauh lebih
  mudah untuk pemula, tinggal install & login, otomatis tersambung) di
  komputer server dan di komputer/HP tiap cabang.
- Cabang mengakses lewat alamat VPN (bukan internet terbuka), jadi jauh lebih
  aman karena tidak "kelihatan" dari internet luar sama sekali.
- Cocok untuk: siapa pun yang mau lebih aman tanpa sewa server, worth
  dicoba duluan karena Tailscale gratis untuk pemakaian kecil.

### Opsi C — Pindah ke VPS murah (paling stabil, sedikit biaya bulanan)
- Sewa VPS (mulai ~Rp50–100rb/bulan di provider lokal atau luar negeri) khusus
  untuk menjalankan backend ini — bukan lagi di komputer kantor.
- Lebih stabil (tidak tergantung listrik/internet kantor menyala terus),
  lebih gampang pasang HTTPS gratis (Let's Encrypt/Certbot), dan cabang-cabang
  akses lewat internet normal.
- Kalau nanti mau, backend PHP ini **bisa langsung dipindah ke VPS** tanpa
  perlu ditulis ulang — tinggal salin foldernya dan import schema.sql yang sama.

**Saran saya:** mulai dari Opsi A atau B dulu di kantor (gratis, cepat
dicoba), sambil menyimpan opsi pindah ke VPS (Opsi C) kalau sistemnya sudah
terbukti dipakai dan bisnisnya makin butuh keandalan lebih tinggi.

---

## Ringkasan Peran & Hak Akses

| Peran         | Cabang       | Bisa apa saja                                              |
|---------------|--------------|--------------------------------------------------------------|
| `super_admin` | Semua cabang | Semua akses, termasuk kelola cabang & lihat semua laporan     |
| `admin`       | 1 cabang     | Kelola barang, karyawan, laporan — khusus cabangnya sendiri   |
| `gudang`      | 1 cabang     | Kelola barang & stok masuk/keluar, tanpa laporan keuangan     |
| `kasir`       | 1 cabang     | Hanya checkout                                                |

Akun Super Admin pertama **tidak terhubung ke cabang manapun** (`cabang_id`
kosong) — ini yang membuatnya bisa memilih cabang mana saja saat memakai API.

---

## Struktur Folder

```
htdocs/
├── index.html                ← Frontend aplikasi (buka ini di browser)
└── toko-backend/
    ├── config/
    │   └── database.php       ← Isi koneksi database di sini
    ├── helpers/                ← Kode bersama (jangan perlu diubah)
    ├── api/
    │   ├── auth/               ← login, logout, cek sesi
    │   ├── barang/              ← daftar, tambah/edit, hapus barang
    │   ├── transaksi/           ← stok masuk/keluar, checkout, riwayat
    │   ├── laporan/             ← laba-rugi, gudang, monitoring
    │   ├── karyawan/            ← kelola akun karyawan
    │   ├── cabang/              ← kelola cabang (khusus super admin)
    │   └── log/                 ← log aktivitas
    ├── database/
    │   └── schema.sql           ← Import ini ke phpMyAdmin
    └── setup_admin.php          ← Jalankan sekali, lalu HAPUS
```
