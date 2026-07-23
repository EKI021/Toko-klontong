<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
$cabangId = resolveCabangId($user, isset($_GET['cabang_id']) ? (int) $_GET['cabang_id'] : null);

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT t.*, b.kode, b.nama
     FROM transaksi t JOIN barang b ON b.id = t.barang_id
     WHERE t.cabang_id = :c
     ORDER BY t.waktu DESC
     LIMIT 300"
);
$stmt->execute(['c' => $cabangId]);
jsonResponse(['transaksi' => $stmt->fetchAll()]);
