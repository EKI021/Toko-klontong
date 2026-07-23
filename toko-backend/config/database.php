<?php
// =========================================================
// KONFIGURASI DATABASE
// Sesuaikan 4 baris di bawah ini dengan server kamu.
// Untuk XAMPP/Laragon default: host=localhost, user=root, password kosong.
// =========================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'toko_kelontong');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Gagal terhubung ke database. Periksa config/database.php. Detail: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
