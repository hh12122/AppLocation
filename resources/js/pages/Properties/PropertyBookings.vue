<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
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

interface PropertyBooking {
    id: number
    checkin_date: string
    checkout_date: string
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled'
    total_amount: number
    nights_count: number
    guests_count: number
    created_at: string
    guest: {
        id: number
        name: string
        rating?: number
        phone?: string
    }
    property: {
        id: number
        title: string
        property_type: string
        city: string
        primary_image?: {
            id: number
            image_path: string
        }
    }
}

interface Props {
    bookings: {
        data: PropertyBooking[]
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

const getPrimaryImage = (property: PropertyBooking['property']) => {
    return property.primary_image ? `/storage/${property.primary_image.image_path}` : '/images/property-placeholder.jpg'
}

const confirmForm = useForm({})
const cancelForm = useForm({
    reason: ''
})

const checkinForm = useForm({
    notes: '',
    late_arrival: false
})

const checkoutForm = useForm({
    notes: '',
    cleaning_status: 'good'
})

const showCheckinDialog = ref(false)
const showCheckoutDialog = ref(false)
const showCancelDialog = ref(false)
const selectedBooking = ref<PropertyBooking | null>(null)

const confirmBooking = (id: number) => {
    if (confirm('Voulez-vous confirmer cette réservation ?')) {
        confirmForm.post(route('property-bookings.confirm', id))
    }
}

const openCancelDialog = (booking: PropertyBooking) => {
    selectedBooking.value = booking
    showCancelDialog.value = true
}

const submitCancel = () => {
    if (selectedBooking.value) {
        cancelForm.post(route('property-bookings.cancel', selectedBooking.value.id), {
            onSuccess: () => {
                showCancelDialog.value = false
                cancelForm.reset()
            }
        })
    }
}

const openCheckinDialog = (booking: PropertyBooking) => {
    selectedBooking.value = booking
    showCheckinDialog.value = true
}

const openCheckoutDialog = (booking: PropertyBooking) => {
    selectedBooking.value = booking
    showCheckoutDialog.value = true
}

const submitCheckin = () => {
    if (selectedBooking.value) {
        checkinForm.post(route('property-bookings.checkin', selectedBooking.value.id), {
            onSuccess: () => {
                showCheckinDialog.value = false
            }
        })
    }
}

const submitCheckout = () => {
    if (selectedBooking.value) {
        checkoutForm.post(route('property-bookings.checkout', selectedBooking.value.id), {
            onSuccess: () => {
                showCheckoutDialog.value = false
            }
        })
    }
}
</script>

<template>
    <Head title="Demandes de réservation (Propriétés)" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Demandes de réservation</h1>
                    <p class="text-gray-600 mt-2">Gérez les séjours dans vos propriétés</p>
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
                                <!-- Property Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        :src="getPrimaryImage(booking.property)"
                                        :alt="booking.property.title"
                                        class="w-full md:w-48 h-32 object-cover rounded-lg"
                                    />
                                </div>

                                <!-- Booking Info -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold">
                                                {{ booking.property.title }}
                                            </h3>
                                            <p class="text-gray-600 mt-1">
                                                Voyageur: <span class="font-medium">{{ booking.guest.name }}</span>
                                                <span v-if="booking.guest.rating" class="ml-2 inline-flex items-center">
                                                    <span class="mr-1">⭐</span>
                                                    {{ booking.guest.rating }}
                                                </span>
                                            </p>
                                            <div class="mt-3 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    <strong>Arrivée:</strong> {{ formatDate(booking.checkin_date) }}
                                                    <strong class="ml-4">Départ:</strong> {{ formatDate(booking.checkout_date) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <strong>Nuits:</strong> {{ booking.nights_count }}
                                                    <strong class="ml-4">Voyageurs:</strong> {{ booking.guests_count }}
                                                </p>
                                                <p class="text-sm font-semibold text-gray-900 mt-2">
                                                    Total: {{ formatPrice(booking.total_amount) }}
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
                                            :href="route('property-bookings.show', booking.id)"
                                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 text-sm font-medium transition"
                                        >
                                            Voir détails
                                        </Link>

                                        <template v-if="booking.status === 'pending'">
                                            <Button
                                                @click="confirmBooking(booking.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700"
                                                :disabled="confirmForm.processing"
                                            >
                                                Accepter
                                            </Button>
                                            <Button
                                                @click="openCancelDialog(booking)"
                                                variant="destructive"
                                                size="sm"
                                            >
                                                Refuser
                                            </Button>
                                        </template>

                                        <template v-if="booking.status === 'confirmed'">
                                            <Button
                                                @click="openCheckinDialog(booking)"
                                                variant="default"
                                                size="sm"
                                                class="bg-blue-600 hover:bg-blue-700"
                                            >
                                                Enregistrer Arrivée
                                            </Button>
                                            <Button
                                                @click="openCancelDialog(booking)"
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 border-red-600 hover:bg-red-50"
                                            >
                                                Annuler
                                            </Button>
                                        </template>

                                        <template v-if="booking.status === 'active'">
                                            <Button
                                                @click="openCheckoutDialog(booking)"
                                                variant="default"
                                                size="sm"
                                                class="bg-indigo-600 hover:bg-indigo-700"
                                            >
                                                Enregistrer Départ
                                            </Button>
                                        </template>

                                        <Link
                                            :href="route('chat.index')"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center"
                                        >
                                            💬 Contacter le voyageur
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-lg shadow">
                    <div class="text-gray-400 text-6xl mb-4">🏠</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune demande reçue</h3>
                    <p class="text-gray-600">
                        Vous n'avez pas encore reçu de demandes pour vos propriétés.
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

        <!-- Cancel Dialog -->
        <Dialog :open="showCancelDialog" @update:open="showCancelDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Annuler la réservation</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="reason">Motif de l'annulation</Label>
                        <Textarea 
                            id="reason" 
                            v-model="cancelForm.reason" 
                            placeholder="Veuillez indiquer la raison de l'annulation..."
                            required
                        />
                        <div v-if="cancelForm.errors.reason" class="text-sm text-red-600">
                            {{ cancelForm.errors.reason }}
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCancelDialog = false">Fermer</Button>
                    <Button 
                        variant="destructive" 
                        @click="submitCancel" 
                        :disabled="cancelForm.processing || !cancelForm.reason"
                    >
                        {{ cancelForm.processing ? 'Annulation...' : 'Confirmer l\'annulation' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Checkin Dialog -->
        <Dialog :open="showCheckinDialog" @update:open="showCheckinDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Enregistrer l'arrivée</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="checkin_notes">Notes d'arrivée</Label>
                        <Textarea 
                            id="checkin_notes" 
                            v-model="checkinForm.notes" 
                            placeholder="État des lieux, remise des clés..."
                        />
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="late_arrival" v-model="checkinForm.late_arrival" class="rounded border-gray-300" />
                        <Label for="late_arrival">Arrivée tardive</Label>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCheckinDialog = false">Annuler</Button>
                    <Button @click="submitCheckin" :disabled="checkinForm.processing">
                        {{ checkinForm.processing ? 'Chargement...' : 'Confirmer l\'arrivée' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Checkout Dialog -->
        <Dialog :open="showCheckoutDialog" @update:open="showCheckoutDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Enregistrer le départ</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="checkout_notes">Notes de départ</Label>
                        <Textarea 
                            id="checkout_notes" 
                            v-model="checkoutForm.notes" 
                            placeholder="Commentaires sur le séjour, état de sortie..."
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="cleaning_status">État de propreté</Label>
                        <select id="cleaning_status" v-model="checkoutForm.cleaning_status" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="excellent">Excellent</option>
                            <option value="good">Bon</option>
                            <option value="needs_attention">À revoir</option>
                            <option value="poor">Médiocre</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCheckoutDialog = false">Annuler</Button>
                    <Button @click="submitCheckout" :disabled="checkoutForm.processing">
                        {{ checkoutForm.processing ? 'Chargement...' : 'Confirmer le départ' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
