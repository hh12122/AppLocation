<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Equipment {
  id: number
  name: string
  category: string
  description: string
  city: string
  owner: {
    id: number
    name: string
    email: string
    phone: string
    rating: number
    rating_count: number
  }
  images: Array<{
    id: number
    image_path: string
    is_primary: boolean
  }>
}

interface Booking {
  id: number
  equipment_id: number
  renter_id: number
  start_datetime: string
  end_datetime: string
  duration_value: number
  duration_unit: string
  unit_rate: number
  subtotal: number
  security_deposit: number
  cleaning_fee: number
  delivery_fee: number
  service_fee: number
  total_amount: number
  owner_payout: number
  fulfillment_type: string
  pickup_address: string
  delivery_address: string | null
  special_requests: string | null
  usage_purpose: string | null
  status: string
  payment_status: string
  created_at: string
  equipment: Equipment
  renter: {
    id: number
    name: string
    email: string
    phone: string
    rating: number
    rating_count: number
  }
}

interface Props {
  booking: Booking
}

const props = defineProps<Props>()

const user = computed(() => usePage().props.auth?.user)
const isOwner = computed(() => user.value?.id === props.booking.equipment.owner.id)
const isRenter = computed(() => user.value?.id === props.booking.renter_id)

const statusLabels: Record<string, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  preparing: 'En préparation',
  ready: 'Prête',
  delivered: 'Livrée/Récupérée',
  in_use: 'En cours',
  returned: 'Rendue',
  completed: 'Terminée',
  cancelled_renter: 'Annulée par le locataire',
  cancelled_owner: 'Annulée par le propriétaire',
  cancelled_admin: 'Annulée par l\'admin',
  damaged: 'Endommagée',
  lost: 'Perdue',
  dispute: 'Litige',
}

const statusColors: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  preparing: 'bg-indigo-100 text-indigo-800',
  ready: 'bg-cyan-100 text-cyan-800',
  delivered: 'bg-green-100 text-green-800',
  in_use: 'bg-emerald-100 text-emerald-800',
  returned: 'bg-gray-100 text-gray-800',
  completed: 'bg-gray-100 text-gray-800',
  cancelled_renter: 'bg-red-100 text-red-800',
  cancelled_owner: 'bg-red-100 text-red-800',
  cancelled_admin: 'bg-red-100 text-red-800',
  damaged: 'bg-orange-100 text-orange-800',
  lost: 'bg-red-100 text-red-800',
  dispute: 'bg-purple-100 text-purple-800',
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getEquipmentImage = () => {
  if (!props.booking.equipment.images || props.booking.equipment.images.length === 0) {
    return '/images/equipment-placeholder.jpg'
  }
  const primary = props.booking.equipment.images.find(img => img.is_primary) || props.booking.equipment.images[0]
  return primary ? `/storage/${primary.image_path}` : '/images/equipment-placeholder.jpg'
}

const unitLabels: Record<string, string> = { hour: 'heure', day: 'jour', week: 'semaine', month: 'mois' }

const canConfirm = isOwner.value && props.booking.status === 'pending'
const canReady = isOwner.value && props.booking.status === 'confirmed'
const canDeliver = isOwner.value && props.booking.status === 'ready'
const canReturn = isOwner.value && props.booking.status === 'in_use'
const canCancel = isRenter.value && ['pending', 'confirmed'].includes(props.booking.status)

const cancelForm = useForm({ cancellation_reason: '' })
</script>

