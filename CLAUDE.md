# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel + Vue.js multi-service rental platform called "AppLocation" built with:
- **Backend**: Laravel 12 with Inertia.js for SPA behavior
- **Frontend**: Vue 3 + TypeScript with Tailwind CSS v4 and Reka UI components
- **Maps**: Leaflet.js with OpenStreetMap tiles and Nominatim geocoding
- **Database**: SQLite (development) with complete car rental schema
- **Build Tools**: Vite for asset compilation and development server

### Core Features
- **Vehicle Management**: Owners can list vehicles with photos, pricing, and availability
- **Advanced Search System**: Comprehensive filtering by type, brand, features, price range, location, etc.
- **Visual Availability Calendar**: Interactive calendar component for date selection and availability visualization
- **Interactive Maps**: Leaflet-powered maps with vehicle locations, search by radius, and geocoding
- **Geolocation Search**: Location-based filtering with customizable radius (1-100km)
- **Navigation Integration**: Direct links to Google Maps, Waze, Apple Plans for turn-by-turn directions
- **Favorites/Wishlist System**: Save vehicles with personal notes and wishlist management
- **Rental System**: Complete booking workflow from search to payment
- **Payment Integration**: Dual payment gateway support with Stripe and PayPal, fee calculation, and refund management
- **User Roles**: Role-based registration system (Propriétaire/Locataire) with dual capabilities and admin features
- **Review System**: Complete mutual rating system with detailed criteria
- **License Verification**: Mandatory driver's license validation before rentals
- **Dashboard**: Statistics and management for both user types
- **Admin Panel**: License verification management interface
- **AI Recommendations**: Personalized suggestions using hybrid algorithms (collaborative + content-based filtering)
- **Multilingual Support**: Full i18n with FR, EN, ES, AR languages and RTL support
- **Referral System**: Complete referral program with codes, rewards, and leaderboard
- **Multi-Type Rentals**: Support for vehicles, properties, and equipment rentals
- **Chat System**: Real-time messaging between users
- **Performance Optimizations**: Optimized queries, caching, database indexes

## Development Commands

### Backend (Laravel/PHP)
- `composer run dev` - Start all development services (server, queue, logs, vite)
- `composer run dev:ssr` - Start development with SSR support
- `composer run test` - Run PHP tests with PHPUnit
- `php artisan serve` - Start Laravel development server only
- `php artisan migrate` - Run database migrations
- `php artisan pail` - View application logs

### Frontend (Vue/TypeScript)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting

### Testing
- PHP tests: `composer run test` (uses PHPUnit)
- Frontend type checking: `vue-tsc` (via TypeScript compiler)

## Architecture

### Backend Structure
- **Controllers**: Located in `app/Http/Controllers/` with Auth and Settings subdirectories
  - ReviewController: Complete CRUD for reviews and ratings
  - LicenseVerificationController: License upload and admin verification
  - AIRecommendationController: AI-powered recommendations and behavior tracking
  - LocalizationController: Language management and translation controls
  - PropertyController: Property listing and management
  - PropertyBookingController: Property reservation workflow
  - EquipmentController: Equipment listing and management
  - EquipmentBookingController: Equipment rental workflow
  - ReferralController: Referral system management
  - ChatController: Real-time messaging
- **Models**: Standard Laravel Eloquent models in `app/Models/` including Review, UserActivity, Recommendation, Language, Translation, Property, PropertyBooking, Equipment, EquipmentBooking, Referral, ReferralReward models
- **Routes**: Organized in separate files (`web.php`, `auth.php`, `settings.php`)
- **Database**: SQLite with migrations in `database/migrations/`
- **Middleware**: IsAdmin middleware for admin-only routes, Localization middleware for language detection
- **Services**: AIRecommendationService for ML algorithms, LocalizationService for translation management

