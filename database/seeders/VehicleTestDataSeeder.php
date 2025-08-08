<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add test vehicles to demonstrate new features
        $this->seedAdvancedVehicles();
    }

    private function seedAdvancedVehicles()
    {
        // For demo purposes - assumes users exist
        $users = \App\Models\User::limit(2)->get();
        if ($users->count() === 0) return;

        $vehicleTypes = ['sedan', 'suv', 'hatchback', 'coupe', 'minivan'];
        $brands = ['Peugeot', 'Renault', 'BMW', 'Mercedes'];
        $cities = ['Paris', 'Lyon', 'Marseille', 'Nice'];
        $features = ['GPS', 'Climatisation', 'Bluetooth', 'Caméra de recul'];

        foreach (range(1, 10) as $i) {
            \App\Models\Vehicle::create([
                'owner_id' => $users->random()->id,
                'brand' => $brands[array_rand($brands)],
                'model' => 'Model ' . $i,
                'vehicle_type' => $vehicleTypes[array_rand($vehicleTypes)],
                'year' => rand(2018, 2024),
                'color' => ['Blanc', 'Noir', 'Gris'][rand(0, 2)],
                'license_plate' => 'TEST-' . $i,
                'mileage' => rand(5000, 100000),
                'fuel_type' => ['gasoline', 'diesel'][rand(0, 1)],
                'engine_size' => '1.' . rand(4, 8) . 'L',
                'fuel_consumption' => rand(45, 85) / 10,
                'transmission' => ['manual', 'automatic'][rand(0, 1)],
                'seats' => rand(4, 7),
                'doors' => rand(3, 5),
                'description' => 'Test vehicle ' . $i,
                'features' => array_slice($features, 0, rand(1, 3)),
                'has_insurance' => rand(0, 1),
                'instant_booking' => rand(0, 1),
                'min_rental_days' => rand(1, 3),
                'daily_rate' => rand(30, 80),
                'address' => $i . ' Test Street',
                'city' => $cities[array_rand($cities)],
                'postal_code' => '75001',
                'status' => 'active',
                'is_available' => true,
            ]);
        }
    }
}
