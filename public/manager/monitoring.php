<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';
include '../includes/header.php';

/* =========================
   Data monitoring (tables)
   ========================= */
$obat_kadaluarsa = $pdo->query("
    SELECT 
        o.*,
        (
            o.stok_awal
            + COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah END), 0)
            - COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah END), 0)
        ) AS stok_akhir
    FROM obat o
    LEFT JOIN transaksi t ON t.id_obat = o.id
    WHERE o.tgl_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    GROUP BY o.id
    ORDER BY o.tgl_kadaluarsa ASC
")->fetchAll();

// Obat stok menipis (real-time)
$obat_menipis = $pdo->query("
    SELECT 
        o.id,
        o.kode_obat,
        o.nama,
        o.stok_minimum,
        (
            o.stok_awal
            + COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah END), 0)
            - COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah END), 0)
        ) AS stok_akhir
    FROM obat o
    LEFT JOIN transaksi t ON t.id_obat = o.id
    GROUP BY o.id
    HAVING stok_akhir < o.stok_minimum
    ORDER BY stok_akhir ASC
")->fetchAll();

/* =========================
   Chart data (6 months)
   ========================= */

// Base total stok_awal (sum of all stok_awal)
$baseStockRow = $pdo->query("SELECT COALESCE(SUM(stok_awal),0) AS base_stock FROM obat")->fetch(PDO::FETCH_ASSOC);
$base_stock = (int)$baseStockRow['base_stock'];

