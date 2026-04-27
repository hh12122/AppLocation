<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

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
    expires_at: string | null
    scheduled_for: string | null
}

interface Props {
    notification: GeoNotification
    types: Record<string, string>
    statusOptions: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
    type: props.notification.type,
    title: props.notification.title,
    message: props.notification.message,
    latitude: props.notification.latitude,
    longitude: props.notification.longitude,
    radius: props.notification.radius,
    location_name: props.notification.location_name,
    target_criteria: {},
    is_active: props.notification.is_active,
    expires_at: props.notification.expires_at
        ? new Date(props.notification.expires_at).toISOString().slice(0, 16)
        : '',
    scheduled_for: props.notification.scheduled_for
        ? new Date(props.notification.scheduled_for).toISOString().slice(0, 16)
        : '',
    data: props.notification.data || {},
})

const submit = () => {
    form.put(route('admin.geo-notifications.update', props.notification.id))
}
</script>

<template>
    <Head title="Modifier la Géo-Notification" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Modifier la Géo-Notification
                    </h2>
                    <Link
                        :href="route('admin.geo-notifications.index')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                    >
                        &larr; Retour à la liste
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Détails de la notification</CardTitle>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre</label>
                                <Input v-model="form.title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                                <textarea
                                    v-model="form.message"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                />
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="text-base">Emplacement</CardTitle>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rayon (mètres)</label>
                                <Input v-model="form.radius" type="number" min="100" max="100000" />
                                <p v-if="form.errors.radius" class="mt-1 text-sm text-red-600">{{ form.errors.radius }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de l'emplacement</label>
                                <Input v-model="form.location_name" />
                                <p v-if="form.errors.location_name" class="mt-1 text-sm text-red-600">{{ form.errors.location_name }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="text-base">Planification</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planifier pour</label>
                                <Input v-model="form.scheduled_for" type="datetime-local" />
                                <p v-if="form.errors.scheduled_for" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_for }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expire le</label>
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
                            Annuler
                        </Link>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
