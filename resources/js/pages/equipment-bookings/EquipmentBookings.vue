<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

interface EquipmentBooking {
    id: number
    start_date: string
    end_date: string
    status: 'pending' | 'confirmed' | 'ready_for_pickup' | 'in_use' | 'completed' | 'cancelled'
    total_amount: number
    total_with_fees: number
    quantity: number
    rental_unit: string
    duration: number
    created_at: string
    renter: {
        id: number
        name: string
        rating?: number
        rating_count?: number
    }
    equipment: {
        id: number
        name: string
        category: string
        images?: Array<{
            id: number
            image_path: string
            is_primary: boolean
        }>
    }
}

interface Props {
    bookings: {
        data: EquipmentBooking[]
        links: any[]
        meta: any
    }
    stats: {
        total: number
        pending: number
        active: number
        completed: number
    }
}

const props = defineProps<Props>()

const statusLabels = {
    pending: 'En attente',
    confirmed: 'Confirmée',
    ready_for_pickup: 'Prêt pour retrait',
    in_use: 'En cours',
    completed: 'Terminée',
    cancelled: 'Annulée'
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    ready_for_pickup: 'bg-purple-100 text-purple-800',
    in_use: 'bg-green-100 text-green-800',
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

const getPrimaryImage = (equipment: any) => {
    if (!equipment.images) return '/images/equipment-placeholder.jpg'
    const imagesArray = Array.isArray(equipment.images) ? equipment.images : Object.values(equipment.images)
    if (imagesArray.length === 0) return '/images/equipment-placeholder.jpg'
    const primary = imagesArray.find((img: any) => img.is_primary) || imagesArray[0]
    return primary ? `/storage/${primary.image_path}` : '/images/equipment-placeholder.jpg'
}

const confirmForm = useForm({})
const cancelForm = useForm({
    cancellation_reason: ''
})
const readyForm = useForm({})
const deliveredForm = useForm({})
const returnedForm = useForm({})

const confirmBooking = (id: number) => {
    if (confirm('Confirmer cette demande de location ?')) {
        confirmForm.post(route('equipment-bookings.confirm', id))
    }
}

const cancelBooking = (id: number) => {
    const reason = prompt('Motif de l\'annulation (facultatif) :')
    if (reason !== null) {
        cancelForm.cancellation_reason = reason
        cancelForm.post(route('equipment-bookings.cancel', id))
    }
}

const markAsReady = (id: number) => {
    readyForm.post(route('equipment-bookings.ready', id))
}

const markAsDelivered = (id: number) => {
    deliveredForm.post(route('equipment-bookings.delivered', id))
}

const markAsReturned = (id: number) => {
    returnedForm.post(route('equipment-bookings.returned', id))
}

const categoryLabels: Record<string, string> = {
    sports_equipment: 'Sport',
    tools_material: 'Outils & Matériel',
    boats: 'Bateaux',
    spaces: 'Espaces'
}
</script>

<template>
    <Head title="Demandes de location (Matériel)" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Demandes de location matériel</h1>
                    <p class="text-gray-600 mt-2">Gérez les locations de votre matériel et équipement</p>
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
                            <div class="text-2xl font-bold text-gray-600">{{ stats.completed }}</div>
                            <p class="text-sm text-gray-600">Terminées</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Bookings List -->
                <div v-if="props.bookings.data.length > 0" class="space-y-6">
                    <Card v-for="booking in props.bookings.data" :key="booking.id">
                        <CardContent class="p-6">
                            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
                                <!-- Equipment Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(booking.equipment)"
                                        :alt="booking.equipment.name"
                                        class="w-full md:w-48 h-32 object-cover rounded-lg"
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
                                                {{ categoryLabels[booking.equipment.category] || booking.equipment.category }}
                                            </p>
                                            <p class="text-gray-600 mt-1">
                                                Locataire: <span class="font-medium">{{ booking.renter.name }}</span>
                                                <span v-if="booking.renter.rating" class="ml-2 inline-flex items-center">
                                                    <span class="mr-1">⭐</span>
                                                    {{ booking.renter.rating }} ({{ booking.renter.rating_count }})
                                                </span>
                                            </p>
                                            <div class="mt-3 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Du:</strong> {{ formatDate(booking.start_date) }}
                                                    <strong class="ml-4">Au:</strong> {{ formatDate(booking.end_date) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Quantité:</strong> {{ booking.quantity }}
                                                    <strong class="ml-4">Durée:</strong> {{ booking.duration }} {{ booking.rental_unit }}(s)
                                                </p>
                                                <p class="text-sm font-semibold text-gray-900 mt-2">
                                                    Revenu: {{ formatPrice(booking.total_amount) }}
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
                                    <div class="flex flex-wrap gap-4 mt-6">
                                        <Link
                                            :href="route('equipment-bookings.show', booking.id)"
                                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 text-sm font-medium transition"
                                        >
                                            Détails
                                        </Link>

                                        <template v-if="booking.status === 'pending'">
                                            <Button
                                                @click="confirmBooking(booking.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700"
                                                :disabled="confirmForm.processing"
                                            >
                                                Confirmer
                                            </Button>
                                            <Button
                                                @click="cancelBooking(booking.id)"
                                                variant="destructive"
                                                size="sm"
                                                :disabled="cancelForm.processing"
                                            >
                                                Refuser
                                            </Button>
                                        </template>

                                        <template v-if="booking.status === 'confirmed'">
                                            <Button
                                                @click="markAsReady(booking.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-blue-600 hover:bg-blue-700"
                                                :disabled="readyForm.processing"
                                            >
                                                Marquer comme prêt
                                            </Button>
                                        </template>

                                        <template v-if="booking.status === 'ready_for_pickup'">
                                            <Button
                                                @click="markAsDelivered(booking.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-indigo-600 hover:bg-indigo-700"
                                                :disabled="deliveredForm.processing"
                                            >
                                                Confirmer Retrait/Livraison
                                            </Button>
                                        </template>

                                        <template v-if="booking.status === 'in_use'">
                                            <Button
                                                @click="markAsReturned(booking.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700"
                                                :disabled="returnedForm.processing"
                                            >
                                                Marquer comme rendu
                                            </Button>
                                        </template>

                                        <Link
                                            :href="route('chat.index')"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center"
                                        >
                                            💬 Message
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-lg shadow">
                    <div class="text-gray-400 text-6xl mb-4">🛠️</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune demande reçue</h3>
                    <p class="text-gray-600">
                        Vous n'avez pas encore reçu de demandes de location pour votre matériel.
                    </p>
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
