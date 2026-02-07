<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';

// Filter tanggal
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$query = "SELECT t.id, o.nama AS nama_obat, t.jenis, t.jumlah, t.keterangan, t.tgl_transaksi
          FROM transaksi t
          JOIN obat o ON t.id_obat = o.id
          WHERE 1=1";

$params = [];

if ($from && $to) {
    $query .= " AND DATE(t.tgl_transaksi) BETWEEN ? AND ?";
    $params = [$from, $to];
}

$query .= " ORDER BY t.tgl_transaksi DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Super Admin</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <form method="GET" class="filter-box">
            <label>Dari:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">

            <label>Sampai:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">

            <button type="submit" class="btn-primary">Filter Transaksi</button>

            <?php if ($from || $to): ?>
                <a href="transaksi.php" class="btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <section class="table-section">
            <h2>Transaksi</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Obat</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksi) > 0): ?>
                        <?php foreach ($transaksi as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                                <td>
                                    <span style="color: <?= $row['jenis'] == 'masuk' ? 'green' : 'red' ?>">
                                        <?= ucfirst($row['jenis']) ?>
                                    </span>
                                </td>
                                <td><?= $row['jumlah'] ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tgl_transaksi'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">
                                Tidak ada data transaksi
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<?php include '../includes/footer.php'; ?>