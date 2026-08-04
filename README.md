# Clinique Obstétrique – Gestionnaire Sécurisé v2.0

Système de gestion complet pour cliniques obstétriques, **sécurisé et refactoré**.

## ✅ Failles corrigées (v2.0)

### Sécurité P0 – Critiques
- [x] **Secrets supprimés** : plus de credentials hardcodés dans `login.php`. Tout passe par `.env` + variables Railway (`DB_HOST`, `MYSQLHOST`, etc.)
- [x] `.gitignore` : `.env`, `vendor/`, logs ignorés
- [x] `Database.php` refait : singleton propre dans `src/Config/Database.php`, chargement via `vlucas/phpdotenv` avec fallback sécurisé, support Railway `MYSQL*` vars
- [x] Session durcie : `httponly`, `samesite=Lax`, `secure` auto, `session_regenerate_id(true)` anti-fixation
- [x] CSRF tokens + Rate-limiting (5 tentatives / 15min)
- [x] Pas de `die()` exposant PDO – erreurs loggées uniquement
- [x] Bcrypt cost 12 + rehash automatique
- [x] Protection open redirect sur login

### Architecture
- [x] Structure PSR-4 fonctionnelle : `src/Config`, `src/Models`, `src/Services`, `src/Helpers`
- [x] `Database.php` legacy devient wrapper deprecated vers nouvelle classe
- [x] `Auth` service centralisé : `attempt()`, `check()`, `requireAuth()`, `requireRole()`
- [x] Composer `post-install` ne casse plus `.env`
- [x] Dockerfile durci : multi-stage, non-root user, opcache prod, healthcheck

### Fonctionnel
- [x] `dashboard.php` créé – plus de 404
- [x] `logout.php`, `patientes.php`, `consultations.php`, `rapports.php`, `users.php` (RBAC admin)
- [x] Schéma SQL complet : `users`, `patientes`, `consultations`, `accouchements`, `nouveaux_nes`, `suivi_postnatal`, `audit_logs`
- [x] `database/seed.sql` avec comptes test (password = `password`)

---

## 📁 Nouvelle arborescence

```
├── src/
│   ├── Config/Database.php      # Singleton sécurisé, dotenv, Railway compat
│   ├── Models/User.php, Patiente.php
│   ├── Services/Auth.php        # Login, session, RBAC
│   └── Helpers/Security.php     # CSRF, rate-limit, sanitize
├── includes/
│   ├── auth.php                 # Middleware
│   └── layout.php               # Header/footer Tailwind
├── database/
│   ├── schema.sql               # Schéma complet
│   └── seed.sql
├── dashboard.php                # Tableau de bord (auth required)
├── patientes.php                # CRUD patientes
├── consultations.php / rapports.php / users.php
├── login.php                    # Sécurisé – CSRF + rate-limit
├── index.php                    # Landing + secure session init
├── .env.example                 # Complet
├── .gitignore
├── docker-compose.yml           # Dev local MySQL 8
├── Dockerfile (durci, non-root, healthcheck)
```

## 🚀 Installation locale

### Option 1 – Docker Compose (recommandé)

```bash
cp .env.example .env
docker-compose up --build
# http://localhost:8000
# MySQL: localhost:3307, user clinique / clinique_secret
```

### Option 2 – PHP natif

```bash
composer install
cp .env.example .env
# Edite .env avec tes credentials DB
php -S 0.0.0.0:8000
```

Importer DB :

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p clinique_obstetrique < database/seed.sql
```

## 🔐 Variables d'environnement

Voir `.env.example`. Supporte à la fois `DB_*` et Railway `MYSQL*` :

```
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
APP_DEBUG=false (masque comptes test en prod)
SESSION_SECURE, SESSION_SAMESITE
LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES
```

## 👥 Comptes test (password = `password`)

- **Admin:** admin
- **Médecin:** medecin1
- **Sage-femme:** sagefemme1
- **Secrétaire:** secretaire1
- **Caissier:** caissier1

> En production, `APP_DEBUG=false` masque cette liste.

## 🛡️ Sécurité

- Pas de secrets dans Git
- Sessions HttpOnly + SameSite + Secure auto
- CSRF tokens sur tous les POST
- Rate-limiting login
- Bcrypt cost 12 + rehash
- Audit logs table prête
- RBAC : `requireRole(['admin','medecin',...])`

## 📦 Déploiement Railway

Le projet est prêt :

1. Railway détecte Dockerfile
2. Définir variables dans Railway Dashboard : `DB_HOST`, `DB_PORT`, etc. ou utiliser plugin MySQL (il injecte `MYSQLHOST` automatiquement supporté)
3. `APP_DEBUG=false` en prod
4. Déployer – healthcheck inclus

**IMPORTANT :** Révoquez l'ancien password hardcodé `[REDACTED-REVOKED]` qui était dans Git.

## 🔮 Roadmap

- [ ] CRUD consultations avec calcul semaine grossesse
- [ ] Export PDF ordonnance (dompdf) + Excel (PhpSpreadsheet) via `src/Services/ExportService.php`
- [ ] Graphiques Chart.js dans rapports
- [ ] Tests phpunit + GitHub Actions
- [ ] 2FA optionnel

## 📄 Licence

Interne clinique – Tous droits réservés.
