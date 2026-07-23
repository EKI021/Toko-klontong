<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin', 'kasir', 'gudang']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$items = $input['items'] ?? [];
$cabangId = resolveCabangId($user, isset($input['cabang_id']) ? (int) $input['cabang_id'] : null);

if (!is_array($items) || count($items) === 0) jsonError('Keranjang kosong', 400);

$pdo = getDB();
$pdo->beginTransaction();
$total = 0;
$receiptItems = [];
try {
    foreach ($items as $it) {
        $barangId = (int) ($it['barang_id'] ?? 0);
        $qty = (int) ($it['qty'] ?? 0);
        if (!$barangId || $qty <= 0) continue;

        $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
        $stmt->execute(['id' => $barangId]);
        $item = $stmt->fetch();
        if (!$item) continue;

        $pdo->prepare(
            "INSERT INTO stok (cabang_id, barang_id, jumlah) VALUES (:c,:b,0)
             ON DUPLICATE KEY UPDATE jumlah = jumlah"
        )->execute(['c' => $cabangId, 'b' => $barangId]);
        $pdo->prepare(
            "UPDATE stok SET jumlah = GREATEST(0, jumlah - :q) WHERE cabang_id = :c AND barang_id = :b"
        )->execute(['q' => $qty, 'c' => $cabangId, 'b' => $barangId]);

        $subtotal = $item['harga_jual'] * $qty;
        $total += $subtotal;
        $receiptItems[] = ['nama' => $item['nama'], 'qty' => $qty, 'harga' => (float) $item['harga_jual'], 'subtotal' => (float) $subtotal];

        $pdo->prepare(
            "INSERT INTO transaksi (cabang_id, barang_id, karyawan_id, jenis, jumlah, catatan, harga_beli_saat, harga_jual_saat)
             VALUES (:c,:b,:k,'keluar',:qty,'Checkout kasir',:hb,:hj)"
        )->execute(['c' => $cabangId, 'b' => $barangId, 'k' => $user['id'], 'qty' => $qty, 'hb' => $item['harga_beli'], 'hj' => $item['harga_jual']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Checkout gagal: ' . $e->getMessage(), 500);
}

if (count($receiptItems) === 0) jsonError('Tidak ada barang valid di keranjang', 400);

jsonResponse([
    'ok' => true,
    'total' => (float) $total,
    'items' => $receiptItems,
    'kasir' => $user['nama'],
    'waktu' => date('c'),
]);
