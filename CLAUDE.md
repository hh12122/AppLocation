# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel + Vue.js car rental application called "CarLocation" built with:
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
- **User Roles**: Dual-role system (renters and owners) with admin capabilities
- **Review System**: Complete mutual rating system with detailed criteria
- **License Verification**: Mandatory driver's license validation before rentals
- **Dashboard**: Statistics and management for both user types
- **Admin Panel**: License verification management interface
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
- **Models**: Standard Laravel Eloquent models in `app/Models/` including Review model
- **Routes**: Organized in separate files (`web.php`, `auth.php`, `settings.php`)
- **Database**: SQLite with migrations in `database/migrations/`
- **Middleware**: IsAdmin middleware for admin-only routes

### Frontend Structure
- **Entry Point**: `resources/js/app.ts` initializes Inertia.js app
- **Pages**: Vue components in `resources/js/pages/` (Inertia pages)
  - Reviews/: Complete review system pages (Index, Create, Edit, Show, VehicleReviews, UserReviews)
  - Admin/LicenseVerifications: Admin panel for license validation
  - Settings/DriverLicense: User license management
  - Favorites/: Wishlist management pages (Index)
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
- **Composables**: Vue composables in `resources/js/composables/`
  - useGeolocation: Geolocation utilities with current position, geocoding, and distance calculation
  - useNavigation: Navigation services integration (Google Maps, Waze, Apple Plans, OpenStreetMap)
- **UI Components**: Reka UI components in `resources/js/components/ui/`
- **Layouts**: Page layouts in `resources/js/layouts/`

### Key Features
- **Authentication**: Full auth system with registration, login, password reset
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

### Important Routes

#### Public Routes
- `/vehicles` - Vehicle search and listing
- `/vehicles/{vehicle}` - Vehicle details with reviews
- `/reviews` - All public reviews
- `/vehicles/{vehicle}/reviews` - Reviews for a specific vehicle
- `/users/{user}/reviews` - Reviews for a specific user

#### Authenticated Routes
- `/my-vehicles` - Manage owned vehicles
- `/my-rentals` - Renter's reservations
- `/my-bookings` - Owner's rental requests
- `/rentals/create/{vehicle}` - Book a vehicle
- `/rentals/{rental}` - Rental details and management
- `/rentals/{rental}/review` - Create review after rental
- `/settings/driver-license` - Manage driver's license
- `/favorites` - Manage wishlist/favorites with personal notes

#### Admin Routes
- `/admin/license-verifications` - Review pending licenses
- `/admin/users/{user}/verify-license` - Approve/reject license

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