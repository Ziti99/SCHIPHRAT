#!/usr/bin/env python3
import mysql.connector
from mysql.connector import Error

def fix_database():
    try:
        # Connexion à la base de données
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='admin',
            database='clinique_obstetrique'
        )
        
        cursor = connection.cursor()
        
        print("🔧 Début de la correction de la base de données...")
        
        # 1. Supprimer la table grossesses si elle existe
        print("🗑️ Suppression de la table grossesses...")
        cursor.execute("DROP TABLE IF EXISTS grossesses")
        
        # 2. Vider et corriger la table consultations_prenatales
        print("📋 Correction de la table consultations_prenatales...")
        cursor.execute("DELETE FROM consultations_prenatales")
        cursor.execute("ALTER TABLE consultations_prenatales ADD COLUMN IF NOT EXISTS patiente_id INT")
        cursor.execute("ALTER TABLE consultations_prenatales DROP FOREIGN KEY IF EXISTS consultations_prenatales_ibfk_1")
        cursor.execute("ALTER TABLE consultations_prenatales DROP COLUMN IF EXISTS grossesse_id")
        cursor.execute("ALTER TABLE consultations_prenatales ADD CONSTRAINT IF NOT EXISTS fk_consultations_patiente FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE")
        cursor.execute("ALTER TABLE consultations_prenatales MODIFY COLUMN patiente_id INT NOT NULL")
        
        # 3. Vider et corriger la table accouchements
        print("👶 Correction de la table accouchements...")
        cursor.execute("DELETE FROM accouchements")
        cursor.execute("ALTER TABLE accouchements ADD COLUMN IF NOT EXISTS patiente_id INT")
        cursor.execute("ALTER TABLE accouchements DROP FOREIGN KEY IF EXISTS accouchements_ibfk_1")
        cursor.execute("ALTER TABLE accouchements DROP COLUMN IF EXISTS grossesse_id")
        cursor.execute("ALTER TABLE accouchements ADD CONSTRAINT IF NOT EXISTS fk_accouchements_patiente FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE")
        cursor.execute("ALTER TABLE accouchements MODIFY COLUMN patiente_id INT NOT NULL")
        
        # 4. Supprimer les index liés aux grossesses
        print("🗂️ Nettoyage des index...")
        cursor.execute("DROP INDEX IF EXISTS idx_grossesses_patiente_id ON grossesses")
        cursor.execute("DROP INDEX IF EXISTS idx_consultations_grossesse_id ON consultations_prenatales")
        cursor.execute("DROP INDEX IF EXISTS idx_accouchements_grossesse_id ON accouchements")
        
        # 5. Créer de nouveaux index
        print("📊 Création des nouveaux index...")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_consultations_patiente_id ON consultations_prenatales(patiente_id)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_accouchements_patiente_id ON accouchements(patiente_id)")
        
        # Valider les changements
        connection.commit()
        
        print("✅ Base de données corrigée avec succès !")
        print("📝 Modifications effectuées :")
        print("   - Table 'grossesses' supprimée")
        print("   - Table 'consultations_prenatales' : grossesse_id → patiente_id")
        print("   - Table 'accouchements' : grossesse_id → patiente_id")
        print("   - Index mis à jour")
        
    except Error as e:
        print(f"❌ Erreur : {e}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("🔌 Connexion fermée")

if __name__ == "__main__":
    fix_database() 