<?php
// Tentukan halaman aktif berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <h2 class="logo">Healventory</h2>
    <ul class="menu">
        <li class="<?= ($current_page == 'dashboard_manager.php') ? 'active' : '' ?>" data-page="dashboard_manager.php">
            <i class="bi bi-house-door-fill"></i>
            <span>Beranda</span>
        <li class="<?= ($current_page == 'laporan.php') ? 'active' : '' ?>" data-page="laporan.php">
            <i class="bi bi-file-earmark-text"></i>
            <span>Laporan</span>
        </li>
        <li class="<?= ($current_page == 'monitoring.php') ? 'active' : '' ?>" data-page="monitoring.php">
            <i class="bi bi-activity"></i>
            <span>Monitoring</span>
        </li>
        <li id="btnLogout">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </li>
    </ul>
</aside>

<!-- Modal Logout -->
<div class="logout-modal" id="logoutModal">
    <div class="logout-box">
        <p>Yakin Ingin Keluar?</p>
        <div class="logout-actions">
            <button id="confirmLogout" class="btn-outline">Ya</button>
            <button id="cancelLogout" class="btn-primary">Batal</button>
        </div>
    </div>
</div>