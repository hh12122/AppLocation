import { ref, reactive } from 'vue'

export interface Coordinates {
    latitude: number
    longitude: number
    accuracy?: number
}

export interface GeolocationError {
    code: number
    message: string
}

export function useGeolocation() {
    const isSupported = ref(typeof navigator !== 'undefined' && 'geolocation' in navigator)
    const isLoading = ref(false)
    const error = ref<GeolocationError | null>(null)
    const coordinates = ref<Coordinates | null>(null)

    const getCurrentPosition = (options?: PositionOptions): Promise<Coordinates> => {
        return new Promise((resolve, reject) => {
            if (!isSupported.value) {
                const notSupportedError: GeolocationError = {
                    code: -1,
                    message: 'Geolocation is not supported by this browser'
                }
                error.value = notSupportedError
                reject(notSupportedError)
                return
            }

            isLoading.value = true
            error.value = null

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const coords: Coordinates = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    }
                    coordinates.value = coords
                    isLoading.value = false
                    resolve(coords)
                },
                (err) => {
                    const geolocationError: GeolocationError = {
                        code: err.code,
                        message: getErrorMessage(err.code)
                    }
                    error.value = geolocationError
                    isLoading.value = false
                    reject(geolocationError)
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000, // 5 minutes
                    ...options
                }
            )
        })
    }

    const getErrorMessage = (code: number): string => {
        switch (code) {
            case 1:
                return 'Permission denied. Please allow location access.'
            case 2:
                return 'Position unavailable. Unable to determine your location.'
            case 3:
                return 'Request timeout. Please try again.'
            default:
                return 'An unknown error occurred while getting your location.'
        }
    }

    // Calculate distance between two coordinates (Haversine formula)
    const calculateDistance = (
        lat1: number,
        lon1: number,
        lat2: number,
        lon2: number
    ): number => {
        const R = 6371 // Earth's radius in kilometers
        const dLat = ((lat2 - lat1) * Math.PI) / 180
        const dLon = ((lon2 - lon1) * Math.PI) / 180
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2)
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
        return R * c
    }

    // Geocoding functions
    const geocodeAddress = async (address: string): Promise<Coordinates[]> => {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=5`
            )
            const data = await response.json()
            
            return data.map((result: any) => ({
                latitude: parseFloat(result.lat),
                longitude: parseFloat(result.lon),
                accuracy: result.importance * 100
            }))
        } catch (error) {
            console.error('Geocoding error:', error)
            return []
        }
    }

    const reverseGeocode = async (lat: number, lon: number): Promise<string> => {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`
            )
            const data = await response.json()
            
            return data.display_name || `${lat}, ${lon}`
        } catch (error) {
            console.error('Reverse geocoding error:', error)
            return `${lat}, ${lon}`
        }
    }

    return {
        isSupported,
        isLoading,
        error,
        coordinates,
        getCurrentPosition,
        calculateDistance,
        geocodeAddress,
        reverseGeocode
    }
}