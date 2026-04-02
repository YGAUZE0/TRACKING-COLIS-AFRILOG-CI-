-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 15 avr. 2025 à 14:04
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `afrilog`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'Root', '$2y$10$Q7rWOtRiLsLrTD/0wEh8WOerngD.olwNRmm9HEAW/sa/JksaWfnsu');

-- --------------------------------------------------------

--
-- Structure de la table `carrier`
--

CREATE TABLE `carrier` (
  `carrier_id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `carriers`
--

CREATE TABLE `carriers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `carriers`
--

INSERT INTO `carriers` (`id`, `name`, `phone`, `vehicle_number`) VALUES
(1, 'KONATE ADAMA', '05 54 34 84 58', 'AA 8525 HJ'),
(2, 'COULIBALY ABOUBACAR', '0819156321', 'AA 8526HJ');

-- --------------------------------------------------------

--
-- Structure de la table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cities`
--

INSERT INTO `cities` (`id`, `name`, `lat`, `lng`) VALUES
(1, 'Gagnoa, Côte d\'Ivoire', 6.15144230, -5.95153990),
(2, 'Divo, Côte d\'Ivoire', 5.84153990, -5.36255160),
(3, 'Kani, Côte d\'Ivoire', 8.47783750, -6.60503680),
(4, 'AFRILOG CI, entrée en face de l\'instec, Rue des Brasseurs, Abidjan, Côte d\'Ivoire', 5.29670760, -4.00099120),
(5, 'Bonikro Gold Mine, Hiré-Ouatta, Côte d\'Ivoire', 6.22110800, -5.36810870),
(6, 'Corridor gesco', 9.44871830, -5.60728170);

-- --------------------------------------------------------

--
-- Structure de la table `city`
--

CREATE TABLE `city` (
  `city_id` int(10) UNSIGNED NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL COMMENT 'WGS84',
  `longitude` decimal(10,8) NOT NULL COMMENT 'WGS84'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `abreviation` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL,
  `role` enum('client','administrateur') NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `abreviation`, `created_at`, `password`, `role`) VALUES
(1, 'AFRILOG CI', 'info@afrilogci.com', 'AFRI', '2025-02-22 11:35:13', '$2y$10$/A6kMFf0r/NDMvwArH2NnubnQq6KRgCI1E/e7jxZJCOTZ0L/dKlta', 'client'),
(2, 'TGDY CORPORATION', 'Tapegauze44@gmail.com', 'TGDY', '2025-02-22 11:40:44', '$2y$10$daCzRmqr8r19CA.XR.OoI.RixxEc7BQHIAhG03oVsSxmMDa3LOXNS', ''),
(3, 'TONGON GOLD MINE', 'info@tongongoldmine.com', 'TON', '2025-02-22 12:03:58', '$2y$10$zA3/pJFoSA/P6NVt7VSR3OCTbgIWHz/LjlYlOXW/YufTSux4xLM8e', '');

-- --------------------------------------------------------

--
-- Structure de la table `dossier`
--

CREATE TABLE `dossier` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `eta` datetime DEFAULT NULL,
  `type_dossier` varchar(50) NOT NULL DEFAULT 'inconnu',
  `mode` varchar(50) NOT NULL DEFAULT 'inconnu',
  `nombre_conteneur` int(11) NOT NULL DEFAULT 0,
  `nom_du_navire` varchar(255) DEFAULT NULL,
  `numero_bl` varchar(50) DEFAULT NULL,
  `poids` decimal(10,2) DEFAULT NULL,
  `statut` enum('en attente','en preparation','en cours','termier','en archive') DEFAULT 'en attente',
  `numero_dossier` varchar(255) DEFAULT NULL,
  `client` int(11) DEFAULT NULL,
  `responsable` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `field_name` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modification_history`
--

CREATE TABLE `modification_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `action_type` enum('create','update','delete','status_change','location_update') NOT NULL,
  `field_name` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `status` enum('en attente','en transit','livré') DEFAULT 'en attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `carrier_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `55` int(11) DEFAULT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `destination_lat` decimal(10,8) DEFAULT NULL,
  `destination_lng` decimal(11,8) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `poids` varchar(20) DEFAULT NULL,
  `type` enum('conteneur','vrac','autres') DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'Poids du colis en kilogrammes',
  `vehicle_type` enum('pick-up','10T','Plateau','porte char','autres') DEFAULT NULL COMMENT 'Type de véhicule pour le transport'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `packages`
--

INSERT INTO `packages` (`id`, `tracking_number`, `sender_name`, `receiver_name`, `status`, `created_at`, `carrier_id`, `city_id`, `55`, `destination`, `destination_lat`, `destination_lng`, `client_email`, `poids`, `type`, `weight`, `vehicle_type`) VALUES
(7, 'TON20256', 'AFRILOG CI', 'TONGON GOLD MINE', 'livré', '2025-03-03 17:57:05', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'AFRI002', 'AFRILOG CI', 'TAPE', 'en attente', '2025-03-04 12:09:17', 1, 2, NULL, '0504911959', NULL, NULL, 'y@gmaiL.com', NULL, NULL, NULL, NULL),
(11, 'TGDY 0012', 'AFRILOG MALI', 'TAPE', 'livré', '2025-03-04 17:24:21', 2, 1, NULL, '0504911959', NULL, NULL, 'y@gmaiL.com', '12025', 'conteneur', NULL, NULL),
(13, 'BONI001', 'AFRILOG CI', 'BONIKRO GOLD', 'livré', '2025-03-19 13:09:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'TON20256', 'AFRILOG CI', 'TONGON GOLD MINE', 'en transit', '2025-03-20 14:01:14', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'BONI002', 'AFRILOG CI', 'BONIKRO GOLD', 'en transit', '2025-03-20 17:52:14', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'BONI003', 'AFRILOG CI', 'BONIKRO GOLD', 'en transit', '2025-03-21 10:27:11', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'BONI004', 'AFRILOG CI', 'BONIKRO GOLD', 'en transit', '2025-03-21 14:12:15', 2, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'BON724', 'AFRILOG CI', 'BONIKRO GOLD MINE', 'en transit', '2025-04-11 09:30:47', 2, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 890.00, 'pick-up');

--
-- Déclencheurs `packages`
--
DELIMITER $$
CREATE TRIGGER `track_package_update` AFTER UPDATE ON `packages` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO modification_history 
        (user_id, package_id, action_type, field_name, old_value, new_value)
        VALUES (@current_user_id, NEW.id, 'status_change', 'status', OLD.status, NEW.status);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `package_locations`
--

CREATE TABLE `package_locations` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(10,8) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `accuracy` float DEFAULT NULL,
  `speed` float DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL COMMENT 'Statut du colis à cette localisation',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('en_attente','en_transit','livré','retardé') NOT NULL DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `positions`
--

INSERT INTO `positions` (`id`, `package_id`, `latitude`, `longitude`, `address`, `timestamp`) VALUES
(2, 13, 5.32522580, -4.01960300, 'Abidjan', '2025-03-19 13:09:35'),
(3, 13, 5.84153990, -5.36255160, 'Divo,', '2025-03-19 17:00:46'),
(4, 13, 8.47783750, -6.60503680, 'Kani', '2025-03-19 17:06:53'),
(5, 14, 5.29671290, -4.00356612, 'AFRILOG CI', '2025-03-20 14:01:14'),
(6, 14, 5.29670760, -4.00099120, 'AFRILOG CI, entrée en face de l\'instec, Rue des Brasseurs, Abidjan, Côte d\'Ivoire', '2025-03-20 17:29:47'),
(7, 14, 6.88833410, -6.43968880, 'Daloa, Côte d\'Ivoire', '2025-03-20 17:40:12'),
(8, 15, 5.29670760, -4.00099120, 'AFRILOG CI, entrée en face de l\'instec, Rue des Brasseurs, Abidjan, Côte d\'Ivoire', '2025-03-20 17:52:15'),
(9, 15, 5.36520590, -4.10213460, 'GESCO, Abidjan, Côte d\'Ivoire', '2025-03-20 17:58:06'),
(10, 15, 6.01189890, -4.82370780, 'N\'Zianouan', '2025-03-21 08:18:55'),
(11, 15, 6.22110800, -5.36810870, 'Bonikro Gold Mine', '2025-03-21 08:21:47'),
(12, 16, 5.29670760, -4.00099120, 'AFRILOG CI', '2025-03-21 10:27:11'),
(16, 18, 5.29670760, -4.00099120, 'AFRILOG CI', '2025-03-21 14:12:15'),
(17, 18, 5.39792800, -4.14466030, 'KM 22 ALLOKOI', '2025-03-21 14:36:58'),
(18, 18, 5.86799830, -4.75880420, 'N\'DOUCI', '2025-03-21 16:02:06'),
(19, 18, 6.22110800, -5.36810870, 'Bonikro Gold Mine', '2025-03-21 16:03:50'),
(20, 19, 5.29670760, -4.00099120, 'AFRILOG CI', '2025-04-11 09:30:47');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `abbreviation` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','administrateur') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `abbreviation`, `email`, `password`, `role`, `created_at`) VALUES
(5, 'AFRILOG', 'AFRI', 'ygauze@gmail.com', '081910Y@nnick', '', '2025-02-28 17:02:44'),
(6, 'AFRILOG', 'AFRI', 'info@afrilogci.com', '$2y$12$s9bZqq41qKmR7X2N6zL0O.9VvTd1EfJWjLk7MhG1bR3aKx5Yt7D2C', '', '2025-02-28 17:23:47'),
(7, 'TONGON GOLD MINE', 'TON', 'Root@gmail.com', '$2y$10$2zqYSl3G2VKPs12Rpy6tRuqqMMBraSVMF0EwwlmVo7QBoT1oKcy2u', 'administrateur', '2025-02-28 17:54:12'),
(8, 'BONIKRO GOLD MINE', 'BONI', 'info@bonikrogoldmine.com', '$2y$10$7Sf7msPPapGC69OArAuhj.laE2g.e6iuiu1Ab.u2OTbYaIj6h/nX2', 'client', '2025-03-03 15:01:16'),
(9, 'BONIKRO GOLD MINE', 'BONI', 'operation@bonikrogoldmine.com', '$2y$10$YTFelygAtfY0vOMqExCQbuj98o7q9IgIXdOB4BQlHLEpsvlaqNZgu', 'client', '2025-03-04 07:30:21'),
(10, 'TAPE', 'tape', 'ui@cohhj.com', '$2y$10$4B.ULTeFe6KPn2awJ0GneOBJNJz2vgB2uEWBbMzmxv8zho5ckVbIq', 'client', '2025-03-04 07:36:04'),
(11, 'AFRILOG CI', 'AFRI', 'operation@afrilogci.com', '$2y$10$j7AgPYPK3G0/CVXAM9jTaewj6fgYAJ6NwFp.Z/VbdXLaWoOUcAWc6', 'administrateur', '2025-03-20 12:36:53');

-- --------------------------------------------------------

--
-- Structure de la table `user_statistics`
--

CREATE TABLE `user_statistics` (
  `user_id` int(11) NOT NULL,
  `total_packages` int(11) DEFAULT 0,
  `packages_delivered` int(11) DEFAULT 0,
  `last_activity` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `carrier`
--
ALTER TABLE `carrier`
  ADD PRIMARY KEY (`carrier_id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD KEY `idx_nom` (`nom`);

--
-- Index pour la table `carriers`
--
ALTER TABLE `carriers`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `city`
--
ALTER TABLE `city`
  ADD PRIMARY KEY (`city_id`),
  ADD UNIQUE KEY `idx_unique_city` (`city_name`),
  ADD KEY `idx_coords` (`latitude`,`longitude`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `abreviation` (`abreviation`);

--
-- Index pour la table `dossier`
--
ALTER TABLE `dossier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `client` (`client`),
  ADD KEY `responsable` (`responsable`);

--
-- Index pour la table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `modification_history`
--
ALTER TABLE `modification_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Index pour la table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carrier` (`carrier_id`),
  ADD KEY `fk_city` (`city_id`),
  ADD KEY `fk_packages_users` (`55`),
  ADD KEY `idx_tracking_number` (`tracking_number`),
  ADD KEY `idx_tracking` (`tracking_number`);

--
-- Index pour la table `package_locations`
--
ALTER TABLE `package_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_id` (`package_id`);

--
-- Index pour la table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_positions` (`package_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_statistics`
--
ALTER TABLE `user_statistics`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `carrier`
--
ALTER TABLE `carrier`
  MODIFY `carrier_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `carriers`
--
ALTER TABLE `carriers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `city`
--
ALTER TABLE `city`
  MODIFY `city_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `dossier`
--
ALTER TABLE `dossier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `modification_history`
--
ALTER TABLE `modification_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `package_locations`
--
ALTER TABLE `package_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `dossier`
--
ALTER TABLE `dossier`
  ADD CONSTRAINT `dossier_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `dossier_ibfk_2` FOREIGN KEY (`client`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `dossier_ibfk_3` FOREIGN KEY (`responsable`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `modification_history`
--
ALTER TABLE `modification_history`
  ADD CONSTRAINT `modification_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `modification_history_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`);

--
-- Contraintes pour la table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `fk_carrier` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_packages_users` FOREIGN KEY (`55`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `package_locations`
--
ALTER TABLE `package_locations`
  ADD CONSTRAINT `package_locations_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_statistics`
--
ALTER TABLE `user_statistics`
  ADD CONSTRAINT `user_statistics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
