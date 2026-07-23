<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') jsonError('Username dan password wajib diisi', 400);

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM karyawan WHERE username = :u LIMIT 1");
$stmt->execute(['u' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Username atau password salah', 401);
}
if (!$user['aktif']) jsonError('Akun ini sudah dinonaktifkan', 403);

$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 12); // sesi berlaku 12 jam

$pdo->prepare("INSERT INTO sesi_login (token, karyawan_id, kadaluarsa_pada) VALUES (:t, :k, :e)")
    ->execute(['t' => $token, 'k' => $user['id'], 'e' => $expiry]);

logAktivitas($pdo, $user, $user['cabang_id'] ? (int)$user['cabang_id'] : null, 'Login', $user['nama'] . ' masuk ke sistem');

jsonResponse([
    'token' => $token,
    'user' => [
        'id' => (int) $user['id'],
        'nama' => $user['nama'],
        'username' => $user['username'],
        'peran' => $user['peran'],
        'cabang_id' => $user['cabang_id'] ? (int) $user['cabang_id'] : null,
    ],
]);
