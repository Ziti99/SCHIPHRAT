# Analyse du projet SCHIPHRAT – Clinique Obstétrique

**Date :** 2026-08-04  
**Branche analysée :** `arena/019fccef-schiphrat` (basée sur `main` - commit `9190fb4`)  
**Type :** Application web PHP vanilla

---

## 1. Résumé exécutif

> Le projet se présente comme un **système complet de gestion de clinique obstétrique** mais l'état actuel du code est un **MVP cassé à ~5%**. Seules 2 pages existent : une landing page (`index.php`) et un login (`login.php`). Aucune fonctionnalité métier listée dans le README n'est implémentée. Le code présente une **faille critique de sécurité : credentials MySQL Railway en clair dans `login.php` committés dans Git.**

**Score global : 2/10**  
- Fonctionnel: 1/10
- Sécurité: 0/10
- Architecture: 2/10
- Maintenabilité: 2/10
- DevOps: 5/10

---

## 2. Arborescence réelle

```
/ (root)
├── index.php          # Landing page Tailwind (1 page marketing)
├── login.php          # Login avec PDO direct + credentials hardcodés
├── Database.php       # Classe Singleton inutilisée, namespace cassé
├── composer.json      # 4 dépendances jamais utilisées + autoload vers src/ inexistant
├── Dockerfile         # php:8.2-cli + php -S 0.0.0.0:${PORT}
├── Procfile           # Railway/Heroku
├── railway.json       # builder dockerfile
├── .env.example       # 4 lignes seulement
└── README.md          # Documentation marketing
```

**Fichiers manquants critiques :**
- `dashboard.php` → référencé dans `index.php` et `login.php` mais inexistant → 404 après login
- `src/` → déclaré dans composer.json PSR-4 `Clinique\` mais dossier absent
- `.gitignore`, `composer.lock`, `.env`, `src/Models`, `src/Controllers`, `logout.php`, `config/`
- Toute la partie métier: patientes, consultations, accouchements, etc.

---

## 3. Analyse fichier par fichier

### 3.1 `index.php` (5602 bytes)
- Bonne intention: page d'accueil moderne avec Tailwind CDN + FontAwesome
- `session_start()` puis redirect vers `dashboard.php` si connecté
- Sections Hero + Features mais Features vide (titre seulement)
- Problèmes:
  - Pas d'inclusion de layout / template engine → duplication future
  - Tailwind en CDN = pas prod-ready (pas de purge, 300kb)
  - Pas de CSP, pas de footer utile

### 3.2 `login.php` (5842 bytes)
**Le point le plus critique.**

```php
$host = 'metro.proxy.rlwy.net';
$port = '29698';
$dbname = 'railway';
$username = 'root';
$password = '[REDACTED-REVOKED]';
```

- Credentials Railway en dur, visibles dans GitHub public. **Doivent être révoqués immédiatement.**
- Connexion PDO directe, pas via `Database.php`
- Requête préparée OK (pas d'injection SQL), `password_verify` OK
- Failles:
  - Pas de `session_regenerate_id()` → fixation de session
  - Pas de rate-limiting / captcha → brute-force
  - Pas de CSRF token
  - Messages d'erreur génériques OK mais log d'erreur avec `die()` expose stack
  - Pas de `httponly`, `secure`, `samesite` sur cookie session
  - Comptes test affichés en clair sur la page (`admin / password`)
  - Pas de logout

### 3.3 `Database.php` (2211 bytes)
- Namespace `Clinique\Config` mais fichier à la racine → autoloader ne le trouvera jamais
- `loadEnv()` maison qui parse `.env` de façon naïve (`explode('=', ...)`) → casse si valeur contient `=` ou `#`
- `__DIR__ . '/../../.env'` → suppose que fichier est dans `src/Config/` mais il est en root → path faux
- Credentials en dur différents de `login.php`: `localhost / clinique_obstetrique / root / admin` → incohérence totale
- Singleton OK mais jamais utilisé nulle part
- Pas de gestion d'environnement Railway

