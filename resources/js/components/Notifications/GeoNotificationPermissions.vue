<template>
    <div class="max-w-2xl mx-auto">
        <!-- Permission Status Display -->
        <div class="permission-status mb-6">
            <h3 class="text-lg font-semibold mb-4">Location-Based Notifications</h3>
            
            <div class="space-y-4">
                <!-- Location Permission -->
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 rounded-full" :class="locationStatusClass">
                            <MapPinIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-medium">Location Access</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ locationStatusText }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 rounded-full text-xs font-medium" :class="locationBadgeClass">
                            {{ locationBadgeText }}
                        </span>
                    </div>
                </div>
                
                <!-- Notification Permission -->
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 rounded-full" :class="notificationStatusClass">
                            <BellIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-medium">Push Notifications</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ notificationStatusText }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 rounded-full text-xs font-medium" :class="notificationBadgeClass">
                            {{ notificationBadgeText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Error Display -->
        <div v-if="error" class="mb-6">
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Permission Error</h4>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                    </div>
                    <button 
                        @click="clearError"
                        class="ml-auto text-red-400 hover:text-red-600"
                    >
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-3">
            <!-- Enable All Button -->
            <button
                v-if="!isFullyEnabled"
                @click="enableAllPermissions"
                :disabled="isProcessing"
                class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors duration-200"
            >
                <span v-if="isProcessing" class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Requesting Permissions...
                </span>
                <span v-else class="flex items-center justify-center">
                    <CheckCircleIcon class="h-5 w-5 mr-2" />
                    Enable Location Notifications
                </span>
            </button>
            
            <!-- Individual Permission Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <button
                    v-if="!isLocationEnabled"
                    @click="requestLocationPermission"
                    :disabled="isProcessing"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 disabled:opacity-50 rounded-lg transition-colors duration-200"
                >
                    <MapPinIcon class="h-4 w-4 inline mr-2" />
                    Location
                </button>
                
                <button
                    v-if="!isNotificationEnabled"
                    @click="requestNotificationPermission"
                    :disabled="isProcessing"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 disabled:opacity-50 rounded-lg transition-colors duration-200"
                >
                    <BellIcon class="h-4 w-4 inline mr-2" />
                    Notifications
                </button>
            </div>
            
            <!-- Success State -->
            <div v-if="isFullyEnabled" class="text-center py-4">
                <div class="flex items-center justify-center text-green-600 dark:text-green-400">
                    <CheckCircleIcon class="h-6 w-6 mr-2" />
                    <span class="font-medium">Location notifications are enabled!</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    You'll receive notifications about nearby rentals and other location-based updates.
                </p>
            </div>
        </div>
        
        <!-- Information Section -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex">
                <InformationCircleIcon class="h-5 w-5 text-blue-400 flex-shrink-0" />
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">How it works</h4>
                    <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Get notified when new vehicles become available near you</li>
                            <li>Receive reminders when you're close to pickup locations</li>
                            <li>Stay updated on price drops for your favorite vehicles</li>
                            <li>Your location is only used to send relevant notifications</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Settings Link -->
        <div class="mt-4 text-center">
            <router-link 
                :href="route('settings.notifications')"
                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                Customize notification preferences →
            </router-link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
    MapPinIcon, 
    BellIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon,
    XMarkIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'
import { useGeoNotifications } from '../../composables/useGeoNotifications'

const {
    isLocationEnabled,
    isNotificationEnabled,
    isProcessing,
    error,
    locationPermission,
    notificationPermission,
    requestLocationPermission,
    requestNotificationPermission,
    requestAllPermissions,
    clearError,
} = useGeoNotifications()

const isFullyEnabled = computed(() => 
    isLocationEnabled.value && isNotificationEnabled.value
)

const locationStatusClass = computed(() => ({
    'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400': isLocationEnabled.value,
    'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400': locationPermission.value.denied,
    'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400': locationPermission.value.prompt,
}))

const notificationStatusClass = computed(() => ({
    'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400': isNotificationEnabled.value,
    'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400': notificationPermission.value.denied,
    'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400': notificationPermission.value.default,
}))

const locationBadgeClass = computed(() => ({
    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': isLocationEnabled.value,
    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': locationPermission.value.denied,
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': locationPermission.value.prompt,
}))

const notificationBadgeClass = computed(() => ({
    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': isNotificationEnabled.value,
    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': notificationPermission.value.denied,
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': notificationPermission.value.default,
}))

const locationStatusText = computed(() => {
    if (isLocationEnabled.value) return 'Your location is being used to send relevant notifications'
    if (locationPermission.value.denied) return 'Location access was denied'
    return 'Location access is required for location-based notifications'
})

const notificationStatusText = computed(() => {
    if (isNotificationEnabled.value) return 'You will receive push notifications'
    if (notificationPermission.value.denied) return 'Push notifications were blocked'
    return 'Push notifications are needed to alert you about nearby opportunities'
})

const locationBadgeText = computed(() => {
    if (isLocationEnabled.value) return 'Enabled'
    if (locationPermission.value.denied) return 'Denied'
    return 'Not enabled'
})

const notificationBadgeText = computed(() => {
    if (isNotificationEnabled.value) return 'Enabled'
    if (notificationPermission.value.denied) return 'Denied'
    return 'Not enabled'
})

const enableAllPermissions = async () => {
    await requestAllPermissions()
}
</script>