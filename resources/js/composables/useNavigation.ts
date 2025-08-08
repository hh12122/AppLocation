export interface NavigationDestination {
    latitude: number
    longitude: number
    address?: string
    name?: string
}

export interface NavigationService {
    name: string
    icon: string
    color: string
    getUrl: (destination: NavigationDestination, origin?: NavigationDestination) => string
}

export function useNavigation() {
    // Navigation services configuration
    const navigationServices: NavigationService[] = [
        {
            name: 'Google Maps',
            icon: '🗺️',
            color: '#4285F4',
            getUrl: (destination: NavigationDestination, origin?: NavigationDestination) => {
                // Google Maps URL scheme
                let url = `https://www.google.com/maps/dir/?api=1`
                
                if (origin) {
                    url += `&origin=${origin.latitude},${origin.longitude}`
                }
                
                url += `&destination=${destination.latitude},${destination.longitude}`
                
                if (destination.name) {
                    url += `&destination_place_id=${encodeURIComponent(destination.name)}`
                }
                
                // Add travel mode (driving by default)
                url += '&travelmode=driving'
                
                return url
            }
        },
        {
            name: 'Waze',
            icon: '🚗',
            color: '#32CCFE',
            getUrl: (destination: NavigationDestination) => {
                // Waze URL scheme
                return `https://waze.com/ul?ll=${destination.latitude},${destination.longitude}&navigate=yes&z=10`
            }
        },
        {
            name: 'Apple Plans',
            icon: '🍎',
            color: '#000000',
            getUrl: (destination: NavigationDestination, origin?: NavigationDestination) => {
                // Apple Maps URL scheme (works on iOS/macOS)
                let url = `https://maps.apple.com/?`
                
                if (origin) {
                    url += `saddr=${origin.latitude},${origin.longitude}&`
                }
                
                url += `daddr=${destination.latitude},${destination.longitude}`
                
                if (destination.name) {
                    url += `&q=${encodeURIComponent(destination.name)}`
                }
                
                return url
            }
        },
        {
            name: 'OpenStreetMap',
            icon: '🌍',
            color: '#7EBC6F',
            getUrl: (destination: NavigationDestination, origin?: NavigationDestination) => {
                // OpenStreetMap directions
                if (origin) {
                    return `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${origin.latitude},${origin.longitude};${destination.latitude},${destination.longitude}`
                }
                return `https://www.openstreetmap.org/?mlat=${destination.latitude}&mlon=${destination.longitude}#map=15/${destination.latitude}/${destination.longitude}`
            }
        },
        {
            name: 'Copier les coordonnées',
            icon: '📋',
            color: '#6B7280',
            getUrl: (destination: NavigationDestination) => {
                // Special case: copy coordinates to clipboard
                return `${destination.latitude},${destination.longitude}`
            }
        }
    ]

    // Open navigation in new tab/app
    const navigateTo = (
        service: NavigationService, 
        destination: NavigationDestination, 
        origin?: NavigationDestination
    ) => {
        const url = service.getUrl(destination, origin)
        
        if (service.name === 'Copier les coordonnées') {
            // Copy to clipboard instead of navigating
            copyToClipboard(url)
            return
        }
        
        // Open in new tab/window
        window.open(url, '_blank', 'noopener,noreferrer')
    }

    // Copy text to clipboard
    const copyToClipboard = async (text: string) => {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text)
                alert('Coordonnées copiées dans le presse-papiers!')
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea')
                textArea.value = text
                textArea.style.position = 'fixed'
                textArea.style.left = '-999999px'
                document.body.appendChild(textArea)
                textArea.focus()
                textArea.select()
                document.execCommand('copy')
                document.body.removeChild(textArea)
                alert('Coordonnées copiées dans le presse-papiers!')
            }
        } catch (error) {
            console.error('Erreur lors de la copie:', error)
            alert('Impossible de copier les coordonnées')
        }
    }

    // Detect if user is on mobile
    const isMobile = () => {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
            navigator.userAgent
        )
    }

    // Get recommended navigation service based on platform
    const getRecommendedService = (): NavigationService => {
        const userAgent = navigator.userAgent.toLowerCase()
        
        if (userAgent.includes('iphone') || userAgent.includes('ipad') || userAgent.includes('mac')) {
            // iOS/macOS - recommend Apple Maps
            return navigationServices.find(s => s.name === 'Apple Plans') || navigationServices[0]
        } else if (userAgent.includes('android')) {
            // Android - recommend Google Maps
            return navigationServices[0]
        }
        
        // Default to Google Maps
        return navigationServices[0]
    }

    // Generate a shareable directions link
    const getShareableLink = (destination: NavigationDestination): string => {
        // Use Google Maps as it's most universal
        return navigationServices[0].getUrl(destination)
    }

    // Calculate estimated travel time (rough estimate)
    const estimateTravelTime = (distanceKm: number, mode: 'driving' | 'walking' | 'cycling' = 'driving'): string => {
        let speedKmh: number
        
        switch (mode) {
            case 'walking':
                speedKmh = 5
                break
            case 'cycling':
                speedKmh = 15
                break
            case 'driving':
            default:
                speedKmh = 50 // Average city driving speed
                break
        }
        
        const hours = distanceKm / speedKmh
        const minutes = Math.round(hours * 60)
        
        if (minutes < 60) {
            return `~${minutes} min`
        } else {
            const h = Math.floor(minutes / 60)
            const m = minutes % 60
            return m > 0 ? `~${h}h ${m}min` : `~${h}h`
        }
    }

    return {
        navigationServices,
        navigateTo,
        copyToClipboard,
        isMobile,
        getRecommendedService,
        getShareableLink,
        estimateTravelTime
    }
}