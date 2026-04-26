<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'

interface Property {
    id: number
    title: string
    city: string
    nightly_rate: number
    images: Array<{
        id: number
        image_path: string
        is_primary: boolean
    }>
    owner: {
        id: number
        name: string
        email: string
        phone: string
        rating: number
    }
}

interface Booking {
    id: number
    checkin_date: string
    checkout_date: string
    nights_count: number
    guests_count: number
    adults_count: number
    children_count: number
    infants_count: number
    nightly_rate: number
    subtotal: number
    cleaning_fee: number
    service_fee: number
    tax_amount: number
    total_amount: number
    security_deposit: number
    status: string
    payment_status: string
    special_requests: string
    purpose_of_trip: string
    created_at: string
    property: Property
    guest: {
        id: number
        name: string
        email: string
    }
}

interface Props {
    booking: Booking
    isOwner: boolean
}

const props = defineProps<Props>()

const statusLabels: Record<string, string> = {
    pending: 'En attente',
    confirmed: 'Confirmée',
    checked_in: 'Arrivée effectuée',
    checked_out: 'Départ effectué',
    completed: 'Terminée',
    cancelled_guest: 'Annulée par le voyageur',
    cancelled_host: 'Annulée par l\'hôte',
    cancelled_admin: 'Annulée par l\'admin',
    no_show: 'Client non présenté',
    dispute: 'Litige',
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled_guest: 'bg-red-100 text-red-800',
    cancelled_host: 'bg-red-100 text-red-800',
    cancelled_admin: 'bg-red-100 text-red-800',
    no_show: 'bg-orange-100 text-orange-800',
    dispute: 'bg-purple-100 text-purple-800',
}

const paymentStatusLabels: Record<string, string> = {
    pending: 'En attente',
    authorized: 'Autorisé',
    paid: 'Payé',
    partially_refunded: 'Partiellement remboursé',
    refunded: 'Remboursé',
    failed: 'Échec',
}

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const getPrimaryImage = () => {
    if (!props.booking.property.images || props.booking.property.images.length === 0) {
        return '/images/property-placeholder.jpg'
    }
    const primary = props.booking.property.images.find(img => img.is_primary) || props.booking.property.images[0]
    return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
}

const canConfirm = props.isOwner && props.booking.status === 'pending'
const canCheckIn = props.isOwner && props.booking.status === 'confirmed'
const canCheckOut = props.isOwner && props.booking.status === 'checked_in'
const canCancel = !props.isOwner && ['pending', 'confirmed'].includes(props.booking.status)

const confirmForm = useForm({})
const checkinForm = useForm({})
const checkoutForm = useForm({})
const cancelForm = useForm({ reason: '' })
</script>

