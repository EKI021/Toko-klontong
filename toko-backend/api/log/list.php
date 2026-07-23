<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
$pdo = getDB();

if ($user['peran'] === 'super_admin') {
    $stmt = $pdo->query("SELECT * FROM log_aktivitas ORDER BY waktu DESC LIMIT 300");
} else {
    $stmt = $pdo->prepare("SELECT * FROM log_aktivitas WHERE cabang_id = :c ORDER BY waktu DESC LIMIT 300");
    $stmt->execute(['c' => $user['cabang_id']]);
}
jsonResponse(['log' => $stmt->fetchAll()]);