### Frontend Structure
- **Entry Point**: `resources/js/app.ts` initializes Inertia.js app
- **Pages**: Vue components in `resources/js/pages/` (Inertia pages)
  - Reviews/: Complete review system pages (Index, Create, Edit, Show, VehicleReviews, UserReviews)
  - Admin/LicenseVerifications: Admin panel for license validation
  - Settings/DriverLicense: User license management
  - Favorites/: Wishlist management pages (Index)
  - AI/Dashboard: AI recommendations dashboard with analytics
- **Components**: Reusable components in `resources/js/components/`
  - StarRating: 5-star rating component
  - LicenseAlert: Contextual license status notifications
  - AvailabilityCalendar: Interactive calendar for date selection and availability visualization
  - VehicleFilters: Advanced filtering component with collapsible sections
  - InteractiveMap: Leaflet-based map component with markers and popups
  - LocationPicker: Address search with map selection for vehicle creation
  - VehicleMapView: Vehicle listing component with map visualization
  - FavoriteButton: Toggle favorite status for vehicles
  - NavigationModal: Multi-service navigation modal with distance calculation
  - QuickNavigation: Dropdown navigation menu for quick access
  - LanguageSwitcher: Language selection dropdown with flags
  - MultilingualExample: Demo component showcasing all i18n features
  - AIRecommendations: Component displaying personalized recommendations
- **Composables**: Vue composables in `resources/js/composables/`
  - useGeolocation: Geolocation utilities with current position, geocoding, and distance calculation
  - useNavigation: Navigation services integration (Google Maps, Waze, Apple Plans, OpenStreetMap)
  - useAIRecommendations: AI recommendation fetching and management
  - useLocalization: i18n utilities with formatting, RTL support, and language switching
- **UI Components**: Reka UI components in `resources/js/components/ui/`
- **Layouts**: Page layouts in `resources/js/layouts/`

### Key Features
- **Authentication**: Full auth system with role-based registration (Propriétaire/Locataire), login, password reset
- **User Roles**: Choose primary role during registration with flexible dual-role capabilities
- **Guest Experience**: Clean landing page with sign-in/sign-up for unauthenticated users
- **User Settings**: Profile management, password updates, appearance themes, driver license validation
- **Vehicle Management**: CRUD operations for vehicles with image upload, advanced fields, and location picking
- **Advanced Search**: Comprehensive filtering system with multiple criteria (vehicle type, engine specs, features, etc.)
- **Geolocation Search**: Location-based filtering with radius search (1-100km) and current position detection
- **Interactive Maps**: Leaflet integration with vehicle markers, popups, and address search
- **Visual Calendar**: Interactive availability calendar with date range selection and booking visualization
- **Favorites/Wishlist**: Save vehicles with personal notes and dedicated management interface
- **Rental Workflow**: Search, book, confirm, pickup, return, review cycle
- **Rating System**: Complete mutual ratings with detailed criteria (cleanliness, communication, condition, value)
- **License Verification**: Multi-step validation process (upload, admin review, approval/rejection)
- **Admin Features**: License verification panel, user management
- **Dashboard**: Role-specific dashboards with statistics and alerts
- **Performance**: Optimized database queries with scopes, eager loading, and caching
- **Theme System**: Light/dark mode via `useAppearance` composable
- **Component Library**: Built on Reka UI with custom components
- **Type Safety**: Full TypeScript integration with proper type definitions
- **AI Recommendations**: Hybrid recommendation engine with collaborative and content-based filtering
- **Multilingual Interface**: Full i18n support with 4 languages, RTL support, and dynamic translations

### Inertia.js Integration
- Pages are Vue components that receive data from Laravel controllers
- No API routes needed - data flows through Inertia props
- Client-side routing handled by Inertia with server-side rendering support
- Ziggy integration for Laravel route helpers in frontend

## Development Notes

- Uses SQLite for development database (`database/database.sqlite`)
- Image storage configured with `storage:link` for vehicle photos and license documents
- Vite handles asset compilation with hot reloading
- ESLint and Prettier configured for code quality
- Tailwind CSS v4 with custom configuration
- Component auto-discovery via Vite glob imports

## Database Schema

