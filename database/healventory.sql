-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 21, 2025 at 03:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healventory`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_laporan_bulanan` (IN `thn` INT, IN `bln` INT)   BEGIN
  INSERT INTO laporan (id_obat, periode, stok_awal, pemasukan, pengeluaran)
  SELECT 
    o.id,
    DATE(CONCAT(thn, '-', LPAD(bln,2,'0'), '-01')),
    o.stok_awal,
    IFNULL((SELECT SUM(jumlah) FROM transaksi WHERE id_obat=o.id AND jenis='masuk' AND MONTH(tgl_transaksi)=bln AND YEAR(tgl_transaksi)=thn),0),
    IFNULL((SELECT SUM(jumlah) FROM transaksi WHERE id_obat=o.id AND jenis='keluar' AND MONTH(tgl_transaksi)=bln AND YEAR(tgl_transaksi)=thn),0)
  FROM obat o;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` int(11) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `periode` date NOT NULL,
  `stok_awal` int(11) DEFAULT 0,
  `pemasukan` int(11) DEFAULT 0,
  `pengeluaran` int(11) DEFAULT 0,
  `stok_akhir` int(11) GENERATED ALWAYS AS (`stok_awal` + `pemasukan` - `pengeluaran`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_obat` int(11) DEFAULT NULL,
  `jenis` enum('stok','kadaluarsa') DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notif`, `id_obat`, `jenis`, `pesan`, `tanggal`, `status`) VALUES
(35, 12, 'kadaluarsa', 'Obat Sanmol Sirup akan kadaluarsa dalam 19 hari! (Exp: 10-01-2026)', '2025-12-21 13:58:12', 'unread'),
(36, 20, 'kadaluarsa', 'Obat Diapet Kapsul akan kadaluarsa dalam 11 hari! (Exp: 2025-12-31)', '2025-12-19 20:06:39', 'unread'),
(37, 21, 'kadaluarsa', 'Obat CTM akan kadaluarsa dalam 5 hari! (Exp: 27-12-2025)', '2025-12-21 09:40:40', 'unread'),
(64, 21, 'stok', 'Stok obat CTM menipis! Sisa: 10 (Min: 20)', '2025-12-21 14:08:12', 'unread');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id` int(11) NOT NULL,
  `kode_obat` varchar(30) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `stok_awal` int(11) DEFAULT 0,
  `stok_minimum` int(11) DEFAULT 100,
  `tgl_kadaluarsa` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id`, `kode_obat`, `nama`, `kategori`, `stok_awal`, `stok_minimum`, `tgl_kadaluarsa`, `created_at`) VALUES
(1, 'OB001', 'Termorex Sirup', 'Demam', 100, 55, '2026-01-28', '2025-11-30 17:29:57'),
(2, 'OB002', 'Laserin 60ml', 'Obat Batuk', 30, 15, '2026-02-10', '2025-11-30 17:29:57'),
(3, 'OB003', 'Enervon-C', 'Vitamin', 100, 30, '2026-12-10', '2025-11-30 17:29:57'),
(10, 'OB004', 'Amoxicillin 500mg', 'Antibiotik', 120, 50, '2026-01-29', '2025-11-30 17:12:03'),
(12, 'OB005', 'Sanmol Sirup', 'Demam', 100, 20, '2026-01-10', '2025-11-30 17:12:58'),
(14, 'OB006', 'Bodrex', 'Analgesik', 110, 100, '2027-01-30', '2025-11-30 17:23:14'),
(15, 'OB007', 'Betadine Sol 30ml', 'Antiseptik', 30, 15, '2026-08-10', '2025-11-30 17:23:14'),
(16, 'OB008', 'Vitacimin', 'Vitamin', 150, 50, '2026-03-22', '2025-11-30 17:23:14'),
(17, 'OB009', 'Promag Tablet', 'Maag', 85, 40, '2027-12-01', '2025-11-30 17:23:14'),
(18, 'OB010', 'Komix Herbal', 'Obat Batuk', 300, 50, '2026-01-31', '2025-11-30 17:23:14'),
(19, 'OB011', 'Salonpas Koyo', 'Nyeri Otot', 60, 25, '2028-02-14', '2025-11-30 17:23:14'),
(20, 'OB012', 'Diapet Kapsul', 'Diare', 100, 30, '2025-12-31', '2025-11-30 17:23:14'),
(21, 'OB013', 'CTM', 'Alergi', 90, 20, '2025-12-27', '2025-11-30 17:23:14'),
(22, 'OB014', 'Entrostop', 'Diare', 50, 20, '2026-07-01', '2025-11-30 17:23:14'),
(23, 'OB015', 'Insto Regular 7ml', 'Obat Mata', 100, 30, '2026-04-09', '2025-11-30 17:23:14');

--
-- Triggers `obat`
--
DELIMITER $$
CREATE TRIGGER `trigger_notif_stok_min` AFTER UPDATE ON `obat` FOR EACH ROW BEGIN
  IF NEW.stok_awal < NEW.stok_minimum THEN
    INSERT INTO notifikasi (id_obat, jenis, pesan)
    VALUES (
      NEW.id,
      'stok',
      CONCAT('⚠️ Stok obat ', NEW.nama, ' hanya ', NEW.stok_awal, ' unit, di bawah batas minimum.')
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_transaksi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `id_user`, `id_obat`, `jenis`, `jumlah`, `keterangan`, `tgl_transaksi`) VALUES
(1, 1, 15, 'keluar', 50, 'Di beli orang', '2025-12-20 19:41:40'),
(2, 1, 2, 'masuk', 50, 'Obat masuk', '2025-12-05 00:53:44'),
(3, 1, 2, 'masuk', 100, 'Baru restock', '2025-12-19 18:23:05'),
(4, 2, 2, 'keluar', 70, 'Di beli oleh mitra', '2025-12-05 19:20:30'),
(5, 1, 1, 'masuk', 150, 'Mengisi Stok Baru', '2025-12-29 19:16:48'),
(6, 1, 1, 'keluar', 55, 'Di beli apotek lain', '2025-12-30 19:16:48'),
(7, 1, 15, 'masuk', 200, 'Mengisi Stok Baru', '2025-12-10 19:16:48'),
(8, 1, 10, 'keluar', 50, 'Di beli apotek lain', '2025-12-13 19:16:48'),
(9, 2, 2, 'keluar', 110, 'Di beli', '2025-12-19 23:36:15'),
(10, 2, 21, 'keluar', 80, 'Di beli apotek A', '2025-12-19 23:56:09'),
(11, 2, 2, 'masuk', 100, 'Di beli dari pabrik', '2025-12-04 01:54:34'),
(12, 1, 3, 'masuk', 200, 'Di beli dari pabrik', '2025-11-12 08:09:41'),
(13, 1, 10, 'masuk', 180, 'Pengisian stok', '2025-10-15 02:11:06'),
(14, 1, 3, 'masuk', 150, 'Pengisian stok', '2025-08-12 02:11:06'),
(15, 1, 1, 'keluar', 50, 'Di beli perusahaan', '2025-09-09 02:13:57'),
(16, 1, 3, 'keluar', 200, 'Di beli oleh mitra', '2025-07-14 09:17:06'),
(17, 1, 18, 'keluar', 200, 'Di beli rumah sakit', '2025-09-16 02:18:20'),
(18, 1, 10, 'keluar', 150, 'Di beli rumah sakit', '2025-09-17 02:18:20'),
(19, 1, 3, 'keluar', 150, 'Di borong temen', '2025-12-26 12:57:38'),
(20, 1, 3, 'masuk', 50, 'Restok', '2025-12-24 19:57:38'),
(29, 2, 3, 'keluar', 50, 'Di beli klinik lohbener', '2025-11-19 18:22:25'),
(31, 2, 21, 'masuk', 100, 'Di restok oleh owner', '2025-12-21 20:51:24'),
(33, 2, 21, 'keluar', 100, 'Di beli Klinik Z', '2025-12-12 21:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'staff',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expired` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `role`, `password`, `created_at`, `reset_token`, `reset_expired`) VALUES
(1, 'admin1', 'bita', 'admin', '$2y$10$wM.0XzSrn5qeZBkgC1q.LOjDDX6TAlGUazbi.TQ9/Pj93AST9oeRC', '2025-10-16 16:36:50', NULL, NULL),
(2, 'staff1', 'ilham', 'staff', '$2y$10$y/fGXsW7DVvY31A1VIiBsexhJAmaHzVcFauGa3UOcFSmKUb/Rotvi', '2025-10-16 16:36:50', NULL, NULL),
(3, 'admin2', 'ai siti', 'admin', '$2y$10$.kwG7dexw9CWZy1dfvNHvOLvrd3bd8KeibGLhAwCFzIYqv4Z7FGnm', '2025-10-16 16:36:50', NULL, NULL),
(4, 'menejer1', 'Ghazali', 'manager', '$2y$10$vpE0omBCJ8kPI8O73uFore5dFn5JUYdmk5oK.urWkYjoeDVDctsiS', '2025-10-16 16:36:50', NULL, NULL),
(6, 'staff2', 'parisya', 'staff', '$2y$10$Xv1EMODi9/mPmbZXEhWiRu.Kj3boEM9X4Np1gHHUJFy7DOVwoPGeW', '2025-11-28 01:27:41', NULL, NULL),
(13, 'menejer2', 'Tsabita', 'manager', '$2y$10$CozAn9ksRs7IrEpGg.FwdOZH7wSvrcIzv8XqCyUvhn0feIknNivgm', '2025-12-18 04:38:17', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_obat` (`kode_obat`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `event_generate_laporan_bulanan` ON SCHEDULE EVERY 1 MONTH STARTS '2025-11-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL generate_laporan_bulanan(YEAR(CURDATE()), MONTH(CURDATE()))$$

CREATE DEFINER=`root`@`localhost` EVENT `event_notif_kadaluarsa` ON SCHEDULE EVERY 1 DAY STARTS '2025-10-16 23:38:02' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
  INSERT INTO notifikasi (id_obat, jenis, pesan)
  SELECT id, 'kadaluarsa',
  CONCAT('⚠️ Obat ', nama, ' akan kedaluwarsa pada ', DATE_FORMAT(tgl_kadaluarsa, '%d-%m-%Y'))
  FROM obat
  WHERE DATEDIFF(tgl_kadaluarsa, CURDATE()) <= 60
  AND DATEDIFF(tgl_kadaluarsa, CURDATE()) > 0
  AND id NOT IN (
    SELECT id_obat FROM notifikasi
    WHERE jenis='kadaluarsa' AND DATE(tanggal)=CURDATE()
  );
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
