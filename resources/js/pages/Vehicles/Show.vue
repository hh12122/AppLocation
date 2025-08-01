<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    color: string
    mileage: number
    fuel_type: string
    transmission: string
    seats: number
    doors: number
    daily_rate: number
    weekly_rate?: number
    monthly_rate?: number
    description?: string
    features?: string[]
    address: string
    city: string
    postal_code: string
    rating: number
    rating_count: number
    rental_count: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
    owner: {
        id: number
        name: string
        rating: number
        rating_count: number
    }
    reviews: Array<{
        id: number
        rating: number
        comment: string
        reviewer: {
            name: string
        }
        created_at: string
    }>
}

interface Props {
    vehicle: Vehicle
    canRent: boolean
}

const props = defineProps<Props>()

const currentImageIndex = ref(0)
const showAllImages = ref(false)

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const fuelTypeLabels = {
    gasoline: 'Essence',
    diesel: 'Diesel', 
    electric: 'Électrique',
    hybrid: 'Hybride'
}

const transmissionLabels = {
    manual: 'Manuelle',
    automatic: 'Automatique'
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getImageUrl = (imagePath: string) => {
    return `/storage/${imagePath}`
}

const nextImage = () => {
    currentImageIndex.value = (currentImageIndex.value + 1) % props.vehicle.images.length
}

const prevImage = () => {
    currentImageIndex.value = currentImageIndex.value === 0 
        ? props.vehicle.images.length - 1 
        : currentImageIndex.value - 1
}
</script>

<template>
    <Head :title="`${vehicle.year} ${vehicle.brand} ${vehicle.model}`" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Back button -->
                <div class="mb-6">
                    <Link 
                        :href="route('vehicles.index')" 
                        class="text-blue-600 hover:text-blue-800 flex items-center"
                    >
                        ← Retour à la recherche
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Images Section -->
                    <div class="lg:col-span-2">
                        <Card class="overflow-hidden">
                            <div class="aspect-video relative bg-gray-100">
                                <img 
                                    v-if="vehicle.images.length > 0"
                                    :src="getImageUrl(vehicle.images[currentImageIndex].image_path)"
                                    :alt="`${vehicle.brand} ${vehicle.model}`"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
                                    <span class="text-gray-500">Aucune image disponible</span>
                                </div>

                                <!-- Navigation arrows -->
                                <button 
                                    v-if="vehicle.images.length > 1"
                                    @click="prevImage"
                                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75"
                                >
                                    ←
                                </button>
                                <button 
                                    v-if="vehicle.images.length > 1"
                                    @click="nextImage"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75"
                                >
                                    →
                                </button>

                                <!-- Image counter -->
                                <div 
                                    v-if="vehicle.images.length > 1"
                                    class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm"
                                >
                                    {{ currentImageIndex + 1 }} / {{ vehicle.images.length }}
                                </div>
                            </div>

                            <!-- Thumbnail images -->
                            <div v-if="vehicle.images.length > 1" class="p-4">
                                <div class="flex gap-2 overflow-x-auto">
                                    <button
                                        v-for="(image, index) in vehicle.images"
                                        :key="image.id"
                                        @click="currentImageIndex = index"
                                        class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2"
                                        :class="{
                                            'border-blue-500': index === currentImageIndex,
                                            'border-gray-200': index !== currentImageIndex
                                        }"
                                    >
                                        <img 
                                            :src="getImageUrl(image.image_path)"
                                            :alt="`Vue ${index + 1}`"
                                            class="w-full h-full object-cover"
                                        />
                                    </button>
                                </div>
                            </div>
                        </Card>

                        <!-- Description -->
                        <Card v-if="vehicle.description" class="mt-6">
                            <CardHeader>
                                <CardTitle>Description</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-gray-700 whitespace-pre-line">{{ vehicle.description }}</p>
                            </CardContent>
                        </Card>

                        <!-- Features -->
                        <Card v-if="vehicle.features && vehicle.features.length > 0" class="mt-6">
                            <CardHeader>
                                <CardTitle>Équipements</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-wrap gap-2">
                                    <Badge 
                                        v-for="feature in vehicle.features" 
                                        :key="feature"
                                        variant="secondary"
                                    >
                                        {{ feature }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Reviews -->
                        <Card v-if="vehicle.reviews.length > 0" class="mt-6">
                            <CardHeader>
                                <CardTitle>Avis des locataires</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div 
                                        v-for="review in vehicle.reviews.slice(0, 3)" 
                                        :key="review.id"
                                        class="border-b pb-4 last:border-b-0"
                                    >
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="font-medium">{{ review.reviewer.name }}</div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <span class="mr-1">⭐</span>
                                                {{ review.rating }}/5
                                            </div>
                                        </div>
                                        <p class="text-gray-700 text-sm mb-1">{{ review.comment }}</p>
                                        <div class="text-xs text-gray-500">{{ formatDate(review.created_at) }}</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Booking Section -->
                    <div class="lg:col-span-1">
                        <Card class="sticky top-6">
                            <CardHeader>
                                <CardTitle class="text-2xl">
                                    {{ vehicle.year }} {{ vehicle.brand }} {{ vehicle.model }}
                                </CardTitle>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <span class="mr-1">⭐</span>
                                        {{ vehicle.rating || 'N/A' }}
                                        <span v-if="vehicle.rating_count" class="ml-1">
                                            ({{ vehicle.rating_count }} avis)
                                        </span>
                                    </div>
                                    <div>{{ vehicle.rental_count }} locations</div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Pricing -->
                                <div class="space-y-2">
                                    <div class="text-3xl font-bold text-green-600">
                                        {{ formatPrice(vehicle.daily_rate) }}
                                        <span class="text-lg font-normal text-gray-600">/jour</span>
                                    </div>
                                    <div v-if="vehicle.weekly_rate" class="text-sm text-gray-600">
                                        {{ formatPrice(vehicle.weekly_rate) }}/semaine
                                    </div>
                                    <div v-if="vehicle.monthly_rate" class="text-sm text-gray-600">
                                        {{ formatPrice(vehicle.monthly_rate) }}/mois
                                    </div>
                                </div>

                                <Separator />

                                <!-- Vehicle specs -->
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium">Carburant:</span><br>
                                        {{ fuelTypeLabels[vehicle.fuel_type] }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Transmission:</span><br>
                                        {{ transmissionLabels[vehicle.transmission] }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Places:</span><br>
                                        {{ vehicle.seats }} personnes
                                    </div>
                                    <div>
                                        <span class="font-medium">Portes:</span><br>
                                        {{ vehicle.doors }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Kilométrage:</span><br>
                                        {{ vehicle.mileage.toLocaleString() }} km
                                    </div>
                                    <div>
                                        <span class="font-medium">Couleur:</span><br>
                                        {{ vehicle.color }}
                                    </div>
                                </div>

                                <Separator />

                                <!-- Location -->
                                <div>
                                    <span class="font-medium">Localisation:</span><br>
                                    <span class="text-sm text-gray-600">
                                        {{ vehicle.address }}<br>
                                        {{ vehicle.postal_code }} {{ vehicle.city }}
                                    </span>
                                </div>

                                <Separator />

                                <!-- Owner info -->
                                <div>
                                    <span class="font-medium">Propriétaire:</span><br>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm">{{ vehicle.owner.name }}</span>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <span class="mr-1">⭐</span>
                                            {{ vehicle.owner.rating || 'N/A' }}
                                            <span v-if="vehicle.owner.rating_count" class="ml-1">
                                                ({{ vehicle.owner.rating_count }})
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Action buttons -->
                                <div class="space-y-3">
                                    <Link
                                        v-if="canRent"
                                        :href="route('rentals.create', vehicle.id)"
                                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 text-center block font-medium"
                                    >
                                        Réserver ce véhicule
                                    </Link>
                                    
                                    <Link
                                        v-else-if="!$page.props.auth.user"
                                        :href="route('login')"
                                        class="w-full bg-gray-600 text-white py-3 px-4 rounded-md hover:bg-gray-700 text-center block font-medium"
                                    >
                                        Se connecter pour réserver
                                    </Link>

                                    <div v-else class="text-center text-gray-600 text-sm">
                                        Vous ne pouvez pas réserver votre propre véhicule
                                    </div>

                                    <!-- Edit button for owner -->
                                    <Link
                                        v-if="$page.props.auth.user && $page.props.auth.user.id === vehicle.owner.id"
                                        :href="route('vehicles.edit', vehicle.id)"
                                        class="w-full border border-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-50 text-center block"
                                    >
                                        Modifier ce véhicule
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>