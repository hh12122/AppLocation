<template>
  <AppLayout title="Mes paiements">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Mes paiements
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="p-2 rounded-md bg-green-500">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total payé</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatAmount(stats.total_paid) }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="p-2 rounded-md bg-blue-500">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Paiements</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_payments }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="p-2 rounded-md bg-orange-500">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Remboursements</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatAmount(stats.total_refunded) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Statut
                </label>
                <select
                  v-model="filters.status"
                  @change="applyFilters"
                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm"
                >
                  <option value="">Tous les statuts</option>
                  <option value="completed">Complétés</option>
                  <option value="pending">En attente</option>
                  <option value="refunded">Remboursés</option>
                  <option value="cancelled">Annulés</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Méthode
                </label>
                <select
                  v-model="filters.payment_method"
                  @change="applyFilters"
                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm"
                >
                  <option value="">Toutes les méthodes</option>
                  <option value="stripe">Stripe</option>
                  <option value="paypal">PayPal</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de début
                </label>
                <input
                  v-model="filters.date_from"
                  @change="applyFilters"
                  type="date"
                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input
                  v-model="filters.date_to"
                  @change="applyFilters"
                  type="date"
                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Payments List -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
              Historique des paiements ({{ payments.total }})
            </h3>
          </div>

          <div v-if="payments.data.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucun paiement</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Vous n'avez effectué aucun paiement pour le moment.
            </p>
            <div class="mt-6">
              <Link
                href="/vehicles"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
              >
                Parcourir les véhicules
              </Link>
            </div>
          </div>

          <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
              v-for="payment in payments.data"
              :key="payment.id"
              class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                      <svg v-if="payment.payment_method === 'stripe'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                      </svg>
                      <svg v-else class="w-5 h-5 text-blue-800" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h8.418c2.508 0 4.514.893 5.535 2.459 1.004 1.537 1.002 3.747-.006 5.282-1.629 2.48-4.106 3.764-7.312 3.764H10.27l-.664 4.183-.062.394a.384.384 0 0 1-.384.355zm2.47-8.108-.897 5.678a.641.641 0 0 0 .633.74h4.963a.641.641 0 0 0 .633-.74l.842-5.334c.343-2.173-.231-3.94-1.378-5.121C12.467 6.437 10.894 5.995 9.204 5.995H6.422l-.84 5.334c-.04.25.146.483.404.483h3.560z"/>
                      </svg>
                    </div>
                  </div>
                  
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-2">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ payment.rental.vehicle.brand }} {{ payment.rental.vehicle.model }}
                      </p>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getStatusBadgeClass(payment.status)">
                        {{ getStatusLabel(payment.status) }}
                      </span>
                    </div>
                    <div class="flex items-center space-x-4 mt-1">
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ formatDate(payment.created_at) }}
                      </p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                        {{ payment.payment_method }}
                      </p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        ID: #{{ payment.id }}
                      </p>
                    </div>
                  </div>
                </div>
                
                <div class="text-right">
                  <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ formatAmount(payment.amount) }}
                  </p>
                  <p v-if="payment.refunded_amount > 0" class="text-sm text-orange-600 dark:text-orange-400">
                    -{{ formatAmount(payment.refunded_amount) }} remboursé
                  </p>
                </div>
              </div>

              <div v-if="payment.failure_reason" class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-sm text-red-800 dark:text-red-200">
                {{ payment.failure_reason }}
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="payments.data.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <Pagination :links="payments.links" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import Pagination from '@/components/Pagination.vue'

interface Props {
  payments: {
    data: Array<{
      id: string
      amount: number
      refunded_amount: number
      status: string
      payment_method: string
      created_at: string
      failure_reason?: string
      rental: {
        vehicle: {
          brand: string
          model: string
        }
      }
    }>
    total: number
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
  }
  stats: {
    total_paid: number
    total_refunded: number
    total_payments: number
  }
}

defineProps<Props>()

const filters = reactive({
  status: '',
  payment_method: '',
  date_from: '',
  date_to: ''
})

const formatAmount = (amountInCents: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amountInCents / 100)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusLabel = (status: string): string => {
  const statusLabels: Record<string, string> = {
    pending: 'En attente',
    completed: 'Complété',
    failed: 'Échoué',
    cancelled: 'Annulé',
    refunded: 'Remboursé',
    partially_refunded: 'Part. remboursé'
  }
  return statusLabels[status] || status
}

const getStatusBadgeClass = (status: string): string => {
  const statusClasses: Record<string, string> = {
    pending: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200',
    completed: 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200',
    failed: 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200',
    cancelled: 'bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-200',
    refunded: 'bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200',
    partially_refunded: 'bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200'
  }
  return statusClasses[status] || 'bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-200'
}

const applyFilters = () => {
  router.get('/payments', filters, {
    preserveState: true,
    replace: true,
  })
}
</script>