<template>
    <Head title="Détails de la réservation" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Back -->
                <div class="mb-6">
                    <Link
                        :href="isOwner ? route('property-bookings.management') : route('property-bookings.my')"
                        class="text-blue-600 hover:text-blue-800"
                    >
                        ← Retour aux réservations
                    </Link>
                </div>

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">Réservation #{{ booking.id }}</h1>
                        <p class="text-gray-600 mt-1">Créée le {{ formatDate(booking.created_at) }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Badge :class="statusColors[booking.status] || 'bg-gray-100 text-gray-800'">
                            {{ statusLabels[booking.status] || booking.status }}
                        </Badge>
                        <Badge variant="outline">
                            {{ paymentStatusLabels[booking.payment_status] || booking.payment_status }}
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Property Info -->
                        <Card>
                            <CardContent class="p-6">
                                <div class="flex space-x-4">
                                    <img
                                        :src="getPrimaryImage()"
                                        :alt="booking.property.title"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
                                    <div class="flex-1">
                                        <Link
                                            :href="route('properties.show', booking.property.id)"
                                            class="text-lg font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            {{ booking.property.title }}
                                        </Link>
                                        <p class="text-gray-600">{{ booking.property.city }}</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Propriétaire : {{ booking.property.owner.name }}
                                            <span class="ml-2">⭐ {{ booking.property.owner.rating || 'N/A' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Booking Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Détails du séjour</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Arrivée</span>
                                        <p class="font-medium">{{ formatDate(booking.checkin_date) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Départ</span>
                                        <p class="font-medium">{{ formatDate(booking.checkout_date) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Durée</span>
                                        <p class="font-medium">{{ booking.nights_count }} nuit(s)</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Voyageurs</span>
                                        <p class="font-medium">
                                            {{ booking.adults_count }} adulte(s)
                                            <span v-if="booking.children_count">, {{ booking.children_count }} enfant(s)</span>
                                            <span v-if="booking.infants_count">, {{ booking.infants_count }} bébé(s)</span>
                                        </p>
                                    </div>
                                </div>

                                <Separator />

                                <div v-if="booking.special_requests" class="text-sm">
                                    <span class="text-gray-500">Demandes spéciales</span>
                                    <p class="mt-1">{{ booking.special_requests }}</p>
                                </div>

                                <div v-if="booking.purpose_of_trip" class="text-sm">
                                    <span class="text-gray-500">Motif du séjour</span>
                                    <p class="mt-1">{{ booking.purpose_of_trip }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Guest Info (for owner) -->
                        <Card v-if="isOwner">
                            <CardHeader>
                                <CardTitle>Informations du voyageur</CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm space-y-2">
                                <p><span class="text-gray-500">Nom :</span> {{ booking.guest.name }}</p>
                                <p><span class="text-gray-500">Email :</span> {{ booking.guest.email }}</p>
                            </CardContent>
                        </Card>

                        <!-- Owner Actions -->
                        <Card v-if="isOwner && (canConfirm || canCheckIn || canCheckOut)">
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent class="flex flex-wrap gap-3">
                                <form v-if="canConfirm" @submit.prevent="confirmForm.post(route('property-bookings.confirm', booking.id))">
                                    <Button :disabled="confirmForm.processing">
                                        {{ confirmForm.processing ? 'Confirmation...' : 'Confirmer la réservation' }}
                                    </Button>
                                </form>
                                <form v-if="canCheckIn" @submit.prevent="checkinForm.post(route('property-bookings.checkin', booking.id))">
                                    <Button :disabled="checkinForm.processing">
                                        {{ checkinForm.processing ? 'Enregistrement...' : 'Enregistrer l\'arrivée' }}
                                    </Button>
                                </form>
                                <form v-if="canCheckOut" @submit.prevent="checkoutForm.post(route('property-bookings.checkout', booking.id))">
                                    <Button :disabled="checkoutForm.processing">
                                        {{ checkoutForm.processing ? 'Enregistrement...' : 'Enregistrer le départ' }}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <!-- Guest Cancel -->
                        <Card v-if="canCancel">
                            <CardHeader>
                                <CardTitle>Annuler la réservation</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="cancelForm.post(route('property-bookings.cancel', booking.id))" class="space-y-4">
                                    <div>
                                        <Label for="cancel_reason">Raison de l'annulation</Label>
                                        <Textarea id="cancel_reason" v-model="cancelForm.reason" rows="3" placeholder="Pourquoi souhaitez-vous annuler ?" class="mt-1" />
                                    </div>
                                    <Button variant="destructive" :disabled="cancelForm.processing">
                                        {{ cancelForm.processing ? 'Annulation...' : 'Annuler ma réservation' }}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Price Summary -->
                    <div class="lg:col-span-1">
                        <Card class="sticky top-6">
                            <CardHeader>
                                <CardTitle class="text-lg">Détail des prix</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ formatPrice(booking.nightly_rate) }} x {{ booking.nights_count }} nuit(s)</span>
                                    <span>{{ formatPrice(booking.subtotal) }}</span>
                                </div>
                                <div v-if="booking.cleaning_fee > 0" class="flex justify-between text-gray-600">
                                    <span>Frais de ménage</span>
                                    <span>{{ formatPrice(booking.cleaning_fee) }}</span>
                                </div>
                                <div v-if="booking.service_fee > 0" class="flex justify-between text-gray-600">
                                    <span>Frais de service</span>
                                    <span>{{ formatPrice(booking.service_fee) }}</span>
                                </div>
                                <div v-if="booking.tax_amount > 0" class="flex justify-between text-gray-600">
                                    <span>Taxes</span>
                                    <span>{{ formatPrice(booking.tax_amount) }}</span>
                                </div>

                                <Separator />

                                <div class="flex justify-between font-bold text-lg">
                                    <span>Total</span>
                                    <span>{{ formatPrice(booking.total_amount) }}</span>
                                </div>

                                <div v-if="booking.security_deposit > 0" class="flex justify-between text-xs text-gray-500">
                                    <span>Caution (non débitée)</span>
                                    <span>{{ formatPrice(booking.security_deposit) }}</span>
                                </div>

                                <Separator />

                                <!-- Payment link -->
                                <div v-if="!isOwner && booking.status === 'confirmed' && booking.payment_status !== 'paid'">
                                    <Link :href="route('property-payments.show', booking.id)" class="block">
                                        <Button class="w-full">Procéder au paiement</Button>
                                    </Link>
                                </div>

                                <!-- Chat button -->
                                <div v-if="['confirmed', 'checked_in', 'checked_out'].includes(booking.status)">
                                    <Button
                                        variant="outline"
                                        class="w-full mt-2"
                                        @click="router.post(route('chat.create-property-booking', booking.id))"
                                    >
                                        Contacter {{ isOwner ? 'le voyageur' : 'le propriétaire' }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
