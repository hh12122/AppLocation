<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogFooter 
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { ref } from 'vue'

interface Rental {
    id: number
    start_date: string
    end_date: string
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled'
    total_amount: number
    total_days: number
    created_at: string
    renter: {
        id: number
        name: string
        rating: number
        phone?: string
    }
    vehicle: {
        id: number
        brand: string
        model: string
        year: number
        mileage: number
        images: Array<{
            id: number
            image_path: string
            is_primary: boolean
        }>
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

const confirmForm = useForm({})
const cancelForm = useForm({})

const pickupForm = useForm({
    pickup_mileage: 0,
    pickup_notes: ''
})

const returnForm = useForm({
    return_mileage: 0,
    return_notes: ''
})

const showPickupDialog = ref(false)
const showReturnDialog = ref(false)
const selectedRental = ref<Rental | null>(null)

const openPickupDialog = (rental: Rental) => {
    selectedRental.value = rental
    pickupForm.pickup_mileage = rental.vehicle.mileage
    showPickupDialog.value = true
}

const openReturnDialog = (rental: Rental) => {
    selectedRental.value = rental
    returnForm.return_mileage = rental.vehicle.mileage
    showReturnDialog.value = true
}

const submitPickup = () => {
    if (selectedRental.value) {
        pickupForm.post(route('rentals.pickup', selectedRental.value.id), {
            onSuccess: () => {
                showPickupDialog.value = false
            }
        })
    }
}

const submitReturn = () => {
    if (selectedRental.value) {
        returnForm.post(route('rentals.return', selectedRental.value.id), {
            onSuccess: () => {
                showReturnDialog.value = false
            }
        })
    }
}

const confirmBooking = (id: number) => {
    if (confirm('Voulez-vous confirmer cette réservation ?')) {
        confirmForm.post(route('rentals.confirm', id))
    }
}

const cancelBooking = (id: number) => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        cancelForm.post(route('rentals.cancel', id))
    }
}

// Calculate stats
const stats = {
    total: props.rentals.meta?.total || 0,
    pending: props.rentals.data.filter(r => r.status === 'pending').length,
    active: props.rentals.data.filter(r => r.status === 'active').length,
    revenue: props.rentals.data
        .filter(r => r.status === 'completed' || r.status === 'active')
        .reduce((sum, r) => sum + r.total_amount, 0)
}
</script>

