<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface Props {
    types: Record<string, string>
    statusOptions: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
    type: 'nearby_rental',
    title: '',
    message: '',
    latitude: 48.8566,
    longitude: 2.3522,
    radius: 5000,
    location_name: '',
    target_criteria: {},
    expires_at: '',
    scheduled_for: '',
    data: {},
})

const submit = () => {
    form.post(route('admin.geo-notifications.store'))
}
</script>

<template>
    <Head title="Create Geo-Notification" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Create Geo-Notification
                    </h2>
                    <Link
                        :href="route('admin.geo-notifications.index')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                    >
                        &larr; Back to list
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Notification Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                <select
                                    v-model="form.type"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option v-for="(label, value) in types" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                <Input v-model="form.title" placeholder="Notification title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                                <textarea
                                    v-model="form.message"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Notification message (max 500 chars)"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="text-base">Location</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Latitude</label>
                                    <Input v-model="form.latitude" type="number" step="any" />
                                    <p v-if="form.errors.latitude" class="mt-1 text-sm text-red-600">{{ form.errors.latitude }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Longitude</label>
                                    <Input v-model="form.longitude" type="number" step="any" />
                                    <p v-if="form.errors.longitude" class="mt-1 text-sm text-red-600">{{ form.errors.longitude }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Radius (meters)</label>
                                <Input v-model="form.radius" type="number" min="100" max="100000" />
                                <p v-if="form.errors.radius" class="mt-1 text-sm text-red-600">{{ form.errors.radius }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Name</label>
                                <Input v-model="form.location_name" placeholder="e.g. Paris City Center" />
                                <p v-if="form.errors.location_name" class="mt-1 text-sm text-red-600">{{ form.errors.location_name }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="text-base">Scheduling</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Schedule For</label>
                                <Input v-model="form.scheduled_for" type="datetime-local" />
                                <p class="mt-1 text-xs text-gray-500">Leave empty to send immediately</p>
                                <p v-if="form.errors.scheduled_for" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_for }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expires At</label>
                                <Input v-model="form.expires_at" type="datetime-local" />
                                <p v-if="form.errors.expires_at" class="mt-1 text-sm text-red-600">{{ form.errors.expires_at }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="mt-6 flex justify-end gap-3">
                        <Link
                            :href="route('admin.geo-notifications.index')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </Link>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Notification' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
