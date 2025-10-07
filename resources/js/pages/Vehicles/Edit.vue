<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { FormError } from '@/components/ui/form'
import LocationPicker from '@/components/LocationPicker.vue'

interface VehicleImage {
    id: number
    image_path: string
    is_primary: boolean
}

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    color: string
    license_plate: string
    mileage: number
    fuel_type: string
    transmission: string
    seats: number
    doors: number
    description: string | null
    features: string[]
    daily_rate: number
    weekly_rate: number | null
    monthly_rate: number | null
    address: string
    city: string
    postal_code: string
    latitude: number
    longitude: number
    status: string
    is_available: boolean
    images: VehicleImage[]
}

const props = defineProps<{
    vehicle: Vehicle
}>()

const form = useForm({
    brand: props.vehicle.brand,
    model: props.vehicle.model,
    year: props.vehicle.year,
    color: props.vehicle.color,
    license_plate: props.vehicle.license_plate,
    mileage: props.vehicle.mileage,
    fuel_type: props.vehicle.fuel_type,
    transmission: props.vehicle.transmission,
    seats: props.vehicle.seats,
    doors: props.vehicle.doors,
    description: props.vehicle.description || '',
    features: props.vehicle.features || [],
    daily_rate: props.vehicle.daily_rate,
    weekly_rate: props.vehicle.weekly_rate || 0,
    monthly_rate: props.vehicle.monthly_rate || 0,
    address: props.vehicle.address,
    city: props.vehicle.city,
    postal_code: props.vehicle.postal_code,
    latitude: props.vehicle.latitude,
    longitude: props.vehicle.longitude,
    status: props.vehicle.status,
    is_available: props.vehicle.is_available
})

const location = ref<{ latitude: number; longitude: number; address: string }>({
    latitude: props.vehicle.latitude,
    longitude: props.vehicle.longitude,
    address: props.vehicle.address
})

const availableFeatures = [
    'Climatisation',
    'GPS',
    'Bluetooth',
    'USB',
    'Siège chauffant',
    'Toit ouvrant',
    'Caméra de recul',
    'Radar de recul',
    'Régulateur de vitesse',
    'Jantes alliage',
    'Feux LED',
    'Système audio premium'
]

const toggleFeature = (feature: string) => {
    const index = form.features.indexOf(feature)
    if (index > -1) {
        form.features.splice(index, 1)
    } else {
        form.features.push(feature)
    }
}

const onLocationUpdate = (newLocation: { latitude: number; longitude: number; address: string }) => {
    location.value = newLocation
    form.latitude = newLocation.latitude
    form.longitude = newLocation.longitude
    form.address = newLocation.address

    // Extract city and postal code from address if possible
    const addressParts = newLocation.address.split(', ')
    if (addressParts.length > 2) {
        const lastPart = addressParts[addressParts.length - 1]
        const secondLastPart = addressParts[addressParts.length - 2]

        // Try to extract postal code and city
        const postalCodeMatch = secondLastPart.match(/\b\d{5}\b/)
        if (postalCodeMatch) {
            form.postal_code = postalCodeMatch[0]
            form.city = secondLastPart.replace(postalCodeMatch[0], '').trim()
        } else {
            form.city = secondLastPart
        }
    }
}

const submit = () => {
    form.put(route('vehicles.update', props.vehicle.id))
}
</script>

