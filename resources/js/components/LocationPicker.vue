<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import InteractiveMap from './InteractiveMap.vue'
import { useGeolocation } from '@/composables/useGeolocation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { MapPin, Loader2, Navigation } from 'lucide-vue-next'

interface Props {
    modelValue?: {
        latitude?: number
        longitude?: number
        address?: string
    }
    label?: string
    placeholder?: string
    required?: boolean
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Localisation',
    placeholder: 'Entrez une adresse ou cliquez sur la carte',
    required: false,
    disabled: false
})

const emit = defineEmits<{
    'update:modelValue': [value: { latitude: number; longitude: number; address: string }]
}>()

const { getCurrentPosition, geocodeAddress, reverseGeocode, isLoading } = useGeolocation()

const addressInput = ref('')
const isSearching = ref(false)
const searchResults = ref<Array<{ display_name: string; lat: number; lon: number }>>([])
const showResults = ref(false)
const mapCenter = ref({
    latitude: props.modelValue?.latitude || 48.8566,
    longitude: props.modelValue?.longitude || 2.3522
})
const selectedLocation = ref(props.modelValue || null)

const mapMarkers = computed(() => {
    if (!selectedLocation.value?.latitude || !selectedLocation.value?.longitude) {
        return []
    }
    
    return [{
        id: 'selected',
        latitude: selectedLocation.value.latitude,
        longitude: selectedLocation.value.longitude,
        title: 'Position sélectionnée',
        description: selectedLocation.value.address || 'Localisation du véhicule'
    }]
})

const searchAddress = async () => {
    if (!addressInput.value.trim() || addressInput.value.length < 3) return

    isSearching.value = true
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressInput.value)}&limit=5&countrycodes=FR`
        )
        const results = await response.json()
        
        searchResults.value = results.map((result: any) => ({
            display_name: result.display_name,
            lat: parseFloat(result.lat),
            lon: parseFloat(result.lon)
        }))
        showResults.value = results.length > 0
    } catch (error) {
        console.error('Search error:', error)
        searchResults.value = []
        showResults.value = false
    } finally {
        isSearching.value = false
    }
}

const selectSearchResult = async (result: { display_name: string; lat: number; lon: number }) => {
    const location = {
        latitude: result.lat,
        longitude: result.lon,
        address: result.display_name
    }
    
    selectedLocation.value = location
    addressInput.value = result.display_name
    mapCenter.value = { latitude: result.lat, longitude: result.lon }
    showResults.value = false
    
    emit('update:modelValue', location)
}

const onMapClick = async (event: { latitude: number; longitude: number }) => {
    if (props.disabled) return

    try {
        const address = await reverseGeocode(event.latitude, event.longitude)
        const location = {
            latitude: event.latitude,
            longitude: event.longitude,
            address
        }
        
        selectedLocation.value = location
        addressInput.value = address
        
        emit('update:modelValue', location)
    } catch (error) {
        console.error('Reverse geocoding error:', error)
        const location = {
            latitude: event.latitude,
            longitude: event.longitude,
            address: `${event.latitude.toFixed(6)}, ${event.longitude.toFixed(6)}`
        }
        
        selectedLocation.value = location
        addressInput.value = location.address
        
        emit('update:modelValue', location)
    }
}

const getCurrentLocationAndSelect = async () => {
    if (props.disabled) return

    try {
        const position = await getCurrentPosition()
        const address = await reverseGeocode(position.latitude, position.longitude)
        
        const location = {
            latitude: position.latitude,
            longitude: position.longitude,
            address
        }
        
        selectedLocation.value = location
        addressInput.value = address
        mapCenter.value = { latitude: position.latitude, longitude: position.longitude }
        
        emit('update:modelValue', location)
    } catch (error) {
        console.error('Geolocation error:', error)
        alert('Impossible d\'obtenir votre position. Veuillez vérifier les permissions de géolocalisation.')
    }
}

const clearLocation = () => {
    if (props.disabled) return
    
    selectedLocation.value = null
    addressInput.value = ''
    showResults.value = false
    
    emit('update:modelValue', { latitude: 0, longitude: 0, address: '' })
}

// Watch for input changes to trigger search
let searchTimeout: NodeJS.Timeout
watch(addressInput, (newValue) => {
    clearTimeout(searchTimeout)
    if (newValue.length >= 3) {
        searchTimeout = setTimeout(searchAddress, 300)
    } else {
        searchResults.value = []
        showResults.value = false
    }
})

// Initialize with existing value
if (props.modelValue?.address) {
    addressInput.value = props.modelValue.address
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <Label :for="`location-input`" class="block text-sm font-medium text-gray-700 mb-2">
                {{ label }}
                <span v-if="required" class="text-red-500 ml-1">*</span>
            </Label>
            
            <!-- Address Input -->
            <div class="relative">
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <Input
                            id="location-input"
                            v-model="addressInput"
                            :placeholder="placeholder"
                            :disabled="disabled"
                            class="pr-10"
                        />
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <Loader2 v-if="isSearching" class="w-4 h-4 animate-spin text-gray-400" />
                            <MapPin v-else class="w-4 h-4 text-gray-400" />
                        </div>
                        
                        <!-- Search Results Dropdown -->
                        <div
                            v-if="showResults && searchResults.length > 0"
                            class="absolute top-full left-0 right-0 z-50 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto mt-1"
                        >
                            <button
                                v-for="(result, index) in searchResults"
                                :key="index"
                                @click="selectSearchResult(result)"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 text-sm"
                            >
                                <div class="flex items-start gap-2">
                                    <MapPin class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
                                    <span class="line-clamp-2">{{ result.display_name }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Current Location Button -->
                    <Button
                        @click="getCurrentLocationAndSelect"
                        :disabled="disabled || isLoading"
                        variant="outline"
                        size="default"
                        class="px-3"
                        title="Utiliser ma position actuelle"
                    >
                        <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
                        <Navigation v-else class="w-4 h-4" />
                    </Button>
                    
                    <!-- Clear Button -->
                    <Button
                        v-if="selectedLocation"
                        @click="clearLocation"
                        :disabled="disabled"
                        variant="outline"
                        size="default"
                        class="px-3"
                        title="Effacer la localisation"
                    >
                        ✕
                    </Button>
                </div>
            </div>
        </div>
        
        <!-- Map -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <InteractiveMap
                :center="mapCenter"
                :markers="mapMarkers"
                :clickable="!disabled"
                :height="'300px'"
                @map-click="onMapClick"
            />
        </div>
        
        <!-- Selected Location Info -->
        <div v-if="selectedLocation?.latitude && selectedLocation?.longitude" class="p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-2">
                <MapPin class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800 mb-1">Position sélectionnée</p>
                    <p class="text-xs text-green-700 mb-2">{{ selectedLocation.address }}</p>
                    <p class="text-xs text-green-600">
                        Latitude: {{ selectedLocation.latitude.toFixed(6) }}, 
                        Longitude: {{ selectedLocation.longitude.toFixed(6) }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <p class="text-xs text-gray-500">
            Tapez une adresse dans le champ de recherche, utilisez le bouton de géolocalisation, ou cliquez directement sur la carte pour sélectionner une position.
        </p>
    </div>
</template>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>