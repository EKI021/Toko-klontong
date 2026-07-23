<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$user = requireRole(['super_admin', 'admin', 'gudang']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Metode tidak diizinkan', 405);

$input = getJsonInput();
$id = isset($input['id']) && $input['id'] !== '' ? (int) $input['id'] : null;
$nama = trim($input['nama'] ?? '');
if ($nama === '') jsonError('Nama barang wajib diisi', 400);

$kategori = trim($input['kategori'] ?? '');
$satuan = trim($input['satuan'] ?? '') ?: 'pcs';
$hargaBeli = (float) ($input['harga_beli'] ?? 0);
$hargaJual = (float) ($input['harga_jual'] ?? 0);
$stokMinimum = (int) ($input['stok_minimum'] ?? 0);
$barcode = trim($input['barcode'] ?? '') ?: null;
$kode = trim($input['kode'] ?? '');
$stokAwal = (int) ($input['stok'] ?? 0);
$cabangId = resolveCabangId($user, isset($input['cabang_id']) ? (int) $input['cabang_id'] : null);

$pdo = getDB();

if ($id) {
    // --- EDIT BARANG ---
    $old = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
    $old->execute(['id' => $id]);
    $oldItem = $old->fetch();
    if (!$oldItem) jsonError('Barang tidak ditemukan', 404);

    if ($kode === '') $kode = $oldItem['kode'];

    $pdo->prepare(
        "UPDATE barang SET kode=:kode, nama=:nama, kategori=:kategori, satuan=:satuan,
         harga_beli=:hb, harga_jual=:hj, stok_minimum=:mn, barcode=:barcode WHERE id=:id"
    )->execute([
        'kode' => $kode, 'nama' => $nama, 'kategori' => $kategori, 'satuan' => $satuan,
        'hb' => $hargaBeli, 'hj' => $hargaJual, 'mn' => $stokMinimum, 'barcode' => $barcode, 'id' => $id,
    ]);

    $changes = [];
    if ((float) $oldItem['harga_beli'] !== $hargaBeli) {
        $changes[] = "Harga Beli: Rp" . number_format((float)$oldItem['harga_beli'], 0, ',', '.') . " → Rp" . number_format($hargaBeli, 0, ',', '.');
    }
    if ((float) $oldItem['harga_jual'] !== $hargaJual) {
        $changes[] = "Harga Jual: Rp" . number_format((float)$oldItem['harga_jual'], 0, ',', '.') . " → Rp" . number_format($hargaJual, 0, ',', '.');
    }
    if ($oldItem['nama'] !== $nama) $changes[] = "Nama: {$oldItem['nama']} → {$nama}";
    if ((int)$oldItem['stok_minimum'] !== $stokMinimum) $changes[] = "Stok Minimum: {$oldItem['stok_minimum']} → {$stokMinimum}";

    if (!empty($changes)) {
        $hargaBerubah = false;
        foreach ($changes as $c) { if (str_starts_with($c, 'Harga')) { $hargaBerubah = true; break; } }
        logAktivitas($pdo, $user, $cabangId, $hargaBerubah ? 'Ubah Harga' : 'Edit Barang', "$nama — " . implode('; ', $changes));
    }

    jsonResponse(['ok' => true, 'id' => $id, 'kode' => $kode]);
} else {
    // --- TAMBAH BARANG BARU ---
    if ($kode === '') {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $kategori ?: 'BRG'), 0, 3)) ?: 'BRG';
        do {
            $kode = $prefix . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $chk = $pdo->prepare("SELECT id FROM barang WHERE kode = :k");
            $chk->execute(['k' => $kode]);
        } while ($chk->fetch());
    }

    $pdo->prepare(
        "INSERT INTO barang (kode, nama, kategori, satuan, harga_beli, harga_jual, stok_minimum, barcode)
         VALUES (:kode,:nama,:kategori,:satuan,:hb,:hj,:mn,:barcode)"
    )->execute([
        'kode' => $kode, 'nama' => $nama, 'kategori' => $kategori, 'satuan' => $satuan,
        'hb' => $hargaBeli, 'hj' => $hargaJual, 'mn' => $stokMinimum, 'barcode' => $barcode,
    ]);
    $barangId = (int) $pdo->lastInsertId();

    if ($stokAwal > 0) {
        $pdo->prepare(
            "INSERT INTO stok (cabang_id, barang_id, jumlah) VALUES (:c,:b,:j)
             ON DUPLICATE KEY UPDATE jumlah = jumlah + :j2"
        )->execute(['c' => $cabangId, 'b' => $barangId, 'j' => $stokAwal, 'j2' => $stokAwal]);
    }

    logAktivitas($pdo, $user, $cabangId, 'Tambah Barang', "$nama (kode $kode)");
    jsonResponse(['ok' => true, 'id' => $barangId, 'kode' => $kode]);
}
