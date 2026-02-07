<?php
// public/chart_data.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

/*
  Pastikan variabel koneksi:
  - $pdo  (PDO)  ATAU
  - $conn (mysqli/PDO)
*/
if (!isset($pdo)) {
    if (isset($conn)) {
        $pdo = $conn;
    }
}

if (!isset($pdo)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database ($pdo) tidak ditemukan'
    ]);
    exit;
}

try {
    // cek filter tanggal
    $useFilter = false;
    if (!empty($_GET['from']) && !empty($_GET['to'])) {
        $from = $_GET['from'];
        $to   = $_GET['to'];
        $useFilter = true;
    }

    // label minggu (FIXED ORDER)
    $labels = [
        'Minggu ke-1',
        'Minggu ke-2',
        'Minggu ke-3',
        'Minggu ke-4'
    ];

    // query utama (SUDAH DISESUAIKAN DB ANDA)
    $sql = "
        SELECT
            CASE
                WHEN DAY(tgl_transaksi) BETWEEN 1 AND 7 THEN 'Minggu ke-1'
                WHEN DAY(tgl_transaksi) BETWEEN 8 AND 14 THEN 'Minggu ke-2'
                WHEN DAY(tgl_transaksi) BETWEEN 15 AND 21 THEN 'Minggu ke-3'
                ELSE 'Minggu ke-4'
            END AS periode,
            SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) AS masuk,
            SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) AS keluar
        FROM transaksi
        WHERE 1=1
    ";

    if ($useFilter) {
        $sql .= " AND DATE(tgl_transaksi) BETWEEN :from AND :to ";
    } else {
        $sql .= " AND MONTH(tgl_transaksi) = MONTH(CURDATE())
                  AND YEAR(tgl_transaksi)  = YEAR(CURDATE()) ";
    }

    $sql .= " GROUP BY periode ";

    $stmt = $pdo->prepare($sql);

    if ($useFilter) {
        $stmt->bindParam(':from', $from);
        $stmt->bindParam(':to', $to);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // default nilai 0
    $masuk  = array_fill(0, count($labels), 0);
    $keluar = array_fill(0, count($labels), 0);

    foreach ($rows as $row) {
        $index = array_search($row['periode'], $labels);
        if ($index !== false) {
            $masuk[$index]  = (int)$row['masuk'];
            $keluar[$index] = (int)$row['keluar'];
        }
    }

    echo json_encode([
        'status' => 'ok',
        'labels' => $labels,
        'masuk'  => $masuk,
        'keluar' => $keluar
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
