<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useGeolocation } from '@/composables/useGeolocation'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { MapPin } from 'lucide-vue-next'

interface Property {
    id: number
    title: string
    description: string
    type: string
    city: string
    address: string
    price_per_night: number
    price_per_week?: number
    price_per_month?: number
    bedrooms: number
    bathrooms: number
    max_guests: number
    rating: number
    latitude?: number
    longitude?: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
    owner: {
        name: string
        rating: number
    }
}

interface Props {
    properties: {
        data: Property[]
        links: any[]
        meta: any
    }
    filters?: any
    searchParams?: {
        search?: string
        city?: string
        property_type?: string
        room_type?: string
        min_guests?: number
        min_price?: number
        max_price?: number
        amenities?: string[]
        checkin?: string
        checkout?: string
    }
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
    searchParams: () => ({})
})

const { getCurrentPosition, geocodeAddress } = useGeolocation()

const locationSearchText = ref('')
const searchingLocation = ref(false)

const searchForm = ref({
    city: props.searchParams?.city || '',
    type: props.searchParams?.property_type || '',
    min_bedrooms: props.searchParams?.min_guests || '',
    max_price: props.searchParams?.max_price || '',
    check_in: props.searchParams?.checkin || '',
    check_out: props.searchParams?.checkout || ''
})

const search = () => {
    router.get(route('properties.index'), searchForm.value, {
        preserveState: true,
        replace: true
    })
}

const clearFilters = () => {
    searchForm.value = {
        city: '',
        type: '',
        min_bedrooms: '',
        max_price: '',
        check_in: '',
        check_out: ''
    }
    search()
}

const getPrimaryImage = (property: Property) => {
    const primary = property.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
}

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const propertyTypeLabels: Record<string, string> = {
    apartment: 'Appartement',
    house: 'Maison',
    villa: 'Villa',
    studio: 'Studio'
}
</script>

<template>
    <Head title="Propriétés disponibles" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search Filters -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Rechercher une propriété</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Ville</label>
                                <Input
                                    v-model="searchForm.city"
                                    placeholder="Ex: Paris, Lyon..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Type</label>
                                <select
                                    v-model="searchForm.type"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Tous</option>
                                    <option value="apartment">Appartement</option>
                                    <option value="house">Maison</option>
                                    <option value="villa">Villa</option>
                                    <option value="studio">Studio</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Chambres min.</label>
                                <Input
                                    v-model="searchForm.min_bedrooms"
                                    type="number"
                                    min="1"
                                    placeholder="1, 2, 3..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Prix max. /nuit</label>
                                <Input
                                    v-model="searchForm.max_price"
                                    type="number"
                                    min="1"
                                    placeholder="€"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Date d'arrivée</label>
                                <Input
                                    v-model="searchForm.check_in"
                                    type="date"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Date de départ</label>
                                <Input
                                    v-model="searchForm.check_out"
                                    type="date"
                                />
                            </div>

                            <div class="md:col-span-2 lg:col-span-4 flex gap-4">
                                <Button type="submit">
                                    Rechercher
                                </Button>
                                <Button type="button" variant="outline" @click="clearFilters">
                                    Effacer les filtres
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Results -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">
                        {{ props.properties?.meta?.total || props.properties.data.length }} propriété(s) trouvée(s)
                    </h2>

                    <Link
                        v-if="$page.props.auth.user?.is_owner"
                        :href="route('properties.create')"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                    >
                        Ajouter ma propriété
                    </Link>
                </div>

                <!-- Properties Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card v-for="property in props.properties.data" :key="property.id" class="overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="aspect-video relative">
                            <img
                                :src="getPrimaryImage(property)"
                                :alt="property.title"
                                class="w-full h-full object-cover"
                            />
                            <Badge class="absolute top-2 right-2 bg-green-500">
                                {{ formatPrice(property.price_per_night) }}/nuit
                            </Badge>
                        </div>

                        <CardContent class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-lg">
                                    {{ property.title }}
                                </h3>
                                <div class="flex items-center text-sm text-gray-600">
                                    <span class="mr-1">⭐</span>
                                    {{ property.rating || 'N/A' }}
                                </div>
                            </div>

                            <p class="text-gray-600 mb-2">{{ propertyTypeLabels[property.type] || property.type }}</p>
                            <p class="text-gray-600 mb-3">{{ property.city }}</p>

                            <div class="flex justify-between text-sm text-gray-600 mb-4">
                                <span>🛏️ {{ property.bedrooms }} chambres</span>
                                <span>🚿 {{ property.bathrooms }} SdB</span>
                                <span>👥 {{ property.max_guests }} pers.</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    Par {{ property.owner.name }}
                                </div>
                                <Link
                                    :href="route('properties.show', property.id)"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm"
                                >
                                    Voir détails
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-if="props.properties.data.length === 0" class="text-center py-12">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune propriété trouvée</h3>
                    <p class="text-gray-600 mb-4">Essayez d'ajuster vos critères de recherche</p>
                    <Button @click="clearFilters">
                        Voir toutes les propriétés
                    </Button>
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