// prepare statements for monthly and cumulative sums
$stmt_monthly = $pdo->prepare("
    SELECT 
      COALESCE(SUM(CASE WHEN jenis = :jenis THEN jumlah END), 0) AS total
    FROM transaksi
    WHERE DATE(tgl_transaksi) BETWEEN :start AND :end
");

$stmt_cumulative = $pdo->prepare("
    SELECT 
      COALESCE(SUM(CASE WHEN jenis = :jenis THEN jumlah END), 0) AS total
    FROM transaksi
    WHERE DATE(tgl_transaksi) <= :end
");

// build last 6 months labels (include current month)
$labels = [];
$monthlyIn = [];   // per-month masuk
$monthlyOut = [];  // per-month keluar
$totalStockAtEnd = []; // total stock 

$now = new DateTime();
$startMonth = (clone $now)->modify('first day of this month'); // current month start
$startMonth = $startMonth->modify('-5 months');

for ($i = 0; $i < 6; $i++) {
    $mStart = (clone $startMonth)->modify("+{$i} months")->setTime(0, 0, 0);
    $mEnd = (clone $mStart)->modify('last day of this month')->setTime(23, 59, 59);

    // label (Month name in Indonesian short)
    setlocale(LC_TIME, 'id_ID.UTF-8');
    // fallback: use English if locale not available
    $label = strftime('%B', $mStart->getTimestamp());
    if (empty($label) || strpos($label, '%') !== false) {
        // fallback to manual month names (Indonesian)
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $label = $monthNames[(int)$mStart->format('n') - 1];
    }
    $labels[] = $label;

    // monthly totals (within month)
    // masuk
    $stmt_monthly->execute([
        ':jenis' => 'masuk',
        ':start' => $mStart->format('Y-m-d'),
        ':end'   => $mEnd->format('Y-m-d')
    ]);
    $rowIn = $stmt_monthly->fetch(PDO::FETCH_ASSOC);
    $monthIn = (int)$rowIn['total'];

    // keluar
    $stmt_monthly->execute([
        ':jenis' => 'keluar',
        ':start' => $mStart->format('Y-m-d'),
        ':end'   => $mEnd->format('Y-m-d')
    ]);
    $rowOut = $stmt_monthly->fetch(PDO::FETCH_ASSOC);
    $monthOut = (int)$rowOut['total'];

    $monthlyIn[] = $monthIn;
    $monthlyOut[] = $monthOut;

    // cumulative up to end of month
    $stmt_cumulative->execute([':jenis' => 'masuk', ':end' => $mEnd->format('Y-m-d')]);
    $cumIn = (int)$stmt_cumulative->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt_cumulative->execute([':jenis' => 'keluar', ':end' => $mEnd->format('Y-m-d')]);
    $cumOut = (int)$stmt_cumulative->fetch(PDO::FETCH_ASSOC)['total'];

    // total stock at end = base_stock + cumIn - cumOut
    $stokAtEnd = $base_stock + $cumIn - $cumOut;
    $totalStockAtEnd[] = $stokAtEnd;
}

// JSON encode for JS
$js_labels = json_encode($labels);
$js_monthly_in = json_encode($monthlyIn);
$js_monthly_out = json_encode($monthlyOut);
$js_total_stock = json_encode($totalStockAtEnd);
?>

<div class="container">
    <?php include '../includes/sidebar_manager.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Menejer</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <section class="table-section" style="margin-bottom:20px;">
            <h3 style="text-align: center;">Grafik Monitoring 6 Bulan Terakhir</h3>
            <div style="position:relative; height:320px; padding:10px;">
                <canvas id="monitorChart" style="width:100%; height:100%;"></canvas>
            </div>
        </section>

        <!-- ================= OBAT KADALUARSA ================= -->
        <section class="table-section">
            <h3>⚠️ Obat Mendekati Kadaluarsa (30 Hari)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Obat</th>
                        <th>Stok Saat Ini</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Sisa Hari</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($obat_kadaluarsa) > 0): ?>
                        <?php foreach ($obat_kadaluarsa as $obat): ?>
                            <?php
                            $today = new DateTime();
                            $exp_date = new DateTime($obat['tgl_kadaluarsa']);
                            $diff = $today->diff($exp_date);
                            $days_left = $diff->days;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($obat['kode_obat']) ?></td>
                                <td><?= htmlspecialchars($obat['nama']) ?></td>
                                <td><?= $obat['stok_akhir'] ?></td>
                                <td><?= date('d-m-Y', strtotime($obat['tgl_kadaluarsa'])) ?></td>
                                <td>
                                    <span class="badge <?= $days_left < 7 ? 'status-habis' : 'status-menipis' ?>">
                                        <?= $days_left ?> hari
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">
                                Tidak ada obat yang mendekati kadaluarsa
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- ================= STOK MENIPIS ================= -->
        <section class="table-section" style="margin-top:20px;">
            <h3>📉 Obat dengan Stok Menipis</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Obat</th>
                        <th>Stok Saat Ini</th>
                        <th>Stok Minimum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($obat_menipis) > 0): ?>
                        <?php foreach ($obat_menipis as $obat): ?>
                            <tr>
                                <td><?= htmlspecialchars($obat['kode_obat']) ?></td>
                                <td><?= htmlspecialchars($obat['nama']) ?></td>
                                <td><?= $obat['stok_akhir'] ?></td>
                                <td><?= $obat['stok_minimum'] ?></td>
                                <td>
                                    <span class="badge <?= $obat['stok_akhir'] == 0 ? 'status-habis' : 'status-menipis' ?>">
                                        <?= $obat['stok_akhir'] == 0 ? 'Habis' : 'Menipis' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:green;">
                                Semua stok obat dalam kondisi aman
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labels = <?= $js_labels; ?>;
    const monthlyIn = <?= $js_monthly_in; ?>;
    const monthlyOut = <?= $js_monthly_out; ?>;
    const totalStock = <?= $js_total_stock; ?>;

    // draw chart
    const ctx = document.getElementById('monitorChart').getContext('2d');

    const monitorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                    label: 'Total Stock (akhir bulan)',
                    data: totalStock,
                    borderColor: '#0D47A1',
                    backgroundColor: 'rgba(9,77,171,0.05)',
                    tension: 0.25,
                    fill: false,
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Masuk',
                    data: monthlyIn,
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(30,136,229,0.05)',
                    tension: 0.25,
                    fill: false,
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Keluar',
                    data: monthlyOut,
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244,67,54,0.05)',
                    tension: 0.25,
                    fill: false,
                    pointRadius: 4,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            stacked: false,
            plugins: {
                title: {
                    display: true,
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Jumlah (unit)'
                    },
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>