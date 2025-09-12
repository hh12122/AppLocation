import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useGeolocation } from './useGeolocation'

export interface GeoNotification {
    id: number
    type: 'nearby_rental' | 'pickup_reminder' | 'area_alert' | 'promotional' | 'new_listing' | 'price_drop'
    title: string
    message: string
    location: {
        latitude: number
        longitude: number
        name?: string
    }
    data?: Record<string, any>
    action_url?: string
    created_at: string
    distance?: number
    is_read: boolean
    is_clicked: boolean
}

export interface LocationPermissionState {
    granted: boolean
    denied: boolean
    prompt: boolean
}

export interface NotificationPermissionState {
    granted: boolean
    denied: boolean
    default: boolean
}

export function useGeoNotifications() {
    const isLocationEnabled = ref(false)
    const isNotificationEnabled = ref(false)
    const isProcessing = ref(false)
    const error = ref<string | null>(null)
    const nearbyNotifications = ref<GeoNotification[]>([])
    const unreadCount = ref(0)
    
    const locationPermission = ref<LocationPermissionState>({
        granted: false,
        denied: false,
        prompt: false
    })
    
    const notificationPermission = ref<NotificationPermissionState>({
        granted: false,
        denied: false,
        default: false
    })
    
    const { getCurrentPosition, watchPosition, stopWatching } = useGeolocation()
    let watchId: number | null = null
    let lastLocationUpdate = 0
    
    // Check current permission states
    const checkPermissions = async () => {
        // Check geolocation permission
        if ('permissions' in navigator) {
            try {
                const geoPermission = await navigator.permissions.query({ name: 'geolocation' })
                updateLocationPermission(geoPermission.state)
                
                geoPermission.addEventListener('change', () => {
                    updateLocationPermission(geoPermission.state)
                })
            } catch (e) {
                console.warn('Unable to query geolocation permission')
            }
        }
        
        // Check notification permission
        if ('Notification' in window) {
            updateNotificationPermission(Notification.permission)
        }
        
        updateEnabledStates()
    }
    
    const updateLocationPermission = (state: PermissionState) => {
        locationPermission.value = {
            granted: state === 'granted',
            denied: state === 'denied',
            prompt: state === 'prompt'
        }
    }
    
    const updateNotificationPermission = (state: NotificationPermission) => {
        notificationPermission.value = {
            granted: state === 'granted',
            denied: state === 'denied',
            default: state === 'default'
        }
    }
    
    const updateEnabledStates = () => {
        isLocationEnabled.value = locationPermission.value.granted
        isNotificationEnabled.value = notificationPermission.value.granted
    }
    
    // Request location permission
    const requestLocationPermission = async (): Promise<boolean> => {
        if (!('geolocation' in navigator)) {
            error.value = 'Geolocation is not supported by this browser'
            return false
        }
        
        isProcessing.value = true
        error.value = null
        
        try {
            const position = await getCurrentPosition({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes
            })
            
            updateLocationPermission('granted')
            updateEnabledStates()
            
            // Update user location on server
            await updateServerLocation(position.coords)
            
            isProcessing.value = false
            return true
        } catch (err) {
            console.error('Geolocation error:', err)
            
            if (err instanceof GeolocationPositionError) {
                switch (err.code) {
                    case err.PERMISSION_DENIED:
                        updateLocationPermission('denied')
                        error.value = 'Location access denied. Please enable location access in your browser settings.'
                        break
                    case err.POSITION_UNAVAILABLE:
                        error.value = 'Location information unavailable. Please check your GPS settings.'
                        break
                    case err.TIMEOUT:
                        error.value = 'Location request timed out. Please try again.'
                        break
                    default:
                        error.value = 'An unknown error occurred while retrieving location.'
                }
            } else {
                error.value = 'Failed to get location permission.'
            }
            
            updateEnabledStates()
            isProcessing.value = false
            return false
        }
    }
    
    // Request notification permission
    const requestNotificationPermission = async (): Promise<boolean> => {
        if (!('Notification' in window)) {
            error.value = 'Push notifications are not supported by this browser'
            return false
        }
        
        if (notificationPermission.value.granted) {
            return true
        }
        
        try {
            const permission = await Notification.requestPermission()
            updateNotificationPermission(permission)
            updateEnabledStates()
            
            return permission === 'granted'
        } catch (err) {
            console.error('Notification permission error:', err)
            error.value = 'Failed to request notification permission'
            return false
        }
    }
    
    // Request both permissions
    const requestAllPermissions = async (): Promise<boolean> => {
        const locationGranted = await requestLocationPermission()
        const notificationGranted = await requestNotificationPermission()
        
        return locationGranted && notificationGranted
    }
    
    // Update user location on server
    const updateServerLocation = async (coords: GeolocationCoordinates) => {
        try {
            const response = await fetch('/api/geo-notifications/location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                    accuracy: coords.accuracy,
                })
            })
            
            if (!response.ok) {
                throw new Error('Failed to update location')
            }
            
            const data = await response.json()
            console.log('Location updated:', data)
            
            // Update last update time
            lastLocationUpdate = Date.now()
            
        } catch (err) {
            console.error('Failed to update server location:', err)
        }
    }
    
    // Start location tracking
    const startLocationTracking = () => {
        if (!locationPermission.value.granted || watchId) {
            return
        }
        
        watchId = watchPosition(
            (position) => {
                const now = Date.now()
                const timeSinceLastUpdate = now - lastLocationUpdate
                
                // Only update server if significant time has passed (5 minutes)
                // or if the user has moved significantly (>100 meters)
                if (timeSinceLastUpdate > 300000) { // 5 minutes
                    updateServerLocation(position.coords)
                }
            },
            (error) => {
                console.error('Location tracking error:', error)
                // Don't stop tracking on errors, just log them
            },
            {
                enableHighAccuracy: false, // Use less battery for tracking
                timeout: 30000, // 30 seconds
                maximumAge: 600000 // 10 minutes
            }
        )
    }
    
    // Stop location tracking
    const stopLocationTracking = () => {
        if (watchId) {
            stopWatching(watchId)
            watchId = null
        }
    }
    
    // Get nearby notifications
    const getNearbyNotifications = async (limit = 20) => {
        try {
            const response = await fetch(`/api/geo-notifications/nearby?limit=${limit}`, {
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                }
            })
            
            if (!response.ok) {
                throw new Error('Failed to fetch notifications')
            }
            
            const data = await response.json()
            nearbyNotifications.value = data.notifications
            unreadCount.value = data.notifications.filter((n: GeoNotification) => !n.is_read).length
            
        } catch (err) {
            console.error('Failed to fetch nearby notifications:', err)
        }
    }
    
    // Mark notification as read
    const markAsRead = async (notificationId: number) => {
        try {
            const response = await fetch(`/api/geo-notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                }
            })
            
            if (response.ok) {
                // Update local state
                const notification = nearbyNotifications.value.find(n => n.id === notificationId)
                if (notification && !notification.is_read) {
                    notification.is_read = true
                    unreadCount.value = Math.max(0, unreadCount.value - 1)
                }
            }
        } catch (err) {
            console.error('Failed to mark notification as read:', err)
        }
    }
    
    // Mark notification as clicked
    const markAsClicked = async (notificationId: number) => {
        try {
            const response = await fetch(`/api/geo-notifications/${notificationId}/clicked`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                }
            })
            
            if (response.ok) {
                // Update local state
                const notification = nearbyNotifications.value.find(n => n.id === notificationId)
                if (notification) {
                    notification.is_clicked = true
                    if (!notification.is_read) {
                        notification.is_read = true
                        unreadCount.value = Math.max(0, unreadCount.value - 1)
                    }
                }
            }
        } catch (err) {
            console.error('Failed to mark notification as clicked:', err)
        }
    }
    
    // Handle notification click
    const handleNotificationClick = async (notification: GeoNotification) => {
        await markAsClicked(notification.id)
        
        if (notification.action_url) {
            // Use Inertia.js router if it's an internal route
            if (notification.action_url.startsWith('/')) {
                router.visit(notification.action_url)
            } else {
                window.open(notification.action_url, '_blank')
            }
        }
    }
    
    // Setup browser notification listeners
    const setupBrowserNotifications = () => {
        if (!('serviceWorker' in navigator) || !('Notification' in window)) {
            return
        }
        
        // Listen for browser notification events
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'geo-notification') {
                const notification: GeoNotification = event.data.notification
                
                // Add to nearby notifications
                nearbyNotifications.value.unshift(notification)
                if (!notification.is_read) {
                    unreadCount.value++
                }
                
                // Show browser notification if permission granted
                if (notificationPermission.value.granted) {
                    showBrowserNotification(notification)
                }
            }
        })
    }
    
    // Show browser notification
    const showBrowserNotification = (notification: GeoNotification) => {
        if (!notificationPermission.value.granted) {
            return
        }
        
        const browserNotification = new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: `geo-${notification.id}`,
            requireInteraction: false,
            data: {
                notificationId: notification.id,
                actionUrl: notification.action_url,
            }
        })
        
        browserNotification.onclick = () => {
            handleNotificationClick(notification)
            browserNotification.close()
        }
        
        // Auto close after 10 seconds
        setTimeout(() => {
            browserNotification.close()
        }, 10000)
    }
    
    // Clear error
    const clearError = () => {
        error.value = null
    }
    
    // Reset permissions (for testing)
    const resetPermissions = () => {
        stopLocationTracking()
        isLocationEnabled.value = false
        isNotificationEnabled.value = false
        locationPermission.value = { granted: false, denied: false, prompt: false }
        notificationPermission.value = { granted: false, denied: false, default: false }
    }
    
    onMounted(() => {
        checkPermissions()
        setupBrowserNotifications()
        
        // Start location tracking if already permitted
        if (locationPermission.value.granted) {
            startLocationTracking()
            getNearbyNotifications()
        }
    })
    
    onUnmounted(() => {
        stopLocationTracking()
    })
    
    return {
        // State
        isLocationEnabled,
        isNotificationEnabled,
        isProcessing,
        error,
        locationPermission,
        notificationPermission,
        nearbyNotifications,
        unreadCount,
        
        // Methods
        requestLocationPermission,
        requestNotificationPermission,
        requestAllPermissions,
        startLocationTracking,
        stopLocationTracking,
        getNearbyNotifications,
        markAsRead,
        markAsClicked,
        handleNotificationClick,
        clearError,
        resetPermissions,
    }
}