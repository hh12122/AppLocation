<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { FormError } from '@/components/ui/form'

interface Property {
    id: number
    title: string
    city: string
    nightly_rate: number
    max_guests: number
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

interface Pricing {
    nights: number
    nightly_rate: number
    subtotal: number
    cleaning_fee: number
    service_fee: number
    tax_amount: number
    total_amount: number
    security_deposit: number
}

interface Props {
    property: Property
    initialData?: {
        checkin_date: string | null
        checkout_date: string | null
        guests: number
        pricing: Pricing | null
    }
    availableCredits?: number
    referralStats?: any
}

const props = defineProps<Props>()

const form = useForm({
    checkin_date: props.initialData?.checkin_date || '',
    checkout_date: props.initialData?.checkout_date || '',
    guests_count: props.initialData?.guests || 1,
    adults_count: props.initialData?.guests || 1,
    children_count: 0,
    infants_count: 0,
    special_requests: '',
    purpose_of_trip: '',
    guest_details: []
})

const submit = () => {
    form.post(route('property-bookings.store', props.property.id))
}

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const getPrimaryImage = () => {
    if (!props.property.images || props.property.images.length === 0) return '/images/property-placeholder.jpg'
    const primary = props.property.images.find(img => img.is_primary) || props.property.images[0]
    return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
}

const minEndDate = computed(() => {
    if (!form.checkin_date) return ''
    const startDate = new Date(form.checkin_date)
    startDate.setDate(startDate.getDate() + 1)
    return startDate.toISOString().split('T')[0]
})

const today = new Date().toISOString().split('T')[0]

const nightsCount = computed(() => {
    if (!form.checkin_date || !form.checkout_date) return 0
    const start = new Date(form.checkin_date)
    const end = new Date(form.checkout_date)
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
    return diff > 0 ? diff : 0
})

const clientPricing = computed(() => {
    if (nightsCount.value === 0) return null
    const rate = props.property.nightly_rate
    const subtotal = rate * nightsCount.value
    const cleaningFee = 0
    const serviceFee = Math.round(subtotal * 0.05 * 100) / 100
    const taxAmount = Math.round(subtotal * 0.1 * 100) / 100
    const totalAmount = subtotal + cleaningFee + serviceFee + taxAmount
    return {
        nights: nightsCount.value,
        nightly_rate: rate,
        subtotal,
        cleaning_fee: cleaningFee,
        service_fee: serviceFee,
        tax_amount: taxAmount,
        total_amount: totalAmount,
        security_deposit: 0,
    }
})

const pricing = computed(() => props.initialData?.pricing || clientPricing.value)

watch(() => form.checkin_date, (newStartDate) => {
    if (newStartDate && form.checkout_date && new Date(newStartDate) >= new Date(form.checkout_date)) {
        form.checkout_date = ''
    }
})
</script>

<template>
    <Head title="Réserver cette propriété" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Back button -->
                <div class="mb-6">
                    <Link 
                        :href="route('properties.show', property.id)" 
                        class="text-blue-600 hover:text-blue-800 flex items-center"
                    >
                        ← Retour à la propriété
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Booking Form -->
                    <div class="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Réserver cette propriété</CardTitle>
                                <p class="text-gray-600">Remplissez les informations de votre réservation</p>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submit" class="space-y-6">
                                    <!-- Dates -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <Label for="checkin_date">Date d'arrivée *</Label>
                                            <Input 
                                                id="checkin_date"
                                                v-model="form.checkin_date"
                                                type="date"
                                                :min="today"
                                                required
                                            />
                                            <FormError :message="form.errors.checkin_date" />
                                        </div>

                                        <div>
                                            <Label for="checkout_date">Date de départ *</Label>
                                            <Input 
                                                id="checkout_date"
                                                v-model="form.checkout_date"
                                                type="date"
                                                :min="minEndDate"
                                                required
                                            />
                                            <FormError :message="form.errors.checkout_date" />
                                        </div>
                                    </div>

                                    <!-- Guests -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <Label for="adults_count">Adultes *</Label>
                                            <Input 
                                                id="adults_count"
                                                v-model.number="form.adults_count"
                                                type="number"
                                                min="1"
                                                :max="property.max_guests"
                                                required
                                                @input="form.guests_count = form.adults_count + form.children_count"
                                            />
                                            <FormError :message="form.errors.adults_count" />
                                        </div>
                                        <div>
                                            <Label for="children_count">Enfants</Label>
                                            <Input 
                                                id="children_count"
                                                v-model.number="form.children_count"
                                                type="number"
                                                min="0"
                                                :max="property.max_guests - form.adults_count"
                                                @input="form.guests_count = form.adults_count + form.children_count"
                                            />
                                            <FormError :message="form.errors.children_count" />
                                        </div>
                                        <div>
                                            <Label for="infants_count">Bébés</Label>
                                            <Input 
                                                id="infants_count"
                                                v-model.number="form.infants_count"
                                                type="number"
                                                min="0"
                                            />
                                            <FormError :message="form.errors.infants_count" />
                                        </div>
                                        <div class="md:col-span-3">
                                            <FormError :message="form.errors.guests_count" />
                                        </div>
                                    </div>

