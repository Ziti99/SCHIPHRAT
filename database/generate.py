#!/usr/bin/env python3
"""
Générateur de base de données conséquente pour Clinique Obstétrique
Utilise SQLite pour démo locale, mais compatible MySQL (même schéma)
Plateforme choisie: SQLite (simple, portable, pas de serveur) + Node/Express pour démo
"""

import sqlite3
import random
import datetime
import hashlib
from pathlib import Path

DB_PATH = Path(__file__).parent / "clinique.db"
SCHEMA_PATH = Path(__file__).parent / "schema.sql"

# Supprime ancienne DB
if DB_PATH.exists():
    DB_PATH.unlink()

conn = sqlite3.connect(str(DB_PATH))
conn.execute("PRAGMA foreign_keys = ON;")
cur = conn.cursor()

# Schéma SQLite adapté
schema_sqlite = """
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('admin','medecin','sagefemme','secretaire','caissier')),
  nom TEXT NOT NULL,
  prenom TEXT NOT NULL,
  email TEXT UNIQUE,
  is_active INTEGER NOT NULL DEFAULT 1,
  last_login_at DATETIME,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patientes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  dossier_number TEXT NOT NULL UNIQUE,
  nom TEXT NOT NULL,
  prenom TEXT NOT NULL,
  date_naissance DATE,
  telephone TEXT,
  adresse TEXT,
  groupe_sanguin TEXT CHECK(groupe_sanguin IN ('A+','A-','B+','B-','AB+','AB-','O+','O-')),
  antecedents TEXT,
  allergies TEXT,
  created_by INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE consultations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patiente_id INTEGER NOT NULL,
  type TEXT NOT NULL CHECK(type IN ('prenatale','postnatale','urgence','autre')) DEFAULT 'prenatale',
  date_consultation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  semaine_grossesse INTEGER,
  poids REAL,
  tension_arterielle TEXT,
  hauteur_uterine REAL,
  bebe_coeur INTEGER,
  observations TEXT,
  prescription TEXT,
  medecin_id INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
  FOREIGN KEY (medecin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE accouchements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patiente_id INTEGER NOT NULL,
  date_accouchement DATETIME NOT NULL,
  type_accouchement TEXT NOT NULL CHECK(type_accouchement IN ('voie_basse','cesarienne','instrumental')),
  lieu TEXT,
  duree_travail TEXT,
  complications TEXT,
  sagefemme_id INTEGER,
  medecin_id INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
  FOREIGN KEY (sagefemme_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (medecin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE nouveaux_nes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  accouchement_id INTEGER NOT NULL,
  sexe TEXT NOT NULL CHECK(sexe IN ('M','F')),
  poids REAL NOT NULL,
  taille REAL,
  apgar_1min INTEGER,
  apgar_5min INTEGER,
  observations TEXT,
  FOREIGN KEY (accouchement_id) REFERENCES accouchements(id) ON DELETE CASCADE
);

CREATE TABLE suivi_postnatal (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patiente_id INTEGER NOT NULL,
  date_suivi DATE NOT NULL,
  type TEXT NOT NULL CHECK(type IN ('mere','bebe','les_deux')) DEFAULT 'mere',
  notes TEXT,
  prochain_rdv DATE,
  created_by INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  action TEXT NOT NULL,
  table_name TEXT,
  record_id INTEGER,
  old_values TEXT,
  new_values TEXT,
  ip_address TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_patientes_nom ON patientes(nom, prenom);
CREATE INDEX idx_consult_patiente ON consultations(patiente_id, date_consultation);
"""

cur.executescript(schema_sqlite)
print("✅ Schéma SQLite créé")

# --- Données réalistes Gabon ---
noms = ["MBOUMBA","OND O","EYANG","MBA","OBAME","NGUEMA","MOUCKAGA","BOUANGA","IBINGA","MOUSSAVOU",
        "NZAMBI","KOUMBA","MABIKA","DIBOTI","ESSO","AWORET","LEYOUBA","MENDOUME","OWONO","NDONG",
        "MENGUE","ADIAHENOT","MOMBO","MVE","MFOUBOU","PEMBE","ZANG","EDZANG","EKOMI","BILOGHE"]
prenoms_f = ["Aïcha","Grace","Fatou","Pauline","Claire","Marie","Chantal","Sylvie","Estelle","Nadine",
             "Jeanine","Prisca","Larissa","Mireille","Yasmine","Divine","Ornella","Belinda","Tania","Lea",
             "Christelle","Sandrine","Josiane","Vanessa","Laure","Joyce","Patricia","Elodie","Jessica","Nathalie"]
