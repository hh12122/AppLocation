<template>
    <Head title="Notification Preferences" />

    <AppLayout>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Permission Status -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <GeoNotificationPermissions />
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-6">Notification Types</h3>
                        
                        <form @submit.prevent="savePreferences" class="space-y-6">
                            <!-- Notification Type Toggles -->
                            <div class="space-y-4">
                                <div v-for="(setting, key) in notificationTypes" :key="key" class="flex items-center justify-between p-4 border rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 rounded-full" :class="setting.iconClass">
                                            <component :is="setting.icon" class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h4 class="font-medium">{{ setting.title }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ setting.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form[key]"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Location Settings -->
                            <div class="pt-6 border-t">
                                <h4 class="font-medium mb-4">Location Settings</h4>
                                
                                <div class="space-y-4">
                                    <!-- Share Location -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="font-medium">Share Location</label>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Allow AppLocation to use your location for relevant notifications
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form.share_location"
                                                class="sr-only peer"
                                            />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- Notification Radius -->
                                    <div>
                                        <label class="block font-medium mb-2">Notification Radius</label>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            How far from your location should we look for relevant notifications?
                                        </p>
                                        <div class="flex items-center space-x-4">
                                            <input
                                                type="range"
                                                v-model="form.notification_radius"
                                                min="1000"
                                                max="50000"
                                                step="1000"
                                                class="flex-1"
                                                :disabled="!form.share_location"
                                            />
                                            <span class="text-sm font-medium w-16 text-right">
                                                {{ formatDistance(form.notification_radius) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timing Preferences -->
                            <div class="pt-6 border-t">
                                <h4 class="font-medium mb-4">Timing Preferences</h4>
                                
                                <div class="space-y-4">
                                    <!-- Quiet Hours -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <div>
                                                <label class="font-medium">Quiet Hours</label>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    Don't send notifications during these hours
                                                </p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    v-model="form.quiet_hours_enabled"
                                                    class="sr-only peer"
                                                />
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>
                                        
                                        <div v-if="form.quiet_hours_enabled" class="flex space-x-4">
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Start</label>
                                                <input
                                                    type="time"
                                                    v-model="form.quiet_hours_start"
                                                    class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium mb-1">End</label>
                                                <input
                                                    type="time"
                                                    v-model="form.quiet_hours_end"
                                                    class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Frequency -->
                                    <div>
                                        <label class="block font-medium mb-2">Notification Frequency</label>
                                        <select
                                            v-model="form.frequency"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                                        >
                                            <option value="realtime">Real-time (as they happen)</option>
                                            <option value="hourly">Hourly digest</option>
                                            <option value="daily">Daily summary</option>
                                            <option value="weekly">Weekly roundup</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Max per day -->
                                    <div>
                                        <label class="block font-medium mb-2">Maximum notifications per day</label>
                                        <div class="flex items-center space-x-4">
                                            <input
                                                type="range"
                                                v-model="form.max_per_day"
                                                min="1"
                                                max="50"
                                                step="1"
                                                class="flex-1"
                                            />
                                            <span class="text-sm font-medium w-8 text-right">
                                                {{ form.max_per_day }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delivery Methods -->
                            <div class="pt-6 border-t">
                                <h4 class="font-medium mb-4">Delivery Methods</h4>
                                
                                <div class="space-y-4">
                                    <div v-for="(method, key) in deliveryMethods" :key="key" class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 rounded-full" :class="method.iconClass">
                                                <component :is="method.icon" class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <label class="font-medium">{{ method.title }}</label>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ method.description }}
                                                </p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form[key]"
                                                class="sr-only peer"
                                            />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="pt-6 border-t">
                                <div class="flex items-center justify-end space-x-4">
                                    <button
                                        type="button"
                                        @click="resetToDefaults"
                                        class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        Reset to Defaults
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <span v-if="form.processing">Saving...</span>
                                        <span v-else>Save Preferences</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <GeoNotificationsList />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import {
    TruckIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    GiftIcon,
    HomeIcon,
    TagIcon,
    BellIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '../../layouts/AppLayout.vue'
import GeoNotificationPermissions from '../../components/Notifications/GeoNotificationPermissions.vue'
import GeoNotificationsList from '../../components/Notifications/GeoNotificationsList.vue'

interface Props {
    preferences: {
        nearby_rentals: boolean
        pickup_reminders: boolean
        area_alerts: boolean
        promotional: boolean
        new_listings: boolean
        price_drops: boolean
        share_location: boolean
        notification_radius: number
        quiet_hours_enabled: boolean
        quiet_hours_start: string | null
        quiet_hours_end: string | null
        frequency: string
        max_per_day: number
        push_enabled: boolean
        email_enabled: boolean
        sms_enabled: boolean
    }
}

const props = defineProps<Props>()

const form = useForm({
    nearby_rentals: props.preferences?.nearby_rentals ?? true,
    pickup_reminders: props.preferences?.pickup_reminders ?? true,
    area_alerts: props.preferences?.area_alerts ?? true,
    promotional: props.preferences?.promotional ?? false,
    new_listings: props.preferences?.new_listings ?? true,
    price_drops: props.preferences?.price_drops ?? true,
    share_location: props.preferences?.share_location ?? false,
    notification_radius: props.preferences?.notification_radius ?? 10000,
    quiet_hours_enabled: props.preferences?.quiet_hours_enabled ?? false,
    quiet_hours_start: props.preferences?.quiet_hours_start ?? '22:00',
    quiet_hours_end: props.preferences?.quiet_hours_end ?? '08:00',
    frequency: props.preferences?.frequency ?? 'realtime',
    max_per_day: props.preferences?.max_per_day ?? 10,
    push_enabled: props.preferences?.push_enabled ?? true,
    email_enabled: props.preferences?.email_enabled ?? false,
    sms_enabled: props.preferences?.sms_enabled ?? false,
})

const notificationTypes = {
    nearby_rentals: {
        title: 'Nearby Rentals',
        description: 'Get notified when new vehicles become available near your location',
        icon: TruckIcon,
        iconClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
    },
    pickup_reminders: {
        title: 'Pickup Reminders',
        description: 'Receive reminders when you\'re near a rental pickup location',
        icon: ClockIcon,
        iconClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400'
    },
    area_alerts: {
        title: 'Area Alerts',
        description: 'Important notifications about your area (traffic, events, etc.)',
        icon: ExclamationTriangleIcon,
        iconClass: 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400'
    },
    promotional: {
        title: 'Promotional Offers',
        description: 'Special deals and offers from nearby vehicle owners',
        icon: GiftIcon,
        iconClass: 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400'
    },
    new_listings: {
        title: 'New Listings',
        description: 'Be the first to know about new vehicles listed in your area',
        icon: HomeIcon,
        iconClass: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400'
    },
    price_drops: {
        title: 'Price Drops',
        description: 'Get alerts when vehicles in your favorites drop in price',
        icon: TagIcon,
        iconClass: 'bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400'
    }
}

const deliveryMethods = {
    push_enabled: {
        title: 'Push Notifications',
        description: 'Instant notifications in your browser or mobile app',
        icon: BellIcon,
        iconClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
    },
    email_enabled: {
        title: 'Email Notifications',
        description: 'Receive notifications via email',
        icon: EnvelopeIcon,
        iconClass: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400'
    },
    sms_enabled: {
        title: 'SMS Notifications',
        description: 'Get text messages for urgent notifications',
        icon: DevicePhoneMobileIcon,
        iconClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400'
    }
}

const formatDistance = (distance: number) => {
    if (distance < 1000) {
        return `${distance}m`
    } else {
        return `${distance / 1000}km`
    }
}

const savePreferences = () => {
    form.post(route('settings.notification-preferences.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Handle success
        },
        onError: (errors) => {
            console.error('Failed to save preferences:', errors)
        }
    })
}

const resetToDefaults = () => {
    form.reset()
    form.nearby_rentals = true
    form.pickup_reminders = true
    form.area_alerts = true
    form.promotional = false
    form.new_listings = true
    form.price_drops = true
    form.share_location = false
    form.notification_radius = 10000
    form.quiet_hours_enabled = false
    form.quiet_hours_start = '22:00'
    form.quiet_hours_end = '08:00'
    form.frequency = 'realtime'
    form.max_per_day = 10
    form.push_enabled = true
    form.email_enabled = false
    form.sms_enabled = false
}
</script>