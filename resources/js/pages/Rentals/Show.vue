<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

interface Rental {
    id: number
    start_date: string
    end_date: string
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled'
    total_amount: number
    daily_rate: number
    total_days: number
    deposit: number
    special_requests?: string
    pickup_datetime?: string
    return_datetime?: string
    pickup_mileage?: number
    return_mileage?: number
    pickup_notes?: string
    return_notes?: string
    payment_status: string
    vehicle: {
        id: number
        brand: string
        model: string
        year: number
        license_plate: string
        images: Array<{
            id: number
            image_path: string
            is_primary: boolean
        }>
        owner: {
            id: number
            name: string
            phone?: string
            rating: number
        }
    }
    renter: {
        id: number
        name: string
        phone?: string
        rating: number
    }
}

interface Props {
    rental: Rental
    canReview: boolean
}

const props = defineProps<Props>()

const confirmForm = useForm({})
const cancelForm = useForm({})

const isOwner = computed(() => {
    return props.rental.vehicle.owner.id === (window as any).$page?.props?.auth?.user?.id
})

const isRenter = computed(() => {
    return props.rental.renter.id === (window as any).$page?.props?.auth?.user?.id
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

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const formatDateTime = (dateTime: string) => {
    return new Date(dateTime).toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getPrimaryImage = () => {
    const primary = props.rental.vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

const confirmRental = () => {
    confirmForm.post(route('rentals.confirm', props.rental.id))
}

const cancelRental = () => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        cancelForm.post(route('rentals.cancel', props.rental.id))
    }
}

const canConfirm = computed(() => {
    return isOwner.value && props.rental.status === 'pending'
})

const canCancel = computed(() => {
    return (isOwner.value || isRenter.value) && ['pending', 'confirmed'].includes(props.rental.status)
})
</script>

<template>
    <Head :title="`Réservation #${rental.id}`" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Back button -->
                <div class="mb-6">
                    <Link 
                        :href="isRenter ? route('rentals.my') : route('rentals.bookings')" 
                        class="text-blue-600 hover:text-blue-800 flex items-center"
                    >
                        ← Retour aux {{ isRenter ? 'réservations' : 'demandes' }}
                    </Link>
                </div>

                <!-- Header -->
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            Réservation #{{ rental.id }}
                        </h1>
                        <p class="text-gray-600 mt-2">
                            {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                        </p>
                    </div>
                    <Badge :class="statusColors[rental.status]">
                        {{ statusLabels[rental.status] }}
                    </Badge>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Vehicle Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Véhicule</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex space-x-4">
                                    <img 
                                        :src="getPrimaryImage()"
                                        :alt="`${rental.vehicle.brand} ${rental.vehicle.model}`"
                                        class="w-24 h-24 object-cover rounded-lg"
                                    />
                                    <div class="flex-1">
                                        <h3 class="text-xl font-semibold">
                                            {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                                        </h3>
                                        <p class="text-gray-600">{{ rental.vehicle.license_plate }}</p>
                                        <Link 
                                            :href="route('vehicles.show', rental.vehicle.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            Voir le véhicule →
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Rental Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Détails de la location</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Date de début</h4>
                                        <p class="text-gray-600">{{ formatDate(rental.start_date) }}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Date de fin</h4>
                                        <p class="text-gray-600">{{ formatDate(rental.end_date) }}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Durée</h4>
                                        <p class="text-gray-600">{{ rental.total_days }} jour(s)</p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Prix par jour</h4>
                                        <p class="text-gray-600">{{ formatPrice(rental.daily_rate) }}</p>
                                    </div>
                                </div>

                                <div v-if="rental.special_requests">
                                    <h4 class="font-medium text-gray-900">Demandes spéciales</h4>
                                    <p class="text-gray-600">{{ rental.special_requests }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Pickup/Return Info -->
                        <Card v-if="rental.pickup_datetime || rental.return_datetime">
                            <CardHeader>
                                <CardTitle>Remise et retour</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="rental.pickup_datetime" class="border-l-4 border-green-500 pl-4">
                                    <h4 class="font-medium text-gray-900">Remise du véhicule</h4>
                                    <p class="text-sm text-gray-600">{{ formatDateTime(rental.pickup_datetime) }}</p>
                                    <div v-if="rental.pickup_mileage" class="text-sm text-gray-600">
                                        Kilométrage : {{ rental.pickup_mileage.toLocaleString() }} km
                                    </div>
                                    <div v-if="rental.pickup_notes" class="text-sm text-gray-600 mt-2">
                                        <strong>Notes :</strong> {{ rental.pickup_notes }}
                                    </div>
                                </div>

                                <div v-if="rental.return_datetime" class="border-l-4 border-blue-500 pl-4">
                                    <h4 class="font-medium text-gray-900">Retour du véhicule</h4>
                                    <p class="text-sm text-gray-600">{{ formatDateTime(rental.return_datetime) }}</p>
                                    <div v-if="rental.return_mileage" class="text-sm text-gray-600">
                                        Kilométrage : {{ rental.return_mileage.toLocaleString() }} km
                                        <span v-if="rental.pickup_mileage" class="ml-2">
                                            ({{ (rental.return_mileage - rental.pickup_mileage).toLocaleString() }} km parcourus)
                                        </span>
                                    </div>
                                    <div v-if="rental.return_notes" class="text-sm text-gray-600 mt-2">
                                        <strong>Notes :</strong> {{ rental.return_notes }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Contact Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations de contact</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Propriétaire</h4>
                                        <p class="text-gray-600">{{ rental.vehicle.owner.name }}</p>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <span class="mr-1">⭐</span>
                                            {{ rental.vehicle.owner.rating || 'N/A' }}
                                        </div>
                                        <p v-if="rental.vehicle.owner.phone" class="text-sm text-gray-600">
                                            {{ rental.vehicle.owner.phone }}
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Locataire</h4>
                                        <p class="text-gray-600">{{ rental.renter.name }}</p>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <span class="mr-1">⭐</span>
                                            {{ rental.renter.rating || 'N/A' }}
                                        </div>
                                        <p v-if="rental.renter.phone" class="text-sm text-gray-600">
                                            {{ rental.renter.phone }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <Card class="sticky top-6">
                            <CardHeader>
                                <CardTitle>Récapitulatif financier</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span>Sous-total :</span>
                                        <span>{{ formatPrice(rental.total_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600">
                                        <span>Acompte (20%) :</span>
                                        <span>{{ formatPrice(rental.deposit) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600">
                                        <span>Reste à payer :</span>
                                        <span>{{ formatPrice(rental.total_amount - rental.deposit) }}</span>
                                    </div>

                                    <Separator />

                                    <div class="flex justify-between font-bold text-lg">
                                        <span>Total :</span>
                                        <span>{{ formatPrice(rental.total_amount) }}</span>
                                    </div>

                                    <div class="text-sm text-gray-600">
                                        Statut paiement : {{ rental.payment_status }}
                                    </div>
                                </div>

                                <Separator />

                                <!-- Actions -->
                                <div class="space-y-3">
                                    <!-- Confirm button for owner -->
                                    <Button
                                        v-if="canConfirm"
                                        @click="confirmRental"
                                        :disabled="confirmForm.processing"
                                        class="w-full bg-green-600 hover:bg-green-700"
                                    >
                                        {{ confirmForm.processing ? 'Confirmation...' : 'Confirmer la réservation' }}
                                    </Button>

                                    <!-- Cancel button -->
                                    <Button
                                        v-if="canCancel"
                                        @click="cancelRental"
                                        :disabled="cancelForm.processing"
                                        variant="destructive"
                                        class="w-full"
                                    >
                                        {{ cancelForm.processing ? 'Annulation...' : 'Annuler la réservation' }}
                                    </Button>

                                    <!-- Review button -->
                                    <Link
                                        v-if="canReview"
                                        :href="route('reviews.create', rental.id)"
                                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-center block"
                                    >
                                        Laisser un avis
                                    </Link>

                                    <!-- Contact buttons -->
                                    <div v-if="rental.status === 'confirmed' || rental.status === 'active'" class="pt-2">
                                        <p class="text-sm text-gray-600 mb-2">Contacter :</p>
                                        <div class="space-y-2">
                                            <Button
                                                v-if="isRenter && rental.vehicle.owner.phone"
                                                variant="outline"
                                                size="sm"
                                                class="w-full"
                                                @click="window.open(`tel:${rental.vehicle.owner.phone}`)"
                                            >
                                                Appeler le propriétaire
                                            </Button>
                                            <Button
                                                v-if="isOwner && rental.renter.phone"
                                                variant="outline"
                                                size="sm"
                                                class="w-full"
                                                @click="window.open(`tel:${rental.renter.phone}`)"
                                            >
                                                Appeler le locataire
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>