prenoms_m = ["Jean","Paul","Pierre","Jacques","Alain","Marc","Luc","Fabrice","Ghislain","Rodrigue",
             "Ulrich","Arnaud","Brice","Wilfried","Franck","Yannick","Steeve","Davy","Christian","Patrick"]
quartiers = ["Nzeng-Ayong","Louis","Owendo","Akebe","Glass","Alibandeng","Plein Ciel","Charbonnages","Nzeng-Ayong","Sotega",
             "Awendje","Batavéa","Damas","Derrière l'école","Lalala","Atong-Abe","Acaé","Nkoltang","Bikélé","Okala"]
groupes = ["A+","A-","B+","B-","AB+","AB-","O+","O-"]
tensions = ["120/80","110/70","130/85","125/80","115/75","140/90","118/78","122/82"]
lieux = ["Salle 1","Salle 2","Bloc opératoire","Maternité","Urgences"]
antecedents_list = ["Aucun","HTA familiale","Diabète gestationnel antérieur","Césarienne antérieure en 2020","Fausse couche 2021","Drépanocytose AS","Asthme léger","Aucun","Paludisme récurrent","Anémie modérée"]
observations_list = [
    "Patiente en bon état général, bonne évolution grossesse",
    "Légère anémie, supplémentation fer prescrite",
    "TA légèrement élevée, repos conseillé, contrôle dans 3 jours",
    "Col fermé, présentation céphalique, RAS",
    "Oedèmes membres inférieurs, protéinurie négative",
    "Prise de poids régulière, conseils nutritionnels donnés",
    "BDC présents, mouvements foetaux perçus",
    "Patiente anxieuse, réassurance et explications données"
]
prescriptions_list = [
    "Acide folique 5mg 1cp/j + Fer 1cp/j",
    "Mebendazole, Fer, Acide folique",
    "Repos, régime hyposodé, contrôle TA quotidien",
    "Paracétamol si douleurs, RDV dans 1 semaine",
    "Echographie morpho T2, Bilan sanguin complet",
    "Vaccin antitétanique, conseils hygiène",
    "SP sulfadoxine, MILDA, fer"
]

# Hash pour password = "password" (bcrypt via python? on utilise un hash simple pour demo, mais on log le vrai bcrypt en commentaire)
# Pour SQLite demo Node, on utilisera bcryptjs côté Node, on stocke un hash compatible
# On génère un hash bcrypt cost 10 via python passlib si dispo, sinon on met un hash connu
try:
    import bcrypt
    def hash_pw(pw):
        return bcrypt.hashpw(pw.encode(), bcrypt.gensalt(rounds=10)).decode()
except:
    # Hash Laravel connu pour "password"
    def hash_pw(pw):
        return "$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi"

# 1. Users – 15 utilisateurs réalistes
users = [
    ("admin","admin","Admin","System","admin@clinique.local"),
    ("medecin1","medecin","Dupont","Marie","marie.dupont@clinique.local"),
    ("medecin2","medecin","Ondo","Richard","richard.ondo@clinique.local"),
    ("medecin3","medecin","Mba","Esther","esther.mba@clinique.local"),
    ("sagefemme1","sagefemme","Nguema","Claire","claire.nguema@clinique.local"),
    ("sagefemme2","sagefemme","Koumba","Sylviane","sylviane.koumba@clinique.local"),
    ("sagefemme3","sagefemme","Moussavou","Grace","grace.moussavou@clinique.local"),
    ("sagefemme4","sagefemme","Ibinga","Prisca","prisca.ibinga@clinique.local"),
    ("secretaire1","secretaire","Mba","Pauline","pauline.mba@clinique.local"),
    ("secretaire2","secretaire","Obame","Nadine","nadine.obame@clinique.local"),
    ("caissier1","caissier","Obame","Jean","jean.obame@clinique.local"),
    ("caissier2","caissier","Nzambi","Luc","luc.nzambi@clinique.local"),
    ("admin2","admin","Eyang","Brice","brice.eyang@clinique.local"),
    ("medecin4","medecin","Mouckaga","Alain","alain.mouckaga@clinique.local"),
    ("sagefemme5","sagefemme","Bouanga","Mireille","mireille.bouanga@clinique.local"),
]

