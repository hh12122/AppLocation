<template>
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
    <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-gray-100">
      Paiement de votre location
    </h2>
    
    <!-- Payment Summary -->
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
      <div class="flex justify-between items-center mb-2">
        <span class="text-gray-600 dark:text-gray-400">Montant de la location :</span>
        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatAmount(rental.total_amount * 100) }}</span>
      </div>
      <div v-if="platformFee > 0" class="flex justify-between items-center mb-2">
        <span class="text-gray-600 dark:text-gray-400">Frais de plateforme :</span>
        <span class="text-gray-900 dark:text-gray-100">{{ formatAmount(platformFee) }}</span>
      </div>
      <div v-if="gatewayFee > 0" class="flex justify-between items-center mb-2">
        <span class="text-gray-600 dark:text-gray-400">Frais de traitement :</span>
        <span class="text-gray-900 dark:text-gray-100">{{ formatAmount(gatewayFee) }}</span>
      </div>
      <hr class="my-2 border-gray-200 dark:border-gray-600">
      <div class="flex justify-between items-center font-bold text-lg">
        <span class="text-gray-900 dark:text-gray-100">Total :</span>
        <span class="text-gray-900 dark:text-gray-100">{{ formatAmount(totalAmount) }}</span>
      </div>
    </div>

    <!-- Payment Method Selection -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Méthode de paiement
      </label>
      <div class="grid grid-cols-2 gap-4">
        <button
          @click="selectedMethod = 'stripe'"
          :class="[
            'p-4 border-2 rounded-lg transition-all duration-200',
            selectedMethod === 'stripe'
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
              : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center justify-center space-x-2">
            <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center">
              <span class="text-white font-bold text-sm">S</span>
            </div>
            <span class="font-medium text-gray-900 dark:text-gray-100">Stripe</span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Carte bancaire</p>
        </button>

        <button
          @click="selectedMethod = 'paypal'"
          :class="[
            'p-4 border-2 rounded-lg transition-all duration-200',
            selectedMethod === 'paypal'
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
              : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center justify-center space-x-2">
            <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
              <span class="text-white font-bold text-xs">PP</span>
            </div>
            <span class="font-medium text-gray-900 dark:text-gray-100">PayPal</span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Compte PayPal</p>
        </button>
      </div>
    </div>

    <!-- Stripe Payment Form -->
    <div v-if="selectedMethod === 'stripe'" class="mb-6">
      <div id="stripe-card-element" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg">
        <!-- Stripe Elements will be mounted here -->
      </div>
      <div id="card-errors" role="alert" class="text-red-500 text-sm mt-2"></div>
    </div>

    <!-- PayPal Payment Info -->
    <div v-if="selectedMethod === 'paypal'" class="mb-6">
      <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
          Vous serez redirigé vers PayPal pour finaliser votre paiement.
        </p>
      </div>
    </div>

    <!-- Payment Button -->
    <button
      @click="processPayment"
      :disabled="processing || !selectedMethod"
      class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200"
    >
      <div v-if="processing" class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
        Traitement en cours...
      </div>
      <span v-else>
        Payer {{ formatAmount(totalAmount) }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

interface Props {
  rental: {
    id: number
    total_amount: number
    vehicle: {
      brand: string
      model: string
    }
  }
}

const props = defineProps<Props>()

const selectedMethod = ref<'stripe' | 'paypal' | null>(null)
const processing = ref(false)
const stripe = ref<any>(null)
const cardElement = ref<any>(null)

// Calculate fees based on selected payment method
const totalAmount = computed(() => {
  const baseAmount = props.rental.total_amount * 100 // Convert to cents
  return baseAmount + platformFee.value + gatewayFee.value
})

const platformFee = computed(() => {
  const baseAmount = props.rental.total_amount * 100
  return Math.round(baseAmount * 0.1) // 10% platform fee
})

const gatewayFee = computed(() => {
  const baseAmount = props.rental.total_amount * 100
  if (selectedMethod.value === 'stripe') {
    return Math.round(baseAmount * 0.029) + 30 // 2.9% + 30 cents
  } else if (selectedMethod.value === 'paypal') {
    return Math.round(baseAmount * 0.034) + 35 // 3.4% + 35 cents
  }
  return 0
})

const formatAmount = (amountInCents: number): string => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amountInCents / 100)
}

const initializeStripe = async () => {
  if (typeof window !== 'undefined' && (window as any).Stripe) {
    stripe.value = (window as any).Stripe(import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY)
    
    const elements = stripe.value.elements()
    cardElement.value = elements.create('card', {
      style: {
        base: {
          fontSize: '16px',
          color: '#424770',
          '::placeholder': {
            color: '#aab7c4',
          },
        },
      },
    })
    
    cardElement.value.mount('#stripe-card-element')
    
    cardElement.value.on('change', (event: any) => {
      const displayError = document.getElementById('card-errors')
      if (event.error) {
        displayError!.textContent = event.error.message
      } else {
        displayError!.textContent = ''
      }
    })
  }
}

const processPayment = async () => {
  if (!selectedMethod.value) return
  
  processing.value = true
  
  try {
    if (selectedMethod.value === 'stripe') {
      await processStripePayment()
    } else if (selectedMethod.value === 'paypal') {
      await processPayPalPayment()
    }
  } catch (error) {
    console.error('Payment error:', error)
    alert('Erreur lors du traitement du paiement. Veuillez réessayer.')
  } finally {
    processing.value = false
  }
}

const processStripePayment = async () => {
  if (!stripe.value || !cardElement.value) return
  
  // Create payment intent on the server
  const response = await fetch(`/api/payments/stripe/create-intent`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
    },
    body: JSON.stringify({
      rental_id: props.rental.id,
      amount: totalAmount.value,
    })
  })
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error(data.error || 'Failed to create payment intent')
  }
  
  // Confirm payment with Stripe
  const result = await stripe.value.confirmCardPayment(data.client_secret, {
    payment_method: {
      card: cardElement.value,
    }
  })
  
  if (result.error) {
    throw new Error(result.error.message)
  }
  
  // Redirect to success page
  router.visit(`/payments/success?payment_intent=${result.paymentIntent.id}`)
}

const processPayPalPayment = async () => {
  // Create PayPal order on the server
  const response = await fetch(`/api/payments/paypal/create-order`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
    },
    body: JSON.stringify({
      rental_id: props.rental.id,
      amount: totalAmount.value,
    })
  })
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error(data.error || 'Failed to create PayPal order')
  }
  
  // Redirect to PayPal approval URL
  window.location.href = data.approval_url
}

onMounted(() => {
  // Load Stripe.js dynamically
  if (typeof window !== 'undefined' && !(window as any).Stripe) {
    const script = document.createElement('script')
    script.src = 'https://js.stripe.com/v3/'
    script.onload = initializeStripe
    document.head.appendChild(script)
  } else {
    initializeStripe()
  }
})

onUnmounted(() => {
  if (cardElement.value) {
    cardElement.value.destroy()
  }
})
</script>