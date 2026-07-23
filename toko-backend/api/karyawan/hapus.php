<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$id = (int) ($input['id'] ?? 0);
if (!$id) jsonError('ID wajib diisi', 400);
if ($id === (int) $user['id']) jsonError('Tidak bisa menghapus akun sendiri yang sedang login', 400);

$pdo = getDB();
$target = $pdo->prepare("SELECT * FROM karyawan WHERE id = :id");
$target->execute(['id' => $id]);
$target = $target->fetch();
if (!$target) jsonError('Karyawan tidak ditemukan', 404);

if ($user['peran'] === 'admin' && (int) $target['cabang_id'] !== (int) $user['cabang_id']) {
    jsonError('Tidak bisa menghapus karyawan cabang lain', 403);
}
if ($target['peran'] === 'super_admin') {
    $count = $pdo->query("SELECT COUNT(*) AS n FROM karyawan WHERE peran='super_admin' AND aktif=1")->fetch()['n'];
    if ($count <= 1) jsonError('Minimal harus ada 1 akun Super Admin aktif', 400);
}

$pdo->prepare("DELETE FROM karyawan WHERE id = :id")->execute(['id' => $id]);
logAktivitas($pdo, $user, $target['cabang_id'] ? (int)$target['cabang_id'] : null, 'Hapus Karyawan', $target['nama']);

jsonResponse(['ok' => true]);
