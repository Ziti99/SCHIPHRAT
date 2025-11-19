# Système de Mise à Jour en Temps Réel

## Vue d'ensemble

Ce système permet de mettre à jour automatiquement la liste des consultations sur la page de la caissière sans avoir à rafraîchir manuellement la page. Quand une secrétaire ajoute une nouvelle consultation, la caissière la voit apparaître automatiquement dans les 5 secondes.

## Architecture

### Solution choisie : **Polling Intelligent**

Nous avons choisi le **polling** (interrogation périodique) plutôt que les WebSockets pour les raisons suivantes :

1. **Simplicité** : Pas besoin de serveur WebSocket ou de configuration complexe
2. **Compatibilité** : Fonctionne avec n'importe quel serveur PHP standard
3. **Fiabilité** : Pas de problèmes de connexion persistante à gérer
4. **Performance** : Pour ce cas d'usage (mise à jour toutes les 5 secondes), c'est largement suffisant

### Alternative : Server-Sent Events (SSE)

Le fichier `check_new_consultations.php` est fourni comme alternative avec SSE, mais n'est pas utilisé par défaut car il nécessite une configuration serveur spécifique.

## Fonctionnement

### 1. Endpoint API : `api/get_consultations.php`

Cet endpoint retourne les consultations au format JSON avec :
- La liste des consultations (avec filtres appliqués)
- Les statistiques (total, en attente, partiel, payé)
- Un timestamp de la réponse

**Paramètres acceptés :**
- `statut` : Filtre par statut (tous, en_attente, paye_partiel, paye_total)
- `date_debut` : Date de début pour le filtre
- `date_fin` : Date de fin pour le filtre
- `search` : Recherche par nom/téléphone
- `last_check` : Timestamp de la dernière vérification (optionnel)
- `last_consultation_id` : ID de la dernière consultation vue (optionnel)

### 2. JavaScript dans `caissiere_consultations.php`

Le script JavaScript :
1. **Vérifie toutes les 5 secondes** s'il y a de nouvelles consultations
2. **Compare les IDs** pour détecter les nouvelles consultations
3. **Met à jour automatiquement** :
   - Le tableau des consultations
   - Les statistiques (total, en attente, etc.)
   - Le montant restant à encaisser
4. **Affiche une notification** discrète quand de nouvelles consultations sont détectées
5. **Affiche un indicateur** de connexion en temps réel (vert = connecté, rouge = déconnecté)

### 3. Indicateur de connexion

En haut à droite de la page, un indicateur montre :
- 🟢 **"En temps réel"** : Le système fonctionne et vérifie les mises à jour
- 🔴 **"Déconnecté"** : Problème de connexion ou page en arrière-plan

## Avantages de cette solution

✅ **Simple à maintenir** : Code JavaScript standard, pas de dépendances externes
✅ **Performant** : Vérification toutes les 5 secondes (ajustable)
✅ **Respecte les filtres** : Les filtres de recherche sont préservés lors des mises à jour
✅ **Économique en ressources** : Une seule requête HTTP toutes les 5 secondes
✅ **Robuste** : Gestion des erreurs et reconnexion automatique
✅ **User-friendly** : Notification discrète quand de nouvelles consultations arrivent

## Personnalisation

### Changer l'intervalle de vérification

Dans `caissiere_consultations.php`, ligne ~320 :

```javascript
const UPDATE_INTERVAL = 5000; // En millisecondes (5000 = 5 secondes)
```

### Désactiver la mise à jour automatique

Pour désactiver temporairement, commentez la ligne qui démarre l'auto-refresh :

```javascript
// startAutoRefresh(); // Désactivé
```

### Rafraîchissement manuel

Vous pouvez forcer un rafraîchissement depuis la console du navigateur :

```javascript
window.manualRefresh();
```

## Alternative : Server-Sent Events (SSE)

Si vous préférez utiliser SSE (plus efficace mais nécessite une configuration serveur), vous pouvez utiliser `api/check_new_consultations.php`.

**Avantages SSE :**
- Connexion persistante (moins de requêtes HTTP)
- Mise à jour instantanée (pas d'attente de 5 secondes)

**Inconvénients SSE :**
- Nécessite une configuration serveur spécifique (désactiver la mise en cache)
- Plus complexe à déboguer
- Peut avoir des problèmes avec certains proxies/load balancers

## Dépannage

### Les mises à jour ne fonctionnent pas

1. Vérifiez la console du navigateur (F12) pour les erreurs JavaScript
2. Vérifiez que le fichier `api/get_consultations.php` est accessible
3. Vérifiez que l'utilisateur est bien authentifié (session active)
4. Vérifiez les permissions du dossier `api/`

### L'indicateur reste sur "Déconnecté"

- Vérifiez votre connexion internet
- Vérifiez que le serveur PHP répond correctement
- Vérifiez les logs d'erreur PHP

### Les notifications n'apparaissent pas

- Vérifiez que JavaScript est activé dans votre navigateur
- Vérifiez la console pour les erreurs

## Performance

- **Requêtes par minute** : 12 requêtes (une toutes les 5 secondes)
- **Taille moyenne d'une réponse** : ~5-50 KB selon le nombre de consultations
- **Impact serveur** : Minimal (requêtes légères, pas de traitement lourd)
- **Impact client** : Minimal (requêtes asynchrones, pas de blocage de l'interface)

## Sécurité

- ✅ Vérification de l'authentification (session PHP)
- ✅ Vérification du rôle utilisateur (admin ou caissiere uniquement)
- ✅ Protection contre les injections SQL (requêtes préparées)
- ✅ Échappement des données dans le HTML (XSS protection)

## Évolutions futures possibles

1. **WebSockets** : Pour une mise à jour vraiment instantanée (< 1 seconde)
2. **Cache Redis** : Pour réduire la charge sur la base de données
3. **Notifications push** : Pour notifier même si la page n'est pas ouverte
4. **Filtrage côté serveur** : Pour ne retourner que les nouvelles consultations