cur.execute("DELETE FROM users")
for u in users:
    cur.execute("INSERT INTO users (username, role, nom, prenom, email, password_hash) VALUES (?,?,?,?,?,?)",
                (u[0], u[1], u[2].upper(), u[3], u[4], hash_pw("password")))

print(f"✅ {len(users)} utilisateurs insérés")

# 2. Patientes – 200 dossiers
cur.execute("DELETE FROM nouveaux_nes")
cur.execute("DELETE FROM accouchements")
cur.execute("DELETE FROM consultations")
cur.execute("DELETE FROM suivi_postnatal")
cur.execute("DELETE FROM patientes")

dossier_start = 1000
patientes_ids = []
for i in range(200):
    nom = random.choice(noms)
    prenom = random.choice(prenoms_f)
    dossier = f"DOS-{random.randint(2023,2025)}-{dossier_start+i:04d}"
    # date naissance 18-40 ans
    age = random.randint(18,42)
    dob = datetime.date.today() - datetime.timedelta(days=age*365 + random.randint(0,365))
    tel = f"+241 {random.randint(2,7):02d} {random.randint(10,99)} {random.randint(10,99)} {random.randint(10,99)}"
    adresse = f"{random.choice(quartiers)}, Libreville"
    gs = random.choice(groupes + [None,None])  # parfois null
    antecedents = random.choice(antecedents_list)
    allergies = random.choice(["Aucune","Pénicilline","Arachide","Aucune","Aucune","Latex"])
    created_by = random.randint(1, len(users))
    created_at = datetime.datetime.now() - datetime.timedelta(days=random.randint(0, 700))
    cur.execute("""INSERT INTO patientes 
                   (dossier_number, nom, prenom, date_naissance, telephone, adresse, groupe_sanguin, antecedents, allergies, created_by, created_at)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?)""",
                (dossier, nom, prenom, dob.isoformat(), tel, adresse, gs, antecedents, allergies, created_by, created_at.isoformat()))
    patientes_ids.append(cur.lastrowid)

print(f"✅ {len(patientes_ids)} patientes insérées")

# 3. Consultations – 600 consultations
consult_ids = []
for _ in range(600):
    pat_id = random.choice(patientes_ids)
    type_c = random.choices(["prenatale","postnatale","urgence","autre"], weights=[75,10,10,5])[0]
    date_cons = datetime.datetime.now() - datetime.timedelta(days=random.randint(0, 365), hours=random.randint(0,23))
    semaine = random.randint(6,40) if type_c=="prenatale" else None
    poids = round(random.uniform(55, 95),1)
    ta = random.choice(tensions)
    hu = round(random.uniform(20, 38),1) if semaine else None
    bebe_coeur = random.randint(120,160) if type_c=="prenatale" else None
    obs = random.choice(observations_list)
    presc = random.choice(prescriptions_list)
    med_id = random.choice([2,3,4,14]) # medecins
    cur.execute("""INSERT INTO consultations 
                   (patiente_id, type, date_consultation, semaine_grossesse, poids, tension_arterielle, hauteur_uterine, bebe_coeur, observations, prescription, medecin_id)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?)""",
                (pat_id, type_c, date_cons.isoformat(), semaine, poids, ta, hu, bebe_coeur, obs, presc, med_id))
    consult_ids.append(cur.lastrowid)

print(f"✅ {len(consult_ids)} consultations insérées")

# 4. Accouchements – 120 accouchements (60% des patientes)
accouchement_ids = []
for _ in range(120):
    pat_id = random.choice(patientes_ids)
    date_acc = datetime.datetime.now() - datetime.timedelta(days=random.randint(0, 400))
    type_acc = random.choices(["voie_basse","cesarienne","instrumental"], weights=[70,25,5])[0]
    lieu = random.choice(lieux)
    duree = f"{random.randint(2,18)}h{random.randint(0,59):02d}"
    complications = random.choices(["Aucune","Hémorragie légère","Délivrance difficile","Aucune","Aucune","Déchirure périnéale 1er degré","Souffrance foetale"], weights=[60,10,5,10,5,5,5])[0]
    sage_id = random.choice([5,6,7,8,15])
    med_id = random.choice([2,3,4,14, None])
    cur.execute("""INSERT INTO accouchements 
                   (patiente_id, date_accouchement, type_accouchement, lieu, duree_travail, complications, sagefemme_id, medecin_id)
                   VALUES (?,?,?,?,?,?,?,?)""",
                (pat_id, date_acc.isoformat(), type_acc, lieu, duree, complications, sage_id, med_id))
    accouchement_ids.append(cur.lastrowid)

