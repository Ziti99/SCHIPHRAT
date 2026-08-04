-- Seeds – Mots de passe: "password" hashé bcrypt cost 12
-- Généré via: password_hash('password', PASSWORD_BCRYPT, ['cost'=>12])

-- Hash bcrypt cost 10 pour 'password' - sera re-hashé en cost 12 au premier login
INSERT INTO `users` (`username`, `password_hash`, `role`, `nom`, `prenom`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'System', 'admin@clinique.local'),
('medecin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin', 'Dupont', 'Marie', 'medecin1@clinique.local'),
('sagefemme1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sagefemme', 'Nguema', 'Claire', 'sagefemme1@clinique.local'),
('secretaire1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretaire', 'Mba', 'Pauline', 'secretaire1@clinique.local'),
('caissier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caissier', 'Obame', 'Jean', 'caissier1@clinique.local')
ON DUPLICATE KEY UPDATE role=VALUES(role);

-- Patientes exemples
INSERT INTO `patientes` (`dossier_number`, `nom`, `prenom`, `date_naissance`, `telephone`, `groupe_sanguin`, `adresse`) VALUES
('DOS-2025-001', 'MBOUMBA', 'Aïcha', '1995-04-12', '+241 06 12 34 56', 'O+', 'Libreville, Nzeng-Ayong'),
('DOS-2025-002', 'OND O', 'Grace', '1998-09-03', '+241 07 98 76 54', 'A+', 'Libreville, Louis'),
('DOS-2025-003', 'EYANG', 'Fatou', '1993-11-22', '+241 06 45 67 89', 'B+', 'Owendo')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);
