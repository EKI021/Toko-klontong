<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
$cabangId = resolveCabangId($user, isset($_GET['cabang_id']) ? (int) $_GET['cabang_id'] : null);

$range = $_GET['range'] ?? '30';
[$klausa, $params] = klausaRentang($range, $_GET['start'] ?? null, $_GET['end'] ?? null);
$params['c'] = $cabangId;

$pdo = getDB();

$sqlLaku = "
    SELECT b.nama, SUM(t.jumlah) AS qty, SUM(t.harga_jual_saat * t.jumlah) AS omzet
    FROM transaksi t JOIN barang b ON b.id = t.barang_id
    WHERE t.cabang_id = :c AND t.jenis='keluar' AND t.catatan='Checkout kasir' AND $klausa
    GROUP BY b.id, b.nama ORDER BY qty DESC LIMIT 8
";
$stmt = $pdo->prepare($sqlLaku);
$stmt->execute($params);
$laku = $stmt->fetchAll();
foreach ($laku as &$l) { $l['qty'] = (int) $l['qty']; $l['omzet'] = (float) $l['omzet']; }
unset($l);

$stmt = $pdo->prepare(
    "SELECT b.id, b.kode, b.nama, b.satuan, b.stok_minimum, COALESCE(s.jumlah,0) AS stok
     FROM barang b LEFT JOIN stok s ON s.barang_id = b.id AND s.cabang_id = :c"
);
$stmt->execute(['c' => $cabangId]);
$semua = $stmt->fetchAll();

$habis = array_values(array_filter($semua, fn($i) => (int) $i['stok'] <= 0));
$menipis = array_values(array_filter($semua, fn($i) => (int) $i['stok'] > 0 && (int) $i['stok'] <= (int) $i['stok_minimum']));

jsonResponse(['laku' => $laku, 'habis' => $habis, 'menipis' => $menipis]);