### 3.4 `composer.json`
```json
"require": {
  "vlucas/phpdotenv": "^5.5",
  "phpmailer/phpmailer": "^6.8",
  "dompdf/dompdf": "^2.0",
  "phpoffice/phpspreadsheet": "^1.29"
}
```
- Dépendances lourdes déclarées mais jamais utilisées (pas de `use PHPMailer\...`)
- `vlucas/phpdotenv` installé mais `Database.php` réinvente son propre parser au lieu de l'utiliser
- `psr-4 Clinique\ => src/` → dossier `src/` n'existe pas → autoload cassé
- `post-install-cmd` copie `.env.example` vers `.env` → dangereux en prod (écrase)
- Pas de `composer.lock` committé → build non déterministe

### 3.5 `Dockerfile` & déploiement
- Base `php:8.2-cli` plutôt que `php:8.2-fpm` ou Apache → fixé dans dernier commit pour éviter conflit MPM. Bonne idée pour Railway simple.
- `php -S 0.0.0.0:${PORT}` → serveur de dev PHP, non recommandé prod (single-thread, pas de robustesse)
- `composer install --no-dev || true` masque les erreurs → build silencieux si échec
- Pas de multi-stage, pas de user non-root, pas de healthcheck
- `EXPOSE 8000` OK, `chmod 755` OK
- `railway.json` et `Procfile` cohérents mais minimalistes

### 3.6 `.env.example`
- 4 lignes, commente DB et MAIL → pas utilisable
- Utilise `${RAILWAY_PUBLIC_DOMAIN}` syntaxe Railway OK

---

## 4. Sécurité – 🔴 CRITIQUE

| # | Vulnérabilité | Gravité | Détail |
|---|---------------|---------|--------|
| S1 | **Secret exposé** | CRITIQUE | MySQL root password Railway en clair dans `login.php` + Git history |
| S2 | Pas de `.gitignore` pour `.env` | Haute | Risque de commit d'autres secrets |
| S3 | Session fixation | Moyenne | Pas de `session_regenerate_id(true)` |
| S4 | Pas de rate limiting | Moyenne | Brute-force sur login possible |
| S5 | XSS stocké potentiel futur | Moyenne | Aucune couche d'échappement centralisée |
| S6 | die() avec message PDO | Moyenne | Expose infos DB à l'attaquant si connexion échoue |
| S7 | Cookies session non durcis | Faible | Pas de `httponly/secure/samesite` |
| S8 | Comptes test `password` | Info | Doivent être changés + hash fort (bcrypt OK si password_verify utilisé) |

**Action immédiate requise:**
1. Révoquer le mot de passe `[REDACTED-REVOKED]` dans dashboard Railway
2. `git filter-branch` ou BFG pour purger l'historique
3. Passer les credentials en variables d'environnement

---

## 5. Architecture & qualité de code

**Pattern actuel :** Aucun. 2 scripts procéduraux + 1 classe orpheline.

**Dette technique :**
- Pas de MVC, pas de router, pas de controller
- Duplication de config DB (2 endroits, 2 jeux de credentials)
- Pas de gestion d'erreur centralisée
- Pas de validation, pas de couche modèle
- HTML + PHP mélangés
- Pas de tests (phpunit déclaré mais 0 test)
- Pas de lint, psr-12, phpstan

**Ce qui devrait exister pour tenir la promesse README :**
```
src/
├── Config/Database.php (utilise dotenv)
├── Models/ Patiente.php, Consultation.php, Accouchement.php, User.php
├── Controllers/ AuthController.php, PatienteController.php...
├── Views/ layout.php, dashboard.php, patientes/*
├── Services/ AuthService.php, ExportService.php (dompdf/phpspreadsheet)
└── Migrations/ 001_create_users.sql, etc.
public/
├── index.php (front controller)
├── assets/
```

---

## 6. Fonctionnel – Promesse vs Réalité

| Fonctionnalité README | Statut |
|-----------------------|--------|
| Gestion des patientes | ❌ 0% |
| Suivi consultations prénatales | ❌ 0% |
| Enregistrement accouchements | ❌ 0% |
| Suivi post-natal | ❌ 0% |
| Registres numériques | ❌ 0% |
| Rapports et statistiques | ❌ 0% (librairies installées mais inutilisées) |
| Gestion utilisateurs (5 rôles) | ⚠️ 20% – table `users` supposée exister, login OK, mais pas de CRUD ni RBAC |
| Authentification | ⚠️ 50% – fonctionne mais cassé après login (dashboard manquant) |

