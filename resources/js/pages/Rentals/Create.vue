<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import InputError from '@/components/InputError.vue'

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
    daily_rate: number
    weekly_rate?: number
    monthly_rate?: number
    city: string
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
    vehicle: Vehicle
    startDate?: string
    endDate?: string
}

const props = defineProps<Props>()

const form = useForm({
    vehicle_id: props.vehicle.id,
    start_date: props.startDate || '',
    end_date: props.endDate || '',
    special_requests: ''
})

const totalDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0
    
    const start = new Date(form.start_date)
    const end = new Date(form.end_date)
    const diffTime = Math.abs(end.getTime() - start.getTime())
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
})

const totalAmount = computed(() => {
    if (totalDays.value === 0) return 0
    
    const days = totalDays.value
    
    // Calculate based on available rates
    if (days >= 30 && props.vehicle.monthly_rate) {
        const months = Math.floor(days / 30)
        const remainingDays = days % 30
        return (months * props.vehicle.monthly_rate) + (remainingDays * props.vehicle.daily_rate)
    }
    
    if (days >= 7 && props.vehicle.weekly_rate) {
        const weeks = Math.floor(days / 7)
        const remainingDays = days % 7
        return (weeks * props.vehicle.weekly_rate) + (remainingDays * props.vehicle.daily_rate)
    }
    
    return days * props.vehicle.daily_rate
})

const deposit = computed(() => {
    return totalAmount.value * 0.2 // 20% deposit
})

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const getPrimaryImage = () => {
    const primary = props.vehicle.images.find(img => img.is_primary)
    return primary ? `/storage/${primary.image_path}` : '/images/car-placeholder.jpg'
}

const submit = () => {
    form.post(route('rentals.store'))
}

// Ensure end date is after start date
watch(() => form.start_date, (newStartDate) => {
    if (newStartDate && form.end_date && new Date(newStartDate) >= new Date(form.end_date)) {
        form.end_date = ''
    }
})

const minEndDate = computed(() => {
    if (!form.start_date) return ''
    const startDate = new Date(form.start_date)
    startDate.setDate(startDate.getDate() + 1)
    return startDate.toISOString().split('T')[0]
})

const today = new Date().toISOString().split('T')[0]
</script>

