# 🚗 CarLocation - Application de Location de Voitures

Une application web complète de location de voitures entre particuliers, construite avec Laravel 12, Vue.js 3, Inertia.js et Tailwind CSS.

## 🎉 Application de Location de Voitures Complète !

### ✅ Ce qui a été accompli

#### 🏗️ Architecture Backend :
- **Base de données complète** : 5 tables (users, vehicles, rentals, reviews, vehicle_images)
- **Modèles Eloquent** : Relations complètes et méthodes utilitaires
- **Contrôleurs** : VehicleController et RentalController avec toutes les fonctionnalités
- **Validation** : Validation robuste pour tous les formulaires
- **Policies** : Système d'autorisation (à compléter)
- **Routes** : Structure RESTful complète

#### 🎨 Frontend Vue.js :
- **Pages principales** :
  - Index des véhicules avec recherche avancée
  - Détails véhicule avec galerie photos
  - Création/édition de véhicules
  - Système de réservation complet
  - Dashboard personnalisé
  - Gestion des véhicules propriétaires
  - Suivi des réservations locataires
- **Navigation** : Menu latéral avec toutes les sections
- **Composants UI** : Badge, Cards, Forms avec Reka UI
- **TypeScript** : Typage complet pour la sécurité

#### ⚙️ Fonctionnalités Clés :
- **Recherche avancée** : Filtres par marque, ville, dates, carburant, etc.
- **Gestion d'images** : Upload multiple avec aperçu
- **Calcul de prix** : Tarification flexible (jour/semaine/mois)
- **Workflow complet** : Recherche → Réservation → Confirmation → Location → Retour
- **Système de notes** : Évaluations mutuelles (structure prête)
- **Données de test** : 3 véhicules et 2 utilisateurs pour tester

## 🚀 Installation et Configuration

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- SQLite/MySQL/PostgreSQL

### Installation

1. **Cloner le repository**
```bash
git clone https://github.com/votre-username/location-app.git
cd location-app
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances JavaScript**
```bash
npm install
```

4. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Base de données**
```bash
php artisan migrate
php artisan db:seed --class=VehicleSeeder  # Pour les données de test
```

6. **Lien de stockage pour les images**
```bash
php artisan storage:link
```

7. **Lancer l'application**
```bash
# Terminal 1 - Serveur Laravel
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Ou tout en un (recommandé)
composer run dev
```

## 🧪 Comptes de Test

Après avoir exécuté le seeder, vous pouvez utiliser ces comptes :

- **Propriétaire** :
  - Email : owner@example.com
  - Mot de passe : password
  
- **Locataire** :
  - Email : renter@example.com
  - Mot de passe : password

## 📋 Fonctionnalités Existantes à Tester

### Pour les Propriétaires de Véhicules

1. **Gestion des véhicules**
   - ✅ Ajouter un nouveau véhicule avec photos multiples
   - ✅ Modifier les informations du véhicule
   - ✅ Gérer la disponibilité et les tarifs
   - ✅ Voir les statistiques de location

2. **Gestion des réservations**
   - ✅ Voir les demandes de réservation
   - ✅ Confirmer ou refuser les demandes
   - ✅ Suivre l'état des locations en cours
   - ✅ Enregistrer la remise et le retour du véhicule

### Pour les Locataires

1. **Recherche et réservation**
   - ✅ Rechercher des véhicules par critères multiples
   - ✅ Voir les détails complets avec galerie photos
   - ✅ Vérifier la disponibilité en temps réel
   - ✅ Calculer automatiquement le prix selon la durée

2. **Gestion des locations**
   - ✅ Suivre l'état de ses réservations
   - ✅ Annuler une réservation (si non confirmée)
   - ✅ Voir l'historique des locations
   - ✅ Contacter le propriétaire

### Pour Tous les Utilisateurs

1. **Profil et authentification**
   - ✅ Inscription avec validation email
   - ✅ Gestion du profil personnel
   - ✅ Ajout des informations de permis de conduire
   - ✅ Mode clair/sombre

2. **Dashboard personnalisé**
   - ✅ Vue d'ensemble des activités
   - ✅ Statistiques personnelles
   - ✅ Accès rapide aux fonctionnalités principales
   - ✅ Conseils pour bien démarrer

## 🔧 Améliorations Possibles

### Court terme
- [ ] Finaliser le système d'évaluations et commentaires
- [ ] Ajouter la validation du permis de conduire
- [ ] Système de notifications in-app
- [ ] Améliorer le système de recherche avec plus de filtres
- [ ] Ajouter un calendrier de disponibilité visuel
- [ ] Optimiser les performances des requêtes

### Moyen terme
- [ ] Intégration système de paiement (Stripe/PayPal)
- [ ] Géolocalisation avec cartes interactives
- [ ] Chat en temps réel entre utilisateurs
- [ ] Système de favoris/wishlist
- [ ] Export PDF des contrats de location
- [ ] Système de parrainage

### Long terme
- [ ] Application mobile (React Native/Flutter)
- [ ] API REST complète pour applications tierces
- [ ] Système d'assurance intégré
- [ ] Intelligence artificielle pour suggestions
- [ ] Support multilingue
- [ ] Expansion vers d'autres types de locations

## 🎯 Extensibilité

L'architecture est conçue pour être facilement étendue à d'autres types de locations :
- **Appartements/maisons** : Locations courte durée style Airbnb
- **Équipements de sport** : Ski, vélos, camping, etc.
- **Outils et matériel** : Bricolage, jardinage, événementiel
- **Bateaux** : Location de bateaux et jet-skis
- **Espaces** : Salles de réunion, espaces de stockage

Il suffit de dupliquer la structure Vehicle/Rental en adaptant les champs spécifiques !

## 🛠️ Technologies Utilisées

- **Backend** : Laravel 12, PHP 8.2
- **Frontend** : Vue.js 3, TypeScript, Inertia.js
- **Styling** : Tailwind CSS v4, Reka UI
- **Base de données** : SQLite (dev), MySQL/PostgreSQL (prod)
- **Build** : Vite, Composer
- **Authentification** : Laravel Breeze

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## 📧 Contact

Pour toute question ou suggestion, n'hésitez pas à ouvrir une issue ou à me contacter.

---

L'application CarLocation est maintenant une base solide pour un service de location peer-to-peer complet. 🚗✨
