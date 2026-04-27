# 🚗🏠🛠️ AppLocation - Plateforme de Location Multi-Services

Une plateforme web complète de location entre particuliers (véhicules, propriétés, équipements), construite avec Laravel 12, Vue.js 3, Inertia.js et Tailwind CSS.

## 🎉 Plateforme de Location Multi-Services Complète !

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
- **Chat en temps réel** : Messagerie instantanée entre utilisateurs avec notifications push navigateur
- **Export PDF** : Génération automatique de contrats de location professionnels
- **Notifications géolocalisées** : Système complet de notifications basées sur la localisation avec préférences utilisateur
- **Système d'inscription par rôle** : Choix du rôle principal (Propriétaire/Locataire) avec interface adaptée et onboarding personnalisé
- **Intelligence artificielle** : Recommandations personnalisées, prédictions de tendances, suggestions de recherche
- **Support multilingue** : Interface disponible en français, anglais, espagnol et arabe (avec support RTL)
- **Système de parrainage** : Codes de référence, récompenses et tableau de classement
- **Location de propriétés** : Système complet pour appartements et maisons avec check-in/check-out
- **Location d'équipements** : Gestion de matériel divers avec livraison et retour
- **Données de test** : 3 véhicules, propriétés et équipements avec 2 utilisateurs pour tester

## 💬 Système de Messagerie en Temps Réel

Le système de messagerie permet aux **propriétaires** et **locataires** de communiquer directement à propos d'une location spécifique.

### Comment ça marche ?

#### 🔹 Démarrer une Conversation

1. **Depuis une location** : Accédez aux détails d'une réservation active
2. **Cliquez sur "Contacter"** ou l'icône de chat
3. Une conversation est automatiquement créée pour cette location
4. Vous êtes redirigé vers l'interface de chat

**Note importante** : Il y a une seule conversation par location entre le propriétaire et le locataire.

#### 🔹 Accéder à vos Messages

- **Icône de chat dans l'en-tête** : Affiche un badge avec le nombre de messages non lus
- **Page Messages (`/chat`)** : Liste toutes vos conversations avec :
  - Nom de l'autre participant
  - Véhicule concerné
  - Dernier message reçu
  - Nombre de messages non lus
  - Date du dernier échange

#### 🔹 Envoyer et Recevoir des Messages

1. **Ouvrir la conversation** en cliquant dessus
2. **Taper votre message** dans le champ de saisie en bas
3. **Appuyer sur Entrée** ou cliquer sur "Envoyer"
4. Le message apparaît **instantanément** pour les deux utilisateurs (temps réel)
5. Les messages s'affichent avec :
   - Votre côté (droite, fond bleu)
   - L'autre utilisateur (gauche, fond blanc)
   - Heure d'envoi
   - Indicateur de lecture

#### 🔹 Notifications en Temps Réel

**Badge de messages non lus :**
- Un badge rouge apparaît sur l'icône de chat dans l'en-tête
- Affiche le nombre total de messages non lus
- Se met à jour automatiquement en temps réel

**Notifications du navigateur :**
- Lors de la première utilisation, le système demande la permission d'afficher des notifications
- Quand vous recevez un nouveau message (même si vous êtes sur une autre page) :
  - Une notification apparaît sur votre bureau/mobile
  - Contient le nom de l'expéditeur et le message
  - Cliquer dessus vous amène directement à la conversation

**Indicateurs de lecture :**
- Les messages sont marqués comme "lus" automatiquement quand vous ouvrez la conversation
- L'autre utilisateur peut voir que vous avez lu ses messages

### ⚡ Caractéristiques Techniques

- **Temps réel** : Les messages arrivent instantanément grâce à WebSockets (Laravel Echo + Pusher)
- **Contexte de location** : Chaque conversation est liée à une location spécifique
- **Sécurité** : Seuls le propriétaire et le locataire d'une location peuvent accéder à leur conversation
- **Archivage** : Possibilité d'archiver les anciennes conversations
- **Types de messages** : Support pour texte, images et messages système
- **Statut en ligne** : Système de présence pour voir qui est connecté (optionnel)

### 📱 Interface Utilisateur

**Page Liste des Conversations (`/chat`)** :
- Vue d'ensemble de toutes vos conversations
- Recherche et filtrage
- Tri par date de dernier message
- Badge de messages non lus par conversation

**Page Conversation (`/chat/{conversation}`)** :
- Messages en temps réel
- Champ de saisie pour envoyer des messages
- Sidebar avec détails de la location
- Liens rapides vers la réservation et le véhicule
- Téléchargement du contrat PDF
- Conseils pour bien communiquer

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

7. **Configuration du système de messagerie en temps réel** (Optionnel mais recommandé)

Le chat en temps réel utilise Laravel Echo avec Pusher pour les WebSockets.

**Option 1 : Utiliser Pusher (Recommandé pour la production)**

