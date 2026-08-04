-- Schéma Clinique Obstétrique – Version sécurisée
-- Compatible MySQL 8 / MariaDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ------------------------------------------------
-- Table: users
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','medecin','sagefemme','secretaire','caissier') NOT NULL DEFAULT 'secretaire',
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NULL UNIQUE,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: patientes
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `patientes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `dossier_number` VARCHAR(20) NOT NULL UNIQUE,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `date_naissance` DATE NULL,
  `telephone` VARCHAR(20) NULL,
  `adresse` TEXT NULL,
  `groupe_sanguin` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
  `antecedents` TEXT NULL,
  `allergies` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_nom_prenom` (`nom`,`prenom`),
  INDEX `idx_dossier` (`dossier_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: consultations (prénatales)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `patiente_id` INT UNSIGNED NOT NULL,
  `type` ENUM('prenatale','postnatale','urgence','autre') NOT NULL DEFAULT 'prenatale',
  `date_consultation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `semaine_grossesse` TINYINT UNSIGNED NULL,
  `poids` DECIMAL(5,2) NULL,
  `tension_arterielle` VARCHAR(20) NULL,
  `hauteur_uterine` DECIMAL(5,2) NULL,
  `bebe_coeur` INT UNSIGNED NULL COMMENT 'BPM',
  `observations` TEXT NULL,
  `prescription` TEXT NULL,
  `medecin_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patiente_id`) REFERENCES `patientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medecin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_patiente_date` (`patiente_id`,`date_consultation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: accouchements
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `accouchements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `patiente_id` INT UNSIGNED NOT NULL,
  `date_accouchement` DATETIME NOT NULL,
  `type_accouchement` ENUM('voie_basse','cesarienne','instrumental') NOT NULL,
  `lieu` VARCHAR(100) NULL,
  `duree_travail` VARCHAR(50) NULL,
  `complications` TEXT NULL,
  `sagefemme_id` INT UNSIGNED NULL,
  `medecin_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patiente_id`) REFERENCES `patientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sagefemme_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`medecin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: nouveaux_nes (liés à accouchement)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `nouveaux_nes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `accouchement_id` INT UNSIGNED NOT NULL,
  `sexe` ENUM('M','F') NOT NULL,
  `poids` DECIMAL(5,3) NOT NULL COMMENT 'en kg',
  `taille` DECIMAL(5,2) NULL COMMENT 'en cm',
  `apgar_1min` TINYINT NULL,
  `apgar_5min` TINYINT NULL,
  `observations` TEXT NULL,
  FOREIGN KEY (`accouchement_id`) REFERENCES `accouchements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: postnatal (suivi)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `suivi_postnatal` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `patiente_id` INT UNSIGNED NOT NULL,
  `date_suivi` DATE NOT NULL,
  `type` ENUM('mere','bebe','les_deux') NOT NULL DEFAULT 'mere',
  `notes` TEXT NULL,
  `prochain_rdv` DATE NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patiente_id`) REFERENCES `patientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Table: audit_logs (traçabilité)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(100) NULL,
  `record_id` INT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_action` (`action`),
  INDEX `idx_table_record` (`table_name`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
