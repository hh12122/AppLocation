<script setup lang="ts">
import { ref, computed } from 'vue'
import { useNavigation } from '@/composables/useNavigation'
import { useGeolocation } from '@/composables/useGeolocation'
import { 
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Navigation, MapPin, Loader2, X } from 'lucide-vue-next'

interface Props {
    open: boolean
    destination: {
        latitude: number
        longitude: number
        address?: string
        name?: string
    }
    vehicleInfo?: {
        brand: string
        model: string
        year: number
    }
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const { navigationServices, navigateTo, isMobile, getRecommendedService, estimateTravelTime } = useNavigation()
const { getCurrentPosition, calculateDistance } = useGeolocation()

const userLocation = ref<{ latitude: number; longitude: number } | null>(null)
const loadingLocation = ref(false)
const distance = ref<number | null>(null)

// Compute display name for destination
const destinationName = computed(() => {
    if (props.vehicleInfo) {
        return `${props.vehicleInfo.year} ${props.vehicleInfo.brand} ${props.vehicleInfo.model}`
    }
    return props.destination.name || 'Destination'
})

// Get user's current location
const getUserLocation = async () => {
    loadingLocation.value = true
    try {
        const position = await getCurrentPosition()
        userLocation.value = {
            latitude: position.latitude,
            longitude: position.longitude
        }
        
        // Calculate distance
        if (props.destination) {
            distance.value = calculateDistance(
                position.latitude,
                position.longitude,
                props.destination.latitude,
                props.destination.longitude
            )
        }
    } catch (error) {
        console.error('Erreur de géolocalisation:', error)
        alert('Impossible d\'obtenir votre position actuelle')
    } finally {
        loadingLocation.value = false
    }
}

// Handle navigation service selection
const handleNavigate = (service: typeof navigationServices[0]) => {
    const destination = {
        ...props.destination,
        name: destinationName.value
    }
    
    navigateTo(service, destination, userLocation.value || undefined)
    
    // Close modal after navigation (except for copy coordinates)
    if (service.name !== 'Copier les coordonnées') {
        emit('update:open', false)
    }
}

// Get estimated travel time
const travelTime = computed(() => {
    if (distance.value) {
        return estimateTravelTime(distance.value)
    }
    return null
})

// Filter services based on platform
const availableServices = computed(() => {
    const mobile = isMobile()
    
    // Show all services on desktop, filter on mobile
    if (!mobile) {
        return navigationServices
    }
    
    // On mobile, prioritize native apps
    const userAgent = navigator.userAgent.toLowerCase()
    if (userAgent.includes('iphone') || userAgent.includes('ipad')) {
        // iOS: Show Apple Maps, Google Maps, Waze
        return navigationServices.filter(s => 
            ['Apple Plans', 'Google Maps', 'Waze', 'Copier les coordonnées'].includes(s.name)
        )
    } else if (userAgent.includes('android')) {
        // Android: Show Google Maps, Waze
        return navigationServices.filter(s => 
            ['Google Maps', 'Waze', 'OpenStreetMap', 'Copier les coordonnées'].includes(s.name)
        )
    }
    
    return navigationServices
})

// Auto-detect location when modal opens
const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value)
})

// Get user location when modal opens
const onOpenChange = (open: boolean) => {
    isOpen.value = open
    if (open && !userLocation.value) {
        getUserLocation()
    }
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Navigation class="w-5 h-5" />
                    Obtenir l'itinéraire
                </DialogTitle>
                <DialogDescription>
                    Choisissez votre application de navigation préférée
                </DialogDescription>
            </DialogHeader>
            
            <div class="space-y-4">
                <!-- Destination info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <MapPin class="w-5 h-5 text-gray-500 mt-0.5" />
                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ destinationName }}</p>
                            <p v-if="destination.address" class="text-xs text-gray-600 mt-1">
                                {{ destination.address }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ destination.latitude.toFixed(6) }}, {{ destination.longitude.toFixed(6) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Distance and travel time -->
                    <div v-if="distance || loadingLocation" class="mt-3 pt-3 border-t">
                        <div v-if="loadingLocation" class="flex items-center gap-2 text-sm text-gray-600">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            Calcul de la distance...
                        </div>
                        <div v-else-if="distance" class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Distance: {{ distance.toFixed(1) }} km</span>
                            <span v-if="travelTime" class="font-medium">{{ travelTime }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- User location status -->
                <div v-if="!userLocation && !loadingLocation" class="text-center">
                    <Button
                        @click="getUserLocation"
                        variant="outline"
                        size="sm"
                        class="text-xs"
                    >
                        <MapPin class="w-3 h-3 mr-1" />
                        Utiliser ma position actuelle
                    </Button>
                    <p class="text-xs text-gray-500 mt-2">
                        Pour obtenir des directions depuis votre position
                    </p>
                </div>
                
                <!-- Navigation services -->
                <div class="grid gap-2">
                    <button
                        v-for="service in availableServices"
                        :key="service.name"
                        @click="handleNavigate(service)"
                        class="flex items-center gap-3 w-full p-3 rounded-lg border hover:bg-gray-50 transition-colors text-left"
                        :style="{ borderColor: service.color + '40' }"
                    >
                        <span class="text-2xl">{{ service.icon }}</span>
                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ service.name }}</p>
                            <p v-if="service.name === 'Copier les coordonnées'" class="text-xs text-gray-500">
                                Copier les coordonnées GPS
                            </p>
                            <p v-else-if="getRecommendedService().name === service.name" class="text-xs text-green-600">
                                Recommandé pour votre appareil
                            </p>
                        </div>
                    </button>
                </div>
                
                <!-- Share link -->
                <div class="pt-3 border-t">
                    <p class="text-xs text-gray-500 text-center">
                        💡 Astuce: Les liens s'ouvrent dans l'application si elle est installée
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>