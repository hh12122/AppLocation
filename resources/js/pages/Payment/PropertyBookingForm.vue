<template>
  <AppLayout title="Paiement de réservation">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Paiement de réservation
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ booking.property.title }}
          </p>
        </div>
        <Link
          :href="`/property-bookings/${booking.id}`"
          class="text-blue-600 hover:text-blue-500 text-sm font-medium"
        >
          &larr; Retour à la réservation
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Payment Form -->
          <div class="lg:col-span-2">
            <PaymentForm :booking="booking" :available-credits="availableCredits" :referral-stats="referralStats" payable-type="property_booking" />
          </div>

          <!-- Booking Summary -->
          <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Résumé de la réservation
              </h3>

              <!-- Property Info -->
              <div class="flex items-center space-x-4 mb-6">
                <div class="flex-shrink-0">
                  <div v-if="booking.property.images && booking.property.images.length > 0">
                    <img
                      :src="getPrimaryImage()"
                      :alt="booking.property.title"
                      class="w-16 h-16 object-cover rounded-lg"
                    >
                  </div>
                  <div v-else class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ booking.property.title }}
                  </h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ booking.property.property_type }} &bull; {{ booking.nights_count }} nuit{{ booking.nights_count > 1 ? 's' : '' }}
                  </p>
                </div>
              </div>

              <!-- Booking Details -->
              <div class="space-y-3 mb-6">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Dates :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatDate(booking.checkin_date) }} - {{ formatDate(booking.checkout_date) }}
                  </span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Voyageurs :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ booking.guests_count }}
                  </span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Tarif / nuit :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatAmount(booking.nightly_rate * 100) }}
                  </span>
                </div>

                <div v-if="booking.cleaning_fee > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Frais de ménage :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatAmount(booking.cleaning_fee * 100) }}
                  </span>
                </div>

                <hr class="border-gray-200 dark:border-gray-600">

                <div class="flex justify-between">
                  <span class="font-medium text-gray-900 dark:text-gray-100">Total :</span>
                  <span class="font-bold text-lg text-gray-900 dark:text-gray-100">
                    {{ formatAmount(booking.total_amount * 100) }}
                  </span>
                </div>
              </div>

              <!-- Host Contact -->
              <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                  Hôte
                </h4>
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">
                      {{ booking.property.owner.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ booking.property.owner.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      Hôte vérifié
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Security Notice -->
            <div class="mt-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
              <div class="flex">
                <svg class="flex-shrink-0 w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <div class="ml-3">
                  <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
                    Paiement sécurisé
                  </h4>
                  <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                    Vos informations de paiement sont protégées par un chiffrement SSL et ne sont jamais stockées sur nos serveurs.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import PaymentForm from '@/components/PaymentForm.vue'
import { Link } from '@inertiajs/vue3'

interface Props {
  booking: {
    id: number
    checkin_date: string
    checkout_date: string
    nights_count: number
    guests_count: number
    nightly_rate: number
    cleaning_fee: number
    service_fee: number
    total_amount: number
    property: {
      title: string
      property_type: string
      images: Array<{
        image_path: string
        is_primary: boolean
      }>
      owner: {
        name: string
      }
    }
  }
  availableCredits: number
  referralStats: {
    total_referrals: number
    successful_referrals: number
    pending_referrals: number
    total_earned: number
    available_credits: number
    referral_rate: number
  }
}

const props = defineProps<Props>()

const formatAmount = (amountInCents: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amountInCents / 100)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const getPrimaryImage = () => {
  const images = props.booking.property.images
  if (!images || images.length === 0) return '/images/property-placeholder.jpg'
  const primary = images.find(img => img.is_primary) || images[0]
  return primary ? `/storage/${primary.image_path}` : '/images/property-placeholder.jpg'
}
</script>
