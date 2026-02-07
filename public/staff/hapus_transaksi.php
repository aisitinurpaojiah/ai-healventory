<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    http_response_code(403);
    exit('Forbidden');
}

require '../../config/database.php';
require '../includes/stock_helper.php';

$id = (int)$_POST['id'];

try {
    if (empty($id)) {
        throw new Exception('ID tidak valid');
    }

    // Ambil id_obat sebelum dihapus untuk update notifikasi
    $stmt = $pdo->prepare("SELECT id_obat FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    $transaksi = $stmt->fetch();

    if (!$transaksi) {
        throw new Exception('Transaksi tidak ditemukan');
    }

    $id_obat = $transaksi['id_obat'];

    // Hapus transaksi
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);

    // Update notifikasi stok
    updateNotifikasiStok($pdo, $id_obat);

    echo "success";
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}