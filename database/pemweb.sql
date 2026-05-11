-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 03, 2026 at 07:59 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pemweb`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `lapangan_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `durasi` int DEFAULT NULL,
  `total_harga` int DEFAULT NULL,
  `catatan` text,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `user_id`, `lapangan_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `durasi`, `total_harga`, `catatan`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-05-03', '07:00:00', '08:00:00', 1, 150000, '', 'confirmed', '2026-05-03 18:49:15'),
(2, 1, 2, '2026-05-03', '09:00:00', '11:00:00', 2, 500000, '', 'pending', '2026-05-03 19:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `lapangan`
--

CREATE TABLE `lapangan` (
  `id` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `harga` int DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lapangan`
--

INSERT INTO `lapangan` (`id`, `nama`, `harga`, `status`, `lokasi`, `deskripsi`, `foto`) VALUES
(1, 'Padelin', 150000, 'aktif', 'Lampung Center', 'hgvgvfyhykhvfyh', NULL),
(2, 'Padel-an', 250000, 'aktif', 'Lapadel', 'zsjnxjasnxjanxjqbwhdbqcvb', NULL),
(4, 'Padel-Yuk', 100000, 'aktif', 'Kedamaian', 'ndhhdhesydhedhu', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Achira Desya Lucy', 'achiralucy@gmail.com', '$2y$10$Fjiv9Psiyd7GAvCpKDL70OyX1Zpf/d1S0SHMB42pHQpWPRrwR6fj2', 'user', '2026-05-03 18:10:15'),
(2, 'Admin', 'admin@padelplay.com', '$2y$10$lgrFZk9U/hNzTRPX2KeZhOvy11XnMAcNmZWxVTKBHY/hMNpOkVUF.', 'admin', '2026-05-03 18:10:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lapangan`
--
ALTER TABLE `lapangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lapangan`
--
ALTER TABLE `lapangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
