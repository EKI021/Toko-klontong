<?php
// Menghasilkan [klausa_sql, params] berdasarkan pilihan rentang waktu.
// range: 'today' | 'all' | 'custom' | '7' | '30' | dst (jumlah hari)
function klausaRentang(string $range, ?string $start, ?string $end, string $kolomWaktu = 't.waktu'): array {
    if ($range === 'today') {
        return ["DATE($kolomWaktu) = CURDATE()", []];
    }
    if ($range === 'custom' && $start && $end) {
        return [
            "$kolomWaktu BETWEEN :rentang_start AND :rentang_end",
            ['rentang_start' => $start . ' 00:00:00', 'rentang_end' => $end . ' 23:59:59'],
        ];
    }
    if ($range === 'all') {
        return ['1=1', []];
    }
    $hari = max(1, (int) $range);
    return ["$kolomWaktu >= (NOW() - INTERVAL $hari DAY)", []];
}
