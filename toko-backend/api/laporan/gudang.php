<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = currentUser();
$cabangId = resolveCabangId($user, isset($_GET['cabang_id']) ? (int) $_GET['cabang_id'] : null);

$range = $_GET['range'] ?? '30';
[$klausa, $params] = klausaRentang($range, $_GET['start'] ?? null, $_GET['end'] ?? null);
$params['c'] = $cabangId;

$pdo = getDB();
$sql = "
    SELECT b.id, b.kode, b.nama,
           SUM(CASE WHEN t.jenis='masuk' THEN t.jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN t.jenis='keluar' THEN t.jumlah ELSE 0 END) AS keluar
    FROM transaksi t JOIN barang b ON b.id = t.barang_id
    WHERE t.cabang_id = :c AND $klausa
    GROUP BY b.id, b.kode, b.nama
    ORDER BY (SUM(t.jumlah)) DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalMasuk = 0;
$totalKeluar = 0;
foreach ($rows as &$r) {
    $r['masuk'] = (int) $r['masuk'];
    $r['keluar'] = (int) $r['keluar'];
    $totalMasuk += $r['masuk'];
    $totalKeluar += $r['keluar'];

    $s = $pdo->prepare("SELECT jumlah FROM stok WHERE cabang_id = :c AND barang_id = :b");
    $s->execute(['c' => $cabangId, 'b' => $r['id']]);
    $srow = $s->fetch();
    $r['saldo'] = $srow ? (int) $srow['jumlah'] : 0;
}
unset($r);

$stmtCount = $pdo->prepare("SELECT COUNT(*) AS n FROM transaksi t WHERE t.cabang_id = :c AND $klausa");
$stmtCount->execute($params);
$totalTx = (int) $stmtCount->fetch()['n'];

jsonResponse([
    'rows' => $rows,
    'total_masuk' => $totalMasuk,
    'total_keluar' => $totalKeluar,
    'netto' => $totalMasuk - $totalKeluar,
    'total_transaksi' => $totalTx,
]);