### Key Tables
- **users**: Extended with rental-specific fields (driver license, ratings, roles, admin status)
- **vehicles**: Complete vehicle information with pricing, location, coordinates, and advanced filtering fields
- **rentals**: Full rental lifecycle with status tracking and payment integration
- **reviews**: Rating system for vehicles, owners, and renters with detailed criteria
- **vehicle_images**: Photo management for vehicles
- **favorites**: User wishlist system with personal notes and timestamps
- **payments**: Complete payment tracking with Stripe and PayPal integration, fee calculation, and refund support
- **user_activities**: Tracking user behavior for AI recommendations
- **recommendations**: Personalized AI-generated suggestions
- **user_preferences**: Learned user preferences for recommendations
- **trending_items**: Popular items tracking
- **search_histories**: Search behavior tracking for improvements
- **languages**: Available languages configuration
- **translations**: Dynamic translation storage
- **properties**: Property listings with amenities and pricing
- **property_bookings**: Property reservations with check-in/check-out
- **property_images**: Property photo management
- **equipment**: Equipment listings with categories and specifications
- **equipment_bookings**: Equipment rentals with delivery tracking
- **equipment_images**: Equipment photo management
- **referrals**: Referral codes and tracking
- **referral_rewards**: Reward distribution system
- **conversations**: Chat conversations between users
- **messages**: Individual chat messages
- **geo_notifications**: Geolocation-based notifications with targeting and scheduling
- **notification_preferences**: User preferences for geo-notifications and location sharing
- **user_locations**: User location history and current position tracking

### Advanced Vehicle Fields (Added 2025-08-08)
- `vehicle_type`: sedan, SUV, hatchback, coupe, minivan, pickup, van
- `engine_size`: Engine displacement information
- `fuel_consumption`: Fuel efficiency rating
- `has_insurance`: Insurance coverage included
- `instant_booking`: Allow immediate booking without owner approval
- `min_rental_days`/`max_rental_days`: Rental duration constraints
- `pickup_location`: Specific pickup location details
- `availability_schedule`: JSON field for custom availability rules
- `latitude`/`longitude`: GPS coordinates for map display and location-based search

### User License Fields
- `driving_license_number`: License identification number
- `driving_license_expiry`: Expiration date
- `driving_license_status`: pending, verified, rejected
- `driving_license_verified_at`: Verification timestamp
- `driving_license_rejection_reason`: Admin feedback for rejections
- `is_admin`: Boolean for admin privileges
- `user_role`: Primary role selection (locataire, proprietaire, both)
- `locale`: User's preferred language
- `timezone`: User's timezone preference

### Important Routes

#### Public Routes
- `/vehicles` - Vehicle search and listing
- `/vehicles/{vehicle}` - Vehicle details with reviews
- `/reviews` - All public reviews
- `/vehicles/{vehicle}/reviews` - Reviews for a specific vehicle
- `/users/{user}/reviews` - Reviews for a specific user

#### Authenticated Routes
- `/my-vehicles` - Manage owned vehicles
- `/my-properties` - Manage owned properties
- `/my-equipment` - Manage owned equipment
- `/my-rentals` - Vehicle renter's reservations
- `/my-property-bookings` - Property guest's reservations
- `/my-equipment-bookings` - Equipment renter's reservations
- `/my-bookings` - Owner's rental requests
- `/rentals/create/{vehicle}` - Book a vehicle
- `/properties/{property}/book` - Book a property
- `/equipment/{equipment}/book` - Book equipment
- `/rentals/{rental}` - Rental details and management
- `/rentals/{rental}/review` - Create review after rental
- `/settings/driver-license` - Manage driver's license
- `/favorites` - Manage wishlist/favorites with personal notes
- `/ai/dashboard` - AI recommendations dashboard
- `/referrals` - Referral program dashboard
- `/chat` - Messaging center
- `/settings/notification-preferences` - Manage geo-notification preferences

