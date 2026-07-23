<?php
// Diperlukan di baris pertama SETIAP file di dalam folder /api.
// Mengatur header CORS (supaya frontend di domain/port lain boleh memanggil API ini),
// lalu memuat helper koneksi database, respons JSON, autentikasi, dan rentang waktu.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rentang.php';
