<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$id = isset($input['id']) && $input['id'] !== '' ? (int) $input['id'] : null;
$nama = trim($input['nama'] ?? '');
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$peran = $input['peran'] ?? 'kasir';
$aktif = !empty($input['aktif']) ? 1 : 0;
$cabangId = isset($input['cabang_id']) && $input['cabang_id'] !== '' ? (int) $input['cabang_id'] : null;

if ($nama === '' || $username === '') jsonError('Nama dan username wajib diisi', 400);
if (!in_array($peran, ['super_admin', 'admin', 'gudang', 'kasir'], true)) jsonError('Peran tidak valid', 400);

// Admin cabang hanya boleh kelola karyawan di cabangnya sendiri, dan tidak boleh membuat Super Admin
if ($user['peran'] === 'admin') {
    if ($peran === 'super_admin') jsonError('Admin cabang tidak boleh membuat akun Super Admin', 403);
    $cabangId = (int) $user['cabang_id'];
}
if ($peran !== 'super_admin' && !$cabangId) jsonError('cabang_id wajib diisi untuk peran ini', 400);

$pdo = getDB();

if ($id) {
    $old = $pdo->prepare("SELECT * FROM karyawan WHERE id = :id");
    $old->execute(['id' => $id]);
    $oldUser = $old->fetch();
    if (!$oldUser) jsonError('Karyawan tidak ditemukan', 404);
    if ($user['peran'] === 'admin' && (int) $oldUser['cabang_id'] !== (int) $user['cabang_id']) {
        jsonError('Tidak bisa mengubah karyawan cabang lain', 403);
    }

    if ($password !== '') {
        if (strlen($password) < 4) jsonError('Password minimal 4 karakter', 400);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE karyawan SET nama=:n, username=:u, peran=:p, aktif=:a, cabang_id=:c, password_hash=:h WHERE id=:id")
            ->execute(['n' => $nama, 'u' => $username, 'p' => $peran, 'a' => $aktif, 'c' => $cabangId, 'h' => $hash, 'id' => $id]);
    } else {
        $pdo->prepare("UPDATE karyawan SET nama=:n, username=:u, peran=:p, aktif=:a, cabang_id=:c WHERE id=:id")
            ->execute(['n' => $nama, 'u' => $username, 'p' => $peran, 'a' => $aktif, 'c' => $cabangId, 'id' => $id]);
    }
    logAktivitas($pdo, $user, $cabangId, 'Edit Karyawan', "$nama — peran $peran, akses " . ($aktif ? 'aktif' : 'dinonaktifkan'));
    jsonResponse(['ok' => true]);
} else {
    if ($password === '' || strlen($password) < 4) jsonError('Password wajib diisi (minimal 4 karakter)', 400);
    $dup = $pdo->prepare("SELECT id FROM karyawan WHERE username = :u");
    $dup->execute(['u' => $username]);
    if ($dup->fetch()) jsonError('Username sudah dipakai', 400);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO karyawan (cabang_id, nama, username, password_hash, peran, aktif) VALUES (:c,:n,:u,:h,:p,:a)")
        ->execute(['c' => $cabangId, 'n' => $nama, 'u' => $username, 'h' => $hash, 'p' => $peran, 'a' => $aktif]);
    logAktivitas($pdo, $user, $cabangId, 'Tambah Karyawan', "$nama — peran $peran");
    jsonResponse(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
}