#### API Routes
- `/api/ai/recommendations` - Get personalized recommendations
- `/api/ai/trending` - Get trending items
- `/api/ai/search-suggestions` - Get search suggestions
- `/api/localization/languages` - Get available languages
- `/api/localization/translations` - Get translations
- `/api/geo-notifications/location` - Update user location
- `/api/geo-notifications/nearby` - Get nearby notifications
- `/api/geo-notifications/{id}/read` - Mark notification as read
- `/api/geo-notifications/{id}/clicked` - Mark notification as clicked

#### Admin Routes
- `/admin/license-verifications` - Review pending licenses
- `/admin/users/{user}/verify-license` - Approve/reject license
- `/admin/translations` - Manage translations
- `/admin/languages/add` - Add new language
- `/admin/languages/{language}/toggle` - Enable/disable language
- `/admin/geo-notifications` - Manage geo-notifications
- `/admin/geo-notifications/create` - Create new geo-notification
- `/admin/geo-notifications/{id}/activate` - Activate notification
- `/admin/geo-notifications/{id}/process` - Process pending notification
- `/admin/geo-notifications/bulk-action` - Bulk operations on notifications

## Recent Updates (2025-08-08)

### Completed Features
1. **Review System**
   - Full CRUD operations for reviews
   - Mutual rating between renters and owners
   - Detailed criteria ratings (cleanliness, communication, condition, value)
   - Public/private review visibility
   - Automatic rating calculation for users and vehicles

2. **License Verification**
   - License upload functionality
   - Admin verification workflow
   - Status tracking (pending, verified, rejected)
   - Blocking rentals for invalid licenses
   - Expiration warnings and alerts

3. **Advanced Search System**
   - Enhanced VehicleController with comprehensive filtering
   - New vehicle fields: type, engine specs, insurance, booking preferences
   - Location-based search with distance calculation
   - Advanced UI filters with collapsible sections
   - Cached filter options for performance

4. **Visual Availability Calendar**
   - Interactive calendar component for date selection
   - Real-time availability visualization
   - Date range selection with validation
   - Booking conflict detection
   - Price calculation integration

5. **Performance Optimizations**
   - Database query scopes for reusability
   - Optimized eager loading with field selection
   - Database indexes for search performance
   - Query result caching
   - Reduced N+1 query problems

6. **Admin Panel**
   - License verification interface
   - Approve/reject functionality with feedback
   - User management capabilities

7. **Geolocation & Interactive Maps**
   - Leaflet.js integration with OpenStreetMap tiles
   - Interactive map view for vehicle listings with grid/map toggle
   - Location-based search with customizable radius (1-100km)  
   - GPS positioning and address geocoding via Nominatim
   - LocationPicker component for vehicle creation
   - Vehicle location display on detail pages
   - useGeolocation composable for location utilities
   - **Navigation Services Integration**:
     - Direct navigation with Google Maps, Waze, Apple Plans
     - Automatic platform detection for recommended apps
     - Distance calculation and travel time estimation
     - Quick navigation from map popups and vehicle cards
     - GPS coordinates copy-to-clipboard option

8. **Favorites/Wishlist System**
   - One-click favorite toggle on all vehicle listings
   - Dedicated favorites management page with notes
   - Personal notes functionality for saved vehicles
   - Dashboard integration with wishlist statistics
   - FavoriteButton component with heart animation

9. **Payment System Integration**
   - Dual payment gateway support (Stripe and PayPal)
   - Automatic fee calculation (platform + gateway fees)
   - Complete refund management with partial/full refunds
   - Payment status tracking and history
   - Secure payment processing with webhooks
   - Service classes: StripeService and PayPalService
   - Payment model with fee calculations and relationships

10. **UI Components**
   - StarRating component for reviews
   - LicenseAlert component for status notifications
   - AvailabilityCalendar component with full interactivity
   - VehicleFilters component with advanced filtering
   - InteractiveMap, LocationPicker, VehicleMapView for maps
   - FavoriteButton for wishlist functionality
   - Pagination component for lists
   - Image preview modals

