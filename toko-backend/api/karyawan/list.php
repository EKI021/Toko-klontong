<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
$pdo = getDB();

if ($user['peran'] === 'super_admin') {
    $stmt = $pdo->query("SELECT id, cabang_id, nama, username, peran, aktif FROM karyawan ORDER BY nama");
} else {
    $stmt = $pdo->prepare("SELECT id, cabang_id, nama, username, peran, aktif FROM karyawan WHERE cabang_id = :c ORDER BY nama");
    $stmt->execute(['c' => $user['cabang_id']]);
}
jsonResponse(['karyawan' => $stmt->fetchAll()]);
