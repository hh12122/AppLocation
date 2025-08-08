<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet default marker icons
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
})

export interface MapMarker {
    id: string | number
    latitude: number
    longitude: number
    title?: string
    description?: string
    icon?: string
    popup?: string
}

export interface MapCenter {
    latitude: number
    longitude: number
}

interface Props {
    center?: MapCenter
    zoom?: number
    height?: string
    markers?: MapMarker[]
    showCurrentLocation?: boolean
    clickable?: boolean
    draggable?: boolean
    scrollWheelZoom?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    center: () => ({ latitude: 48.8566, longitude: 2.3522 }), // Paris by default
    zoom: 13,
    height: '400px',
    markers: () => [],
    showCurrentLocation: false,
    clickable: false,
    draggable: true,
    scrollWheelZoom: true
})

const emit = defineEmits<{
    mapClick: [event: { latitude: number; longitude: number }]
    markerClick: [marker: MapMarker]
    locationFound: [location: { latitude: number; longitude: number }]
}>()

const mapContainer = ref<HTMLElement>()
const map = ref<L.Map>()
const markersLayer = ref<L.LayerGroup>()
const currentLocationMarker = ref<L.Marker>()

const initializeMap = () => {
    if (!mapContainer.value) return

    // Create map
    map.value = L.map(mapContainer.value, {
        dragging: props.draggable,
        scrollWheelZoom: props.scrollWheelZoom,
        doubleClickZoom: true,
        touchZoom: true,
        keyboard: true
    }).setView([props.center.latitude, props.center.longitude], props.zoom)

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map.value)

    // Create markers layer
    markersLayer.value = L.layerGroup().addTo(map.value)

    // Add click handler if clickable
    if (props.clickable) {
        map.value.on('click', (e: L.LeafletMouseEvent) => {
            emit('mapClick', {
                latitude: e.latlng.lat,
                longitude: e.latlng.lng
            })
        })
    }

    // Add current location if requested
    if (props.showCurrentLocation) {
        showCurrentLocation()
    }

    // Add markers
    addMarkers()
}

const addMarkers = () => {
    if (!markersLayer.value) return

    // Clear existing markers
    markersLayer.value.clearLayers()

    // Add new markers
    props.markers.forEach(marker => {
        const leafletMarker = L.marker([marker.latitude, marker.longitude])
        
        if (marker.popup || marker.title || marker.description) {
            const popupContent = marker.popup || `
                <div>
                    ${marker.title ? `<h3 class="font-semibold text-lg mb-1">${marker.title}</h3>` : ''}
                    ${marker.description ? `<p class="text-sm text-gray-600">${marker.description}</p>` : ''}
                </div>
            `
            leafletMarker.bindPopup(popupContent)
        }

        leafletMarker.on('click', () => {
            emit('markerClick', marker)
        })

        markersLayer.value?.addLayer(leafletMarker)
    })

    // Fit map to markers if there are any
    if (props.markers.length > 0) {
        const group = new L.featureGroup(markersLayer.value.getLayers())
        map.value?.fitBounds(group.getBounds().pad(0.1))
    }
}

const showCurrentLocation = () => {
    if (!map.value) return

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude
                const lng = position.coords.longitude

                // Add current location marker
                if (currentLocationMarker.value) {
                    map.value?.removeLayer(currentLocationMarker.value)
                }

                const currentLocationIcon = L.divIcon({
                    className: 'current-location-marker',
                    html: '<div class="w-4 h-4 bg-blue-500 rounded-full border-2 border-white shadow-lg pulse"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })

                currentLocationMarker.value = L.marker([lat, lng], { icon: currentLocationIcon })
                    .addTo(map.value!)
                    .bindPopup('Votre position actuelle')

                emit('locationFound', { latitude: lat, longitude: lng })
            },
            (error) => {
                console.warn('Geolocation error:', error)
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        )
    }
}

const centerMap = (latitude: number, longitude: number, zoom?: number) => {
    if (map.value) {
        map.value.setView([latitude, longitude], zoom || props.zoom)
    }
}

const addMarker = (marker: MapMarker) => {
    if (!markersLayer.value) return

    const leafletMarker = L.marker([marker.latitude, marker.longitude])
    
    if (marker.popup || marker.title || marker.description) {
        const popupContent = marker.popup || `
            <div>
                ${marker.title ? `<h3 class="font-semibold text-lg mb-1">${marker.title}</h3>` : ''}
                ${marker.description ? `<p class="text-sm text-gray-600">${marker.description}</p>` : ''}
            </div>
        `
        leafletMarker.bindPopup(popupContent)
    }

    leafletMarker.on('click', () => {
        emit('markerClick', marker)
    })

    markersLayer.value.addLayer(leafletMarker)
}

const removeMarker = (markerId: string | number) => {
    if (!markersLayer.value) return

    markersLayer.value.eachLayer((layer: any) => {
        if (layer.options && layer.options.markerId === markerId) {
            markersLayer.value?.removeLayer(layer)
        }
    })
}

// Watch for marker changes
watch(() => props.markers, () => {
    addMarkers()
}, { deep: true })

// Watch for center changes
watch(() => props.center, (newCenter) => {
    if (newCenter && map.value) {
        map.value.setView([newCenter.latitude, newCenter.longitude], props.zoom)
    }
}, { deep: true })

onMounted(() => {
    nextTick(() => {
        initializeMap()
    })
})

// Expose methods for parent component
defineExpose({
    centerMap,
    addMarker,
    removeMarker,
    showCurrentLocation,
    map: map.value
})
</script>

<template>
    <div class="relative">
        <div
            ref="mapContainer"
            :style="{ height }"
            class="w-full rounded-lg overflow-hidden shadow-sm border border-gray-200"
        />
        
        <!-- Loading overlay -->
        <div v-if="!map" class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600">Chargement de la carte...</p>
            </div>
        </div>
    </div>
</template>

<style>
.current-location-marker .pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

/* Leaflet popup customization */
.leaflet-popup-content-wrapper {
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.leaflet-popup-content {
    margin: 12px 16px;
}

.leaflet-popup-tip {
    box-shadow: none;
}
</style>