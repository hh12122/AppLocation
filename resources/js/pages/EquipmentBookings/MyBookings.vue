<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface EquipmentBooking {
    id: number
    start_date: string
    end_date: string
    status: 'pending' | 'confirmed' | 'ready' | 'delivered' | 'returned' | 'cancelled'
    total_amount: number
    quantity: number
    delivery_required: boolean
    delivery_address?: string
    created_at: string
    equipment: {
        id: number
        name: string
        category: string
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
        data: EquipmentBooking[]
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
    ready: 'Prêt',
    delivered: 'Livré',
    returned: 'Retourné',
    cancelled: 'Annulée'
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    ready: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    returned: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
}

const categoryLabels: Record<string, string> = {
    sports: 'Sport',
    tools: 'Outils',
    events: 'Événements',
    camping: 'Camping',
    electronics: 'Électronique'
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

const getPrimaryImage = (equipment: EquipmentBooking['equipment']) => {
    if (!equipment.images || equipment.images.length === 0) {
        return '/images/equipment-placeholder.jpg'
    }
    const primary = equipment.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/equipment-placeholder.jpg'
}

// Calculate stats
const stats = {
    total: props.bookings.meta?.total,
    pending: props.bookings.data.filter(r => r.status === 'pending').length,
    active: props.bookings.data.filter(r => ['confirmed', 'ready', 'delivered'].includes(r.status)).length,
    completed: props.bookings.data.filter(r => r.status === 'returned').length,
    totalSpent: props.bookings.data
        .filter(r => r.status === 'returned')
        .reduce((sum, r) => sum + r.total_amount, 0)
}
</script>

<template>
    <Head title="Mes locations matériel" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes locations matériel</h1>
                    <p class="text-gray-600 mt-2">Suivez l'état de vos locations d'équipements</p>
                    <Link
                        :href="route('equipment.index')"
                        class="inline-block mt-4 text-blue-600 hover:text-blue-800"
                    >
                        Rechercher du matériel →
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                            <p class="text-sm text-gray-600">Total locations</p>
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
                                <!-- Equipment Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(booking.equipment)"
                                        :alt="booking.equipment.name"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Booking Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ booking.equipment.name }}
                                            </h3>
                                            <p class="text-gray-600">
                                                {{ categoryLabels[booking.equipment.category] || booking.equipment.category }} - {{ booking.equipment.city }}
                                            </p>
                                            <p class="text-gray-600">
                                                Propriétaire: {{ booking.equipment.owner.name }}
                                                <span class="ml-2 flex items-center inline">
                                                    <span class="mr-1">⭐</span>
                                                    {{ booking.equipment.owner.rating || 'N/A' }}
                                                </span>
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Du:</strong> {{ formatDate(booking.start_date) }}
                                                    <strong class="ml-4">Au:</strong> {{ formatDate(booking.end_date) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Quantité:</strong> {{ booking.quantity }}
                                                    <strong class="ml-4">Total:</strong> {{ formatPrice(booking.total_amount) }}
                                                </p>
                                                <p v-if="booking.delivery_required" class="text-sm text-gray-600">
                                                    <strong>📦 Livraison:</strong> {{ booking.delivery_address }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Réservé le:</strong> {{ formatDate(booking.created_at) }}
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
                                            :href="route('equipment-bookings.show', booking.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link
                                            :href="route('equipment.show', booking.equipment.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Voir équipement
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">🛠️</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune location</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore loué d'équipement. Parcourez le matériel disponible pour commencer.
                    </p>
                    <Link
                        :href="route('equipment.index')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Rechercher du matériel
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
