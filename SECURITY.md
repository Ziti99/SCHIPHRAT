# Politique de sécurité – SCHIPHRAT

## Failles corrigées (2026-08-04)

### Critique S1 – Secrets en clair
- **Avant:** `login.php` contenait `root / [REDACTED-REVOKED]` + host Railway.
- **Après:** Supprimé. Tout via `.env` + `$_ENV`. `.gitignore` bloque `.env`.
- **Action requise:** Révoquer le mot de passe Railway dans dashboard Railway → MySQL → Reset password. Purger historique Git avec BFG: `bfg --delete-files login.php` ou `git filter-branch`.

### S2 – Database.php cassé
- Avant: path `__DIR__/../../.env` faux, parser maison fragile.
- Après: `src/Config/Database.php` utilise `vlucas/phpdotenv` + fallback robuste, supporte `MYSQLHOST` etc.

### S3 – Session fixation
- Ajout `session_regenerate_id(true)` après login, `httponly=true`, `samesite=Lax`, `secure` auto-détecté.

### S4 – Brute-force
- Rate limiting: 5 tentatives / 15min stocké en session + sleep(1) anti-timing.
- CSRF tokens sur tous les POST.

### S5 – Information disclosure
- `try/catch` autour PDO, `error_log()` seulement, message générique en prod `APP_DEBUG=false`.
- `open redirect` protection sur `?redirect=`.

### S6 – Mots de passe faibles
- Tous hashés bcrypt cost 12. Anciens cost 10 rehash automatique au login.
- Seeds utilisent hash connu Laravel pour `password` (sera rehashé).

## Bonnes pratiques adoptées

- `.env.example` complet, `composer.json` post-install safe
- Dockerfile non-root + healthcheck + opcache prod
- RBAC via `Auth::requireRole()`
- Sanitization `e()` + `htmlspecialchars`
- Audit logs table prête

## Comment reporter une faille

Contact admin – Ne pas committer de secrets. Utiliser variables Railway.

## Checklist prod

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Variables Railway définies
- [ ] Ancien password Railway révoqué
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] HTTPS activé (Railway fournit)
- [ ] Purge historique Git des secrets
