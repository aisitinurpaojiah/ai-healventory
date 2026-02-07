<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    http_response_code(403);
    exit('Forbidden');
}

require '../../config/database.php';
require '../includes/stock_helper.php';

$id_user = (int)$_SESSION['user_id'];
$id_obat = (int)$_POST['id_obat'];
$jenis = trim($_POST['jenis']);
$jumlah = (int)$_POST['jumlah'];
$keterangan = trim($_POST['keterangan']);

try {
    // Validasi
    if (empty($id_obat) || empty($jenis) || $jumlah <= 0) {
        throw new Exception('Data tidak lengkap');
    }

    if (!in_array($jenis, ['masuk', 'keluar'])) {
        throw new Exception('Jenis transaksi tidak valid');
    }

    // Insert transaksi
    $stmt = $pdo->prepare("
        INSERT INTO transaksi (id_user, id_obat, jenis, jumlah, keterangan, tgl_transaksi)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$id_user, $id_obat, $jenis, $jumlah, $keterangan]);

    // Update notifikasi stok
    updateNotifikasiStok($pdo, $id_obat);

    echo "success";
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}