a. Créez un compte gratuit sur [Pusher.com](https://pusher.com)
b. Créez une nouvelle app dans votre dashboard Pusher
c. Copiez vos credentials et ajoutez-les dans votre `.env` :

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=eu  # ou us2, ap1, etc. selon votre région
```

**Option 2 : Utiliser Reverb (Alternative Laravel)**

```bash
php artisan reverb:install
```

Puis dans votre `.env` :
```env
BROADCAST_DRIVER=reverb
```

**Tester la configuration :**
```bash
# Lancer le serveur de queue pour traiter les jobs de broadcast
php artisan queue:work

# Dans un autre terminal, tester l'envoi d'événements
php artisan tinker
>>> broadcast(new App\Events\TestEvent());
```

**Note** : Sans cette configuration, l'application fonctionnera toujours mais les messages ne seront pas livrés en temps réel. Les utilisateurs devront rafraîchir la page pour voir les nouveaux messages.

8. **Lancer l'application**
```bash
# Terminal 1 - Serveur Laravel
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Terminal 3 - Queue worker (pour les notifications en temps réel)
php artisan queue:work

# Ou tout en un (recommandé)
composer run dev
```

### 🐳 Déploiement avec Docker (Production)

L'application est configurée pour être facilement déployée en production avec Docker et Docker Compose. Cela configure automatiquement l'application PHP, Nginx, MySQL, Redis, le Queue Worker et le serveur WebSockets (Reverb).

1. Configurez votre fichier `.env` avec les valeurs pour l'environnement Docker :
```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=applocation
DB_USERNAME=applocation
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

BROADCAST_DRIVER=reverb
```

2. Lancez les services en arrière-plan :
```bash
docker-compose up -d --build
```

3. Exécutez les commandes initiales (la première fois uniquement) :
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
docker-compose exec app php artisan storage:link
```

Votre application sera accessible sur le port 80 (`http://localhost` ou IP de votre serveur) et les WebSockets sur le port 8080.

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
   - ✅ Inscription avec sélection du rôle (Propriétaire/Locataire)
   - ✅ Page d'accueil adaptée selon l'authentification (landing page pour invités, navigation complète pour utilisateurs connectés)
   - ✅ Système de rôles flexible avec capacités duales
   - ✅ Validation email et gestion du profil personnel
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

6. **Intelligence artificielle et recommandations**
   - ✅ Recommandations personnalisées basées sur l'historique de navigation
   - ✅ Analyse des préférences utilisateur (filtrage collaboratif et content-based)
   - ✅ Suggestions de recherche intelligentes
   - ✅ Prédictions de tendances et véhicules populaires
   - ✅ Apprentissage continu des comportements utilisateur
   - ✅ Système de feedback pour améliorer les recommandations
   - ✅ Dashboard AI avec statistiques de performance

7. **Support multilingue**
   - ✅ Interface disponible en 4 langues : Français (défaut), Anglais, Espagnol, Arabe
   - ✅ Support complet RTL pour l'arabe
   - ✅ Détection automatique de la langue du navigateur
   - ✅ Sauvegarde des préférences linguistiques par utilisateur
   - ✅ Traductions dynamiques stockées en base de données
   - ✅ Utilitaires de formatage localisés (dates, devises, nombres)
   - ✅ Composant LanguageSwitcher avec drapeaux

8. **Système de parrainage**
   - ✅ Génération de codes de parrainage uniques
   - ✅ Système de récompenses pour parrains et filleuls
   - ✅ Tableau de classement des meilleurs parrains
   - ✅ Suivi des conversions et statistiques
   - ✅ Partage facile sur réseaux sociaux
   - ✅ Crédits automatiques sur les réservations

9. **Location de propriétés (Appartements/Maisons)**
   - ✅ Système complet de réservation de logements
   - ✅ Gestion des check-in et check-out
   - ✅ Calendrier de disponibilité
   - ✅ Tarification flexible par nuit/semaine/mois
   - ✅ Galerie photos et équipements
   - ✅ Évaluations spécifiques aux propriétés

10. **Location d'équipements**
   - ✅ Catégories multiples (sport, bricolage, événementiel)
   - ✅ Gestion des stocks et quantités
   - ✅ Système de livraison et retour
   - ✅ États de l'équipement (prêt, livré, retourné)
   - ✅ Extensions de location
   - ✅ Tarification horaire/journalière/hebdomadaire

11. **Chat et notifications**
   - ✅ Messagerie en temps réel entre utilisateurs
   - ✅ Notifications push du navigateur pour les nouveaux messages
   - ✅ Conversations contextuelles liées aux locations
   - ✅ Gestion des conversations et archivage
   - ✅ Intégration avec Laravel Echo et Pusher
   - ✅ Composable useNotifications pour la gestion des notifications
   - ✅ Notifications push géolocalisées avec système complet de localisation

   **Comment tester le système de messagerie :**

   a. **Créer une conversation depuis une location :**
      1. Connectez-vous avec le compte propriétaire (owner@example.com)
      2. Allez dans "Mes Véhicules" et créez une location (ou utilisez une existante)
      3. Déconnectez-vous et connectez-vous avec le compte locataire (renter@example.com)
      4. Réservez un véhicule du propriétaire
      5. Une fois la réservation confirmée, accédez aux détails de la location
      6. Cliquez sur "Contacter" ou l'icône de chat pour démarrer une conversation

   b. **Envoyer et recevoir des messages en temps réel :**
      1. Ouvrez deux navigateurs (ou fenêtres incognito)
      2. Connectez-vous comme propriétaire dans un navigateur
      3. Connectez-vous comme locataire dans l'autre navigateur
      4. Ouvrez la même conversation dans les deux navigateurs
      5. Envoyez un message depuis l'un → Il devrait apparaître **instantanément** dans l'autre
      6. Vérifiez que les messages s'alignent correctement (droite pour l'expéditeur, gauche pour le destinataire)

   c. **Tester les notifications en temps réel :**
      1. Dans un navigateur, restez sur une page différente (pas la page de chat)
      2. Dans l'autre navigateur, envoyez un message
      3. Vérifiez que le **badge rouge** sur l'icône de chat se met à jour avec le nombre de messages non lus
      4. Si les permissions sont activées, une **notification de bureau** devrait apparaître
      5. Cliquez sur l'icône de chat pour voir la liste des conversations avec le badge de messages non lus

   d. **Vérifier les indicateurs de lecture :**
      1. Envoyez plusieurs messages sans que l'autre utilisateur ouvre la conversation
      2. Les messages doivent avoir `read_at = null` dans la base de données
      3. Quand l'autre utilisateur ouvre la conversation, les messages sont automatiquement marqués comme lus
      4. Le badge de messages non lus disparaît

   e. **Tester l'interface et les fonctionnalités :**
      - ✅ Page `/chat` affiche toutes les conversations avec aperçu du dernier message
      - ✅ Badge de messages non lus sur chaque conversation
      - ✅ Tri par date de dernier message (les plus récentes en haut)
      - ✅ Cliquer sur une conversation ouvre `/chat/{conversation}`
      - ✅ Messages affichés avec timestamps et nom de l'expéditeur
      - ✅ Auto-scroll vers le dernier message
      - ✅ Sidebar affiche les détails de la location (véhicule, dates, statut)
      - ✅ Liens rapides vers la réservation et le véhicule
      - ✅ Permission de notification du navigateur demandée au premier message

   f. **Vérifier la sécurité :**
      1. Essayez d'accéder à une conversation d'un autre utilisateur via l'URL
      2. Vous devriez recevoir une erreur 403 (Forbidden)
      3. Seuls les participants (propriétaire + locataire) peuvent accéder à la conversation