                                    <!-- Special Requests -->
                                    <div>
                                        <Label for="special_requests">Demandes spéciales</Label>
                                        <textarea 
                                            id="special_requests"
                                            v-model="form.special_requests"
                                            rows="4"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Avez-vous des demandes particulières ? (heures d'arrivée spécifiques, allergies, etc.)"
                                        />
                                        <FormError :message="form.errors.special_requests" />
                                    </div>

                                    <!-- Purpose of trip -->
                                    <div>
                                        <Label for="purpose_of_trip">Motif du séjour</Label>
                                        <Input 
                                            id="purpose_of_trip"
                                            v-model="form.purpose_of_trip"
                                            placeholder="ex: Vacances en famille, Déplacement professionnel..."
                                        />
                                        <FormError :message="form.errors.purpose_of_trip" />
                                    </div>

                                    <!-- Submit -->
                                    <div class="flex justify-end space-x-4">
                                        <Button 
                                            type="button" 
                                            variant="outline"
                                            @click="$inertia.visit(route('properties.show', property.id))"
                                        >
                                            Annuler
                                        </Button>
                                        <Button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="bg-blue-600 hover:bg-blue-700"
                                        >
                                            {{ form.processing ? 'Réservation en cours...' : 'Confirmer la réservation' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Booking Summary -->
                    <div class="lg:col-span-1">
                        <Card class="sticky top-6">
                            <CardHeader>
                                <CardTitle class="text-lg">Récapitulatif</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Property Info -->
                                <div class="flex space-x-3">
                                    <img 
                                        :src="getPrimaryImage()"
                                        :alt="property.title"
                                        class="w-20 h-20 object-cover rounded-lg"
                                    />
                                    <div class="flex-1">
                                        <h3 class="font-medium text-sm">
                                            {{ property.title }}
                                        </h3>
                                        <p class="text-xs text-gray-600 mt-1">{{ property.city }}</p>
                                        <div class="flex items-center text-xs text-gray-600 mt-1">
                                            <span class="mr-1">⭐</span>
                                            {{ property.owner.rating || 'N/A' }}
                                            <span class="ml-2 truncate">{{ property.owner.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Dates Summary -->
                                <div v-if="form.checkin_date && form.checkout_date">
                                    <h4 class="font-medium mb-2">Détails de la réservation</h4>
                                    <div class="text-sm space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Arrivée</span>
                                            <span>{{ new Date(form.checkin_date).toLocaleDateString('fr-FR') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Départ</span>
                                            <span>{{ new Date(form.checkout_date).toLocaleDateString('fr-FR') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Voyageurs</span>
                                            <span>{{ form.guests_count }} voyageur(s)</span>
                                        </div>
                                    </div>
                                </div>

                                <Separator v-if="pricing" />

                                <!-- Price Breakdown -->
                                <div v-if="pricing">
                                    <h4 class="font-medium mb-2">Détail des prix</h4>
                                    <div class="text-sm space-y-2">
                                        <div class="flex justify-between text-gray-600">
                                            <span>{{ formatPrice(pricing.nightly_rate) }} x {{ pricing.nights }} nuit(s)</span>
                                            <span>{{ formatPrice(pricing.subtotal) }}</span>
                                        </div>

                                        <div class="flex justify-between text-gray-600" v-if="pricing.cleaning_fee > 0">
                                            <span>Frais de ménage</span>
                                            <span>{{ formatPrice(pricing.cleaning_fee) }}</span>
                                        </div>

                                        <div class="flex justify-between text-gray-600" v-if="pricing.service_fee > 0">
                                            <span>Frais de service</span>
                                            <span>{{ formatPrice(pricing.service_fee) }}</span>
                                        </div>

                                        <div class="flex justify-between text-gray-600" v-if="pricing.tax_amount > 0">
                                            <span>Taxes</span>
                                            <span>{{ formatPrice(pricing.tax_amount) }}</span>
                                        </div>

                                        <Separator />

                                        <div class="flex justify-between font-bold text-lg">
                                            <span>Total</span>
                                            <span>{{ formatPrice(pricing.total_amount) }}</span>
                                        </div>

                                        <div class="flex justify-between text-xs text-gray-500 mt-2" v-if="pricing.security_deposit > 0">
                                            <span>Caution (non débitée)</span>
                                            <span>{{ formatPrice(pricing.security_deposit) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- No dates selected -->
                                <div v-else class="text-center text-sm text-gray-500 py-4 bg-gray-50 rounded-lg">
                                    <p>Saisissez vos dates pour voir le prix total</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
