<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

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
        name: string
        rating: number
    }
}

interface Props {
    vehicles: {
        data: Vehicle[]
        links: any[]
        meta: any
    }
    filters: {
        brand?: string
        city?: string
        fuel_type?: string
        transmission?: string
        min_seats?: number
        max_price?: number
        start_date?: string
        end_date?: string
    }
}

const props = defineProps<Props>()

const searchForm = ref({
    brand: props.filters.brand || '',
    city: props.filters.city || '',
    fuel_type: props.filters.fuel_type || '',
    transmission: props.filters.transmission || '',
    min_seats: props.filters.min_seats || '',
    max_price: props.filters.max_price || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || ''
})

const search = () => {
    router.get(route('vehicles.index'), searchForm.value, {
        preserveState: true,
        replace: true
    })
}

const clearFilters = () => {
    searchForm.value = {
        brand: '',
        city: '',
        fuel_type: '',
        transmission: '',
        min_seats: '',
        max_price: '',
        start_date: '',
        end_date: ''
    }
    search()
}

const getPrimaryImage = (vehicle: Vehicle) => {
    const primary = vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

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
</script>

<template>
    <Head title="Véhicules disponibles" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search Filters -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Rechercher un véhicule</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Marque</label>
                                <Input 
                                    v-model="searchForm.brand" 
                                    placeholder="Ex: Peugeot, Renault..."
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Ville</label>
                                <Input 
                                    v-model="searchForm.city" 
                                    placeholder="Ex: Paris, Lyon..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Date de début</label>
                                <Input 
                                    v-model="searchForm.start_date" 
                                    type="date"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Date de fin</label>
                                <Input 
                                    v-model="searchForm.end_date" 
                                    type="date"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Carburant</label>
                                <select 
                                    v-model="searchForm.fuel_type" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Tous</option>
                                    <option value="gasoline">Essence</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="electric">Électrique</option>
                                    <option value="hybrid">Hybride</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Transmission</label>
                                <select 
                                    v-model="searchForm.transmission" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Toutes</option>
                                    <option value="manual">Manuelle</option>
                                    <option value="automatic">Automatique</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Places min.</label>
                                <Input 
                                    v-model="searchForm.min_seats" 
                                    type="number" 
                                    min="1" 
                                    max="9"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Prix max. /jour</label>
                                <Input 
                                    v-model="searchForm.max_price" 
                                    type="number" 
                                    min="1"
                                    placeholder="€"
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
                        {{ props.vehicles.meta.total }} véhicule(s) trouvé(s)
                    </h2>
                    
                    <Link 
                        v-if="$page.props.auth.user"
                        :href="route('vehicles.create')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                    >
                        Ajouter mon véhicule
                    </Link>
                </div>

                <!-- Vehicles Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card v-for="vehicle in props.vehicles.data" :key="vehicle.id" class="overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="aspect-video relative">
                            <img 
                                :src="getPrimaryImage(vehicle)" 
                                :alt="`${vehicle.brand} ${vehicle.model}`"
                                class="w-full h-full object-cover"
                            />
                            <Badge class="absolute top-2 right-2 bg-green-500">
                                {{ formatPrice(vehicle.daily_rate) }}/jour
                            </Badge>
                        </div>
                        
                        <CardContent class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-lg">
                                    {{ vehicle.year }} {{ vehicle.brand }} {{ vehicle.model }}
                                </h3>
                                <div class="flex items-center text-sm text-gray-600">
                                    <span class="mr-1">⭐</span>
                                    {{ vehicle.rating || 'N/A' }}
                                </div>
                            </div>

                            <p class="text-gray-600 mb-3">{{ vehicle.city }}</p>

                            <div class="flex justify-between text-sm text-gray-600 mb-4">
                                <span>{{ fuelTypeLabels[vehicle.fuel_type] }}</span>
                                <span>{{ transmissionLabels[vehicle.transmission] }}</span>
                                <span>{{ vehicle.seats }} places</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    Par {{ vehicle.owner.name }}
                                </div>
                                <Link 
                                    :href="route('vehicles.show', vehicle.id)" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm"
                                >
                                    Voir détails
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-if="props.vehicles.data.length === 0" class="text-center py-12">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun véhicule trouvé</h3>
                    <p class="text-gray-600 mb-4">Essayez d'ajuster vos critères de recherche</p>
                    <Button @click="clearFilters">
                        Voir tous les véhicules
                    </Button>
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