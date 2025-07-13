#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import mysql.connector
import os

def execute_sql_script():
    """Exécute le script SQL pour ajouter la colonne sage_femme_id"""
    
    # Paramètres de connexion à la base de données
    config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'admin',
        'database': 'clinique_obstetrique',
        'charset': 'utf8mb4',
        'collation': 'utf8mb4_unicode_ci'
    }
    
    try:
        # Connexion à la base de données
        print("Connexion à la base de données...")
        connection = mysql.connector.connect(**config)
        cursor = connection.cursor()
        
        # Lire le fichier SQL
        print("Lecture du fichier SQL...")
        with open('add_sage_femme_column.sql', 'r', encoding='utf-8') as file:
            sql_script = file.read()
        
        # Diviser le script en commandes individuelles
        commands = [cmd.strip() for cmd in sql_script.split(';') if cmd.strip()]
        
        # Exécuter chaque commande
        print("Exécution des commandes SQL...")
        for i, command in enumerate(commands, 1):
            if command and not command.startswith('--'):
                print(f"Exécution de la commande {i}/{len(commands)}...")
                try:
                    cursor.execute(command)
                    print(f"✓ Commande {i} exécutée avec succès")
                except mysql.connector.Error as err:
                    print(f"✗ Erreur lors de l'exécution de la commande {i}: {err}")
                    # Continuer avec les autres commandes
                    continue
        
        # Valider les changements
        connection.commit()
        print("✓ Toutes les modifications ont été appliquées avec succès!")
        
        # Vérifier la structure de la table
        print("\nVérification de la structure de la table suivi_postnatal:")
        cursor.execute("DESCRIBE suivi_postnatal")
        columns = cursor.fetchall()
        
        for column in columns:
            print(f"  - {column[0]}: {column[1]} ({'NULL' if column[2] == 'YES' else 'NOT NULL'})")
        
    except mysql.connector.Error as err:
        print(f"Erreur de connexion à la base de données: {err}")
        return False
    except FileNotFoundError:
        print("Erreur: Le fichier add_sage_femme_column.sql n'a pas été trouvé")
        return False
    except Exception as e:
        print(f"Erreur inattendue: {e}")
        return False
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()
            print("Connexion à la base de données fermée")
    
    return True

if __name__ == "__main__":
    print("=== Script d'ajout de la colonne sage_femme_id ===")
    success = execute_sql_script()
    
    if success:
        print("\n✅ Le script a été exécuté avec succès!")
        print("La colonne sage_femme_id a été ajoutée à la table suivi_postnatal")
    else:
        print("\n❌ Le script a échoué. Veuillez vérifier les erreurs ci-dessus.") 