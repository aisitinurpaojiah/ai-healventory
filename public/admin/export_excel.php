<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';

// Set header untuk download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Inventori_Obat_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data laporan
try {
    $query = "
        SELECT 
            o.id,
            o.kode_obat,
            o.nama AS nama_obat,
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
    die("Error: " . $e->getMessage());
}

$grand_total_masuk = array_sum(array_column($laporan, 'total_masuk'));
$grand_total_keluar = array_sum(array_column($laporan, 'total_keluar'));
$obat_keluar = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE jenis='keluar'")->fetchColumn();
$grand_total_stok = array_sum(array_column($laporan, 'stok_akhir'));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #1565c0;
            color: white;
            font-weight: bold;
        }

        .header {
            background-color: #e3f2fd;
            font-weight: bold;
            text-align: center;
        }

        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <table>
        <tr class="header">
            <td colspan="9" style="font-size: 18px; padding: 15px;">
                <strong>LAPORAN INVENTORI OBAT - HEALVENTORY</strong>
            </td>
        </tr>
        <tr>
            <td colspan="4"><strong>Tanggal Cetak:</strong> <?= date('d F Y, H:i:s') ?></td>
            <td colspan="5"><strong>Dicetak Oleh:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></td>
        </tr>
        <tr>
            <td colspan="4"><strong>Total Obat:</strong> <?= count($laporan) ?> item</td>
            <td colspan="5"><strong>Total Transaksi:</strong> <?= $grand_total_masuk + $grand_total_keluar ?> unit</td>
        </tr>
        <tr style="height: 10px;">
            <td colspan="9"></td>
        </tr>

        <!-- Header Tabel -->
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 10%;">Kode Obat</th>
            <th style="width: 20%;">Nama Obat</th>
            <th style="width: 8%;">Stok Awal</th>
            <th style="width: 10%;">Pemasukan</th>
            <th style="width: 10%;">Pengeluaran</th>
            <th style="width: 10%;">Stok Akhir</th>
            <th style="width: 10%;">Status</th>
            <th style="width: 12%;">Kadaluarsa</th>
        </tr>

        <!-- Data -->
        <?php foreach ($laporan as $index => $row): ?>
            <?php
            // Tentukan status stok
            $status = '';
            if ($row['stok_akhir'] <= 0) {
                $status = 'Habis';
            } elseif ($row['stok_akhir'] < $row['stok_minimum']) {
                $status = 'Menipis';
            } else {
                $status = 'Aman';
            }
            ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($row['kode_obat']) ?></td>
                <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                <td class="text-center"><?= $row['stok_awal'] ?></td>
                <td class="text-center"><?= $row['total_masuk'] ?></td>
                <td class="text-center"><?= $row['total_keluar'] ?></td>
                <td class="text-center"><?= $row['stok_akhir'] ?></td>
                <td class="text-center"><?= $status ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])) ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Total Row -->
        <tr class="total-row">
            <td colspan="3" class="text-center"></td>
            <td class="text-center"><strong>TOTAL</strong></td>
            <td class="text-center"><strong><?= $grand_total_masuk ?></strong></td>
            <td class="text-center"><strong><?= $grand_total_keluar ?></strong></td>
            <td class="text-center"><strong><?= $grand_total_stok ?></strong></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
        </tr>

        <tr style="height: 10px;">
            <td colspan="9"></td>
        </tr>

        <!-- Summary -->
        <tr>
            <td colspan="4"><strong>Total Pemasukan:</strong> <?= $grand_total_masuk ?> unit</td>
            <td colspan="5"><strong>Total Pengeluaran:</strong> <?= $grand_total_keluar ?> unit</td>
        </tr>
        <tr>
            <td colspan="9" class="text-center" style="padding: 15px;">
                <em>Dokumen ini dibuat otomatis oleh sistem Healventory</em>
            </td>
        </tr>
    </table>
</body>

</html>