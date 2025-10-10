#!/usr/bin/env python3
"""
Script Python pour corriger la structure de la table users
Exécuter avec: python fix_users_table.py
"""

import mysql.connector
import bcrypt
from datetime import datetime

# Configuration de la base de données
config = {
    'host': 'metro.proxy.rlwy.net',
    'port': 29698,
    'database': 'railway',
    'user': 'root',
    'password': 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI'
}

try:
    # Connexion à la base de données
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()
    
    print("✓ Connexion réussie à la base de données\n")
    
    # Vérifier si la table users existe
    cursor.execute("SHOW TABLES LIKE 'users'")
    if cursor.fetchone() is None:
        print("❌ La table 'users' n'existe pas.")
        print("Création de la table users...\n")
        
        # Créer la table users
        create_table = """
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            role ENUM('admin', 'medecin', 'sage_femme', 'secretaire', 'caissiere') NOT NULL,
            telephone VARCHAR(20),
            specialite VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
        
        cursor.execute(create_table)
        print("✓ Table 'users' créée avec succès\n")
        
        # Hash du mot de passe par défaut
        default_password = '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy'
        
        # Insérer les utilisateurs par défaut
        print("Insertion des utilisateurs par défaut...")
        users = [
            ('admin', 'admin@clinique.com', default_password, 'Administrateur', 'Système', 'admin', None),
            ('medecin1', 'medecin1@clinique.com', default_password, 'Dupont', 'Marie', 'medecin', 'Gynécologie-Obstétrique'),
            ('sagefemme1', 'sagefemme1@clinique.com', default_password, 'Martin', 'Sophie', 'sage_femme', 'Sage-femme'),
            ('secretaire1', 'secretaire1@clinique.com', default_password, 'Bernard', 'Julie', 'secretaire', None)
        ]
        
        insert_query = """
            INSERT INTO users (username, email, password, nom, prenom, role, specialite)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        
        for user in users:
            cursor.execute(insert_query, user)
            print(f"  ✓ Utilisateur '{user[0]}' créé")
        
        conn.commit()
        print("\n✓ Tous les utilisateurs par défaut ont été créés")
        
    else:
        print("✓ La table 'users' existe\n")
        
        # Vérifier la structure de la table
        print("Structure actuelle de la table 'users':")
        print("-" * 80)
        
        cursor.execute("DESCRIBE users")
        columns = cursor.fetchall()
        
        has_password_column = False
        for column in columns:
            print(f"  {column[0]:<20} {column[1]:<30} {column[2]}")
            if column[0] == 'password':
                has_password_column = True
        
        print("-" * 80 + "\n")
        
        # Ajouter la colonne password si elle n'existe pas
        if not has_password_column:
            print("❌ La colonne 'password' n'existe pas.")
            print("Ajout de la colonne 'password'...")
            
            cursor.execute("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email")
            print("✓ Colonne 'password' ajoutée avec succès\n")
            
            # Mettre à jour tous les utilisateurs avec le mot de passe par défaut
            print("Mise à jour des mots de passe pour tous les utilisateurs...")
            default_password = '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy'
            cursor.execute(f"UPDATE users SET password = '{default_password}'")
            conn.commit()
            print("✓ Mots de passe mis à jour (mot de passe par défaut: 'password')\n")
        else:
            print("✓ La colonne 'password' existe déjà\n")
        
        # Vérifier les utilisateurs existants
        print("Utilisateurs dans la base de données:")
        print("-" * 80)
        
        cursor.execute("SELECT id, username, email, role, nom, prenom FROM users")
        users = cursor.fetchall()
        
        if users:
            for user in users:
                print(f"  ID: {user[0]:<3} | Username: {user[1]:<15} | Role: {user[3]:<12} | Nom: {user[5]} {user[4]}")
        else:
            print("  Aucun utilisateur trouvé.")
        
        print("-" * 80 + "\n")
    
    print("\n✅ SUCCÈS : La base de données est maintenant correctement configurée !")
    print("\nVous pouvez maintenant vous connecter avec:")
    print("  - Username: admin")
    print("  - Password: password\n")
    
    cursor.close()
    conn.close()
    
except mysql.connector.Error as e:
    print(f"❌ ERREUR : {e}")
    exit(1)

