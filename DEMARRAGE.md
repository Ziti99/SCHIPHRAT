# Démarrage local – Tu as PHP sur ton PC

## ✅ Ce que j'ai fait pour toi

1. **Base de données conséquente créée** `database/clinique.db` (340KB)
   - 15 users, 200 patientes, 600 consultations, 120 accouchements, 132 bébés, 250 suivis, 500 logs
   - Noms gabonais réalistes

2. **Support SQLite ajouté dans le code PHP**
   - `src/Config/Database.php` détecte `DB_CONNECTION=sqlite` et résout le chemin relatif `database/clinique.db`
   - Fonctionne aussi en MySQL pour prod (`DB_CONNECTION=mysql`)

3. **Fichiers .env mis à jour**
   - `.env` et `.env.example` utilisent maintenant `DB_CONNECTION=sqlite` + `DB_DATABASE=database/clinique.db`

4. **Scripts de démarrage créés**
   - `start_local.sh` (Linux/Mac)
   - `start_local.bat` (Windows)
   - `server.js` (Node alternative, déjà en cours sur 0.0.0.0:8000 dans sandbox)

---

## 🚀 Comment démarrer l'app PHP chez toi (2 commandes)

### Prérequis (tu as déjà PHP)
```bash
php -v  # doit afficher PHP 8.0+
```

### Étapes

**1. Récupère la branche**
```bash
git pull origin arena/019fccef-schiphrat
# ou clone si pas encore
git clone -b arena/019fccef-schiphrat https://github.com/Abdoussalam001/SCHIPHRAT.git
cd SCHIPHRAT
```

**2. Installe dépendances (1ère fois)**
```bash
composer install
# Si tu n'as pas composer: https://getcomposer.org
# Alternative sans composer (mode dégradé): l'app a un fallback si vendor absent,
# mais mieux d'installer vlucas/phpdotenv
```

**3. Vérifie la base**
```bash
ls -lh database/clinique.db
# Doit afficher 340K
# Si absent:
python3 database/generate.py
# ou
php -r "echo 'ok';"
```

**4. Vérifie .env**
```bash
cat .env
# Doit contenir:
# DB_CONNECTION=sqlite
# DB_DATABASE=database/clinique.db
```
Si `.env` n'existe pas:
```bash
cp .env.example .env
```

**5. Démarre PHP**
```bash
# Linux/Mac:
./start_local.sh
# ou manuel:
php -S 0.0.0.0:8000 -t .

# Windows:
start_local.bat
# ou:
php -S 0.0.0.0:8000 -t .
```

**6. Ouvre dans navigateur**
```
http://localhost:8000
```

---

## 🔐 Comptes test (password = `password` pour tous)

- **Admin:** admin
- **Médecin:** medecin1, medecin2, medecin3, medecin4
- **Sage-femme:** sagefemme1, sagefemme2, sagefemme3, sagefemme4, sagefemme5
- **Secrétaire:** secretaire1, secretaire2
- **Caissier:** caissier1, caissier2

---

## 📊 Vérification que ça marche

Après login `admin / password`, tu dois voir:

- Dashboard: **Total Patientes 200**, **Consultations 600**, **Accouchements 120**
- Page Patientes: liste avec dossier `DOS-2025-xxxx`, noms gabonais (MBOUMBA, OND O...)
- Recherche: tape "MBOUMBA" → résultats
- Tu peux créer une nouvelle patiente (modal +)
- Consultations: 600 lignes, type prénatale etc.
- Rapports: stats groupes sanguins, types accouchement

Si tu vois **0 patiente**, c'est que la base SQLite n'est pas trouvée → vérifie que `database/clinique.db` existe et que `.env` pointe bien dessus.

---

## 🔄 Switch MySQL (prod)

Pour repasser en MySQL (Railway):

```env
DB_CONNECTION=mysql
DB_HOST=ton_host_railway
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASSWORD=ton_password
```

Puis importe `database/schema.sql` dans MySQL (le SQLite actuel est juste pour démo locale)

---

## 🖥️ Alternative Node (déjà en cours dans sandbox)

Si PHP pose problème, tu as aussi `server.js` qui utilise la même base SQLite:

```bash
npm install
npm start
# http://localhost:8000 aussi, mais version Node
```

Dans le sandbox Arena, ce serveur Node tourne déjà sur `0.0.0.0:8000` (PID 2065) – tu peux le voir en preview.

---

## ❓ Problèmes fréquents

**`SQLSTATE[HY000] [14] unable to open database file`**
→ Chemin SQLite incorrect. Mets `DB_DATABASE=database/clinique.db` relatif, pas absolu.

**`Vendor/autoload.php not found`**
→ `composer install`

**`Port 8000 already in use`**
→ Ferme ancien serveur: `lsof -ti:8000 | xargs kill` (Linux/Mac) ou change port: `php -S 0.0.0.0:8001`

**Page blanche**
→ Active `APP_DEBUG=true` dans `.env` pour voir erreurs

---

**C'est prêt ! Lance `php -S 0.0.0.0:8000` et dis-moi ce que tu vois.**
