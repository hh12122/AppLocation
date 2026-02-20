<template>
    <Head title="Geo-Notifications Management" />
    
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Geo-Notifications Management
                </h2>
                <div class="flex items-center space-x-3">
                    <button
                        @click="refreshStatistics"
                        class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200"
                    >
                        <ArrowPathIcon :class="['h-4 w-4', { 'animate-spin': isRefreshing }]" />
                    </button>
                    <Link
                        :href="route('admin.geo-notifications.create')"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
                    >
                        <PlusIcon class="h-4 w-4 inline mr-2" />
                        Create Notification
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                                <BellIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Notifications</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ statistics.total_notifications?.toLocaleString() ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-full">
                                <CheckCircleIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ statistics.active_notifications?.toLocaleString() ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                                <ClockIcon class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sent Today</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ statistics.sent_today?.toLocaleString() ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-full">
                                <UsersIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Users with Location</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ statistics.users_with_location?.toLocaleString() ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Search -->
                            <div class="flex-1">
                                <input
                                    type="text"
                                    v-model="searchForm.search"
                                    @input="debouncedSearch"
                                    placeholder="Search notifications..."
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                                />
                            </div>
                            
                            <!-- Status Filter -->
                            <select
                                v-model="searchForm.status"
                                @change="applyFilters"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="sent">Sent</option>
                                <option value="read">Read</option>
                                <option value="clicked">Clicked</option>
                                <option value="failed">Failed</option>
                            </select>
                            
                            <!-- Type Filter -->
                            <select
                                v-model="searchForm.type"
                                @change="applyFilters"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900"
                            >
                                <option value="">All Types</option>
                                <option value="nearby_rental">Nearby Rental</option>
                                <option value="pickup_reminder">Pickup Reminder</option>
                                <option value="area_alert">Area Alert</option>
                                <option value="promotional">Promotional</option>
                                <option value="new_listing">New Listing</option>
                                <option value="price_drop">Price Drop</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notifications Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedNotifications.length > 0" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-800 dark:text-blue-200">
                                    {{ selectedNotifications.length }} notification(s) selected
                                </span>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="bulkAction('activate')"
                                        class="px-3 py-1 text-xs bg-green-100 hover:bg-green-200 text-green-800 rounded-lg transition-colors"
                                    >
                                        Activate
                                    </button>
                                    <button
                                        @click="bulkAction('deactivate')"
                                        class="px-3 py-1 text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-lg transition-colors"
                                    >
                                        Deactivate
                                    </button>
                                    <button
                                        @click="bulkAction('process')"
                                        class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg transition-colors"
                                    >
                                        Process
                                    </button>
                                    <button
                                        @click="bulkAction('delete')"
                                        class="px-3 py-1 text-xs bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="selectedNotifications.length === notifications.data.length && notifications.data.length > 0"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Notification
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="notification in notifications.data"
                                        :key="notification.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                    >
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="notification.id"
                                                v-model="selectedNotifications"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ notification.title }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ truncate(notification.message, 50) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getTypeBadgeClass(notification.type)">
                                                {{ getTypeLabel(notification.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusBadgeClass(notification.status)">
                                                {{ getStatusLabel(notification.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ notification.location_name || 'Unknown' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatCoordinates(notification.latitude, notification.longitude) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDateTime(notification.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <Link
                                                    :href="route('admin.geo-notifications.show', notification.id)"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    <EyeIcon class="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    :href="route('admin.geo-notifications.edit', notification.id)"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                >
                                                    <PencilIcon class="h-4 w-4" />
                                                </Link>
                                                <button
                                                    @click="deleteNotification(notification.id)"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="notifications.links" class="mt-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex justify-between flex-1 sm:hidden">
                                    <Link
                                        v-if="notifications.prev_page_url"
                                        :href="notifications.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="notifications.next_page_url"
                                        :href="notifications.next_page_url"
                                        class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                                        </p>
                                    </div>
                                    <div class="flex space-x-1">
                                        <template v-for="link in notifications.links" :key="link.label">
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                class="px-3 py-2 text-sm leading-tight border rounded-lg transition-colors duration-200"
                                                :class="link.active 
                                                    ? 'text-blue-600 bg-blue-50 border-blue-300 dark:text-blue-400 dark:bg-blue-900/20 dark:border-blue-700'
                                                    : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50 dark:text-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700'"
                                                v-html="link.label"
                                            />
                                            <span
                                                v-else
                                                class="px-3 py-2 text-sm leading-tight text-gray-400 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500"
                                                v-html="link.label"
                                            />
                                        </template>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import {
    BellIcon,
    CheckCircleIcon,
    ClockIcon,
    UsersIcon,
    PlusIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline'
import AuthenticatedLayout from '../../../layouts/AppLayout.vue'

interface GeoNotification {
    id: number
    type: string
    title: string
    message: string
    latitude: number
    longitude: number
    location_name: string
    status: string
    is_active: boolean
    created_at: string
    user?: {
        id: number
        name: string
        email: string
    }
}

interface Props {
    notifications: {
        data: GeoNotification[]
        links: any[]
        prev_page_url: string | null
        next_page_url: string | null
        from: number
        to: number
        total: number
    }
    statistics: {
        total_notifications: number
        active_notifications: number
        sent_today: number
        pending_notifications: number
        users_with_location: number
        users_with_push_enabled: number
        notifications_by_type: Record<string, number>
    }
    filters: {
        status?: string
        type?: string
        search?: string
    }
}

const props = defineProps<Props>()

const isRefreshing = ref(false)
const selectedNotifications = ref<number[]>([])
const searchForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
})

const applyFilters = () => {
    router.get(route('admin.geo-notifications.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    })
}

const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

const refreshStatistics = async () => {
    isRefreshing.value = true
    try {
        router.reload({ only: ['statistics'] })
    } finally {
        setTimeout(() => {
            isRefreshing.value = false
        }, 1000)
    }
}

const toggleSelectAll = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.checked) {
        selectedNotifications.value = props.notifications.data.map(n => n.id)
    } else {
        selectedNotifications.value = []
    }
}

const bulkAction = (action: string) => {
    if (selectedNotifications.value.length === 0) return

    const confirmed = confirm(`Are you sure you want to ${action} ${selectedNotifications.value.length} notification(s)?`)
    if (!confirmed) return

    router.post(route('admin.geo-notifications.bulk-action'), {
        action: action,
        notification_ids: selectedNotifications.value,
    }, {
        onSuccess: () => {
            selectedNotifications.value = []
        }
    })
}

const deleteNotification = (id: number) => {
    const confirmed = confirm('Are you sure you want to delete this notification?')
    if (!confirmed) return

    router.delete(route('admin.geo-notifications.destroy', id))
}

const getTypeBadgeClass = (type: string) => {
    const classes = {
        nearby_rental: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        pickup_reminder: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        area_alert: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        promotional: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        new_listing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        price_drop: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    }
    return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const getStatusBadgeClass = (status: string) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        read: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        clicked: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    }
    return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const getTypeLabel = (type: string) => {
    const labels = {
        nearby_rental: 'Nearby Rental',
        pickup_reminder: 'Pickup Reminder',
        area_alert: 'Area Alert',
        promotional: 'Promotional',
        new_listing: 'New Listing',
        price_drop: 'Price Drop',
    }
    return labels[type as keyof typeof labels] || type
}

const getStatusLabel = (status: string) => {
    const labels = {
        pending: 'Pending',
        sent: 'Sent',
        read: 'Read',
        clicked: 'Clicked',
        failed: 'Failed',
    }
    return labels[status as keyof typeof labels] || status
}

const truncate = (text: string, length: number) => {
    return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDateTime = (datetime: string) => {
    return new Date(datetime).toLocaleString()
}

const formatCoordinates = (lat: number, lng: number) => {
    return `${lat.toFixed(4)}, ${lng.toFixed(4)}`
}
</script>