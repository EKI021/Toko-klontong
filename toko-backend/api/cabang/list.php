<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
$pdo = getDB();

if ($user['peran'] === 'super_admin') {
    $stmt = $pdo->query("SELECT * FROM cabang ORDER BY nama_cabang");
    jsonResponse(['cabang' => $stmt->fetchAll()]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cabang WHERE id = :id");
    $stmt->execute(['id' => $user['cabang_id']]);
    $row = $stmt->fetch();
    jsonResponse(['cabang' => $row ? [$row] : []]);
}
