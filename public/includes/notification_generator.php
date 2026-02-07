<?php
// ===============================
// 1. HITUNG STOK TERKINI PER OBAT
// ===============================
$stokData = $pdo->query("
    SELECT 
        o.id,
        o.nama,
        o.stok_minimum,
        (
            o.stok_awal
            + COALESCE(SUM(CASE WHEN t.jenis='masuk' THEN t.jumlah END),0)
            - COALESCE(SUM(CASE WHEN t.jenis='keluar' THEN t.jumlah END),0)
        ) AS stok_akhir
    FROM obat o
    LEFT JOIN transaksi t ON t.id_obat = o.id
    GROUP BY o.id
")->fetchAll();

foreach ($stokData as $obat) {

    // ===============================
    // STOK MENIPIS / HABIS
    // ===============================
    if ($obat['stok_akhir'] <= $obat['stok_minimum']) {

        // Cek duplikat
        $cek = $pdo->prepare("
            SELECT COUNT(*) FROM notifikasi
            WHERE jenis='stok'
            AND id_obat=?
            AND status='unread'
        ");
        $cek->execute([$obat['id']]);

        if ($cek->fetchColumn() == 0) {
            $pesan = "Stok obat {$obat['nama']} menipis! Sisa: {$obat['stok_akhir']} (Min: {$obat['stok_minimum']})";

            $insert = $pdo->prepare("
                INSERT INTO notifikasi (id_obat, jenis, pesan, status)
                VALUES (?, 'stok', ?, 'unread')
            ");
            $insert->execute([$obat['id'], $pesan]);
        }
    } else {
        // ===============================
        // AUTO DELETE JIKA STOK AMAN
        // ===============================
        $hapus = $pdo->prepare("
            DELETE FROM notifikasi
            WHERE jenis='stok'
            AND id_obat=?
        ");
        $hapus->execute([$obat['id']]);
    }
}

// ===============================
// 2. NOTIFIKASI KADALUARSA
// ===============================
$kadaluarsa = $pdo->query("
    SELECT id, nama, tgl_kadaluarsa
    FROM obat
")->fetchAll();

foreach ($kadaluarsa as $obat) {

    $hari = (new DateTime())->diff(new DateTime($obat['tgl_kadaluarsa']))->days;
    $isUpcoming = $obat['tgl_kadaluarsa'] >= date('Y-m-d')
        && $obat['tgl_kadaluarsa'] <= date('Y-m-d', strtotime('+30 days'));

    if ($isUpcoming) {

        // Cek duplikat
        $cek = $pdo->prepare("
            SELECT COUNT(*) FROM notifikasi
            WHERE jenis='kadaluarsa'
            AND id_obat=?
            AND status='unread'
        ");
        $cek->execute([$obat['id']]);

        if ($cek->fetchColumn() == 0) {
            $pesan = "Obat {$obat['nama']} akan kadaluarsa dalam {$hari} hari! (Exp: {$obat['tgl_kadaluarsa']})";

            $insert = $pdo->prepare("
                INSERT INTO notifikasi (id_obat, jenis, pesan, status)
                VALUES (?, 'kadaluarsa', ?, 'unread')
            ");
            $insert->execute([$obat['id'], $pesan]);
        }
    } else {
        // ===============================
        // AUTO DELETE JIKA SUDAH AMAN
        // ===============================
        $hapus = $pdo->prepare("
            DELETE FROM notifikasi
            WHERE jenis='kadaluarsa'
            AND id_obat=?
        ");
        $hapus->execute([$obat['id']]);
    }
}
