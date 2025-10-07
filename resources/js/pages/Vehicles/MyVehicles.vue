<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    daily_rate: number
    city: string
    status: string
    is_available: boolean
    rental_count: number
    rating: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
    rentals: Array<{
        id: number
        status: string
        start_date: string
        end_date: string
    }>
}

interface Props {
    vehicles: {
        data: Vehicle[]
        links: any[]
        meta: any
    }
}

const props = defineProps<Props>()

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const getPrimaryImage = (vehicle: Vehicle) => {
    const primary = vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

const getStatusColor = (status: string, isAvailable: boolean) => {
    if (!isAvailable) return 'bg-gray-100 text-gray-800'
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800'
        case 'inactive': return 'bg-yellow-100 text-yellow-800'
        case 'maintenance': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const getStatusLabel = (status: string, isAvailable: boolean) => {
    if (!isAvailable) return 'Indisponible'
    switch (status) {
        case 'active': return 'Actif'
        case 'inactive': return 'Inactif'
        case 'maintenance': return 'Maintenance'
        default: return status
    }
}
</script>

<template>
    <Head title="Mes véhicules" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Mes véhicules</h1>
                        <p class="text-gray-600 mt-2">Gérez vos véhicules mis en location</p>
                    </div>
                    <Link
                        :href="route('vehicles.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter un véhicule
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ props.vehicles.meta?.total }}</div>
                            <p class="text-sm text-gray-600">Véhicules total</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-green-600">
                                {{ props.vehicles.data.filter(v => v.status === 'active' && v.is_available).length }}
                            </div>
                            <p class="text-sm text-gray-600">Disponibles</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-orange-600">
                                {{ props.vehicles.data.reduce((sum, v) => sum + v.rental_count, 0) }}
                            </div>
                            <p class="text-sm text-gray-600">Locations totales</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ Number((props.vehicles.data.reduce((sum, v) => sum + (Number(v.rating) || 0), 0) / props.vehicles.data.length) || 0).toFixed(1) }}
                            </div>
                            <p class="text-sm text-gray-600">Note moyenne</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Vehicles List -->
                <div v-if="props.vehicles.data.length > 0" class="space-y-6">
                    <Card v-for="vehicle in props.vehicles.data" :key="vehicle.id">
                        <CardContent class="p-6">
                            <div class="flex space-x-6">
                                <!-- Vehicle Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(vehicle)"
                                        :alt="`${vehicle.brand} ${vehicle.model}`"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Vehicle Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ vehicle.year }} {{ vehicle.brand }} {{ vehicle.model }}
                                            </h3>
                                            <p class="text-gray-600">{{ vehicle.city }}</p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <span class="text-lg font-bold text-green-600">
                                                    {{ formatPrice(vehicle.daily_rate) }}/jour
                                                </span>
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <span class="mr-1">⭐</span>
                                                    {{ vehicle.rating || 'N/A' }}
                                                </div>
                                                <span class="text-sm text-gray-600">
                                                    {{ vehicle.rental_count }} location(s)
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end space-y-2">
                                            <Badge :class="getStatusColor(vehicle.status, vehicle.is_available)">
                                                {{ getStatusLabel(vehicle.status, vehicle.is_available) }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Recent Rentals -->
                                    <div v-if="vehicle.rentals.length > 0" class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Réservations récentes :</h4>
                                        <div class="flex space-x-2">
                                            <Badge
                                                v-for="rental in vehicle.rentals.slice(0, 3)"
                                                :key="rental.id"
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                {{ rental.status }} - {{ new Date(rental.start_date).toLocaleDateString('fr-FR') }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-3 mt-4">
                                        <Link
                                            :href="route('vehicles.show', vehicle.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link
                                            :href="route('vehicles.edit', vehicle.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Modifier
                                        </Link>
                                        <Link
                                            :href="route('rentals.bookings')"
                                            class="text-purple-600 hover:text-purple-800 text-sm font-medium"
                                        >
                                            Voir réservations
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">🚗</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucun véhicule</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore ajouté de véhicule. Commencez par ajouter votre premier véhicule pour commencer à gagner de l'argent.
                    </p>
                    <Link
                        :href="route('vehicles.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter mon premier véhicule
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.vehicles.links && props.vehicles.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.vehicles.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-2 text-sm border rounded-md"
                                    :class="{
                                        'bg-blue-600 text-white border-blue-600': link.active,
                                        'text-gray-700 border-gray-300 hover:bg-gray-50': !link.active
                                    }"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-400 border border-gray-300 rounded-md"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
