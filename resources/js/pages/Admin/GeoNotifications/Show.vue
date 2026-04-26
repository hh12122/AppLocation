<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

interface GeoNotification {
    id: number
    type: string
    title: string
    message: string
    latitude: number
    longitude: number
    radius: number
    location_name: string
    status: string
    is_active: boolean
    data: Record<string, any>
    scheduled_for: string | null
    expires_at: string | null
    sent_at: string | null
    created_at: string
    user?: {
        id: number
        name: string
        email: string
    }
}

interface Props {
    notification: GeoNotification
    eligibleUsersCount: number
}

const props = defineProps<Props>()

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        nearby_rental: 'Nearby Rental',
        pickup_reminder: 'Pickup Reminder',
        area_alert: 'Area Alert',
        promotional: 'Promotional',
        new_listing: 'New Listing',
        price_drop: 'Price Drop',
    }
    return labels[type] || type
}

const getStatusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        sent: 'bg-blue-100 text-blue-800',
        read: 'bg-green-100 text-green-800',
        clicked: 'bg-purple-100 text-purple-800',
        failed: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDateTime = (datetime: string | null) => {
    if (!datetime) return 'N/A'
    return new Date(datetime).toLocaleString()
}
</script>

<template>
    <Head title="Geo-Notification Details" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Notification Details
                    </h2>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('admin.geo-notifications.edit', notification.id)"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                        >
                            Edit
                        </Link>
                        <Link
                            :href="route('admin.geo-notifications.index')"
                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                        >
                            &larr; Back to list
                        </Link>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ notification.title }}</CardTitle>
                            <span class="px-3 py-1 text-xs font-medium rounded-full" :class="getStatusBadgeClass(notification.status)">
                                {{ notification.status }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ getTypeLabel(notification.type) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</p>
                                <p class="text-sm" :class="notification.is_active ? 'text-green-600' : 'text-red-600'">
                                    {{ notification.is_active ? 'Yes' : 'No' }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Message</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ notification.message }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Location</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Location Name</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ notification.location_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Radius</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ (notification.radius / 1000).toFixed(1) }} km</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Coordinates</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                    {{ notification.latitude.toFixed(4) }}, {{ notification.longitude.toFixed(4) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Eligible Users</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ eligibleUsersCount }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Timeline</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDateTime(notification.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled For</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDateTime(notification.scheduled_for) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sent At</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDateTime(notification.sent_at) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires At</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDateTime(notification.expires_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="notification.data && Object.keys(notification.data).length > 0">
                    <CardHeader>
                        <CardTitle class="text-base">Additional Data</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-4 rounded-lg overflow-x-auto text-gray-900 dark:text-gray-100">{{ JSON.stringify(notification.data, null, 2) }}</pre>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
