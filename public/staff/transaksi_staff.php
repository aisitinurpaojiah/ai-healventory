<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

require '../../config/database.php';
include '../includes/header.php';
include '../includes/stock_helper.php';

// Ambil data transaksi dengan filter
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$query = "SELECT 
            t.id, 
            o.id as id_obat,
            o.nama AS nama_obat, 
            t.jenis, 
            t.jumlah, 
            t.keterangan, 
            t.tgl_transaksi
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

// Ambil list obat untuk dropdown
$obat_list = $pdo->query("SELECT id, kode_obat, nama FROM obat ORDER BY nama ASC")->fetchAll();
?>

<div class="container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Staff</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <section class="table-section">
            <div class="table-header">
                <h2>Transaksi Obat</h2>
                <button class="btn-primary" id="btnTambahTransaksi">+ Tambah Transaksi</button>
            </div>

            <!-- Filter Box -->
            <form method="GET" class="filter-box">
                <label>Dari:</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">

                <label>Sampai:</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">

                <button type="submit" class="btn-primary">Filter</button>

                <?php if ($from || $to): ?>
                    <a href="transaksi_staff.php" class="btn-outline">Reset</a>
                <?php endif; ?>
            </form>

            <!-- Tabel Transaksi -->
            <table id="tabelTransaksi">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Obat</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksi) > 0): ?>
                        <?php foreach ($transaksi as $row): ?>
                            <tr data-id="<?= $row['id'] ?>" data-id-obat="<?= $row['id_obat'] ?>">
                                <td><?= date('d-m-Y H:i', strtotime($row['tgl_transaksi'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                                <td>
                                    <span style="color: <?= $row['jenis'] == 'masuk' ? 'green' : 'red' ?>">
                                        <?= ucfirst($row['jenis']) ?>
                                    </span>
                                </td>
                                <td><?= $row['jumlah'] ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td>
                                    <button class="btn-edit">✏️</button>
                                    <button class="btn-delete">🗑️</button>
                                </td>
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

<!-- Modal Form Transaksi -->
<div class="modal" id="modalForm">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Transaksi</h3>
        <form id="formTransaksi">
            <input type="hidden" name="id" id="id">
            <input type="hidden" name="id_user" value="<?= $_SESSION['user_id'] ?>">

            <label>Pilih Obat</label>
            <select name="id_obat" id="id_obat" required>
                <option value="">-- Pilih Obat --</option>
                <?php foreach ($obat_list as $obat): ?>
                    <option value="<?= $obat['id'] ?>">
                        <?= htmlspecialchars($obat['kode_obat']) ?> - <?= htmlspecialchars($obat['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Jenis Transaksi</label>
            <select name="jenis" id="jenis" required>
                <option value="">-- Pilih Jenis --</option>
                <option value="masuk">Masuk (Penambahan Stok)</option>
                <option value="keluar">Keluar (Pengurangan Stok)</option>
            </select>

            <label>Jumlah</label>
            <input type="number" name="jumlah" id="jumlah" required min="1" placeholder="Contoh: 50">

            <label>Keterangan</label>
            <textarea name="keterangan" id="keterangan" rows="3" placeholder="Contoh: Pembelian dari supplier ABC"></textarea>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Simpan</button>
                <button type="button" class="btn-outline" id="btnBatal">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal" id="modalHapus">
    <div class="modal-box">
        <p>Yakin ingin menghapus transaksi ini?</p>
        <div class="form-actions">
            <button id="confirmHapus" class="btn-outline">Ya, Hapus</button>
            <button id="cancelHapus" class="btn-primary">Batal</button>
        </div>
    </div>
</div>

<script src="../assets/js/transaksi_staff.js"></script>

<?php include '../includes/footer.php'; ?>