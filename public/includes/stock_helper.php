<?php

/**
 * STOCK HELPER
 * Helper functions untuk menghitung stok real dan manage notifikasi
 */

/**
 * Hitung stok real berdasarkan transaksi
 * @param PDO $pdo
 * @param int $id_obat
 * @return int
 */
function hitungStokReal($pdo, $id_obat)
{
    $query = "
        SELECT 
            o.stok_awal,
            COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END), 0) as total_masuk,
            COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END), 0) as total_keluar
        FROM obat o
        LEFT JOIN transaksi t ON o.id = t.id_obat
        WHERE o.id = ?
        GROUP BY o.id, o.stok_awal
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_obat]);
    $row = $stmt->fetch();

    if ($row) {
        return (int)($row['stok_awal'] + $row['total_masuk'] - $row['total_keluar']);
    }

    return 0;
}

/**
 * Update atau generate notifikasi stok menipis
 * @param PDO $pdo
 * @param int $id_obat
 */
function updateNotifikasiStok($pdo, $id_obat)
{
    // Ambil data obat
    $obat = $pdo->prepare("SELECT id, nama, stok_awal, stok_minimum FROM obat WHERE id = ?");
    $obat->execute([$id_obat]);
    $data = $obat->fetch();

    if (!$data) return;

    // Hitung stok real
    $stok_real = hitungStokReal($pdo, $id_obat);

    // Cek apakah sudah ada notifikasi untuk obat ini
    $check = $pdo->prepare("SELECT id_notif, status FROM notifikasi WHERE id_obat = ? AND jenis = 'stok'");
    $check->execute([$id_obat]);
    $notif = $check->fetch();

    // Jika stok menipis
    if ($stok_real < $data['stok_minimum']) {
        $pesan = "Stok obat {$data['nama']} menipis! Sisa: {$stok_real} (Min: {$data['stok_minimum']})";

        if ($notif) {
            // Update notifikasi existing
            $stmt = $pdo->prepare("UPDATE notifikasi SET pesan = ?, tanggal = NOW(), status = 'unread' WHERE id_notif = ?");
            $stmt->execute([$pesan, $notif['id_notif']]);
        } else {
            // Buat notifikasi baru
            $stmt = $pdo->prepare("INSERT INTO notifikasi (id_obat, jenis, pesan, tanggal, status) VALUES (?, 'stok', ?, NOW(), 'unread')");
            $stmt->execute([$id_obat, $pesan]);
        }
    } else {
        // Stok aman, hapus notifikasi jika ada
        if ($notif) {
            $stmt = $pdo->prepare("DELETE FROM notifikasi WHERE id_notif = ?");
            $stmt->execute([$notif['id_notif']]);
        }
    }
}

/**
 * Update semua notifikasi stok (untuk maintenance)
 * @param PDO $pdo
 */
function updateSemuaNotifikasiStok($pdo)
{
    $obats = $pdo->query("SELECT id FROM obat");
    while ($obat = $obats->fetch()) {
        updateNotifikasiStok($pdo, $obat['id']);
    }
}

/**
 * Update notifikasi kadaluarsa
 * @param PDO $pdo
 * @param int $id_obat
 */
function updateNotifikasiKadaluarsa($pdo, $id_obat)
{
    $obat = $pdo->prepare("SELECT id, nama, tgl_kadaluarsa FROM obat WHERE id = ?");
    $obat->execute([$id_obat]);
    $data = $obat->fetch();

    if (!$data) return;

    $today = new DateTime();
    $exp_date = new DateTime($data['tgl_kadaluarsa']);
    $diff = $today->diff($exp_date);

    // Cek apakah sudah ada notifikasi
    $check = $pdo->prepare("SELECT id_notif FROM notifikasi WHERE id_obat = ? AND jenis = 'kadaluarsa'");
    $check->execute([$id_obat]);
    $notif = $check->fetch();

    // Jika kurang dari 30 hari
    if ($diff->days <= 30 && $exp_date >= $today) {
        $pesan = "Obat {$data['nama']} akan kadaluarsa dalam {$diff->days} hari! (Exp: " . $exp_date->format('d-m-Y') . ")";

        if ($notif) {
            $stmt = $pdo->prepare("UPDATE notifikasi SET pesan = ?, tanggal = NOW(), status = 'unread' WHERE id_notif = ?");
            $stmt->execute([$pesan, $notif['id_notif']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notifikasi (id_obat, jenis, pesan, tanggal, status) VALUES (?, 'kadaluarsa', ?, NOW(), 'unread')");
            $stmt->execute([$id_obat, $pesan]);
        }
    } elseif ($exp_date < $today) {
        // Sudah kadaluarsa
        $pesan = "Obat {$data['nama']} SUDAH KADALUARSA! (Exp: " . $exp_date->format('d-m-Y') . ")";

        if ($notif) {
            $stmt = $pdo->prepare("UPDATE notifikasi SET pesan = ?, tanggal = NOW(), status = 'unread' WHERE id_notif = ?");
            $stmt->execute([$pesan, $notif['id_notif']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notifikasi (id_obat, jenis, pesan, tanggal, status) VALUES (?, 'kadaluarsa', ?, NOW(), 'unread')");
            $stmt->execute([$id_obat, $pesan]);
        }
    } else {
        // Masih aman, hapus notifikasi jika ada
        if ($notif) {
            $stmt = $pdo->prepare("DELETE FROM notifikasi WHERE id_notif = ?");
            $stmt->execute([$notif['id_notif']]);
        }
    }
}