print(f"✅ {len(accouchement_ids)} accouchements insérés")

# 5. Nouveaux nés – 130 bébés (jumeaux possibles)
bebes = []
for acc_id in accouchement_ids:
    # 8% jumeaux
    nb_bebes = 2 if random.random() < 0.08 else 1
    for _ in range(nb_bebes):
        sexe = random.choice(["M","F"])
        poids = round(random.uniform(2.2, 4.2),3)
        taille = round(random.uniform(45,55),1)
        apgar1 = random.randint(7,10)
        apgar5 = random.randint(8,10)
        obs = random.choice(["Vigoureux, cri immédiat","Léger encombrement, aspiration","RAS","Bon adaptation"])
        cur.execute("""INSERT INTO nouveaux_nes 
                       (accouchement_id, sexe, poids, taille, apgar_1min, apgar_5min, observations)
                       VALUES (?,?,?,?,?,?,?)""",
                    (acc_id, sexe, poids, taille, apgar1, apgar5, obs))
        bebes.append(cur.lastrowid)

print(f"✅ {len(bebes)} nouveaux-nés insérés")

# 6. Suivi postnatal – 250 suivis
for _ in range(250):
    pat_id = random.choice(patientes_ids)
    date_suivi = (datetime.date.today() - datetime.timedelta(days=random.randint(0, 200))).isoformat()
    type_s = random.choice(["mere","bebe","les_deux"])
    notes = random.choice([
        "Mère en bon état, allaitement bien établi",
        "Bébé icterique léger, surveillance",
        "Plaie césarienne propre, fils à J7",
        "Conseils PF donnés, RDV 6 semaines",
        "Bébé prise de poids satisfaisante"
    ])
    prochain = (datetime.date.today() + datetime.timedelta(days=random.randint(7, 45))).isoformat()
    created_by = random.choice([5,6,7,8])
    cur.execute("""INSERT INTO suivi_postnatal 
                   (patiente_id, date_suivi, type, notes, prochain_rdv, created_by)
                   VALUES (?,?,?,?,?,?)""",
                (pat_id, date_suivi, type_s, notes, prochain, created_by))

print(f"✅ 250 suivis postnatals insérés")

# 7. Audit logs – 500 logs
actions = ["LOGIN","CREATE_PATIENTE","UPDATE_PATIENTE","CREATE_CONSULTATION","EXPORT_PDF","EXPORT_EXCEL","CREATE_ACCOUCHEMENT"]
for _ in range(500):
    user_id = random.randint(1, len(users))
    action = random.choice(actions)
    table = random.choice(["patientes","consultations","accouchements","users", None])
    record_id = random.randint(1, 200)
    ip = f"192.168.1.{random.randint(2,254)}"
    dt = datetime.datetime.now() - datetime.timedelta(days=random.randint(0,30), hours=random.randint(0,23))
    cur.execute("INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, created_at) VALUES (?,?,?,?,?,?)",
                (user_id, action, table, record_id, ip, dt.isoformat()))

print(f"✅ 500 logs d'audit insérés")

conn.commit()

# Stats finales
cur.execute("SELECT COUNT(*) FROM patientes")
print(f"\n📊 STATS FINALES:")
print(f"  Users: {cur.execute('SELECT COUNT(*) FROM users').fetchone()[0]}")
print(f"  Patientes: {cur.execute('SELECT COUNT(*) FROM patientes').fetchone()[0]}")
print(f"  Consultations: {cur.execute('SELECT COUNT(*) FROM consultations').fetchone()[0]}")
print(f"  Accouchements: {cur.execute('SELECT COUNT(*) FROM accouchements').fetchone()[0]}")
print(f"  Nouveaux-nés: {cur.execute('SELECT COUNT(*) FROM nouveaux_nes').fetchone()[0]}")
print(f"  Suivi postnatal: {cur.execute('SELECT COUNT(*) FROM suivi_postnatal').fetchone()[0]}")
print(f"  Audit logs: {cur.execute('SELECT COUNT(*) FROM audit_logs').fetchone()[0]}")

conn.close()
print(f"\n💾 Base SQLite créée: {DB_PATH} ({DB_PATH.stat().st_size/1024/1024:.2f} MB)")
