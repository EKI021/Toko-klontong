<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin']);
$cabangId = resolveCabangId($user, isset($_GET['cabang_id']) ? (int) $_GET['cabang_id'] : null);

$range = $_GET['range'] ?? '30';
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
[$klausa, $params] = klausaRentang($range, $start, $end);
$params['c'] = $cabangId;

$pdo = getDB();
$sql = "
    SELECT b.id, b.nama, SUM(t.jumlah) AS qty,
           SUM(t.harga_jual_saat * t.jumlah) AS omzet,
           SUM(t.harga_beli_saat * t.jumlah) AS hpp
    FROM transaksi t JOIN barang b ON b.id = t.barang_id
    WHERE t.cabang_id = :c AND t.jenis='keluar' AND t.catatan='Checkout kasir' AND $klausa
    GROUP BY b.id, b.nama
    ORDER BY (SUM(t.harga_jual_saat * t.jumlah) - SUM(t.harga_beli_saat * t.jumlah)) DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalOmzet = 0;
$totalHpp = 0;
foreach ($rows as &$r) {
    $r['qty'] = (int) $r['qty'];
    $r['omzet'] = (float) $r['omzet'];
    $r['hpp'] = (float) $r['hpp'];
    $r['laba'] = $r['omzet'] - $r['hpp'];
    $r['margin'] = $r['omzet'] > 0 ? ($r['laba'] / $r['omzet'] * 100) : 0;
    $totalOmzet += $r['omzet'];
    $totalHpp += $r['hpp'];
}
unset($r);

jsonResponse([
    'rows' => $rows,
    'total_omzet' => $totalOmzet,
    'total_hpp' => $totalHpp,
    'total_laba' => $totalOmzet - $totalHpp,
    'margin_rata' => $totalOmzet > 0 ? (($totalOmzet - $totalHpp) / $totalOmzet * 100) : 0,
]);
