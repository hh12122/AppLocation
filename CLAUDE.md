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

### Chat/Messaging System Architecture

The real-time messaging system enables direct communication between owners and renters for each rental.

#### Message Flow
```
User sends message → API endpoint → Save to database → Broadcast event
    ↓
Laravel Echo/Pusher → Real-time update in UI → Browser notification (if permitted)
```

**Complete Flow:**
1. User types message in `Chat/Show.vue` and clicks send
2. Frontend calls `POST /api/chat/{conversation}/send-message`
3. `ChatController@sendMessage` validates and saves message to database
4. `MessageSent` event broadcasts to `conversation.{id}` private channel
5. Laravel Echo listens on frontend and updates message list in real-time
6. `NewMessageNotification` sent to other participant's private channel
7. Browser notification appears (if permission granted)

#### Database Schema

**conversations table:**
- `id`: Primary key
- `rental_id`: Foreign key to rentals (unique - one conversation per rental)
- `renter_id`: Foreign key to users (the renter)
- `owner_id`: Foreign key to users (the vehicle owner)
- `last_message_at`: Timestamp of last message
- `is_archived`: Boolean for archiving conversations
- `timestamps`: created_at, updated_at

**messages table:**
- `id`: Primary key
- `conversation_id`: Foreign key to conversations
- `user_id`: Foreign key to users (message sender)
- `message`: Text content of the message
- `message_type`: Enum (text, image, system)
- `attachments`: JSON field for file attachments
- `read_at`: Timestamp when message was read (null = unread)
- `is_edited`: Boolean flag for edited messages
- `edited_at`: Timestamp of last edit
- `timestamps`: created_at, updated_at

#### Key Components

**Models:**
- `Conversation`: Manages chat conversations with scopes (`forUser`, `active`)
- `Message`: Individual messages with read status tracking
- Relationships: Conversation hasMany Messages, belongs to Rental/Users
- Helper methods: `isParticipant()`, `getOtherParticipant()`, `markAllMessagesAsReadFor()`

**Events & Broadcasting:**
- `MessageSent` event: Broadcasts to `conversation.{id}` private channel
- `NewMessageNotification`: Database + broadcast notification to user's channel
- Channel authorization in `routes/channels.php` ensures only participants can join

**Controllers:**
- `ChatController@index`: List all conversations for current user
- `ChatController@show`: Display conversation with all messages
- `ChatController@sendMessage`: Send new message in conversation
- `ChatController@createForRental`: Create/find conversation for a rental
- `ChatController@markAsRead`: Mark messages as read
- `ChatController@getUnreadCount`: Get total unread count (for badge)
- `ChatController@archive`: Archive a conversation

**Real-time Setup:**
- Laravel Echo configured with Pusher in `resources/js/app.ts`
- Private channels require authentication via `/broadcasting/auth`
- Frontend listens on `conversation.{id}` and `App.Models.User.{id}` channels
- WebSocket connection maintained for instant message delivery

**Security:**
- Only rental participants (owner + renter) can access conversation
- Channel authorization via `Broadcast::channel()` in routes/channels.php
- CSRF protection on all POST requests
- User authentication required for all chat endpoints

### Frontend Structure
- **Entry Point**: `resources/js/app.ts` initializes Inertia.js app
- **Pages**: Vue components in `resources/js/pages/` (Inertia pages)
  - Reviews/: Complete review system pages (Index, Create, Edit, Show, VehicleReviews, UserReviews)
  - Admin/LicenseVerifications: Admin panel for license validation
  - Settings/DriverLicense: User license management
  - Favorites/: Wishlist management pages (Index)
  - AI/Dashboard: AI recommendations dashboard with analytics
  - Chat/Index: Conversations list with unread counts and last message preview
  - Chat/Show: Real-time chat interface with message sending and live updates
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
  - ChatNotificationButton: Header notification icon with unread message badge and real-time updates
- **Composables**: Vue composables in `resources/js/composables/`
  - useGeolocation: Geolocation utilities with current position, geocoding, and distance calculation
  - useNavigation: Navigation services integration (Google Maps, Waze, Apple Plans, OpenStreetMap)
  - useAIRecommendations: AI recommendation fetching and management
  - useLocalization: i18n utilities with formatting, RTL support, and language switching
  - useNotifications: Chat notification management with browser notifications, real-time updates via Echo
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
- **conversations**: Chat conversations between users (rental-specific, one per rental with renter_id/owner_id)
- **messages**: Individual chat messages (belongs to conversation, tracks sender, read status, message type, attachments)
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
- `/chat` - Messaging center (list all conversations)
- `/chat/{conversation}` - View conversation with real-time messaging
- `/chat/rental/{rental}` - Create/access conversation for a specific rental
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
- `/api/chat/send` - Create new message for a rental
- `/api/chat/{conversation}/messages` - Get paginated messages for conversation
- `/api/chat/{conversation}/send-message` - Send message in existing conversation
- `/api/chat/{conversation}/mark-read` - Mark all conversation messages as read
- `/api/chat/unread-count` - Get total unread message count for current user

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
   - Direct messaging between users (owner ↔ renter communication)
   - Rental-specific conversations (one conversation per rental)
   - Real-time message delivery via Laravel Echo + Pusher WebSockets
   - Read receipts and unread message tracking
   - Conversation management with last message timestamps
   - Archive functionality for old conversations
   - Integration with rental context (accessible from rental details)
   - Browser push notifications for new messages (with permission handling)
   - Unread message badge in header navigation
   - Message types support (text, image, system messages)
   - Attachment support (JSON field for future file uploads)
   - Private channel authorization (only participants can access)
   - Auto-scroll to latest messages
   - Message timestamps with formatted display
   - Laravel Echo and Pusher integration configured globally
   - Frontend components: Chat/Index.vue, Chat/Show.vue, ChatNotificationButton.vue
   - useNotifications composable for notification management
   - Controller: ChatController with full CRUD operations
   - Models: Conversation (with scopes and helper methods), Message (with read tracking)
   - Events: MessageSent (broadcasts to conversation channel)
   - Notifications: NewMessageNotification (database + broadcast + email)
   - Broadcasting channels: conversation.{id}, App.Models.User.{id}
   - Database migrations: conversations and messages tables with proper indexes
   - API routes for send, fetch, mark-read, unread-count operations

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.16
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- tightenco/ziggy (ZIGGY) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- laravel-echo (ECHO) - v1
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
