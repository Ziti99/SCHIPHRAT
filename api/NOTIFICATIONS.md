# Système de Notifications en Temps Réel

## 🎯 Fonctionnalités

Le système de notifications permet à la caissière d'être alertée en temps réel lorsqu'une nouvelle consultation est ajoutée par la secrétaire.

### ✨ Caractéristiques

1. **Icône de notification avec badge** (style Facebook)
   - Badge rouge avec le nombre de notifications non lues
   - Animation de la cloche lors de nouvelles notifications
   - Visible uniquement pour les rôles `admin` et `caissiere`

2. **Dropdown des notifications**
   - Liste complète des notifications
   - Notifications non lues mises en évidence (fond violet)
   - Horodatage de chaque notification
   - Bouton "Tout marquer lu"

3. **Son de notification**
   - Son court et discret lors de nouvelles notifications
   - Utilise l'API Web Audio du navigateur

4. **Persistance**
   - Les notifications sont sauvegardées dans le localStorage
   - Conservées même après rechargement de la page
   - Maximum 50 notifications conservées

5. **Actions**
   - Clic sur une notification → Redirection vers la page de paiement
   - Notification automatiquement marquée comme lue au clic

## 📍 Emplacement

L'icône de notification apparaît dans la **navbar** (barre de navigation en haut), à côté du nom d'utilisateur et avant le bouton de déconnexion.

## 🔔 Fonctionnement

1. **Détection automatique** : Le système vérifie toutes les 5 secondes s'il y a de nouvelles consultations
2. **Création de notification** : Quand une nouvelle consultation est détectée :
   - Une notification est créée avec les détails (nom patiente, montant à encaisser)
   - Le badge s'affiche avec le nombre de notifications non lues
   - Un son de notification est joué
   - La cloche s'anime brièvement
3. **Affichage** : La notification apparaît dans le dropdown accessible en cliquant sur l'icône de cloche

## 🎨 Interface

### Badge de notification
- **Couleur** : Rouge (#EF4444)
- **Position** : En haut à droite de l'icône de cloche
- **Affichage** : Seulement s'il y a des notifications non lues
- **Format** : Affiche le nombre (max 99+)

### Dropdown
- **Largeur** : 320px (mobile) / 384px (desktop)
- **Hauteur max** : 384px avec scroll automatique
- **Style** : Fond blanc, ombre portée, bordure arrondie

### Notification individuelle
- **Non lue** : Fond violet clair + bordure gauche violette + point violet
- **Lue** : Fond blanc
- **Contenu** : Icône, titre, message, date/heure

## 💾 Stockage

Les notifications sont stockées dans le `localStorage` du navigateur sous la clé `caissiere_notifications`.

**Format JSON** :
```json
[
  {
    "id": 1234567890.123,
    "type": "new_consultation",
    "title": "Nouvelle consultation",
    "message": "Marie Dupont - 15 000 FCFA à encaisser",
    "consultation_id": 42,
    "paiement_id": 25,
    "patiente_id": 10,
    "timestamp": "2024-01-15T14:30:00.000Z",
    "read": false
  }
]
```

## 🔧 Personnalisation

### Désactiver le son
Pour désactiver le son de notification, commentez ou supprimez l'appel à `playNotificationSound()` dans la fonction `addNotification()`.

### Changer le nombre maximum de notifications
Modifiez la ligne dans `addNotification()` :
```javascript
if (allNotifications.length > 50) { // Changez 50 par le nombre souhaité
```

### Modifier l'intervalle de vérification
Modifiez `UPDATE_INTERVAL` dans le script (par défaut 5000ms = 5 secondes).

## 🐛 Debug

Pour activer le mode debug et voir les logs détaillés :
1. Ajoutez `?debug=1` à l'URL de la page
2. Ou utilisez dans la console : `window.realtimeDebug.enableDebug()`

Les logs apparaîtront dans la console du navigateur (F12).

## 📱 Compatibilité

- ✅ Chrome/Edge (dernières versions)
- ✅ Firefox (dernières versions)
- ✅ Safari (dernières versions)
- ✅ Mobile (iOS Safari, Chrome Mobile)

**Note** : Le son de notification peut ne pas fonctionner sur certains navigateurs mobiles (limitation des navigateurs).

## 🔒 Sécurité

- Les notifications sont stockées localement (localStorage)
- Aucune donnée sensible n'est stockée
- Les notifications sont automatiquement nettoyées (max 50)
- Vérification du rôle utilisateur côté serveur

## 🚀 Évolutions futures possibles

1. **Notifications push** : Notifications même si la page n'est pas ouverte
2. **Filtres** : Filtrer les notifications par type
3. **Suppression** : Bouton pour supprimer une notification
4. **Synchronisation** : Synchroniser les notifications entre plusieurs onglets
5. **Préférences** : Permettre à l'utilisateur de configurer les notifications

