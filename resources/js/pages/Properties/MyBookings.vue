<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface PropertyBooking {
    id: number
    check_in: string
    check_out: string
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled'
    total_amount: number
    total_nights: number
    guests: number
    created_at: string
    property: {
        id: number
        title: string
        type: string
        city: string
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
    bookings: {
        data: PropertyBooking[]
        links?: any[]
        meta?: any
    }
}

const props = withDefaults(defineProps<Props>(), {
    bookings: () => ({ data: [], links: [], meta: {} })
})

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

const propertyTypeLabels: Record<string, string> = {
    apartment: 'Appartement',
    house: 'Maison',
    villa: 'Villa',
    studio: 'Studio'
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

const getPrimaryImage = (property: PropertyBooking['property']) => {
    if (!property.images || property.images.length === 0) {
        return '/images/property-placeholder.jpg'
    }
    const primary = property.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
}

// Calculate stats
const stats = {
    total: props.bookings.meta?.total,
    pending: props.bookings.data.filter(r => r.status === 'pending').length,
    active: props.bookings.data.filter(r => r.status === 'active').length,
    completed: props.bookings.data.filter(r => r.status === 'completed').length,
    totalSpent: props.bookings.data
        .filter(r => r.status === 'completed')
        .reduce((sum, r) => sum + r.total_amount, 0)
}
</script>

<template>
    <Head title="Mes séjours" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes séjours</h1>
                    <p class="text-gray-600 mt-2">Suivez l'état de vos réservations de propriétés</p>
                    <Link
                        :href="route('properties.index')"
                        class="inline-block mt-4 text-blue-600 hover:text-blue-800"
                    >
                        Rechercher une nouvelle propriété →
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

                <!-- Bookings List -->
                <div v-if="props.bookings.data.length > 0" class="space-y-6">
                    <Card v-for="booking in props.bookings.data" :key="booking.id">
                        <CardContent class="p-6">
                            <div class="flex space-x-6">
                                <!-- Property Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(booking.property)"
                                        :alt="booking.property.title"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Booking Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ booking.property.title }}
                                            </h3>
                                            <p class="text-gray-600">
                                                {{ propertyTypeLabels[booking.property.type] || booking.property.type }} - {{ booking.property.city }}
                                            </p>
                                            <p class="text-gray-600">
                                                Propriétaire: {{ booking.property.owner.name }}
                                                <span class="ml-2 flex items-center inline">
                                                    <span class="mr-1">⭐</span>
                                                    {{ booking.property.owner.rating || 'N/A' }}
                                                </span>
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Arrivée:</strong> {{ formatDate(booking.check_in) }}
                                                    <strong class="ml-4">Départ:</strong> {{ formatDate(booking.check_out) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Durée:</strong> {{ booking.total_nights }} nuit(s)
                                                    <strong class="ml-4">Voyageurs:</strong> {{ booking.guests }} personne(s)
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Total:</strong> {{ formatPrice(booking.total_amount) }}
                                                    <strong class="ml-4">Réservé le:</strong> {{ formatDate(booking.created_at) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end space-y-2">
                                            <Badge :class="statusColors[booking.status]">
                                                {{ statusLabels[booking.status] }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-3 mt-4">
                                        <Link
                                            :href="route('property-bookings.show', booking.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link
                                            :href="route('properties.show', booking.property.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Voir propriété
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">🏠</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune réservation</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore réservé de propriété. Parcourez les propriétés disponibles pour commencer.
                    </p>
                    <Link
                        :href="route('properties.index')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Rechercher une propriété
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.bookings.links && props.bookings.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.bookings.links" :key="link.label">
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
