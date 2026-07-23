<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
jsonResponse([
    'id' => (int) $user['id'],
    'nama' => $user['nama'],
    'username' => $user['username'],
    'peran' => $user['peran'],
    'cabang_id' => $user['cabang_id'] ? (int) $user['cabang_id'] : null,
]);
