<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import InteractiveMap from './InteractiveMap.vue'
import FavoriteButton from './FavoriteButton.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { MapPin, Car, Users, Fuel, Settings, Star, Eye, Navigation } from 'lucide-vue-next'

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    daily_rate: number
    city: string
    fuel_type: string
    transmission: string
    seats: number
    latitude?: number
    longitude?: number
    rating?: number
    reviews_count?: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
    owner: {
        id: number
        name: string
        rating?: number
    }
}

interface Props {
    vehicles: Vehicle[]
    favoritedVehicleIds?: number[]
    center?: { latitude: number; longitude: number }
    showControls?: boolean
    height?: string
}

const props = withDefaults(defineProps<Props>(), {
    favoritedVehicleIds: () => [],
    showControls: true,
    height: '500px'
})

const selectedVehicle = ref<Vehicle | null>(null)
const showVehicleCard = ref(false)

const mapCenter = computed(() => {
    if (props.center) return props.center
    
    // Calculate center based on vehicles with coordinates
    const vehiclesWithCoords = props.vehicles.filter(v => v.latitude && v.longitude)
    
    if (vehiclesWithCoords.length === 0) {
        return { latitude: 48.8566, longitude: 2.3522 } // Paris default
    }
    
    const avgLat = vehiclesWithCoords.reduce((sum, v) => sum + (v.latitude || 0), 0) / vehiclesWithCoords.length
    const avgLng = vehiclesWithCoords.reduce((sum, v) => sum + (v.longitude || 0), 0) / vehiclesWithCoords.length
    
    return { latitude: avgLat, longitude: avgLng }
})

const mapMarkers = computed(() => {
    return props.vehicles
        .filter(vehicle => vehicle.latitude && vehicle.longitude)
        .map(vehicle => ({
            id: vehicle.id,
            latitude: vehicle.latitude!,
            longitude: vehicle.longitude!,
            title: `${vehicle.year} ${vehicle.brand} ${vehicle.model}`,
            description: `${formatPrice(vehicle.daily_rate)}/jour - ${vehicle.city}`,
            popup: createVehiclePopup(vehicle)
        }))
})

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

