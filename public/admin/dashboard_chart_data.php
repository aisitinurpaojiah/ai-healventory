<?php
// dashboard_chart_data.php
header('Content-Type: application/json; charset=utf-8');

require '../../config/database.php';

$start = isset($_GET['start']) && $_GET['start'] ? $_GET['start'] : null;
$end = isset($_GET['end']) && $_GET['end'] ? $_GET['end'] : null;
date_default_timezone_set('Asia/Jakarta');

if (!$start || !$end) {
    // default: first day -> last day of current month
    $now = new DateTime();
    $start = $now->format('Y-m-01');
    $end = $now->format('Y-m-t');
}

// validate dates
try {
    $dStart = new DateTime($start);
    $dEnd = new DateTime($end);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}
if ($dStart > $dEnd) {
    // swap
    $tmp = $dStart;
    $dStart = $dEnd;
    $dEnd = $tmp;
}

// base stock
$baseRow = $pdo->query("SELECT COALESCE(SUM(stok_awal),0) AS base_stock FROM obat")->fetch(PDO::FETCH_ASSOC);
$baseStock = (int)$baseRow['base_stock'];

// prepare statements
$stmt_monthly = $pdo->prepare("
    SELECT COALESCE(SUM(CASE WHEN jenis = :jenis THEN jumlah END),0) AS total
    FROM transaksi
    WHERE DATE(tgl_transaksi) BETWEEN :start AND :end
");
$stmt_cumulative = $pdo->prepare("
    SELECT COALESCE(SUM(CASE WHEN jenis = :jenis THEN jumlah END),0) AS total
    FROM transaksi
    WHERE DATE(tgl_transaksi) <= :end
");

// compute weekly buckets starting from start date, each 7 days
$periodDays = (int)$dStart->diff($dEnd)->days + 1;
$weeks = (int)ceil($periodDays / 7);

$labels = [];
$masuk = [];
$keluar = [];
$totalStock = [];

for ($i = 0; $i < $weeks; $i++) {
    $ws = (clone $dStart)->modify("+" . ($i * 7) . " days")->setTime(0, 0, 0);
    $we = (clone $ws)->modify('+6 days')->setTime(23, 59, 59);
    if ($we > $dEnd) $we = (clone $dEnd)->setTime(23, 59, 59);

    $labels[] = "Minggu ke-" . ($i + 1);

    // monthly per-week totals
    $stmt_monthly->execute([':jenis' => 'masuk', ':start' => $ws->format('Y-m-d'), ':end' => $we->format('Y-m-d')]);
    $mIn = (int)$stmt_monthly->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt_monthly->execute([':jenis' => 'keluar', ':start' => $ws->format('Y-m-d'), ':end' => $we->format('Y-m-d')]);
    $mOut = (int)$stmt_monthly->fetch(PDO::FETCH_ASSOC)['total'];

    $masuk[] = $mIn;
    $keluar[] = $mOut;

    // cumulative up to end of week
    $stmt_cumulative->execute([':jenis' => 'masuk', ':end' => $we->format('Y-m-d')]);
    $cumIn = (int)$stmt_cumulative->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt_cumulative->execute([':jenis' => 'keluar', ':end' => $we->format('Y-m-d')]);
    $cumOut = (int)$stmt_cumulative->fetch(PDO::FETCH_ASSOC)['total'];

    $stokAtEnd = $baseStock + $cumIn - $cumOut;
    $totalStock[] = $stokAtEnd;
}

echo json_encode([
    'labels' => $labels,
    'masuk' => $masuk,
    'keluar' => $keluar,
    'total' => $totalStock,
]);
