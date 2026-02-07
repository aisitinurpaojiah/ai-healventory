<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';
include '../includes/header.php';
include '../includes/stock_helper.php';

// statistik singkat
$total_obat = $pdo->query("SELECT COUNT(*) FROM obat")->fetchColumn();
$obat_masuk = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE jenis='masuk'")->fetchColumn();
$obat_keluar = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE jenis='keluar'")->fetchColumn();

// obat menipis (count)
$obat_menipis = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT o.id,
        (o.stok_awal
          + COALESCE(SUM(CASE WHEN t.jenis='masuk' THEN t.jumlah END),0)
          - COALESCE(SUM(CASE WHEN t.jenis='keluar' THEN t.jumlah END),0)
        ) AS stok_akhir,
        o.stok_minimum
        FROM obat o
        LEFT JOIN transaksi t ON t.id_obat = o.id
        GROUP BY o.id
        HAVING stok_akhir < o.stok_minimum
    ) m
")->fetchColumn();

// ambil notifikasi unread dari tabel (tampil di panel)
$notifStmt = $pdo->prepare("
    SELECT pesan FROM notifikasi WHERE status = 'unread' ORDER BY tanggal DESC LIMIT 6
");
$notifStmt->execute();
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container">
    <?php include '../includes/sidebar_manager.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Menejer</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <!-- Kartu Statistik -->
        <section class="cards">
            <div class="card">
                <i class="bi bi-capsule"></i>
                <p>Jumlah Obat</p>
                <h3><?= (int)$total_obat ?></h3>
            </div>
            <div class="card">
                <i class="bi bi-check-circle"></i>
                <p>Obat Masuk</p>
                <h3><?= (int)$obat_masuk ?></h3>
            </div>
            <div class="card">
                <i class="bi bi-arrow-up"></i>
                <p>Obat Keluar</p>
                <h3><?= (int)$obat_keluar ?></h3>
            </div>
            <div class="card">
                <i class="bi bi-exclamation-triangle"></i>
                <p>Obat Menipis</p>
                <h3><?= (int)$obat_menipis ?></h3>
            </div>
        </section>

        <!-- Filter tanggal untuk chart -->
        <section class="table-section filter-box2" style="align-items:center;">
            <label for="startDate">Dari:</label>
            <input type="date" id="startDate" />
            <label for="endDate">Sampai:</label>
            <input type="date" id="endDate" />
            <button id="btnFilter" class="btn-primary">Filter Transaksi</button>
            <button id="btnResetFilter" class="btn-outline" style="display:none;">
                Reset
            </button>
        </section>

        <!-- Grafik & Notifikasi -->
        <section class="dashboard-content">
            <div class="chart" style="height:370px;">
                <h4>Grafik Stok</h4>
                <canvas id="stokChart"></canvas>
            </div>

            <div class="notif">
                <h4>Notifikasi Terbaru</h4>
                <div class="notif-body">
                    <ul>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $n): ?>
                                <li><?= htmlspecialchars($n['pesan']) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li style="text-align:center; color:#999;">
                                <i class="bi bi-check-circle"></i> Tidak ada notifikasi
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Transaksi Terakhir -->
        <section class="table-section">
            <h4>Transaksi Terakhir</h4>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Obat</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $transaksi = $pdo->query("
                        SELECT t.tgl_transaksi, o.nama AS nama_obat, t.jenis, t.jumlah
                        FROM transaksi t
                        JOIN obat o ON o.id = t.id_obat
                        ORDER BY t.tgl_transaksi DESC LIMIT 5
                    ");
                    while ($row = $transaksi->fetch()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tgl_transaksi']))) ?></td>
                            <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                            <td>
                                <span style="color: <?= $row['jenis'] == 'masuk' ? 'green' : 'red' ?>">
                                    <?= htmlspecialchars(ucfirst($row['jenis'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['jumlah']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- chart script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= $basePath ?>assets/js/dashboard_chart.js"></script>

<?php include '../includes/footer.php'; ?>