const getPrimaryImage = (vehicle: Vehicle) => {
    const primary = vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

const createVehiclePopup = (vehicle: Vehicle) => {
    return `
        <div class="min-w-[280px]">
            <div class="aspect-video mb-3 rounded overflow-hidden">
                <img 
                    src="${getPrimaryImage(vehicle)}" 
                    alt="${vehicle.brand} ${vehicle.model}"
                    class="w-full h-full object-cover"
                />
            </div>
            <h3 class="font-semibold text-lg mb-2">${vehicle.year} ${vehicle.brand} ${vehicle.model}</h3>
            <div class="flex items-center gap-2 mb-2">
                <span class="text-green-600 font-semibold">${formatPrice(vehicle.daily_rate)}/jour</span>
                ${vehicle.rating ? `
                    <div class="flex items-center text-sm text-gray-600">
                        <span class="text-yellow-400 mr-1">★</span>
                        ${vehicle.rating.toFixed(1)} (${vehicle.reviews_count || 0})
                    </div>
                ` : ''}
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                <div>${fuelTypeLabels[vehicle.fuel_type]}</div>
                <div>${transmissionLabels[vehicle.transmission]}</div>
                <div>${vehicle.seats} places</div>
                <div>${vehicle.city}</div>
            </div>
            <div class="flex gap-2">
                <button 
                    onclick="window.viewVehicle(${vehicle.id})" 
                    class="flex-1 bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm font-medium"
                >
                    Détails
                </button>
                ${vehicle.latitude && vehicle.longitude ? `
                    <button 
                        onclick="window.openNavigation(${vehicle.latitude}, ${vehicle.longitude}, '${vehicle.brand} ${vehicle.model}')" 
                        class="flex-1 bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm font-medium"
                    >
                        🧭 Itinéraire
                    </button>
                ` : ''}
            </div>
        </div>
    `
}

const onMarkerClick = (marker: any) => {
    const vehicle = props.vehicles.find(v => v.id === marker.id)
    if (vehicle) {
        selectedVehicle.value = vehicle
        showVehicleCard.value = true
    }
}

const viewVehicle = (vehicleId: number) => {
    router.visit(`/vehicles/${vehicleId}`)
}

const closeVehicleCard = () => {
    showVehicleCard.value = false
    selectedVehicle.value = null
}

// Navigation function for popup buttons
const openNavigation = (lat: number, lng: number, name: string) => {
    // Quick navigation to Google Maps (most universal)
    const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&destination_place_id=${encodeURIComponent(name)}`
    window.open(url, '_blank', 'noopener,noreferrer')
}

// Make functions available globally for popup buttons
;(window as any).viewVehicle = viewVehicle
;(window as any).openNavigation = openNavigation
</script>

<template>
    <div class="relative">
        <!-- Map -->
        <InteractiveMap
            :center="mapCenter"
            :markers="mapMarkers"
            :height="height"
            :zoom="12"
            @marker-click="onMarkerClick"
        />
        
        <!-- Map Controls -->
        <div v-if="showControls" class="absolute top-4 left-4 z-[1000]">
            <div class="bg-white rounded-lg shadow-lg p-3 border">
                <div class="flex items-center gap-2 text-sm">
                    <Car class="w-4 h-4 text-blue-600" />
                    <span class="font-medium">{{ vehicles.length }} véhicule(s)</span>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Details Card -->
        <div
            v-if="showVehicleCard && selectedVehicle"
            class="absolute bottom-4 left-4 right-4 z-[1000] max-w-md mx-auto"
        >
            <Card class="shadow-lg border-0">
                <CardContent class="p-4">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-semibold text-lg">
                            {{ selectedVehicle.year }} {{ selectedVehicle.brand }} {{ selectedVehicle.model }}
                        </h3>
                        <button
                            @click="closeVehicleCard"
                            class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                        >
                            ×
                        </button>
                    </div>
                    
                    <div class="aspect-video mb-3 rounded overflow-hidden bg-gray-100">
                        <img 
                            :src="getPrimaryImage(selectedVehicle)" 
                            :alt="`${selectedVehicle.brand} ${selectedVehicle.model}`"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <Badge class="bg-green-500 text-white">
                                {{ formatPrice(selectedVehicle.daily_rate) }}/jour
                            </Badge>
                            <div v-if="selectedVehicle.rating" class="flex items-center text-sm text-gray-600">
                                <Star class="w-4 h-4 text-yellow-400 fill-current mr-1" />
                                {{ selectedVehicle.rating.toFixed(1) }}
                                <span class="ml-1">({{ selectedVehicle.reviews_count }})</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <FavoriteButton
                                v-if="$page.props.auth?.user"
                                :vehicle-id="selectedVehicle.id"
                                :initial-is-favorited="favoritedVehicleIds.includes(selectedVehicle.id)"
                                size="sm"
                            />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                        <div class="flex items-center gap-1">
                            <Fuel class="w-3 h-3" />
                            {{ fuelTypeLabels[selectedVehicle.fuel_type] }}
                        </div>
                        <div class="flex items-center gap-1">
                            <Settings class="w-3 h-3" />
                            {{ transmissionLabels[selectedVehicle.transmission] }}
                        </div>
                        <div class="flex items-center gap-1">
                            <Users class="w-3 h-3" />
                            {{ selectedVehicle.seats }} places
                        </div>
                        <div class="flex items-center gap-1">
                            <MapPin class="w-3 h-3" />
                            {{ selectedVehicle.city }}
                        </div>
                    </div>
                    
                    <div class="text-xs text-gray-600 mb-4">
                        Par <span class="font-medium">{{ selectedVehicle.owner.name }}</span>
                    </div>
                    
                    <div class="flex gap-2">
                        <Button
                            @click="viewVehicle(selectedVehicle.id)"
                            class="flex-1"
                            size="sm"
                        >
                            <Eye class="w-4 h-4 mr-2" />
                            Détails
                        </Button>
                        <Button
                            v-if="selectedVehicle.latitude && selectedVehicle.longitude"
                            @click="openNavigation(selectedVehicle.latitude, selectedVehicle.longitude, `${selectedVehicle.year} ${selectedVehicle.brand} ${selectedVehicle.model}`)"
                            class="flex-1"
                            size="sm"
                            variant="outline"
                        >
                            <Navigation class="w-4 h-4 mr-2" />
                            Itinéraire
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
        
        <!-- Legend -->
        <div v-if="showControls" class="absolute bottom-4 right-4 z-[1000]">
            <div class="bg-white rounded-lg shadow-lg p-3 border text-xs">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                    <span>Véhicules disponibles</span>
                </div>
                <div class="text-gray-500">
                    Cliquez sur un marqueur pour plus d'infos
                </div>
            </div>
        </div>
    </div>
</template>