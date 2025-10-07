<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import FavoriteButton from '@/components/FavoriteButton.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import Textarea from '@/components/ui/textarea.vue'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Calendar, MapPin, Users, Fuel, Settings, Edit3, Trash2, Home, Wrench, Car } from 'lucide-vue-next'

interface Owner {
    id: number
    name: string
    rating: number
}

interface Image {
    id: number
    image_path: string
    is_primary: boolean
}

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
    images: Image[]
    owner: Owner
    reviews_count: number
    reviews_avg_rating: number
}

interface Equipment {
    id: number
    name: string
    category: string
    daily_rate: number
    city: string
    description: string
    images: Image[]
    owner: Owner
}

interface Property {
    id: number
    title: string
    property_type: string
    bedrooms: number
    bathrooms: number
    max_guests: number
    price_per_night: number
    city: string
    images: Image[]
    owner: Owner
}

type FavoritableItem = Vehicle | Equipment | Property

interface Favorite {
    id: number
    notes: string
    created_at: string
    favoritable_type: string
    favoritable_id: number
    favoritable: FavoritableItem
}

interface Props {
    favorites: {
        data: Favorite[]
        links: any[]
        meta: any
    }
    currentType: string
    stats: {
        total: number
        vehicles: number
        equipment: number
        properties: number
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

const fuelTypeLabels: Record<string, string> = {
    gasoline: 'Essence',
    diesel: 'Diesel',
    electric: 'Électrique',
    hybrid: 'Hybride'
}

const transmissionLabels: Record<string, string> = {
    manual: 'Manuelle',
    automatic: 'Automatique'
}

const propertyTypeLabels: Record<string, string> = {
    house: 'Maison',
    apartment: 'Appartement',
    villa: 'Villa',
    studio: 'Studio'
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getPrimaryImage = (item: FavoritableItem) => {
    const primary = item.images?.find(img => img.is_primary)
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

const removeFavorite = (favorite: Favorite) => {
    if (confirm('Êtes-vous sûr de vouloir retirer cet élément de vos favoris ?')) {
        const type = favorite.favoritable_type.split('\\').pop()?.toLowerCase() || 'vehicle'
        router.delete(route('favorites.destroy', favorite.favoritable_id), {
            data: { type }
        })
    }
}

const isVehicle = (item: any): item is Vehicle => {
    return 'brand' in item && 'model' in item
}

const isEquipment = (item: any): item is Equipment => {
    return 'category' in item && !('brand' in item)
}

const isProperty = (item: any): item is Property => {
    return 'property_type' in item && 'bedrooms' in item
}

const getItemType = (favorite: Favorite): 'Vehicle' | 'Equipment' | 'Property' => {
    const typeName = favorite.favoritable_type.split('\\').pop()
    return (typeName || 'Vehicle') as 'Vehicle' | 'Equipment' | 'Property'
}

const getItemRoute = (favorite: Favorite) => {
    const item = favorite.favoritable
    if (isVehicle(item)) {
        return route('vehicles.show', item.id)
    } else if (isEquipment(item)) {
        return route('equipment.show', item.id)
    } else if (isProperty(item)) {
        return route('properties.show', item.id)
    }
    return '#'
}

const getItemPrice = (item: FavoritableItem): number => {
    if ('daily_rate' in item) {
        return item.daily_rate
    } else if ('price_per_night' in item) {
        return item.price_per_night
    }
    return 0
}

const getItemTitle = (item: FavoritableItem): string => {
    if (isVehicle(item)) {
        return `${item.year} ${item.brand} ${item.model}`
    } else if (isEquipment(item)) {
        return item.name
    } else if (isProperty(item)) {
        return item.title
    }
    return 'Item'
}

const filterByType = (type: string) => {
    router.visit(route('favorites.index', { type }))
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
                        Retrouvez tous vos articles favoris : véhicules, équipements et propriétés
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                        ❤️
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                                    <p class="text-sm text-gray-600">Total favoris</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <Car class="w-6 h-6 text-blue-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.vehicles }}</p>
                                    <p class="text-sm text-gray-600">Véhicules</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <Wrench class="w-6 h-6 text-green-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.equipment }}</p>
                                    <p class="text-sm text-gray-600">Équipements</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <Home class="w-6 h-6 text-purple-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.properties }}</p>
                                    <p class="text-sm text-gray-600">Propriétés</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Type Filter Tabs -->
                <Tabs :default-value="currentType" class="mb-6">
                    <TabsList class="grid w-full grid-cols-4">
                        <TabsTrigger value="all" @click="filterByType('all')">
                            Tous ({{ stats.total }})
                        </TabsTrigger>
                        <TabsTrigger value="vehicles" @click="filterByType('vehicles')">
                            Véhicules ({{ stats.vehicles }})
                        </TabsTrigger>
                        <TabsTrigger value="equipment" @click="filterByType('equipment')">
                            Équipements ({{ stats.equipment }})
                        </TabsTrigger>
                        <TabsTrigger value="properties" @click="filterByType('properties')">
                            Propriétés ({{ stats.properties }})
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                <!-- Favorites Grid -->
                <div v-if="favorites.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                        v-for="favorite in favorites.data"
                        :key="favorite.id"
                        class="group hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="aspect-video relative overflow-hidden rounded-t-lg">
                            <img
                                :src="getPrimaryImage(favorite.favoritable)"
                                :alt="getItemTitle(favorite.favoritable)"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-200"></div>

                            <!-- Price badge -->
                            <Badge class="absolute top-3 right-3 bg-green-500 text-white">
                                {{ formatPrice(getItemPrice(favorite.favoritable)) }}/jour
                            </Badge>

                            <!-- Favorite button -->
                            <div class="absolute top-3 left-3">
                                <FavoriteButton
                                    :favoritable-type="getItemType(favorite)"
                                    :favoritable-id="favorite.favoritable.id"
                                    :is-favorited="true"
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
                            <!-- Item info -->
                            <div class="mb-3">
                                <h3 class="font-semibold text-lg mb-1">
                                    {{ getItemTitle(favorite.favoritable) }}
                                </h3>
                                <div class="flex items-center text-sm text-gray-600 mb-2">
                                    <MapPin class="w-4 h-4 mr-1" />
                                    {{ favorite.favoritable.city }}
                                </div>
                            </div>

                            <!-- Vehicle specs -->
                            <div v-if="isVehicle(favorite.favoritable)" class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                <div class="flex items-center">
                                    <Fuel class="w-3 h-3 mr-1" />
                                    {{ fuelTypeLabels[favorite.favoritable.fuel_type] }}
                                </div>
                                <div class="flex items-center">
                                    <Settings class="w-3 h-3 mr-1" />
                                    {{ transmissionLabels[favorite.favoritable.transmission] }}
                                </div>
                                <div class="flex items-center">
                                    <Users class="w-3 h-3 mr-1" />
                                    {{ favorite.favoritable.seats }} places
                                </div>
                                <div class="flex items-center">
                                    ⭐ {{ Number(favorite.favoritable.reviews_avg_rating || 0).toFixed(1) }}
                                    <span class="ml-1">({{ favorite.favoritable.reviews_count }})</span>
                                </div>
                            </div>

                            <!-- Equipment specs -->
                            <div v-else-if="isEquipment(favorite.favoritable)" class="text-xs text-gray-600 mb-3">
                                <Badge variant="outline" class="mb-2">{{ favorite.favoritable.category }}</Badge>
                                <p class="line-clamp-2">{{ favorite.favoritable.description }}</p>
                            </div>

                            <!-- Property specs -->
                            <div v-else-if="isProperty(favorite.favoritable)" class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                <div class="flex items-center">
                                    <Home class="w-3 h-3 mr-1" />
                                    {{ propertyTypeLabels[favorite.favoritable.property_type] }}
                                </div>
                                <div class="flex items-center">
                                    🛏️ {{ favorite.favoritable.bedrooms }} chambres
                                </div>
                                <div class="flex items-center">
                                    🚿 {{ favorite.favoritable.bathrooms }} salles de bain
                                </div>
                                <div class="flex items-center">
                                    <Users class="w-3 h-3 mr-1" />
                                    {{ favorite.favoritable.max_guests }} voyageurs
                                </div>
                            </div>

                            <!-- Owner info -->
                            <div class="text-xs text-gray-600 mb-3">
                                Par <span class="font-medium">{{ favorite.favoritable.owner.name }}</span>
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
                                    :href="getItemRoute(favorite)"
                                    class="flex-1"
                                >
                                    <Button class="w-full" size="sm">
                                        Voir détails
                                    </Button>
                                </Link>
                                <Button
                                    @click="removeFavorite(favorite)"
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
                        Explorez notre sélection et ajoutez vos préférés à votre liste de souhaits.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <Link :href="route('vehicles.index')">
                            <Button>
                                Découvrir des véhicules
                            </Button>
                        </Link>
                        <Link :href="route('equipment.index')">
                            <Button variant="outline">
                                Découvrir des équipements
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="favorites.links && favorites.links.length > 3" class="mt-8">
                    <nav class="flex justify-center">
                        <div class="flex space-x-1">
                            <template v-for="link in favorites.links" :key="link.label">
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