<template>
  <Head title="Réservation de matériel" />

  <AppLayout>
    <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <!-- Back -->
        <div class="mb-6">
          <Link
            :href="isOwner ? route('equipment-bookings.management') : route('equipment-bookings.my')"
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
          <Badge :class="statusColors[booking.status] || 'bg-gray-100 text-gray-800'" class="text-sm px-3 py-1">
            {{ statusLabels[booking.status] || booking.status }}
          </Badge>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Equipment Info -->
            <Card>
              <CardContent class="p-6">
                <div class="flex space-x-4">
                  <img
                    :src="getEquipmentImage()"
                    :alt="booking.equipment.name"
                    class="w-32 h-24 object-cover rounded-lg"
                  />
                  <div class="flex-1">
                    <Link
                      :href="route('equipment.show', booking.equipment.id)"
                      class="text-lg font-semibold text-blue-600 hover:text-blue-800"
                    >
                      {{ booking.equipment.name }}
                    </Link>
                    <p class="text-gray-600">{{ booking.equipment.city }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                      Propriétaire : {{ booking.equipment.owner.name }}
                      <span class="ml-2">⭐ {{ booking.equipment.owner.rating || 'N/A' }}</span>
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Booking Details -->
            <Card>
              <CardHeader>
                <CardTitle>Détails de la location</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500">Début</span>
                    <p class="font-medium">{{ formatDate(booking.start_datetime) }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500">Fin</span>
                    <p class="font-medium">{{ formatDate(booking.end_datetime) }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500">Durée</span>
                    <p class="font-medium">{{ booking.duration_value }} {{ unitLabels[booking.duration_unit] || booking.duration_unit }}(s)</p>
                  </div>
                  <div>
                    <span class="text-gray-500">Type</span>
                    <p class="font-medium">{{ booking.fulfillment_type === 'delivery' ? 'Livraison' : 'Retrait sur place' }}</p>
                  </div>
                </div>

                <div v-if="booking.pickup_address" class="text-sm">
                  <span class="text-gray-500">Adresse de retrait</span>
                  <p>{{ booking.pickup_address }}</p>
                </div>
                <div v-if="booking.delivery_address" class="text-sm">
                  <span class="text-gray-500">Adresse de livraison</span>
                  <p>{{ booking.delivery_address }}</p>
                </div>

                <Separator />

                <div v-if="booking.usage_purpose" class="text-sm">
                  <span class="text-gray-500">Motif</span>
                  <p>{{ booking.usage_purpose }}</p>
                </div>
                <div v-if="booking.special_requests" class="text-sm">
                  <span class="text-gray-500">Demandes spéciales</span>
                  <p>{{ booking.special_requests }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Renter Info (owner view) -->
            <Card v-if="isOwner">
              <CardHeader>
                <CardTitle>Locataire</CardTitle>
              </CardHeader>
              <CardContent class="text-sm space-y-2">
                <p><span class="text-gray-500">Nom :</span> {{ booking.renter.name }}</p>
                <p><span class="text-gray-500">Email :</span> {{ booking.renter.email }}</p>
                <p><span class="text-gray-500">Téléphone :</span> {{ booking.renter.phone }}</p>
                <p><span class="text-gray-500">Note :</span> ⭐ {{ booking.renter.rating || 'N/A' }} ({{ booking.renter.rating_count }} avis)</p>
              </CardContent>
            </Card>

            <!-- Owner Actions -->
            <Card v-if="isOwner && (canConfirm || canReady || canDeliver || canReturn)">
              <CardHeader>
                <CardTitle>Actions</CardTitle>
              </CardHeader>
              <CardContent class="flex flex-wrap gap-3">
                <form v-if="canConfirm" @submit.prevent="useForm({}).post(route('equipment-bookings.confirm', booking.id))">
                  <Button type="submit">Confirmer</Button>
                </form>
                <form v-if="canReady" @submit.prevent="useForm({}).post(route('equipment-bookings.ready', booking.id))">
                  <Button type="submit">Marquer prête</Button>
                </form>
                <form v-if="canDeliver" @submit.prevent="useForm({}).post(route('equipment-bookings.delivered', booking.id))">
                  <Button type="submit">Marquer livrée</Button>
                </form>
                <form v-if="canReturn" @submit.prevent="useForm({}).post(route('equipment-bookings.returned', booking.id))">
                  <Button type="submit">Marquer rendue</Button>
                </form>
              </CardContent>
            </Card>

            <!-- Cancel (renter) -->
            <Card v-if="canCancel">
              <CardHeader>
                <CardTitle>Annuler la réservation</CardTitle>
              </CardHeader>
              <CardContent>
                <form @submit.prevent="cancelForm.post(route('equipment-bookings.cancel', booking.id))" class="space-y-4">
                  <div>
                    <label for="cancel_reason" class="text-sm text-gray-500">Raison (optionnel)</label>
                    <textarea
                      id="cancel_reason"
                      v-model="cancelForm.cancellation_reason"
                      rows="2"
                      class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
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
                  <span class="text-gray-600">{{ formatPrice(booking.unit_rate) }} x {{ booking.duration_value }} {{ unitLabels[booking.duration_unit] || booking.duration_unit }}(s)</span>
                  <span>{{ formatPrice(booking.subtotal) }}</span>
                </div>
                <div v-if="booking.cleaning_fee > 0" class="flex justify-between text-gray-600">
                  <span>Frais de ménage</span>
                  <span>{{ formatPrice(booking.cleaning_fee) }}</span>
                </div>
                <div v-if="booking.delivery_fee > 0" class="flex justify-between text-gray-600">
                  <span>Frais de livraison</span>
                  <span>{{ formatPrice(booking.delivery_fee) }}</span>
                </div>
                <div v-if="booking.service_fee > 0" class="flex justify-between text-gray-600">
                  <span>Frais de service</span>
                  <span>{{ formatPrice(booking.service_fee) }}</span>
                </div>

                <Separator />

                <div class="flex justify-between font-bold text-lg">
                  <span>Total</span>
                  <span>{{ formatPrice(booking.total_amount) }}</span>
                </div>

                <div v-if="booking.security_deposit > 0" class="flex justify-between text-xs text-gray-500">
                  <span>Caution (remboursable)</span>
                  <span>{{ formatPrice(booking.security_deposit) }}</span>
                </div>
              </CardContent>
            </Card>

            <!-- Chat button -->
            <Card v-if="['confirmed', 'in_use', 'returned'].includes(booking.status)">
              <CardContent class="p-4">
                <Button
                  variant="outline"
                  class="w-full"
                  @click="router.post(route('chat.create-equipment-booking', booking.id))"
                >
                  Contacter {{ isOwner ? 'le locataire' : 'le propriétaire' }}
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
