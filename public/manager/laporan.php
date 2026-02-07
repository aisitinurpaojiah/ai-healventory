<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';
include '../includes/header.php';

// Ambil data laporan dari database
try {
    // Query untuk mengambil semua data obat dengan info transaksi
    $query = "
        SELECT 
            o.id,
            o.kode_obat,
            o.nama AS nama_obat,
            o.kategori,
            o.stok_awal,
            o.stok_minimum,
            COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END), 0) as total_masuk,
            COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END), 0) as total_keluar,
            (o.stok_awal + COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END), 0)) as stok_akhir,
            o.tgl_kadaluarsa
        FROM obat o
        LEFT JOIN transaksi t ON o.id = t.id_obat
        GROUP BY o.id, o.kode_obat, o.nama, o.kategori, o.stok_awal, o.stok_minimum, o.tgl_kadaluarsa
        ORDER BY o.nama ASC
    ";

    $stmt = $pdo->query($query);
    $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $laporan = [];
    $error_message = "Error: " . $e->getMessage();
}

// Hitung total keseluruhan
$total_obat = count($laporan);
$grand_total_masuk = array_sum(array_column($laporan, 'total_masuk'));
$grand_total_keluar = array_sum(array_column($laporan, 'total_keluar'));
$grand_total_stok = array_sum(array_column($laporan, 'stok_akhir'));
?>

<div class="container">
    <?php include '../includes/sidebar_manager.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Menejer</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <section class="cards">
            <div class="card">
                <i class="bi bi-capsule"></i>
                <p>Total Obat</p>
                <h3><?= $total_obat ?></h3>
            </div>
            <div class="card">
                <i class="bi bi-box-arrow-in-down"></i>
                <p>Total Pemasukan</p>
                <h3><?= $grand_total_masuk ?></h3>
            </div>
            <div class="card">
                <i class="bi bi-box-arrow-up"></i>
                <p>Total Pengeluaran</p>
                <h3><?= $grand_total_keluar ?></h3>
            </div>
        </section>

        <!-- Tabel Laporan -->
        <section class="table-section" id="laporanTable">
            <h4>Detail Laporan Stok Obat</h4>
            <p class="text-muted">Tanggal Cetak: <?= date('d-m-Y H:i:s') ?></p>

            <div class="table-responsive">
                <table class="table-laporan">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Obat</th>
                            <th>Nama Obat</th>
                            <th>Stok Awal</th>
                            <th>Pemasukan</th>
                            <th>Pengeluaran</th>
                            <th>Stok Akhir</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($laporan) > 0): ?>
                            <?php foreach ($laporan as $index => $row): ?>
                                <?php
                                // Tentukan status stok
                                $status = '';
                                $status_class = '';
                                if ($row['stok_akhir'] <= 0) {
                                    $status = 'Habis';
                                    $status_class = 'status-habis';
                                } elseif ($row['stok_akhir'] < $row['stok_minimum']) {
                                    $status = 'Menipis';
                                    $status_class = 'status-menipis';
                                } else {
                                    $status = 'Aman';
                                    $status_class = 'status-aman';
                                }
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['kode_obat']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                                    <td class="text-center"><?= $row['stok_awal'] ?></td>
                                    <td class="text-center text-success"><?= $row['total_masuk'] ?></td>
                                    <td class="text-center text-danger"><?= $row['total_keluar'] ?></td>
                                    <td class="text-center font-weight-bold"><?= $row['stok_akhir'] ?></td>
                                    <td>
                                        <span class="badge <?= $status_class ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Total Row -->
                            <tr class="total-row">
                                <td colspan="3" class="text-center"></td>
                                <td class="text-center"><strong>TOTAL</strong></td>
                                <td class="text-center text-success"><strong><?= $grand_total_masuk ?></strong></td>
                                <td class="text-center text-danger"><strong><?= $grand_total_keluar ?></strong></td>
                                <td class="text-center text-info"><strong><?= $grand_total_stok ?></strong></td>
                                <td colspan="1"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data laporan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="laporan-header">
                    <h2>Laporan Obat</h2>
                    <div class="laporan-actions">
                        <button class="btn-primary" id="btnCetakPDF">
                            <i class="bi bi-file-pdf"></i> Cetak PDF
                        </button>
                        <button class="btn-outline" id="btnExportExcel">
                            <i class="bi bi-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/laporan.js"></script>