# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel + Vue.js car rental application called "CarLocation" built with:
- **Backend**: Laravel 12 with Inertia.js for SPA behavior
- **Frontend**: Vue 3 + TypeScript with Tailwind CSS v4 and Reka UI components
- **Database**: SQLite (development) with complete car rental schema
- **Build Tools**: Vite for asset compilation and development server

### Core Features
- **Vehicle Management**: Owners can list vehicles with photos, pricing, and availability
- **Rental System**: Complete booking workflow from search to payment
- **User Roles**: Dual-role system (renters and owners)
- **Review System**: Ratings for vehicles, owners, and renters
- **Dashboard**: Statistics and management for both user types

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
- **Models**: Standard Laravel Eloquent models in `app/Models/`
- **Routes**: Organized in separate files (`web.php`, `auth.php`, `settings.php`)
- **Database**: SQLite with migrations in `database/migrations/`

### Frontend Structure
- **Entry Point**: `resources/js/app.ts` initializes Inertia.js app
- **Pages**: Vue components in `resources/js/pages/` (Inertia pages)
- **Components**: Reusable components in `resources/js/components/`
- **UI Components**: Reka UI components in `resources/js/components/ui/`
- **Layouts**: Page layouts in `resources/js/layouts/`
- **Composables**: Vue composables in `resources/js/composables/`

### Key Features
- **Authentication**: Full auth system with registration, login, password reset
- **User Settings**: Profile management, password updates, appearance themes, driver license info
- **Vehicle Management**: CRUD operations for vehicles with image upload
- **Rental Workflow**: Search, book, confirm, pickup, return, review cycle
- **Rating System**: Mutual ratings between owners and renters
- **Dashboard**: Role-specific dashboards with statistics
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
- Image storage configured with `storage:link` for vehicle photos
- Vite handles asset compilation with hot reloading
- ESLint and Prettier configured for code quality
- Tailwind CSS v4 with custom configuration
- Component auto-discovery via Vite glob imports

## Database Schema

### Key Tables
- **users**: Extended with rental-specific fields (driver license, ratings, roles)
- **vehicles**: Complete vehicle information with pricing and location
- **rentals**: Full rental lifecycle with status tracking
- **reviews**: Rating system for vehicles, owners, and renters
- **vehicle_images**: Photo management for vehicles

### Important Routes
- `/vehicles` - Vehicle search and listing
- `/vehicles/create` - Add new vehicle (owners)
- `/my-vehicles` - Manage owned vehicles
- `/my-rentals` - Renter's reservations
- `/my-bookings` - Owner's rental requests
- `/rentals/create/{vehicle}` - Book a vehicle
- `/rentals/{rental}` - Rental details and management