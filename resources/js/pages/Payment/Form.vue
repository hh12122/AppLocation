<template>
  <AppLayout title="Paiement de location">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Paiement de location
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
          </p>
        </div>
        <Link
          :href="`/rentals/${rental.id}`"
          class="text-blue-600 hover:text-blue-500 text-sm font-medium"
        >
          ← Retour à la location
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Payment Form -->
          <div class="lg:col-span-2">
            <PaymentForm :rental="rental" />
          </div>

          <!-- Rental Summary -->
          <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Résumé de la location
              </h3>

              <!-- Vehicle Info -->
              <div class="flex items-center space-x-4 mb-6">
                <div class="flex-shrink-0">
                  <div v-if="rental.vehicle.images && rental.vehicle.images.length > 0">
                    <img
                      :src="rental.vehicle.images[0]"
                      :alt="`${rental.vehicle.brand} ${rental.vehicle.model}`"
                      class="w-16 h-16 object-cover rounded-lg"
                    >
                  </div>
                  <div v-else class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                  </h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ rental.vehicle.year }} • {{ rental.vehicle.fuel_type }}
                  </p>
                </div>
              </div>

              <!-- Rental Details -->
              <div class="space-y-3 mb-6">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Dates :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatDateRange(rental.start_date, rental.end_date) }}
                  </span>
                </div>
                
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Durée :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ rental.total_days }} jour{{ rental.total_days > 1 ? 's' : '' }}
                  </span>
                </div>

                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Tarif journalier :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatAmount(rental.daily_rate * 100) }}
                  </span>
                </div>

                <div v-if="rental.deposit > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">Caution :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatAmount(rental.deposit * 100) }}
                  </span>
                </div>

                <hr class="border-gray-200 dark:border-gray-600">

                <div class="flex justify-between">
                  <span class="font-medium text-gray-900 dark:text-gray-100">Total :</span>
                  <span class="font-bold text-lg text-gray-900 dark:text-gray-100">
                    {{ formatAmount(rental.total_amount * 100) }}
                  </span>
                </div>
              </div>

              <!-- Special Requests -->
              <div v-if="rental.special_requests" class="mb-6">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                  Demandes spéciales :
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded">
                  {{ rental.special_requests }}
                </p>
              </div>

              <!-- Owner Contact -->
              <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                  Propriétaire
                </h4>
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">
                      {{ rental.vehicle.owner.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ rental.vehicle.owner.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      Propriétaire vérifié
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
  rental: {
    id: number
    start_date: string
    end_date: string
    total_amount: number
    daily_rate: number
    total_days: number
    deposit: number
    special_requests?: string
    vehicle: {
      brand: string
      model: string
      year: number
      fuel_type: string
      images?: string[]
      owner: {
        name: string
      }
    }
  }
}

defineProps<Props>()

const formatAmount = (amountInCents: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amountInCents / 100)
}

const formatDateRange = (startDate: string, endDate: string): string => {
  const start = new Date(startDate)
  const end = new Date(endDate)
  
  const formatOptions: Intl.DateTimeFormatOptions = {
    day: 'numeric',
    month: 'short'
  }
  
  return `${start.toLocaleDateString('fr-FR', formatOptions)} - ${end.toLocaleDateString('fr-FR', formatOptions)}`
}
</script>