12. **Système d'inscription par rôle (Ajouté 2025-09-29)**
   - ✅ Sélection du rôle principal lors de l'inscription (Propriétaire/Locataire)
   - ✅ Interface d'accueil adaptée : landing page pour les invités, navigation complète pour les utilisateurs connectés
   - ✅ Système de layouts dynamiques (GuestLayout/AppLayout) selon l'authentification
   - ✅ Expérience d'onboarding spécifique au rôle choisi
   - ✅ Système de rôles flexible permettant les capacités duales
   - ✅ Formulaire d'inscription amélioré avec descriptions détaillées des rôles
   - ✅ Configuration automatique du flag `is_owner` selon le rôle sélectionné
   - ✅ Informations contextuelles sur la page de connexion
   - ✅ Migration de base de données pour le champ `user_role`

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
- [x] ✅ Intelligence artificielle pour suggestions (recommandations personnalisées)
- [x] ✅ Support multilingue (FR, EN, ES, AR avec support RTL)
- [x] ✅ Système de parrainage avec codes de référence et récompenses
- [x] ✅ Expansion vers d'autres types de locations (Propriétés et Équipements)
- [x] ✅ Notifications push géolocalisées
- [ ] Intégration avec API de trafic en temps réel

### Long terme
- [ ] Application mobile (React Native/Flutter)
- [ ] API REST complète pour applications tierces
- [ ] Système d'assurance intégré

## 🎯 Extensibilité

L'application supporte maintenant plusieurs types de locations :

### ✅ Déjà implémentés :
- **🚗 Véhicules** : Système complet de location de voitures avec toutes les fonctionnalités
- **🏠 Propriétés** : Location de logements (appartements, maisons, villas)
  - Réservation avec dates d'arrivée/départ
  - Gestion des check-in/check-out
  - Galerie photos et descriptions détaillées
- **🛠️ Équipements** : Location de matériel et équipements
  - Support de différentes catégories (sport, bricolage, événementiel)
  - Gestion des stocks et disponibilités
  - Système de livraison/retour

### 💡 Facilement extensible vers :
- **Bateaux** : Location de bateaux et jet-skis
- **Espaces** : Salles de réunion, espaces de stockage
- **Services** : Location de services avec professionnels

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
