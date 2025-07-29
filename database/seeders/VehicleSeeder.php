<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test owner
        $owner = User::create([
            'name' => 'Jean Dupont',
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'phone' => '06 12 34 56 78',
            'city' => 'Paris',
            'address' => '123 Rue de la Paix',
            'postal_code' => '75001',
            'country' => 'FR',
            'driving_license_number' => 'DL123456789',
            'driving_license_expiry' => now()->addYears(5),
            'is_owner' => true,
            'is_verified' => true,
            'rating' => 4.8,
            'rating_count' => 25,
        ]);

        // Create another test user (renter)
        User::create([
            'name' => 'Marie Martin',
            'email' => 'renter@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'phone' => '06 98 76 54 32',
            'city' => 'Lyon',
            'address' => '456 Avenue des Champs',
            'postal_code' => '69000',
            'country' => 'FR',
            'driving_license_number' => 'DL987654321',
            'driving_license_expiry' => now()->addYears(3),
            'is_owner' => false,
            'is_verified' => true,
            'rating' => 4.5,
            'rating_count' => 12,
        ]);

        // Create sample vehicles
        $vehicles = [
            [
                'brand' => 'Peugeot',
                'model' => '308',
                'year' => 2022,
                'color' => 'Blanc',
                'license_plate' => 'AB-123-CD',
                'mileage' => 25000,
                'fuel_type' => 'gasoline',
                'transmission' => 'manual',
                'seats' => 5,
                'doors' => 5,
                'daily_rate' => 45.00,
                'weekly_rate' => 280.00,
                'monthly_rate' => 1000.00,
                'description' => 'Peugeot 308 en excellent état, climatisation, GPS intégré. Parfaite pour les déplacements en ville et les longs trajets.',
                'features' => ['Climatisation', 'GPS', 'Bluetooth', 'USB'],
                'address' => '123 Rue de la Paix',
                'city' => 'Paris',
                'postal_code' => '75001',
                'status' => 'active',
                'is_available' => true,
                'rating' => 4.7,
                'rating_count' => 15,
                'rental_count' => 23,
            ],
            [
                'brand' => 'Renault',
                'model' => 'Clio',
                'year' => 2021,
                'color' => 'Rouge',
                'license_plate' => 'EF-456-GH',
                'mileage' => 18000,
                'fuel_type' => 'gasoline',
                'transmission' => 'automatic',
                'seats' => 5,
                'doors' => 5,
                'daily_rate' => 35.00,
                'weekly_rate' => 220.00,
                'description' => 'Renault Clio automatique, idéale pour la ville. Consommation économique et très maniable.',
                'features' => ['Climatisation', 'Bluetooth', 'Régulateur de vitesse'],
                'address' => '123 Rue de la Paix',
                'city' => 'Paris',
                'postal_code' => '75001',
                'status' => 'active',
                'is_available' => true,
                'rating' => 4.3,
                'rating_count' => 8,
                'rental_count' => 12,
            ],
            [
                'brand' => 'BMW',
                'model' => 'Serie 3',
                'year' => 2023,
                'color' => 'Noir',
                'license_plate' => 'IJ-789-KL',
                'mileage' => 12000,
                'fuel_type' => 'diesel',
                'transmission' => 'automatic',
                'seats' => 5,
                'doors' => 4,
                'daily_rate' => 85.00,
                'weekly_rate' => 550.00,
                'monthly_rate' => 2000.00,
                'description' => 'BMW Série 3 haut de gamme, cuir, GPS premium, conduite sportive et confortable.',
                'features' => ['Climatisation', 'GPS', 'Bluetooth', 'Siège chauffant', 'Jantes alliage', 'Système audio premium'],
                'address' => '123 Rue de la Paix',
                'city' => 'Paris',
                'postal_code' => '75001',
                'status' => 'active',
                'is_available' => true,
                'rating' => 4.9,
                'rating_count' => 22,
                'rental_count' => 35,
            ]
        ];

        foreach ($vehicles as $vehicleData) {
            $vehicleData['owner_id'] = $owner->id;
            Vehicle::create($vehicleData);
        }
    }
}