<?php
// =========================================================
// JALANKAN SEKALI SAJA lewat browser, contoh:
//   http://localhost/toko-backend/setup_admin.php
// Setelah akun Super Admin pertama berhasil dibuat, SEGERA HAPUS
// file ini dari server (atau minimal ganti namanya) demi keamanan.
// =========================================================
require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$sudahAda = (int) $pdo->query("SELECT COUNT(*) AS n FROM karyawan WHERE peran='super_admin'")->fetch()['n'];

$pesan = '';
$berhasil = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($sudahAda > 0) {
        $pesan = 'Sudah ada akun Super Admin. Demi keamanan, file ini tidak akan membuat akun baru lagi. Silakan hapus file setup_admin.php dari server.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($nama !== '' && $username !== '' && strlen($password) >= 4) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare(
                "INSERT INTO karyawan (cabang_id, nama, username, password_hash, peran, aktif)
                 VALUES (NULL, :n, :u, :h, 'super_admin', 1)"
            )->execute(['n' => $nama, 'u' => $username, 'h' => $hash]);
            $pesan = "Akun Super Admin \"$nama\" berhasil dibuat. Sekarang HAPUS file setup_admin.php ini dari server, lalu login lewat aplikasi.";
            $berhasil = true;
            $sudahAda = 1;
        } else {
            $pesan = 'Nama dan username wajib diisi, dan password minimal 4 karakter.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Setup Akun Super Admin</title>
<style>
  body{font-family:-apple-system,Arial,sans-serif;max-width:420px;margin:60px auto;padding:0 20px;color:#2A2118;}
  h2{margin-bottom:4px;}
  p.sub{color:#7A6C58;font-size:14px;margin-top:0;}
  label{display:block;font-size:13px;font-weight:600;margin:14px 0 4px;}
  input{width:100%;padding:9px 10px;border:1px solid #ccc;border-radius:5px;box-sizing:border-box;font-size:14px;}
  button{margin-top:18px;padding:10px 16px;background:#2F5233;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:600;font-size:14px;}
  .msg{margin-top:16px;padding:12px;border-radius:6px;font-size:13.5px;line-height:1.5;}
  .msg.ok{background:#E4EBDE;color:#1E3A20;}
  .msg.warn{background:#F5EAD3;color:#7A5A1E;}
</style>
</head>
<body>
<h2>Setup Akun Super Admin</h2>
<p class="sub">Jalankan sekali saja untuk membuat akun pemilik sistem pertama.</p>

<?php if ($pesan): ?>
  <div class="msg <?= $berhasil ? 'ok' : 'warn' ?>"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<?php if ($sudahAda == 0): ?>
<form method="POST">
  <label>Nama</label>
  <input type="text" name="nama" required>
  <label>Username</label>
  <input type="text" name="username" required>
  <label>Password</label>
  <input type="password" name="password" required minlength="4">
  <button type="submit">Buat Akun Super Admin</button>
</form>
<?php endif; ?>
</body>
</html>
