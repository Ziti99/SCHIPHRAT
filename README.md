# Clinique Obstétrique - Gestionnaire de Cabinet Médical

Système de gestion complet pour cliniques obstétriques avec:
- Gestion des patientes
- Suivi des consultations prénatales
- Enregistrement des accouchements
- Suivi post-natal
- Registres numériques
- Rapports et statistiques
- Gestion des utilisateurs (Admin, Médecin, Sage-femme, Secrétaire, Caissier)

## Déploiement sur Railway

Ce projet est configuré pour être déployé sur Railway avec Docker.

### Prérequis
- Compte Railway (https://railway.app)
- Accès à GitHub

### Étapes de déploiement

1. **Créer un nouveau projet Railway**
   - Allez sur https://railway.app
   - Cliquez sur "Create New Project"
   - Sélectionnez "Deploy from GitHub repo"

2. **Connecter le dépôt GitHub**
   - Autorisez Railway à accéder à vos dépôts
   - Sélectionnez `Ziti99/SCHIPHRAT`

3. **Configuration automatique**
   - Railway détectera automatiquement le Dockerfile
   - Le déploiement commencera immédiatement

4. **Accéder à l'application**
   - Une URL publique sera générée (ex: https://clinique-production.railway.app)
   - Utilisez vos identifiants de test pour vous connecter

### Identifiants de test
- **Admin:** admin / password
- **Médecin:** medecin1 / password
- **Sage-femme:** sagefemme1 / password
- **Secrétaire:** secretaire1 / password
- **Caissier:** caissier1 / password

## Variables d'environnement

Vous pouvez configurer les variables dans le Dashboard Railway:

- `APP_ENV`: Mode d'exécution (production/development)
- `DB_HOST`: Hôte de la base de données
- `DB_NAME`: Nom de la base de données
- `DB_USER`: Utilisateur de la base
- `DB_PASSWORD`: Mot de passe de la base

## Support

Pour plus d'informations sur Railway, consultez: https://docs.railway.app