11. **AI Recommendations System (Added 2025-09-12)**
   - Hybrid recommendation algorithms (collaborative + content-based filtering)
   - User behavior tracking with activity logging
   - Personalized recommendations based on browsing history
   - Search suggestions and autocomplete
   - Trending items analysis
   - Continuous learning from user feedback
   - Confidence scoring for recommendations
   - Dashboard with performance metrics
   - Services: AIRecommendationService with ML algorithms
   - Models: UserActivity, Recommendation, UserPreference, TrendingItem, SearchHistory

12. **Multilingual Support (Added 2025-09-12)**
   - Support for 4 languages: French (default), English, Spanish, Arabic
   - Complete RTL support for Arabic language
   - Automatic browser language detection
   - User preference persistence
   - Dynamic database-stored translations
   - Static file-based translations for UI strings
   - Localized formatting utilities (dates, currency, numbers, relative time)
   - LanguageSwitcher component with flags
   - Vue i18n integration with Inertia.js
   - Services: LocalizationService for translation management
   - Middleware: Localization for automatic locale setting
   - Models: Language, Translation for dynamic content

13. **Referral System (Added 2025-09-12)**
   - Unique referral code generation for each user
   - Reward system for both referrer and referee
   - Leaderboard with top referrers
   - Conversion tracking and statistics
   - Social media sharing integration
   - Automatic credit application on bookings
   - Models: Referral, ReferralReward for tracking

14. **Property Rental System (Added 2025-09-12)**
   - Complete property management (houses, apartments, villas)
   - Check-in/check-out workflow
   - Amenities and features management
   - Flexible pricing (per night/week/month)
   - Property-specific reviews
   - Gallery with multiple photos
   - Controllers: PropertyController, PropertyBookingController
   - Models: Property, PropertyBooking, PropertyImage

15. **Equipment Rental System (Added 2025-09-12)**
   - Multi-category equipment support (sports, tools, events)
   - Stock and quantity management
   - Delivery and return tracking
   - Equipment status workflow (ready, delivered, returned)
   - Rental extension requests
   - Hourly/daily/weekly pricing
   - Controllers: EquipmentController, EquipmentBookingController
   - Models: Equipment, EquipmentBooking, EquipmentImage

16. **Real-time Chat System**
   - Direct messaging between users
   - Conversation management
   - Archive functionality
   - Integration with rental context
   - Browser push notifications for new messages
   - Laravel Echo and Pusher integration
   - useNotifications composable for notification management
   - Controller: ChatController
   - Models: Conversation, Message

17. **Geolocation-Based Push Notifications (Added 2025-09-12)**
   - Complete location-based notification system
   - Automatic nearby rental alerts when new vehicles become available
   - Pickup reminders when users are near rental locations
   - Area-specific alerts and promotional notifications
   - Comprehensive user preference management with quiet hours and frequency controls
   - Admin panel for creating and managing geo-notifications
   - Advanced targeting with radius control (1-100km) and demographic criteria
   - Browser notification permission handling with fallback options
   - Real-time location tracking with privacy controls
   - Database models: GeoNotification, NotificationPreferences, UserLocation
   - Services: GeoNotificationService with ML-powered targeting algorithms
   - Frontend components: GeoNotificationPermissions, GeoNotificationsList
   - Composables: useGeoNotifications for complete location and notification management
   - Admin dashboard with statistics, bulk actions, and testing tools
   - Automatic cleanup of expired notifications and old location data

18. **Role-Based Registration System (Added 2025-09-29)**
   - User role selection during registration (Propriétaire/Locataire)
   - GuestLayout for unauthenticated users with clean landing page
   - AppLayout with full sidebar navigation for authenticated users
   - Dynamic layout switching based on authentication status
   - Role-specific onboarding experience
   - Flexible dual-role system maintaining backward compatibility
   - Enhanced registration form with descriptive role options
   - Automatic `is_owner` flag setting based on selected role
   - Contextual information on login page about user roles
   - Database migration for `user_role` enum field
   - Updated User model and RegisteredUserController