Le README est donc purement aspiratif.

---

## 7. Base de données (supposée)

`login.php` suppose :
```sql
CREATE TABLE users (
  id INT,
  username VARCHAR,
  password_hash VARCHAR,
  role ENUM('admin','medecin','sagefemme','secretaire','caissier'),
  nom VARCHAR,
  prenom VARCHAR
);
```

Aucun fichier de migration, aucun schema.sql, aucun seed. Impossible de reproduire en local sans accéder à Railway.

---

## 8. DevOps / Déploiement

**Positif:**
- Déploiement Railway fonctionnel (Dockerfile détecté)
- Fix récent du conflit Apache MPM → pragmatique

**À améliorer:**
- Serveur `php -S` non prod → passer à `php-fpm + nginx` ou `frankenphp / caddy`
- Pas de CI (GitHub Actions)
- Pas de healthcheck
- Pas de logs structurés
- Pas de gestion `PORT` robuste
- Image Docker non optimisée (libzip-dev, oniguruma-dev laissés dans image)

---

## 9. Recommandations priorisées

### P0 – Bloquant sécurité (à faire aujourd'hui)
1. **Changer le mot de passe MySQL Railway** et ne plus jamais commiter de secrets
2. Créer `.gitignore` avec `.env`, `vendor/`, `*.log`
3. Créer `.env` réel avec `DB_HOST`, `DB_PORT`, etc. + utiliser `vlucas/phpdotenv`
4. Refactor `Database.php` pour lire `$_ENV` + corriger path + déplacer dans `src/Config/`
5. Ajouter `session_regenerate_id(true)` après login + config cookie: `httponly=1, samesite=Lax`
6. Implémenter `dashboard.php` minimal pour ne plus avoir de 404

### P1 – Fondations (cette semaine)
1. Créer structure MVC minimale + front-controller `public/index.php` + router simple
2. Créer `src/Config/Database.php` singleton qui marche, suppression de l'ancien
3. Créer migrations SQL (`database/schema.sql`)
4. Créer modèles `User`, `Patiente`
5. Supprimer dépendances inutilisées ou les utiliser (ex: PHPMailer pour reset password)
6. Ajouter validation + gestion erreurs + logger
7. Ajouter `docker-compose.yml` pour dev local MySQL
8. Remplacer Tailwind CDN par build, ou au moins ajouter CSP

### P2 – Features métier (roadmap 1 mois)
1. CRUD Patientes
2. Consultations prénatales
3. Auth avec RBAC middleware (admin, medecin, sagefemme...)
4. Dashboard avec stats
5. Exports PDF (dompdf) et Excel (PhpSpreadsheet)
6. Tests phpunit + GitHub Actions
7. Passer à PHP-FPM + Nginx en Docker multi-stage

---

## 10. Proposition de refactor rapide (exemple)

**Nouveau `Database.php` (corrigé) :**
```php
<?php
namespace Clinique\Config;
use Dotenv\Dotenv;
use PDO;

class Database {
  private static ?self $instance = null;
  private PDO $pdo;
  private function __construct() {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->safeLoad();
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
      $_ENV['DB_HOST']??'127.0.0.1',
      $_ENV['DB_PORT']??'3306',
      $_ENV['DB_NAME']??'clinique'
    );
    $this->pdo = new PDO($dsn, $_ENV['DB_USER']??'root', $_ENV['DB_PASSWORD']??'', [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
  }
  public static function getInstance(): self { ... }
}
```

---

## 11. Conclusion

Le projet est actuellement une **coquille vide avec une faille critique**. Le README vend une application complète, mais le code ne permet même pas de se connecter sans tomber sur une 404.

Il y a deux options :

**Option A – Refonte propre (recommandée)**
- Garder le design Tailwind (bonne base visuelle)
- Repartir sur un squelette MVC propre (ou micro-framework comme Slim/Laravel)
- Implémenter réellement les features une par une avec migrations.

**Option B – Patcher le minimum pour demo**
- Corriger `Database.php`, créer `dashboard.php`, utiliser `.env`, changer password.
- Mais la dette restera énorme.

Dans tous les cas, **la première action est sécurité : révoquer le secret Railway exposé.**

---

*Analyse générée automatiquement – Arena Agent*
