<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';

// Library untuk PDF - menggunakan TCPDF
// Install via composer: composer require tecnickcom/tcpdf
// Atau download manual dari https://tcpdf.org/

// Jika belum install TCPDF, gunakan alternatif HTML to PDF dengan CSS
// Untuk sementara kita buat dengan HTML yang bisa di-print

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
$grand_total_stok = array_sum(array_column($laporan, 'stok_akhir'));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Inventori Obat -- PDF</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 20mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #1565c0;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #1565c0;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 12px;
        }

        .info-box {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            padding: 5px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th {
            background: #1565c0;
            color: white;
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0d47a1;
        }

        table.data-table td {
            padding: 8px 5px;
            border: 1px solid #ddd;
        }

        table.data-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        table.data-table tbody tr:hover {
            background: #e3f2fd;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-success {
            color: #4caf50;
            font-weight: bold;
        }

        .text-danger {
            color: #f44336;
            font-weight: bold;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }

        .status-aman {
            background: #4caf50;
            color: white;
        }

        .status-menipis {
            background: #ff9800;
            color: white;
        }

        .status-habis {
            background: #f44336;
            color: white;
        }

        .total-row {
            background: #e3f2fd !important;
            font-weight: bold;
            font-size: 12px;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }

        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Button Print  -->
    <div class="no-print" style="text-align: center; margin: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background: #1565c0; color: white; border: none; border-radius: 5px;">
            🖨️ Cetak PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background: #f44336; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            ✖️ Tutup
        </button>
    </div>

    <div class="header">
        <h1>HEALVENTORY</h1>
        <h2>LAPORAN INVENTORI OBAT</h2>
        <p>Sistem Informasi Manajemen Inventori Obat</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td width="20%"><strong>Tanggal Cetak</strong></td>
                <td width="30%">: <?= date('d F Y, H:i:s') ?></td>
                <td width="20%"><strong>Total Obat</strong></td>
                <td width="30%">: <?= count($laporan) ?> item</td>
            </tr>
            <tr>
                <td><strong>Dicetak Oleh</strong></td>
                <td>: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></td>
                <td><strong>Total Pemasukan</strong></td>
                <td>: <?= $grand_total_masuk ?> unit</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>: Laporan Lengkap</td>
                <td><strong>Total Pengeluaran</strong></td>
                <td>: <?= $grand_total_keluar ?> unit</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">Kode Obat</th>
                <th width="20%">Nama Obat</th>
                <th width="8%">Stok Awal</th>
                <th width="10%">Pemasukan</th>
                <th width="10%">Pengeluaran</th>
                <th width="10%">Stok Akhir</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
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
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['kode_obat']) ?></td>
                    <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                    <td class="text-center"><?= $row['stok_awal'] ?></td>
                    <td class="text-center text-success"><?= $row['total_masuk'] ?></td>
                    <td class="text-center text-danger"><?= $row['total_keluar'] ?></td>
                    <td class="text-center" style="font-weight: bold;"><?= $row['stok_akhir'] ?></td>

                    <td class="text-center">
                        <span class="badge <?= $status_class ?>">
                            <?= $status ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr class="total-row">
                <td colspan="3" class="text-right"></td>
                <td class="text-center"><strong>TOTAL</strong></td>
                <td class="text-center text-success"><?= $grand_total_masuk ?></td>
                <td class="text-center text-danger"><?= $grand_total_keluar ?></td>
                <td class="text-center text-info"><strong><?= $grand_total_stok ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="signature">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p><strong>Kepala Apotek</strong></p>
            <div class="signature-line">
                <p>(_________________)</p>
            </div>
        </div>

        <div class="signature-box">
            <p>Diperiksa Oleh,</p>
            <p><strong>Supervisor</strong></p>
            <div class="signature-line">
                <p>(_________________)</p>
            </div>
        </div>

        <div class="signature-box">
            <p>Dibuat Oleh,</p>
            <p><strong>Admin</strong></p>
            <div class="signature-line">
                <p>(_________________)</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Dokumen ini dicetak otomatis oleh sistem Healventory</p>
        <p>© <?= date('Y') ?> Healventory - All Rights Reserved</p>
    </div>
</body>

</html>