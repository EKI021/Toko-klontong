<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin', 'gudang']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$barangId = (int) ($input['barang_id'] ?? 0);
$jenis = $input['jenis'] ?? '';
$jumlah = (int) ($input['jumlah'] ?? 0);
$catatan = trim($input['catatan'] ?? '');
$cabangId = resolveCabangId($user, isset($input['cabang_id']) ? (int) $input['cabang_id'] : null);

if (!$barangId || !in_array($jenis, ['masuk', 'keluar'], true) || $jumlah <= 0) {
    jsonError('Data tidak lengkap atau tidak valid', 400);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
$stmt->execute(['id' => $barangId]);
$item = $stmt->fetch();
if (!$item) jsonError('Barang tidak ditemukan', 404);

$pdo->beginTransaction();
try {
    $pdo->prepare(
        "INSERT INTO stok (cabang_id, barang_id, jumlah) VALUES (:c,:b,0)
         ON DUPLICATE KEY UPDATE jumlah = jumlah"
    )->execute(['c' => $cabangId, 'b' => $barangId]);

    $delta = $jenis === 'masuk' ? $jumlah : -$jumlah;
    $pdo->prepare(
        "UPDATE stok SET jumlah = GREATEST(0, jumlah + :d) WHERE cabang_id = :c AND barang_id = :b"
    )->execute(['d' => $delta, 'c' => $cabangId, 'b' => $barangId]);

    $pdo->prepare(
        "INSERT INTO transaksi (cabang_id, barang_id, karyawan_id, jenis, jumlah, catatan, harga_beli_saat, harga_jual_saat)
         VALUES (:c,:b,:k,:jenis,:jumlah,:catatan,:hb,:hj)"
    )->execute([
        'c' => $cabangId, 'b' => $barangId, 'k' => $user['id'], 'jenis' => $jenis, 'jumlah' => $jumlah,
        'catatan' => $catatan ?: null, 'hb' => $item['harga_beli'], 'hj' => $item['harga_jual'],
    ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Gagal menyimpan transaksi: ' . $e->getMessage(), 500);
}

jsonResponse(['ok' => true]);
