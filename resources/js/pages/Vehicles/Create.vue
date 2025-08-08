<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import LocationPicker from '@/components/LocationPicker.vue'

const imageFiles = ref<File[]>([])
const imagePreviewUrls = ref<string[]>([])

const form = useForm({
    brand: '',
    model: '',
    year: new Date().getFullYear(),
    color: '',
    license_plate: '',
    mileage: 0,
    fuel_type: 'gasoline',
    transmission: 'manual',
    seats: 5,
    doors: 4,
    description: '',
    features: [] as string[],
    daily_rate: 0,
    weekly_rate: 0,
    monthly_rate: 0,
    address: '',
    city: '',
    postal_code: '',
    latitude: 0,
    longitude: 0,
    images: [] as File[]
})

const location = ref<{ latitude: number; longitude: number; address: string }>({
    latitude: 0,
    longitude: 0,
    address: ''
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

const handleImageSelection = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.files) {
        const files = Array.from(target.files)
        
        // Limit to 10 images
        if (files.length > 10) {
            alert('Vous pouvez uploader maximum 10 images')
            return
        }

        imageFiles.value = files
        form.images = files

        // Create preview URLs
        imagePreviewUrls.value = []
        files.forEach(file => {
            const reader = new FileReader()
            reader.onload = (e) => {
                if (e.target?.result) {
                    imagePreviewUrls.value.push(e.target.result as string)
                }
            }
            reader.readAsDataURL(file)
        })
    }
}

const removeImage = (index: number) => {
    imageFiles.value.splice(index, 1)
    imagePreviewUrls.value.splice(index, 1)
    form.images = imageFiles.value

    // Update the file input
    const fileInput = document.getElementById('images') as HTMLInputElement
    if (fileInput) {
        const dt = new DataTransfer()
        imageFiles.value.forEach(file => dt.items.add(file))
        fileInput.files = dt.files
    }
}

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
    form.post(route('vehicles.store'), {
        forceFormData: true
    })
}
</script>

<template>
    <Head title="Ajouter un véhicule" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Ajouter un véhicule</CardTitle>
                        <p class="text-gray-600">Partagez votre véhicule et gagnez de l'argent</p>
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
                                    <InputError :message="form.errors.brand" />
                                </div>

                                <div>
                                    <Label for="model">Modèle *</Label>
                                    <Input 
                                        id="model"
                                        v-model="form.model"
                                        placeholder="Ex: 308, Clio, Serie 3..."
                                        required
                                    />
                                    <InputError :message="form.errors.model" />
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
                                    <InputError :message="form.errors.year" />
                                </div>

                                <div>
                                    <Label for="color">Couleur *</Label>
                                    <Input 
                                        id="color"
                                        v-model="form.color"
                                        placeholder="Ex: Blanc, Noir, Rouge..."
                                        required
                                    />
                                    <InputError :message="form.errors.color" />
                                </div>

                                <div>
                                    <Label for="license_plate">Plaque d'immatriculation *</Label>
                                    <Input 
                                        id="license_plate"
                                        v-model="form.license_plate"
                                        placeholder="Ex: AB-123-CD"
                                        required
                                    />
                                    <InputError :message="form.errors.license_plate" />
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
                                    <InputError :message="form.errors.mileage" />
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
                                    <InputError :message="form.errors.fuel_type" />
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
                                    <InputError :message="form.errors.transmission" />
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
                                    <InputError :message="form.errors.seats" />
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
                                    <InputError :message="form.errors.doors" />
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
                                    <InputError :message="form.errors.daily_rate" />
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
                                    <InputError :message="form.errors.weekly_rate" />
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
                                    <InputError :message="form.errors.monthly_rate" />
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
                                <InputError :message="form.errors.address" />
                                <InputError :message="form.errors.latitude" />
                                <InputError :message="form.errors.longitude" />
                                
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
                                        <InputError :message="form.errors.city" />
                                    </div>
                                    <div>
                                        <Label for="postal_code">Code postal (auto-rempli) *</Label>
                                        <Input 
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            placeholder="Ex: 75001"
                                            required
                                        />
                                        <InputError :message="form.errors.postal_code" />
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
                                <InputError :message="form.errors.description" />
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

                            <!-- Images -->
                            <div>
                                <Label for="images">Photos du véhicule *</Label>
                                <input 
                                    id="images"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    @change="handleImageSelection"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2"
                                    required
                                />
                                <p class="text-sm text-gray-600 mt-1">
                                    Sélectionnez entre 1 et 10 images (formats: JPG, PNG). La première image sera l'image principale.
                                </p>
                                <InputError :message="form.errors.images" />

                                <!-- Image previews -->
                                <div v-if="imagePreviewUrls.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                    <div 
                                        v-for="(url, index) in imagePreviewUrls" 
                                        :key="index"
                                        class="relative"
                                    >
                                        <img 
                                            :src="url" 
                                            :alt="`Preview ${index + 1}`"
                                            class="w-full h-32 object-cover rounded-lg border"
                                        />
                                        <button
                                            type="button"
                                            @click="removeImage(index)"
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 text-xs hover:bg-red-600"
                                        >
                                            ✕
                                        </button>
                                        <div v-if="index === 0" class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">
                                            Principal
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-end space-x-4">
                                <Button 
                                    type="button" 
                                    variant="outline"
                                    @click="$inertia.visit(route('vehicles.index'))"
                                >
                                    Annuler
                                </Button>
                                <Button 
                                    type="submit" 
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Ajout en cours...' : 'Ajouter le véhicule' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>