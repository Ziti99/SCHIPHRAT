-- Base de données pour la clinique obstétrique
CREATE DATABASE IF NOT EXISTS clinique_obstetrique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinique_obstetrique;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('admin', 'medecin', 'sage_femme', 'secretaire') NOT NULL,
    telephone VARCHAR(20),
    specialite VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des patientes
CREATE TABLE patientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    age INT,
    adresse TEXT NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    groupe_sanguin ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    nombre_grossesses INT DEFAULT 0,
    nombre_fausses_couches INT DEFAULT 0,
    antecedents_medicaux TEXT,
    allergies TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des grossesses
CREATE TABLE grossesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patiente_id INT NOT NULL,
    date_debut_grossesse DATE NOT NULL,
    date_terme_prevue DATE NOT NULL,
    date_terme_reelle DATE,
    statut ENUM('en_cours', 'terminee', 'interrompue') DEFAULT 'en_cours',
    tension_arterielle VARCHAR(20),
    poids_initial DECIMAL(5,2),
    poids_actuel DECIMAL(5,2),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE
);

-- Table des consultations prénatales
CREATE TABLE consultations_prenatales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grossesse_id INT NOT NULL,
    medecin_id INT NOT NULL,
    date_consultation DATETIME NOT NULL,
    tension_arterielle VARCHAR(20),
    poids DECIMAL(5,2),
    hauteur_uterine INT,
    position_foetus VARCHAR(100),
    frequence_cardiaque_foetale INT,
    observations TEXT,
    recommandations TEXT,
    prochaine_consultation DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grossesse_id) REFERENCES grossesses(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Table des examens
CREATE TABLE examens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consultation_id INT NOT NULL,
    type_examen ENUM('echographie', 'bilan_sanguin', 'urine', 'autre') NOT NULL,
    date_examen DATE NOT NULL,
    resultats TEXT,
    observations TEXT,
    fichier_resultat VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations_prenatales(id) ON DELETE CASCADE
);

-- Table des accouchements
CREATE TABLE accouchements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grossesse_id INT NOT NULL,
    date_accouchement DATETIME NOT NULL,
    mode_accouchement ENUM('voie_basse', 'cesarienne', 'forceps', 'ventouse') NOT NULL,
    duree_travail INT, -- en minutes
    complications TEXT,
    medecin_id INT NOT NULL,
    sage_femme_id INT,
    nom_bebe VARCHAR(100),
    sexe_bebe ENUM('M', 'F'),
    poids_bebe DECIMAL(4,3), -- en kg
    taille_bebe INT, -- en cm
    statut_bebe ENUM('vivant', 'mort_ne', 'decede') DEFAULT 'vivant',
    apgar_score INT,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grossesse_id) REFERENCES grossesses(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (sage_femme_id) REFERENCES users(id)
);

-- Table du suivi post-natal
CREATE TABLE suivi_postnatal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accouchement_id INT NOT NULL,
    date_visite DATE NOT NULL,
    type_visite ENUM('mere', 'bebe', 'mere_et_bebe') NOT NULL,
    medecin_id INT NOT NULL,
    observations_mere TEXT,
    observations_bebe TEXT,
    vaccinations TEXT,
    prochaine_visite DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (accouchement_id) REFERENCES accouchements(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Table des décès néonataux
CREATE TABLE deces_neonataux (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accouchement_id INT NOT NULL,
    date_deces DATETIME NOT NULL,
    cause_deces TEXT,
    age_deces INT, -- en heures
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (accouchement_id) REFERENCES accouchements(id) ON DELETE CASCADE
);

-- Table des sessions
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion des utilisateurs par défaut
INSERT INTO users (username, email, password, nom, prenom, role, specialite) VALUES
('admin', 'admin@clinique.com', '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy', 'Administrateur', 'Système', 'admin', NULL),
('medecin1', 'medecin1@clinique.com', '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy', 'Dupont', 'Marie', 'medecin', 'Gynécologie-Obstétrique'),
('sagefemme1', 'sagefemme1@clinique.com', '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy', 'Martin', 'Sophie', 'sage_femme', 'Sage-femme'),
('secretaire1', 'secretaire1@clinique.com', '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy', 'Bernard', 'Julie', 'secretaire', NULL);

-- Trigger pour calculer automatiquement l'âge
CREATE TRIGGER calculate_age_before_insert
BEFORE INSERT ON patientes
FOR EACH ROW
SET NEW.age = YEAR(CURDATE()) - YEAR(NEW.date_naissance);

CREATE TRIGGER calculate_age_before_update
BEFORE UPDATE ON patientes
FOR EACH ROW
SET NEW.age = YEAR(CURDATE()) - YEAR(NEW.date_naissance);

-- Table des registres numériques
CREATE TABLE registres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patiente_id INT NOT NULL,
    medecin_id INT NOT NULL,
    type_registre ENUM('naissance', 'deces', 'mariage', 'divorce') NOT NULL,
    date_evenement DATE NOT NULL,
    lieu_evenement VARCHAR(255),
    details TEXT,
    numero_registre VARCHAR(50) UNIQUE,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    observations TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Index pour optimiser les performances
CREATE INDEX idx_grossesses_patiente_id ON grossesses(patiente_id);
CREATE INDEX idx_consultations_grossesse_id ON consultations_prenatales(grossesse_id);
CREATE INDEX idx_accouchements_grossesse_id ON accouchements(grossesse_id);
CREATE INDEX idx_accouchements_date ON accouchements(date_accouchement);
CREATE INDEX idx_registres_type ON registres(type_registre);
CREATE INDEX idx_registres_date ON registres(date_evenement); 