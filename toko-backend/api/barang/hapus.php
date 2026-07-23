<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$id = (int) ($input['id'] ?? 0);
if (!$id) jsonError('ID barang wajib diisi', 400);

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();
if (!$item) jsonError('Barang tidak ditemukan', 404);

$cabangId = resolveCabangId($user, isset($input['cabang_id']) ? (int) $input['cabang_id'] : null);

$pdo->prepare("DELETE FROM barang WHERE id = :id")->execute(['id' => $id]);
logAktivitas($pdo, $user, $cabangId, 'Hapus Barang', "{$item['nama']} (kode {$item['kode']})");

jsonResponse(['ok' => true]);
