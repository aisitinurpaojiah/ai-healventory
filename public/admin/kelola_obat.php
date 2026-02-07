<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../staff/login.php");
    exit;
}

include '../../config/database.php';
include '../includes/header.php';

// Query dengan stok real
$query = "
    SELECT 
        o.id,
        o.kode_obat,
        o.nama,
        o.kategori,
        o.stok_awal,
        o.stok_minimum,
        o.tgl_kadaluarsa,
        (o.stok_awal + 
         COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END), 0)) as stok_real
    FROM obat o
    LEFT JOIN transaksi t ON o.id = t.id_obat
    GROUP BY o.id, o.kode_obat, o.nama, o.kategori, o.stok_awal, o.stok_minimum, o.tgl_kadaluarsa
    ORDER BY o.id ASC
";

$obat = $pdo->query($query);
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Super Admin</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <section class="table-section">
            <div class="table-header">
                <h2>Kelola Obat</h2>
                <button id="btnTambah" class="btn-primary">+ Tambah Obat</button>
            </div>

            <table id="tabelObat">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Stok Awal</th>
                        <th>Stok Real</th>
                        <th>Stok Min</th>
                        <th>Status</th>
                        <th>Kadaluarsa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $obat->fetch()): ?>
                        <?php
                        // Tentukan status
                        $status = '';
                        $status_class = '';
                        if ($row['stok_real'] <= 0) {
                            $status = 'Habis';
                            $status_class = 'status-habis';
                        } elseif ($row['stok_real'] < $row['stok_minimum']) {
                            $status = 'Menipis';
                            $status_class = 'status-menipis';
                        } else {
                            $status = 'Aman';
                            $status_class = 'status-aman';
                        }
                        ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td><?= htmlspecialchars($row['kode_obat']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td><?= $row['stok_awal'] ?></td>
                            <td><strong><?= $row['stok_real'] ?></strong></td>
                            <td><?= $row['stok_minimum'] ?></td>
                            <td>
                                <span class="badge <?= $status_class ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['tgl_kadaluarsa']) ?></td>
                            <td>
                                <button class="btn-edit">✏️</button>
                                <button class="btn-delete">🗑️</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- Modal Form -->
<div class="modal" id="modalForm">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Obat</h3>
        <form id="formObat">
            <input type="hidden" name="id" id="id">

            <label>Kode Obat</label>
            <input type="text" name="kode_obat" id="kode_obat" required placeholder="Contoh: OB001">

            <label>Nama Obat</label>
            <input type="text" name="nama" id="nama" required>

            <label>Kategori</label>
            <input type="text" name="kategori" id="kategori">

            <label>Stok Awal</label>
            <input type="number" name="stok_awal" id="stok_awal" required>

            <label>Stok Minimum</label>
            <input type="number" name="stok_minimum" id="stok_minimum" required>

            <label>Tanggal Kadaluarsa</label>
            <input type="date" name="tgl_kadaluarsa" id="tgl_kadaluarsa" required>

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
        <p>Yakin ingin menghapus obat ini?</p>
        <div class="form-actions">
            <button id="confirmHapus" class="btn-outline">Ya</button>
            <button id="cancelHapus" class="btn-primary">Batal</button>
        </div>
    </div>
</div>
<script src="../assets/js/kelola_obat.js"></script>
<?php include '../includes/footer.php'; ?>