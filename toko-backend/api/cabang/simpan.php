<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$nama = trim($input['nama_cabang'] ?? '');
$alamat = trim($input['alamat'] ?? '');
if ($nama === '') jsonError('Nama cabang wajib diisi', 400);

$pdo = getDB();
$pdo->prepare("INSERT INTO cabang (nama_cabang, alamat) VALUES (:n,:a)")->execute(['n' => $nama, 'a' => $alamat]);
$id = (int) $pdo->lastInsertId();
logAktivitas($pdo, $user, null, 'Tambah Cabang', $nama);

jsonResponse(['ok' => true, 'id' => $id]);
