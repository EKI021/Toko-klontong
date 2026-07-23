<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
$cabangId = resolveCabangId($user, isset($_GET['cabang_id']) ? (int) $_GET['cabang_id'] : null);

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT b.*, COALESCE(s.jumlah, 0) AS stok
     FROM barang b
     LEFT JOIN stok s ON s.barang_id = b.id AND s.cabang_id = :cabang_id
     ORDER BY b.nama ASC"
);
$stmt->execute(['cabang_id' => $cabangId]);
jsonResponse(['items' => $stmt->fetchAll()]);
