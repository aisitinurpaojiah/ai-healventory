<?php
include '../../config/database.php';
include '../includes/stock_helper.php';

$kode = trim($_POST['kode_obat']);
$nama = trim($_POST['nama']);
$kategori = trim($_POST['kategori']);
$stok_awal = (int)$_POST['stok_awal'];
$stok_minimum = (int)$_POST['stok_minimum'];
$tgl_kadaluarsa = $_POST['tgl_kadaluarsa'];

try {
    $stmt = $pdo->prepare("INSERT INTO obat (kode_obat, nama, kategori, stok_awal, stok_minimum, tgl_kadaluarsa)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$kode, $nama, $kategori, $stok_awal, $stok_minimum, $tgl_kadaluarsa]);
    
    // Ambil ID obat yang baru ditambahkan
    $id_obat = $pdo->lastInsertId();
    
    // Generate notifikasi jika perlu
    updateNotifikasiStok($pdo, $id_obat);
    updateNotifikasiKadaluarsa($pdo, $id_obat);
    
    echo "success";
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}
?>