<template>
    <Head title="Mes locations reçues" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Demandes de location</h1>
                    <p class="text-gray-600 mt-2">Gérez les réservations de vos véhicules</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                            <p class="text-sm text-gray-600">Total demandes</p>
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
                            <div class="text-2xl font-bold text-blue-600">{{ formatPrice(stats.revenue) }}</div>
                            <p class="text-sm text-gray-600">Revenus estimés</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Bookings List -->
                <div v-if="props.rentals.data.length > 0" class="space-y-6">
                    <Card v-for="rental in props.rentals.data" :key="rental.id">
                        <CardContent class="p-6">
                            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
                                <!-- Vehicle Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(rental.vehicle)"
                                        :alt="`${rental.vehicle.brand} ${rental.vehicle.model}`"
                                        class="w-full md:w-48 h-32 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Booking Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                                            </h3>
                                            <p class="text-gray-600 mt-1">
                                                Locataire: <span class="font-medium">{{ rental.renter.name }}</span>
                                                <span class="ml-2 inline-flex items-center">
                                                    <span class="mr-1">⭐</span>
                                                    {{ rental.renter.rating || 'N/A' }}
                                                </span>
                                            </p>
                                            <div class="mt-3 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Du:</strong> {{ formatDate(rental.start_date) }}
                                                    <strong class="ml-4">Au:</strong> {{ formatDate(rental.end_date) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Durée:</strong> {{ rental.total_days }} jour(s)
                                                    <strong class="ml-4">Revenu:</strong> {{ formatPrice(rental.total_amount) }}
                                                </p>
                                                <p class="text-sm text-gray-600 italic">
                                                    Demandé le {{ formatDate(rental.created_at) }}
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
                                    <div class="flex flex-wrap gap-4 mt-6">
                                        <Link
                                            :href="route('rentals.show', rental.id)"
                                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 text-sm font-medium transition"
                                        >
                                            Gérer la location
                                        </Link>

                                        <template v-if="rental.status === 'pending'">
                                            <Button
                                                @click="confirmBooking(rental.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700"
                                                :disabled="confirmForm.processing"
                                            >
                                                Confirmer
                                            </Button>
                                            <Button
                                                @click="cancelBooking(rental.id)"
                                                variant="destructive"
                                                size="sm"
                                                :disabled="cancelForm.processing"
                                            >
                                                Refuser
                                            </Button>
                                        </template>

                                        <template v-if="rental.status === 'confirmed'">
                                            <Button
                                                @click="openPickupDialog(rental)"
                                                variant="default"
                                                size="sm"
                                                class="bg-blue-600 hover:bg-blue-700"
                                            >
                                                Enregistrer départ
                                            </Button>
                                        </template>

                                        <template v-if="rental.status === 'active'">
                                            <Button
                                                @click="openReturnDialog(rental)"
                                                variant="default"
                                                size="sm"
                                                class="bg-indigo-600 hover:bg-indigo-700"
                                            >
                                                Enregistrer retour
                                            </Button>
                                        </template>

                                        <Link
                                            v-if="['confirmed', 'active', 'completed'].includes(rental.status)"
                                            :href="route('rentals.contract', rental.id)"
                                            target="_blank"
                                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center"
                                        >
                                            📄 Contrat PDF
                                        </Link>

                                        <button
                                            v-if="['confirmed', 'active'].includes(rental.status)"
                                            @click="router.post(route('chat.create-rental', rental.id))"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center"
                                        >
                                            💬 Chat
                                        </button>

                                        <a
                                            v-if="rental.renter.phone && ['confirmed', 'active'].includes(rental.status)"
                                            :href="`tel:${rental.renter.phone}`"
                                            class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center"
                                        >
                                            📞 Appeler
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-lg shadow">
                    <div class="text-gray-400 text-6xl mb-4">🚗</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune demande reçue</h3>
                    <p class="text-gray-600">
                        Vous n'avez pas encore reçu de demandes de location pour vos véhicules.
                    </p>
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

        <!-- Pickup Dialog -->
        <Dialog :open="showPickupDialog" @update:open="showPickupDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Enregistrer le départ</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="pickup_mileage">Kilométrage actuel (km)</Label>
                        <Input 
                            id="pickup_mileage" 
                            type="number" 
                            v-model="pickupForm.pickup_mileage" 
                            :error="pickupForm.errors.pickup_mileage"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="pickup_notes">Notes (état du véhicule, etc.)</Label>
                        <Textarea 
                            id="pickup_notes" 
                            v-model="pickupForm.pickup_notes" 
                            placeholder="Ex: Petite rayure sur l'aile gauche..."
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showPickupDialog = false">Annuler</Button>
                    <Button @click="submitPickup" :disabled="pickupForm.processing">
                        {{ pickupForm.processing ? 'Chargement...' : 'Confirmer le départ' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Return Dialog -->
        <Dialog :open="showReturnDialog" @update:open="showReturnDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Enregistrer le retour</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="return_mileage">Nouveau kilométrage (km)</Label>
                        <Input 
                            id="return_mileage" 
                            type="number" 
                            v-model="returnForm.return_mileage" 
                            :error="returnForm.errors.return_mileage"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="return_notes">Notes de retour</Label>
                        <Textarea 
                            id="return_notes" 
                            v-model="returnForm.return_notes" 
                            placeholder="Ex: Véhicule rendu propre..."
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showReturnDialog = false">Annuler</Button>
                    <Button @click="submitReturn" :disabled="returnForm.processing">
                        {{ returnForm.processing ? 'Chargement...' : 'Confirmer le retour' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
