<template>
  <AppLayout title="Paiement réussi">
    <div class="py-12">
      <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
          <div class="p-6 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 mb-4">
              <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            
            <!-- Success Message -->
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
              Paiement réussi !
            </h2>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">
              Votre paiement a été traité avec succès. Vous allez recevoir un email de confirmation.
            </p>

            <!-- Payment Details -->
            <div v-if="payment" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6 text-left">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Détails du paiement</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Montant :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatAmount(payment.amount) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Méthode :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ payment.payment_method }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">ID Transaction :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100 font-mono text-xs">{{ payment.id }}</span>
                </div>
                <div v-if="payment.paid_at" class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Date :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(payment.paid_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Rental Details -->
            <div v-if="rental" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 text-left">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Détails de la location</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Véhicule :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ rental.vehicle.brand }} {{ rental.vehicle.model }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Dates :</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ formatDate(rental.start_date) }} - {{ formatDate(rental.end_date) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Statut :</span>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">
                    {{ getRentalStatusLabel(rental.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
              <Link
                :href="`/rentals/${rental?.id}`"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center"
              >
                Voir la location
              </Link>
              <Link
                href="/my-rentals"
                class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center"
              >
                Mes locations
              </Link>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <h3 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Prochaines étapes</h3>
          <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
            <li>• Vous recevrez un email de confirmation</li>
            <li>• Le propriétaire sera notifié de votre réservation</li>
            <li>• Vous pourrez contacter le propriétaire pour organiser la remise</li>
            <li>• Un reçu détaillé sera disponible dans votre espace personnel</li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
//
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

interface Props {
  payment?: {
    id: string
    amount: number
    payment_method: string
    paid_at: string
  }
  rental?: {
    id: number
    status: string
    start_date: string
    end_date: string
    vehicle: {
      brand: string
      model: string
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

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getRentalStatusLabel = (status: string): string => {
  const statusLabels: Record<string, string> = {
    pending: 'En attente',
    confirmed: 'Confirmée',
    active: 'En cours',
    completed: 'Terminée',
    cancelled: 'Annulée'
  }
  return statusLabels[status] || status
}
</script>