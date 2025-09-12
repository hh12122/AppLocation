# 🚗 CarLocation - Application de Location de Voitures

Une application web complète de location de voitures entre particuliers, construite avec Laravel 12, Vue.js 3, Inertia.js et Tailwind CSS.

## 🎉 Application de Location de Voitures Complète !

### ✅ Ce qui a été accompli

#### 🏗️ Architecture Backend :
- **Base de données complète** : 5 tables (users, vehicles, rentals, reviews, vehicle_images)
- **Modèles Eloquent** : Relations complètes et méthodes utilitaires
- **Contrôleurs** : VehicleController, RentalController, ReviewController et LicenseVerificationController
- **Validation** : Validation robuste pour tous les formulaires
- **Policies** : Système d'autorisation avec RentalPolicy et ReviewPolicy
- **Routes** : Structure RESTful complète avec routes admin

#### 🎨 Frontend Vue.js :
- **Pages principales** :
  - Index des véhicules avec recherche avancée
  - Détails véhicule avec galerie photos
  - Création/édition de véhicules
  - Système de réservation complet
  - Dashboard personnalisé
  - Gestion des véhicules propriétaires
  - Suivi des réservations locataires
  - Système complet d'évaluations (création, édition, affichage)
  - Vérification du permis de conduire
  - Panel admin pour validation des permis
- **Navigation** : Menu latéral avec toutes les sections
- **Composants UI** : Badge, Cards, Forms, StarRating, LicenseAlert avec Reka UI
- **TypeScript** : Typage complet pour la sécurité

#### ⚙️ Fonctionnalités Clés :
- **Recherche avancée** : Filtres par marque, ville, dates, carburant, type de véhicule, équipements, prix, etc.
- **Calendrier visuel** : Interface calendrier interactive pour visualiser les disponibilités
- **Gestion d'images** : Upload multiple avec aperçu
- **Calcul de prix** : Tarification flexible (jour/semaine/mois)
- **Workflow complet** : Recherche → Réservation → Confirmation → Location → Retour → Évaluation
- **Système de notes** : Évaluations mutuelles complètes avec critères spécifiques
- **Validation du permis** : Vérification obligatoire avant location avec statuts (pending, verified, rejected)
- **Notifications contextuelles** : Alertes pour permis manquant, expiré ou rejeté
- **Panel admin** : Interface de validation des permis de conduire
- **Performances optimisées** : Requêtes optimisées, mise en cache, indexes de base de données
- **Géolocalisation et cartes interactives** : Cartes Leaflet intégrées, recherche par localisation, visualisation sur carte
- **Système de favoris/wishlist** : Sauvegarde des véhicules préférés avec notes personnelles
- **Système de paiement** : Intégration Stripe et PayPal avec gestion des frais et remboursements
- **Chat en temps réel** : Messagerie instantanée entre utilisateurs avec notifications push et en temps réel
- **Export PDF** : Génération automatique de contrats de location professionnels
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
   - ✅ Consulter les évaluations de ses véhicules

2. **Gestion des réservations**
   - ✅ Voir les demandes de réservation
   - ✅ Confirmer ou refuser les demandes
   - ✅ Suivre l'état des locations en cours
   - ✅ Enregistrer la remise et le retour du véhicule
   - ✅ Évaluer les locataires après la location

### Pour les Locataires

1. **Recherche et réservation**
   - ✅ Rechercher des véhicules par critères multiples (marque, ville, type, carburant, prix, équipements, etc.)
   - ✅ Calendrier visuel interactif pour sélectionner les dates de location
   - ✅ Voir les détails complets avec galerie photos
   - ✅ Consulter les évaluations du véhicule et du propriétaire
   - ✅ Vérifier la disponibilité en temps réel
   - ✅ Calculer automatiquement le prix selon la durée

2. **Gestion des locations**
   - ✅ Suivre l'état de ses réservations
   - ✅ Annuler une réservation (si non confirmée)
   - ✅ Voir l'historique des locations
   - ✅ Contacter le propriétaire
   - ✅ Évaluer le véhicule et le propriétaire après la location

### Pour Tous les Utilisateurs

1. **Profil et authentification**
   - ✅ Inscription avec validation email
   - ✅ Gestion du profil personnel
   - ✅ Ajout et validation du permis de conduire
   - ✅ Mode clair/sombre
   - ✅ Notifications de statut du permis

2. **Dashboard personnalisé**
   - ✅ Vue d'ensemble des activités
   - ✅ Statistiques personnelles
   - ✅ Accès rapide aux fonctionnalités principales
   - ✅ Conseils pour bien démarrer
   - ✅ Alertes pour permis manquant ou expirant

3. **Système d'évaluations**
   - ✅ Consultation de toutes les évaluations publiques
   - ✅ Système de notation sur 5 étoiles
   - ✅ Critères détaillés (propreté, communication, état, rapport qualité/prix)
   - ✅ Historique des évaluations reçues et données

4. **Géolocalisation et cartes interactives**
   - ✅ Visualisation des véhicules sur carte interactive (Leaflet)
   - ✅ Recherche géolocalisée avec rayon personnalisable (1-100km)
   - ✅ Positionnement automatique avec géolocalisation du navigateur
   - ✅ Recherche d'adresse avec géocodage (OpenStreetMap/Nominatim)
   - ✅ Vue carte/grille commutable sur la liste des véhicules
   - ✅ Sélecteur de localisation sur carte pour l'ajout de véhicules
   - ✅ Affichage de la localisation sur la page de détail du véhicule
   - ✅ Intégration avec services de navigation (Google Maps, Waze, Apple Plans)
   - ✅ Calcul de distance et temps de trajet estimé
   - ✅ Navigation rapide depuis les popups de carte

5. **Système de favoris/wishlist**
   - ✅ Ajout/suppression de véhicules aux favoris d'un clic
   - ✅ Page dédiée pour gérer sa wishlist
   - ✅ Possibilité d'ajouter des notes personnelles aux favoris
   - ✅ Statistiques et accès rapide depuis le dashboard
   - ✅ Intégration dans toutes les vues de véhicules

## 🔧 Améliorations Possibles

### Court terme
- [x] ✅ Système d'évaluations et commentaires complet
- [x] ✅ Validation du permis de conduire avec workflow admin
- [x] ✅ Améliorer le système de recherche avec plus de filtres
- [x] ✅ Ajouter un calendrier de disponibilité visuel
- [x] ✅ Optimiser les performances des requêtes
- [x] ✅ Système de favoris/wishlist
- [x] ✅ Géolocalisation avec cartes interactives
- [x] ✅ Intégration avec services de navigation (Google Maps, Waze)
- [x] ✅ Système de notifications in-app

### Moyen terme
- [x] ✅ Intégration système de paiement (Stripe et PayPal)
- [x] ✅ Chat en temps réel entre utilisateurs
- [x] ✅ Export PDF des contrats de location
- [ ] Système de parrainage
- [ ] Notifications push géolocalisées
- [ ] Intégration avec API de trafic en temps réel

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
- **Cartes** : Leaflet.js, OpenStreetMap, Nominatim (géocodage)
- **Base de données** : SQLite (dev), MySQL/PostgreSQL (prod)
- **Build** : Vite, Composer
- **Authentification** : Laravel Breeze
- **Paiement** : Stripe SDK, PayPal Checkout SDK

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