<template>
    <Head title="Réserver un véhicule" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Back button -->
                <div class="mb-6">
                    <Link 
                        :href="route('vehicles.show', vehicle.id)" 
                        class="text-blue-600 hover:text-blue-800 flex items-center"
                    >
                        ← Retour au véhicule
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Booking Form -->
                    <div class="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Réserver ce véhicule</CardTitle>
                                <p class="text-gray-600">Remplissez les informations de votre réservation</p>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submit" class="space-y-6">
                                    <!-- Dates -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <Label for="start_date">Date de début *</Label>
                                            <Input 
                                                id="start_date"
                                                v-model="form.start_date"
                                                type="date"
                                                :min="today"
                                                required
                                            />
                                            <InputError :message="form.errors.start_date" />
                                        </div>

                                        <div>
                                            <Label for="end_date">Date de fin *</Label>
                                            <Input 
                                                id="end_date"
                                                v-model="form.end_date"
                                                type="date"
                                                :min="minEndDate"
                                                required
                                            />
                                            <InputError :message="form.errors.end_date" />
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
                                            placeholder="Avez-vous des demandes particulières ? (siège bébé, GPS, point de rendez-vous spécifique, etc.)"
                                        />
                                        <InputError :message="form.errors.special_requests" />
                                    </div>

                                    <!-- Terms and conditions -->
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h4 class="font-medium mb-2">Conditions de location :</h4>
                                        <ul class="text-sm text-gray-600 space-y-1">
                                            <li>• Un acompte de 20% est requis pour confirmer la réservation</li>
                                            <li>• Le véhicule doit être rendu avec le même niveau de carburant</li>
                                            <li>• Les dommages constatés seront facturés au locataire</li>
                                            <li>• L'annulation est possible jusqu'à 24h avant le début de la location</li>
                                            <li>• Une pièce d'identité et un permis de conduire valides sont requis</li>
                                        </ul>
                                    </div>

                                    <!-- Submit -->
                                    <div class="flex justify-end space-x-4">
                                        <Button 
                                            type="button" 
                                            variant="outline"
                                            @click="$inertia.visit(route('vehicles.show', vehicle.id))"
                                        >
                                            Annuler
                                        </Button>
                                        <Button 
                                            type="submit" 
                                            :disabled="form.processing || totalDays === 0"
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
                                <!-- Vehicle Info -->
                                <div class="flex space-x-3">
                                    <img 
                                        :src="getPrimaryImage()"
                                        :alt="`${vehicle.brand} ${vehicle.model}`"
                                        class="w-16 h-16 object-cover rounded-lg"
                                    />
                                    <div class="flex-1">
                                        <h3 class="font-medium">
                                            {{ vehicle.year }} {{ vehicle.brand }} {{ vehicle.model }}
                                        </h3>
                                        <p class="text-sm text-gray-600">{{ vehicle.city }}</p>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <span class="mr-1">⭐</span>
                                            {{ vehicle.owner.rating || 'N/A' }}
                                            <span class="ml-2">{{ vehicle.owner.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Dates Summary -->
                                <div v-if="form.start_date && form.end_date">
                                    <h4 class="font-medium mb-2">Dates de location</h4>
                                    <div class="text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span>Du :</span>
                                            <span>{{ new Date(form.start_date).toLocaleDateString('fr-FR') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Au :</span>
                                            <span>{{ new Date(form.end_date).toLocaleDateString('fr-FR') }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium">
                                            <span>Durée :</span>
                                            <span>{{ totalDays }} jour(s)</span>
                                        </div>
                                    </div>
                                </div>

                                <Separator v-if="form.start_date && form.end_date" />

                                <!-- Price Breakdown -->
                                <div v-if="totalDays > 0">
                                    <h4 class="font-medium mb-2">Détail des prix</h4>
                                    <div class="text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span>Prix de base :</span>
                                            <span>{{ formatPrice(vehicle.daily_rate) }}/jour</span>
                                        </div>
                                        
                                        <!-- Weekly rate if applicable -->
                                        <div 
                                            v-if="totalDays >= 7 && vehicle.weekly_rate"
                                            class="flex justify-between text-green-600"
                                        >
                                            <span>Tarif semaine :</span>
                                            <span>{{ formatPrice(vehicle.weekly_rate) }}/semaine</span>
                                        </div>
                                        
                                        <!-- Monthly rate if applicable -->
                                        <div 
                                            v-if="totalDays >= 30 && vehicle.monthly_rate"
                                            class="flex justify-between text-green-600"
                                        >
                                            <span>Tarif mois :</span>
                                            <span>{{ formatPrice(vehicle.monthly_rate) }}/mois</span>
                                        </div>

                                        <Separator />
                                        
                                        <div class="flex justify-between font-medium">
                                            <span>Sous-total :</span>
                                            <span>{{ formatPrice(totalAmount) }}</span>
                                        </div>
                                        
                                        <div class="flex justify-between text-sm text-gray-600">
                                            <span>Acompte (20%) :</span>
                                            <span>{{ formatPrice(deposit) }}</span>
                                        </div>
                                        
                                        <div class="flex justify-between text-sm text-gray-600">
                                            <span>Reste à payer :</span>
                                            <span>{{ formatPrice(totalAmount - deposit) }}</span>
                                        </div>

                                        <Separator />
                                        
                                        <div class="flex justify-between text-lg font-bold">
                                            <span>Total :</span>
                                            <span>{{ formatPrice(totalAmount) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- No dates selected -->
                                <div v-else class="text-center text-gray-500 py-8">
                                    <p>Sélectionnez vos dates pour voir le prix</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>