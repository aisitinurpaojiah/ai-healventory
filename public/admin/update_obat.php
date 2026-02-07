<?php
include '../../config/database.php';
include '../includes/stock_helper.php';

$id = (int)$_POST['id'];
$kode = trim($_POST['kode_obat']);
$nama = trim($_POST['nama']);
$kategori = trim($_POST['kategori']);
$stok_awal = (int)$_POST['stok_awal'];
$stok_minimum = (int)$_POST['stok_minimum'];
$tgl_kadaluarsa = $_POST['tgl_kadaluarsa'];

try {
    $stmt = $pdo->prepare("UPDATE obat 
                           SET kode_obat=?, nama=?, kategori=?, stok_awal=?, stok_minimum=?, tgl_kadaluarsa=? 
                           WHERE id=?");
    $stmt->execute([$kode, $nama, $kategori, $stok_awal, $stok_minimum, $tgl_kadaluarsa, $id]);
    
    // Update notifikasi stok dan kadaluarsa
    updateNotifikasiStok($pdo, $id);
    updateNotifikasiKadaluarsa($pdo, $id);
    
    echo "success";
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}
?>