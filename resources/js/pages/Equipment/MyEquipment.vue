<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Equipment {
    id: number
    name: string
    category: string
    subcategory: string
    city: string
    status: string
    is_available: boolean
    daily_rate?: number
    hourly_rate?: number
    weekly_rate?: number
    monthly_rate?: number
    rental_unit: string
    stock_quantity: number
    rating: number
    bookings_count: number
    primary_image?: {
        image_path: string
        alt_text: string
    }
}

interface Props {
    equipment: {
        data: Equipment[]
        links?: any[]
        meta?: any
    }
    categories?: Record<string, any>
    stats?: {
        total: number
        active: number
        inactive: number
        bookings: number
        revenue: number
    }
}

const props = withDefaults(defineProps<Props>(), {
    equipment: () => ({ data: [], links: [], meta: {} }),
    categories: () => ({}),
    stats: () => ({ total: 0, active: 0, inactive: 0, bookings: 0, revenue: 0 })
})

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const getEquipmentImage = (equipment: Equipment) => {
    return equipment.primary_image?.image_path
        ? `/storage/${equipment.primary_image.image_path}`
        : '/images/equipment-placeholder.jpg'
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

const getCategoryLabel = (category: string) => {
    return props.categories?.[category]?.label || category
}

const getRentalUnitLabel = (unit: string) => {
    const labels: Record<string, string> = {
        hour: '/heure',
        day: '/jour',
        week: '/semaine',
        month: '/mois'
    }
    return labels[unit] || '/jour'
}

const getPrice = (equipment: Equipment) => {
    switch (equipment.rental_unit) {
        case 'hour':
            return equipment.hourly_rate
        case 'week':
            return equipment.weekly_rate
        case 'month':
            return equipment.monthly_rate
        default:
            return equipment.daily_rate
    }
}
</script>

<template>
    <Head title="Mon matériel" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Mon matériel</h1>
                        <p class="text-gray-600 mt-2">Gérez votre équipement mis en location</p>
                    </div>
                    <Link
                        :href="route('equipment.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter du matériel
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ props.stats?.total || props.equipment.data.length }}</div>
                            <p class="text-sm text-gray-600">Matériel total</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-green-600">
                                {{ props.stats?.active || props.equipment.data.filter(e => e.status === 'active' && e.is_available).length }}
                            </div>
                            <p class="text-sm text-gray-600">Disponibles</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-orange-600">
                                {{ props.stats?.bookings || props.equipment.data.reduce((sum, e) => sum + (e.bookings_count || 0), 0) }}
                            </div>
                            <p class="text-sm text-gray-600">Locations totales</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ (props.equipment.data.reduce((sum, e) => sum + (e.rating || 0), 0) / props.equipment.data.length || 0).toFixed(1) }}
                            </div>
                            <p class="text-sm text-gray-600">Note moyenne</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-indigo-600">
                                {{ props.stats?.revenue ? formatPrice(props.stats.revenue) : formatPrice(0) }}
                            </div>
                            <p class="text-sm text-gray-600">Revenus totaux</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Equipment List -->
                <div v-if="props.equipment.data.length > 0" class="space-y-6">
                    <Card v-for="item in props.equipment.data" :key="item.id">
                        <CardContent class="p-6">
                            <div class="flex space-x-6">
                                <!-- Equipment Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getEquipmentImage(item)"
                                        :alt="item.name"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Equipment Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ item.name }}
                                            </h3>
                                            <p class="text-gray-600">
                                                {{ getCategoryLabel(item.category) }} - {{ item.city }}
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Prix:</strong> {{ formatPrice(getPrice(item) || 0) }}{{ getRentalUnitLabel(item.rental_unit) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Stock:</strong> {{ item.stock_quantity }} unité(s)
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Locations:</strong> {{ item.bookings_count || 0 }}
                                                    <strong class="ml-4">Note:</strong> ⭐ {{ item.rating?.toFixed(1) || 'N/A' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end space-y-2">
                                            <Badge :class="getStatusColor(item.status, item.is_available)">
                                                {{ getStatusLabel(item.status, item.is_available) }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-3 mt-4">
                                        <Link
                                            :href="route('equipment.show', item.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link
                                            :href="route('equipment.edit', item.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Modifier
                                        </Link>
                                        <Link
                                            :href="route('equipment-bookings.management')"
                                            class="text-purple-600 hover:text-purple-800 text-sm font-medium"
                                        >
                                            Gérer les locations
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
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucun équipement</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore ajouté d'équipement. Commencez par ajouter votre premier matériel.
                    </p>
                    <Link
                        :href="route('equipment.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter mon premier équipement
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.equipment.links && props.equipment.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.equipment.links" :key="link.label">
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
