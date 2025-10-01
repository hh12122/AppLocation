<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import FavoriteButton from '@/components/FavoriteButton.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import Textarea from '@/components/ui/textarea.vue'
import { Calendar, MapPin, Users, Fuel, Settings, Edit3, Trash2 } from 'lucide-vue-next'

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    daily_rate: number
    city: string
    fuel_type: string
    transmission: string
    seats: number
    rating: number
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
    reviews_count: number
    reviews_avg_rating: number
}

interface Favorite {
    id: number
    notes: string
    created_at: string
    vehicle: Vehicle
}

interface Props {
    favorites: {
        data: Favorite[]
        links: any[]
        meta: any
    }
}

const props = defineProps<Props>()

const editingNotes = ref<number | null>(null)
const newNotes = ref('')

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const fuelTypeLabels = {
    gasoline: 'Essence',
    diesel: 'Diesel',
    electric: 'Électrique',
    hybrid: 'Hybride'
}

const transmissionLabels = {
    manual: 'Manuelle',
    automatic: 'Automatique'
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getPrimaryImage = (vehicle: Vehicle) => {
    const primary = vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

const startEditingNotes = (favorite: Favorite) => {
    editingNotes.value = favorite.id
    newNotes.value = favorite.notes || ''
}

const saveNotes = (favoriteId: number) => {
    router.put(route('favorites.update', favoriteId), {
        notes: newNotes.value
    }, {
        onSuccess: () => {
            editingNotes.value = null
            newNotes.value = ''
        }
    })
}

const cancelEditingNotes = () => {
    editingNotes.value = null
    newNotes.value = ''
}

const removeFavorite = (vehicleId: number) => {
    if (confirm('Êtes-vous sûr de vouloir retirer ce véhicule de vos favoris ?')) {
        router.delete(route('favorites.destroy', vehicleId))
    }
}
</script>

<template>
    <Head title="Mes Favoris" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes Favoris</h1>
                    <p class="mt-2 text-gray-600">
                        Retrouvez tous les véhicules que vous avez ajoutés à votre liste de souhaits
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                        ❤️
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ props.favorites?.meta?.total }}</p>
                                    <p class="text-sm text-gray-600">Véhicules favoris</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <MapPin class="w-6 h-6 text-blue-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ new Set(props.favorites.data.map(f => f.vehicle.city)).size }}
                                    </p>
                                    <p class="text-sm text-gray-600">Villes différentes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        💰
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ props.favorites.data.length > 0
                                            ? formatPrice(Math.round(props.favorites.data.reduce((sum, f) => sum + f.vehicle.daily_rate, 0) / props.favorites.data.length))
                                            : '0 €'
                                        }}
                                    </p>
                                    <p class="text-sm text-gray-600">Prix moyen/jour</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Favorites Grid -->
                <div v-if="props.favorites.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                        v-for="favorite in props.favorites.data"
                        :key="favorite.id"
                        class="group hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="aspect-video relative overflow-hidden rounded-t-lg">
                            <img
                                :src="getPrimaryImage(favorite.vehicle)"
                                :alt="`${favorite.vehicle.brand} ${favorite.vehicle.model}`"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-200"></div>

                            <!-- Price badge -->
                            <Badge class="absolute top-3 right-3 bg-green-500 text-white">
                                {{ formatPrice(favorite.vehicle.daily_rate) }}/jour
                            </Badge>

                            <!-- Favorite button -->
                            <div class="absolute top-3 left-3">
                                <FavoriteButton
                                    :vehicle-id="favorite.vehicle.id"
                                    :initial-is-favorited="true"
                                    size="sm"
                                />
                            </div>

                            <!-- Added date -->
                            <div class="absolute bottom-3 left-3">
                                <Badge variant="secondary" class="bg-black bg-opacity-50 text-white">
                                    <Calendar class="w-3 h-3 mr-1" />
                                    {{ formatDate(favorite.created_at) }}
                                </Badge>
                            </div>
                        </div>

                        <CardContent class="p-4">
                            <!-- Vehicle info -->
                            <div class="mb-3">
                                <h3 class="font-semibold text-lg mb-1">
                                    {{ favorite.vehicle.year }} {{ favorite.vehicle.brand }} {{ favorite.vehicle.model }}
                                </h3>
                                <div class="flex items-center text-sm text-gray-600 mb-2">
                                    <MapPin class="w-4 h-4 mr-1" />
                                    {{ favorite.vehicle.city }}
                                </div>
                            </div>

                            <!-- Vehicle specs -->
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                <div class="flex items-center">
                                    <Fuel class="w-3 h-3 mr-1" />
                                    {{ fuelTypeLabels[favorite.vehicle.fuel_type] }}
                                </div>
                                <div class="flex items-center">
                                    <Settings class="w-3 h-3 mr-1" />
                                    {{ transmissionLabels[favorite.vehicle.transmission] }}
                                </div>
                                <div class="flex items-center">
                                    <Users class="w-3 h-3 mr-1" />
                                    {{ favorite.vehicle.seats }} places
                                </div>
                                <div class="flex items-center">
                                    ⭐ {{ favorite.vehicle.reviews_avg_rating ? favorite.vehicle.reviews_avg_rating.toFixed(1) : 'N/A' }}
                                    <span class="ml-1">({{ favorite.vehicle.reviews_count }})</span>
                                </div>
                            </div>

                            <!-- Owner info -->
                            <div class="text-xs text-gray-600 mb-3">
                                Par <span class="font-medium">{{ favorite.vehicle.owner.name }}</span>
                            </div>

                            <!-- Personal notes -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Notes personnelles</span>
                                    <Button
                                        v-if="editingNotes !== favorite.id"
                                        @click="startEditingNotes(favorite)"
                                        variant="ghost"
                                        size="sm"
                                        class="p-1"
                                    >
                                        <Edit3 class="w-3 h-3" />
                                    </Button>
                                </div>

                                <div v-if="editingNotes === favorite.id" class="space-y-2">
                                    <Textarea
                                        v-model="newNotes"
                                        placeholder="Ajoutez vos notes personnelles..."
                                        class="text-xs"
                                        rows="2"
                                    />
                                    <div class="flex gap-2">
                                        <Button @click="saveNotes(favorite.id)" size="sm" class="text-xs">
                                            Sauvegarder
                                        </Button>
                                        <Button @click="cancelEditingNotes" variant="outline" size="sm" class="text-xs">
                                            Annuler
                                        </Button>
                                    </div>
                                </div>

                                <p v-else class="text-xs text-gray-600 italic">
                                    {{ favorite.notes || 'Aucune note ajoutée' }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <Link
                                    :href="route('vehicles.show', favorite.vehicle.id)"
                                    class="flex-1"
                                >
                                    <Button class="w-full" size="sm">
                                        Voir détails
                                    </Button>
                                </Link>
                                <Button
                                    @click="removeFavorite(favorite.vehicle.id)"
                                    variant="outline"
                                    size="sm"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <div class="text-4xl">💔</div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucun favori pour le moment</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        Explorez notre sélection de véhicules et ajoutez vos préférés à votre liste de souhaits.
                    </p>
                    <Link :href="route('vehicles.index')">
                        <Button>
                            Découvrir des véhicules
                        </Button>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="props.favorites.links && props.favorites.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in props.favorites.links" :key="link.label">
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
