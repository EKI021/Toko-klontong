<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function getBearerToken(): ?string {
    $authHeader = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'authorization') { $authHeader = $v; break; }
        }
    }
    if ($authHeader === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if ($authHeader !== '' && preg_match('/Bearer\s+(.+)$/i', $authHeader, $m)) {
        return trim($m[1]);
    }
    return null;
}

// Mengembalikan baris karyawan yang sedang login (dari token), atau menghentikan
// request dengan error 401 kalau token tidak ada/tidak valid/kadaluarsa.
function currentUser(): array {
    $token = getBearerToken();
    if (!$token) jsonError('Token tidak ditemukan. Silakan login.', 401);

    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT s.kadaluarsa_pada, k.* FROM sesi_login s
         JOIN karyawan k ON k.id = s.karyawan_id
         WHERE s.token = :token"
    );
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    if (!$row) jsonError('Sesi tidak valid. Silakan login ulang.', 401);
    if (strtotime($row['kadaluarsa_pada']) < time()) {
        $pdo->prepare("DELETE FROM sesi_login WHERE token = :token")->execute(['token' => $token]);
        jsonError('Sesi sudah kadaluarsa. Silakan login ulang.', 401);
    }
    if (!$row['aktif']) jsonError('Akun ini sudah dinonaktifkan.', 403);

    return $row;
}

function requireRole(array $allowedRoles): array {
    $user = currentUser();
    if (!in_array($user['peran'], $allowedRoles, true)) {
        jsonError('Kamu tidak punya akses untuk aksi ini.', 403);
    }
    return $user;
}

// Menentukan cabang_id yang berlaku untuk request ini.
// super_admin WAJIB mengirim cabang_id (karena dia bisa mengakses banyak cabang).
// Peran lain dipaksa memakai cabang_id miliknya sendiri (tidak bisa akses cabang lain).
function resolveCabangId(array $user, ?int $requestedCabangId): int {
    if ($user['peran'] === 'super_admin') {
        if (!$requestedCabangId) jsonError('Parameter cabang_id wajib diisi untuk akun Super Admin.', 400);
        return $requestedCabangId;
    }
    if (!$user['cabang_id']) jsonError('Akun ini tidak terhubung ke cabang manapun.', 400);
    return (int) $user['cabang_id'];
}

function logAktivitas(PDO $pdo, array $user, ?int $cabangId, string $aksi, string $detail): void {
    $stmt = $pdo->prepare(
        "INSERT INTO log_aktivitas (cabang_id, karyawan_id, nama_karyawan_saat, aksi, detail)
         VALUES (:cabang_id, :karyawan_id, :nama, :aksi, :detail)"
    );
    $stmt->execute([
        'cabang_id' => $cabangId,
        'karyawan_id' => $user['id'],
        'nama' => $user['nama'],
        'aksi' => $aksi,
        'detail' => $detail,
    ]);
}