<template>
    <Head title="Modifier le véhicule" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Modifier le véhicule</CardTitle>
                        <p class="text-gray-600">Mettez à jour les informations de votre véhicule</p>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Vehicle Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <Label for="brand">Marque *</Label>
                                    <Input
                                        id="brand"
                                        v-model="form.brand"
                                        placeholder="Ex: Peugeot, Renault, BMW..."
                                        required
                                    />
                                    <FormError :message="form.errors.brand" />
                                </div>

                                <div>
                                    <Label for="model">Modèle *</Label>
                                    <Input
                                        id="model"
                                        v-model="form.model"
                                        placeholder="Ex: 308, Clio, Serie 3..."
                                        required
                                    />
                                    <FormError :message="form.errors.model" />
                                </div>

                                <div>
                                    <Label for="year">Année *</Label>
                                    <Input
                                        id="year"
                                        v-model.number="form.year"
                                        type="number"
                                        :min="1950"
                                        :max="new Date().getFullYear() + 1"
                                        required
                                    />
                                    <FormError :message="form.errors.year" />
                                </div>

                                <div>
                                    <Label for="color">Couleur *</Label>
                                    <Input
                                        id="color"
                                        v-model="form.color"
                                        placeholder="Ex: Blanc, Noir, Rouge..."
                                        required
                                    />
                                    <FormError :message="form.errors.color" />
                                </div>

                                <div>
                                    <Label for="license_plate">Plaque d'immatriculation *</Label>
                                    <Input
                                        id="license_plate"
                                        v-model="form.license_plate"
                                        placeholder="Ex: AB-123-CD"
                                        required
                                    />
                                    <FormError :message="form.errors.license_plate" />
                                </div>

                                <div>
                                    <Label for="mileage">Kilométrage (km) *</Label>
                                    <Input
                                        id="mileage"
                                        v-model.number="form.mileage"
                                        type="number"
                                        min="0"
                                        required
                                    />
                                    <FormError :message="form.errors.mileage" />
                                </div>
                            </div>

                            <!-- Vehicle Specifications -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <Label for="fuel_type">Carburant *</Label>
                                    <select
                                        id="fuel_type"
                                        v-model="form.fuel_type"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="gasoline">Essence</option>
                                        <option value="diesel">Diesel</option>
                                        <option value="electric">Électrique</option>
                                        <option value="hybrid">Hybride</option>
                                    </select>
                                    <FormError :message="form.errors.fuel_type" />
                                </div>

                                <div>
                                    <Label for="transmission">Transmission *</Label>
                                    <select
                                        id="transmission"
                                        v-model="form.transmission"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="manual">Manuelle</option>
                                        <option value="automatic">Automatique</option>
                                    </select>
                                    <FormError :message="form.errors.transmission" />
                                </div>

                                <div>
                                    <Label for="seats">Nombre de places *</Label>
                                    <Input
                                        id="seats"
                                        v-model.number="form.seats"
                                        type="number"
                                        min="1"
                                        max="9"
                                        required
                                    />
                                    <FormError :message="form.errors.seats" />
                                </div>

                                <div>
                                    <Label for="doors">Nombre de portes *</Label>
                                    <Input
                                        id="doors"
                                        v-model.number="form.doors"
                                        type="number"
                                        min="2"
                                        max="5"
                                        required
                                    />
                                    <FormError :message="form.errors.doors" />
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <Label for="daily_rate">Prix par jour (€) *</Label>
                                    <Input
                                        id="daily_rate"
                                        v-model.number="form.daily_rate"
                                        type="number"
                                        min="1"
                                        step="0.01"
                                        required
                                    />
                                    <FormError :message="form.errors.daily_rate" />
                                </div>

                                <div>
                                    <Label for="weekly_rate">Prix par semaine (€)</Label>
                                    <Input
                                        id="weekly_rate"
                                        v-model.number="form.weekly_rate"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="Optionnel"
                                    />
                                    <FormError :message="form.errors.weekly_rate" />
                                </div>

                                <div>
                                    <Label for="monthly_rate">Prix par mois (€)</Label>
                                    <Input
                                        id="monthly_rate"
                                        v-model.number="form.monthly_rate"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="Optionnel"
                                    />
                                    <FormError :message="form.errors.monthly_rate" />
                                </div>
                            </div>

                            <!-- Location -->
                            <div>
                                <LocationPicker
                                    v-model="location"
                                    label="Localisation du véhicule *"
                                    placeholder="Recherchez l'adresse ou cliquez sur la carte"
                                    required
                                    @update:model-value="onLocationUpdate"
                                />
                                <FormError :message="form.errors.address" />
                                <FormError :message="form.errors.latitude" />
                                <FormError :message="form.errors.longitude" />

                                <!-- Additional location fields for user verification -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <Label for="city">Ville (auto-remplie) *</Label>
                                        <Input
                                            id="city"
                                            v-model="form.city"
                                            placeholder="Ex: Paris"
                                            required
                                        />
                                        <FormError :message="form.errors.city" />
                                    </div>
                                    <div>
                                        <Label for="postal_code">Code postal (auto-rempli) *</Label>
                                        <Input
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            placeholder="Ex: 75001"
                                            required
                                        />
                                        <FormError :message="form.errors.postal_code" />
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Décrivez votre véhicule, ses points forts, conseils d'utilisation..."
                                />
                                <FormError :message="form.errors.description" />
                            </div>

                            <!-- Features -->
                            <div>
                                <Label>Équipements disponibles</Label>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mt-2">
                                    <label
                                        v-for="feature in availableFeatures"
                                        :key="feature"
                                        class="flex items-center space-x-2 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.features.includes(feature)"
                                            @change="toggleFeature(feature)"
                                            class="rounded border-gray-300 text-indigo-600 focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm">{{ feature }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Status and Availability -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <Label for="status">Statut *</Label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="active">Actif</option>
                                        <option value="inactive">Inactif</option>
                                        <option value="maintenance">En maintenance</option>
                                    </select>
                                    <FormError :message="form.errors.status" />
                                </div>

                                <div class="flex items-center space-x-2 mt-6">
                                    <input
                                        id="is_available"
                                        v-model="form.is_available"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <Label for="is_available" class="!mt-0">Disponible à la location</Label>
                                </div>
                            </div>

                            <!-- Current Images -->
                            <div v-if="vehicle.images && vehicle.images.length > 0">
                                <Label>Photos actuelles</Label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                    <div
                                        v-for="image in vehicle.images"
                                        :key="image.id"
                                        class="relative"
                                    >
                                        <img
                                            :src="`/storage/${image.image_path}`"
                                            :alt="`Vehicle image`"
                                            class="w-full h-32 object-cover rounded-lg border"
                                        />
                                        <div v-if="image.is_primary" class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">
                                            Principal
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">
                                    Pour modifier les photos, veuillez contacter le support.
                                </p>
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-end space-x-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="$inertia.visit(route('vehicles.show', vehicle.id))"
                                >
                                    Annuler
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Mise à jour en cours...' : 'Mettre à jour le véhicule' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
