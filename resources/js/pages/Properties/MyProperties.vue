<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Property {
    id: number
    title: string
    type: string
    city: string
    address: string
    status: string
    is_available: boolean
    price_per_night: number
    bedrooms: number
    bathrooms: number
    max_guests: number
    rating: number
    bookings_count: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
}

interface Props {
    properties: {
        data: Property[]
        links?: any[]
        meta?: any
    }
    stats?: {
        total: number
        active: number
        inactive: number
        total_bookings: number
        total_revenue: number
    }
}

const props = withDefaults(defineProps<Props>(), {
    properties: () => ({ data: [], links: [], meta: {} }),
    stats: () => ({ total: 0, active: 0, inactive: 0, total_bookings: 0, total_revenue: 0 })
})

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const getPrimaryImage = (property: Property) => {
    if (!property.images || property.images.length === 0) {
        return '/images/property-placeholder.jpg'
    }
    const primary = property.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
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
        case 'active': return 'Active'
        case 'inactive': return 'Inactive'
        case 'maintenance': return 'Maintenance'
        default: return status
    }
}

const propertyTypeLabels: Record<string, string> = {
    apartment: 'Appartement',
    house: 'Maison',
    villa: 'Villa',
    studio: 'Studio'
}
</script>

<template>
    <Head title="Mes propriétés" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Mes propriétés</h1>
                        <p class="text-gray-600 mt-2">Gérez vos propriétés mises en location</p>
                    </div>
                    <Link
                        :href="route('properties.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter une propriété
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-blue-600">{{ props.stats?.total || props.properties.data.length }}</div>
                            <p class="text-sm text-gray-600">Propriétés total</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-green-600">
                                {{ props.stats?.active || props.properties.data.filter(p => p.status === 'active' && p.is_available).length }}
                            </div>
                            <p class="text-sm text-gray-600">Disponibles</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-orange-600">
                                {{ props.stats?.total_bookings || props.properties.data.reduce((sum, p) => sum + (p.bookings_count || 0), 0) }}
                            </div>
                            <p class="text-sm text-gray-600">Réservations totales</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ props.stats?.total_revenue ? formatPrice(props.stats.total_revenue) : formatPrice(0) }}
                            </div>
                            <p class="text-sm text-gray-600">Revenus totaux</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Properties List -->
                <div v-if="props.properties.data.length > 0" class="space-y-6">
                    <Card v-for="property in props.properties.data" :key="property.id">
                        <CardContent class="p-6">
                            <div class="flex space-x-6">
                                <!-- Property Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(property)"
                                        :alt="property.title"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Property Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ property.title }}
                                            </h3>
                                            <p class="text-gray-600">
                                                {{ propertyTypeLabels[property.type] || property.type }} - {{ property.city }}
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Prix:</strong> {{ formatPrice(property.price_per_night) }}/nuit
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    🛏️ {{ property.bedrooms }} chambres • 🚿 {{ property.bathrooms }} SdB • 👥 {{ property.max_guests }} pers.
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Réservations:</strong> {{ property.bookings_count || 0 }}
                                                    <strong class="ml-4">Note:</strong> ⭐ {{ Number(property.rating || 0).toFixed(1) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end space-y-2">
                                            <Badge :class="getStatusColor(property.status, property.is_available)">
                                                {{ getStatusLabel(property.status, property.is_available) }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex space-x-3 mt-4">
                                        <Link
                                            :href="route('properties.show', property.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Voir détails
                                        </Link>
                                        <Link
                                            :href="route('properties.edit', property.id)"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                                        >
                                            Modifier
                                        </Link>
                                        <Link
                                            :href="route('property-bookings.management')"
                                            class="text-purple-600 hover:text-purple-800 text-sm font-medium"
                                        >
                                            Gérer les réservations
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
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune propriété</h3>
                    <p class="text-gray-600 mb-6">
                        Vous n'avez pas encore ajouté de propriété. Commencez par ajouter votre première propriété.
                    </p>
                    <Link
                        :href="route('properties.create')"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                    >
                        Ajouter ma première propriété
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.properties.links && props.properties.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.properties.links" :key="link.label">
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
