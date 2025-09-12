<template>
    <div class="geo-notifications-list">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold">Nearby Notifications</h3>
            <div class="flex items-center space-x-2">
                <button
                    @click="refreshNotifications"
                    :disabled="isLoading"
                    class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200"
                >
                    <ArrowPathIcon :class="['h-4 w-4', { 'animate-spin': isLoading }]" />
                </button>
                <span v-if="unreadCount > 0" class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 text-xs font-medium rounded-full">
                    {{ unreadCount }} new
                </span>
            </div>
        </div>
        
        <!-- No Location Permission -->
        <div v-if="!isLocationEnabled" class="text-center py-8">
            <MapPinIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Location Required</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Enable location access to see notifications about nearby rentals and opportunities.
            </p>
            <button
                @click="requestLocationPermission"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200"
            >
                Enable Location
            </button>
        </div>
        
        <!-- Loading State -->
        <div v-else-if="isLoading && notifications.length === 0" class="space-y-4">
            <div v-for="i in 3" :key="i" class="animate-pulse">
                <div class="flex space-x-4 p-4 border rounded-lg">
                    <div class="rounded-full bg-gray-200 dark:bg-gray-700 h-10 w-10"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                </div>
            </div>
        </div>
        
        <!-- No Notifications -->
        <div v-else-if="notifications.length === 0" class="text-center py-8">
            <BellIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Nearby Notifications</h4>
            <p class="text-gray-600 dark:text-gray-400">
                When there are new rentals or opportunities in your area, they'll appear here.
            </p>
        </div>
        
        <!-- Notifications List -->
        <div v-else class="space-y-3">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                @click="handleNotificationClick(notification)"
                class="group cursor-pointer p-4 border rounded-lg hover:border-blue-200 dark:hover:border-blue-700 transition-colors duration-200"
                :class="{
                    'border-blue-200 bg-blue-50/50 dark:border-blue-700 dark:bg-blue-900/10': !notification.is_read,
                    'border-gray-200 dark:border-gray-700': notification.is_read
                }"
            >
                <div class="flex space-x-4">
                    <!-- Notification Icon -->
                    <div class="flex-shrink-0">
                        <div class="p-2 rounded-full" :class="getNotificationIconClass(notification.type)">
                            <component :is="getNotificationIcon(notification.type)" class="h-5 w-5" />
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ notification.title }}
                                    <span v-if="!notification.is_read" class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-2"></span>
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ notification.message }}
                                </p>
                                
                                <!-- Location and Distance -->
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div v-if="notification.location?.name" class="flex items-center">
                                        <MapPinIcon class="h-3 w-3 mr-1" />
                                        {{ notification.location.name }}
                                    </div>
                                    <div v-if="notification.distance" class="flex items-center">
                                        <span>{{ formatDistance(notification.distance) }}</span>
                                    </div>
                                    <div>
                                        {{ formatTime(notification.created_at) }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Type Badge -->
                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full" :class="getTypeBadgeClass(notification.type)">
                                {{ getTypeLabel(notification.type) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Action Arrow -->
                    <div class="flex-shrink-0 self-center">
                        <ChevronRightIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-500 transition-colors" />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Load More Button -->
        <div v-if="notifications.length > 0 && notifications.length >= 20" class="mt-6 text-center">
            <button
                @click="loadMoreNotifications"
                :disabled="isLoadingMore"
                class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 disabled:opacity-50 rounded-lg transition-colors duration-200"
            >
                <span v-if="isLoadingMore">Loading...</span>
                <span v-else>Load More</span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
    BellIcon,
    MapPinIcon,
    ArrowPathIcon,
    ChevronRightIcon,
    TruckIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    GiftIcon,
    HomeIcon,
    TagIcon
} from '@heroicons/vue/24/outline'
import { useGeoNotifications, type GeoNotification } from '../../composables/useGeoNotifications'

const {
    isLocationEnabled,
    nearbyNotifications: notifications,
    unreadCount,
    getNearbyNotifications,
    handleNotificationClick,
    requestLocationPermission,
} = useGeoNotifications()

const isLoading = ref(false)
const isLoadingMore = ref(false)

const refreshNotifications = async () => {
    isLoading.value = true
    try {
        await getNearbyNotifications(20)
    } finally {
        isLoading.value = false
    }
}

const loadMoreNotifications = async () => {
    isLoadingMore.value = true
    try {
        await getNearbyNotifications(notifications.value.length + 20)
    } finally {
        isLoadingMore.value = false
    }
}

const getNotificationIcon = (type: GeoNotification['type']) => {
    const icons = {
        nearby_rental: TruckIcon,
        pickup_reminder: ClockIcon,
        area_alert: ExclamationTriangleIcon,
        promotional: GiftIcon,
        new_listing: HomeIcon,
        price_drop: TagIcon,
    }
    return icons[type] || BellIcon
}

const getNotificationIconClass = (type: GeoNotification['type']) => {
    const classes = {
        nearby_rental: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
        pickup_reminder: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400',
        area_alert: 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400',
        promotional: 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
        new_listing: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
        price_drop: 'bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400',
    }
    return classes[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-900/20 dark:text-gray-400'
}

const getTypeBadgeClass = (type: GeoNotification['type']) => {
    const classes = {
        nearby_rental: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        pickup_reminder: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        area_alert: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        promotional: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        new_listing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        price_drop: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    }
    return classes[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const getTypeLabel = (type: GeoNotification['type']) => {
    const labels = {
        nearby_rental: 'Nearby',
        pickup_reminder: 'Reminder',
        area_alert: 'Alert',
        promotional: 'Promo',
        new_listing: 'New',
        price_drop: 'Deal',
    }
    return labels[type] || 'Info'
}

const formatDistance = (distance: number) => {
    if (distance < 1000) {
        return `${Math.round(distance)}m away`
    } else if (distance < 10000) {
        return `${(distance / 1000).toFixed(1)}km away`
    } else {
        return `${Math.round(distance / 1000)}km away`
    }
}

const formatTime = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMins / 60)
    const diffDays = Math.floor(diffHours / 24)
    
    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    return date.toLocaleDateString()
}

onMounted(() => {
    if (isLocationEnabled.value) {
        refreshNotifications()
    }
})
</script>