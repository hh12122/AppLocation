<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

interface Rental {
    id: number
    start_date: string
    end_date: string
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled'
    total_amount: number
    total_days: number
    created_at: string
    vehicle: {
        id: number
        brand: string
        model: string
        year: number
        images: Array<{
            id: number
            image_path: string
            is_primary: boolean
        }>
        owner: {
            id: number
            name: string
            rating: number
        }
    }
}

interface Props {
    rentals: {
        data: Rental[]
        links: any[]
        meta: any
    }
}

const props = defineProps<Props>()

const statusLabels = {
    pending: 'En attente',
    confirmed: 'Confirmée',
    active: 'En cours',
    completed: 'Terminée',
    cancelled: 'Annulée'
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
}

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getPrimaryImage = (vehicle: Rental['vehicle']) => {
    const primary = vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

// Calculate stats
const stats = {
    total: props.rentals.meta.total,
    pending: props.rentals.data.filter(r => r.status === 'pending').length,
    active: props.rentals.data.filter(r => r.status === 'active').length,
    completed: props.rentals.data.filter(r => r.status === 'completed').length,
    totalSpent: props.rentals.data
        .filter(r => r.status === 'completed')
        .reduce((sum, r) => sum + r.total_amount, 0)
}
</script>

<template>
    <Head title="Mes réservations" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes réservations</h1>
                    <p class="text-gray-600 mt-2">Suivez l'état de vos locations de véhicules</p>
                    <Link 
                        :href="route('vehicles.index')"
                        class="inline-block mt-4 text-blue-600 hover:text-blue-800"
                    >
                        Rechercher un nouveau véhicule →
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                            <p class="text-sm text-gray-600">Total réservations</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                            <p class="text-sm text-gray-600">En attente</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                            <p class="text-sm text-gray-600">En cours</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-gray-600">{{ stats.completed }}</div>
                            <p class="text-sm text-gray-600">Terminées</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ formatPrice(stats.totalSpent) }}</div>
                            <p class="text-sm text-gray-600">Total dépensé</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Rentals List -->
                <div v-if="props.rentals.data.length > 0" class="space-y-6">
                    <Card v-for="rental in props.rentals.data" :key="rental.id">
                        <CardContent class="p-6">
                            <div class="flex space-x-6">
                                <!-- Vehicle Image -->
                                <div class="flex-shrink-0">
                                    <img 
                                        :src="getPrimaryImage(rental.vehicle)"
                                        :alt="`${rental.vehicle.brand} ${rental.vehicle.model}`"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Rental Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                                            </h3>
                                            <p class="text-gray-600">
                                                Propriétaire: {{ rental.vehicle.owner.name }}
                                                <span class="ml-2 flex items-center inline">
                                                    <span class="mr-1">⭐</span>
                                                    {{ rental.vehicle.owner.rating || 'N/A' }}
                                                </span>
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Du:</strong> {{ formatDate(rental.start_date) }}
                                                    <strong class="ml-4">Au:</strong> {{ formatDate(rental.end_date) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Durée:</strong> {{ rental.total_days }} jour(s)
                                                    <strong class="ml-4">Total:</strong> {{ formatPrice(rental.total_amount) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Réservé le:</strong> {{ formatDate(rental.created_at) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end space-y-2">
                                            <Badge :class="statusColors[rental.status]">
                                                {{ statusLabels[rental.status] }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-3 mt-4">
                                        <Link 
                                            :href="route('rentals.show', rental.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link 
                                            :href="route('vehicles.show', rental.vehicle.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Voir véhicule
                                        </Link>
                                        <span 
                                            v-if="rental.vehicle.owner.phone"
                                            class="text-purple-600 hover:text-purple-800 text-sm font-medium cursor-pointer"
                                            @click="window.open(`tel:${rental.vehicle.owner.phone}`)"
                                        >
                                            Contacter propriétaire
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune réservation</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore fait de réservation. Parcourez les véhicules disponibles pour commencer.
                    </p>
                    <Link 
                        :href="route('vehicles.index')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Rechercher un véhicule
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.rentals.links && props.rentals.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.rentals.links" :key="link.label">
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