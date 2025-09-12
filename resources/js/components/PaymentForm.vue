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
      <div class="flex justify-between items-center">
        <span class="text-gray-900 dark:text-gray-100">Sous-total :</span>
        <span class="text-gray-900 dark:text-gray-100">{{ formatAmount(totalAmountBeforeCredits) }}</span>
      </div>
      <div v-if="useReferralCredits && referralCreditDiscount > 0" class="flex justify-between items-center text-green-600">
        <span>Crédits de parrainage :</span>
        <span>-{{ formatAmount(referralCreditDiscount) }}</span>
      </div>
      <hr v-if="useReferralCredits && referralCreditDiscount > 0" class="my-2 border-gray-200 dark:border-gray-600">
      <div class="flex justify-between items-center font-bold text-lg">
        <span class="text-gray-900 dark:text-gray-100">Total à payer :</span>
        <span class="text-gray-900 dark:text-gray-100">{{ formatAmount(totalAmount) }}</span>
      </div>
    </div>

    <!-- Referral Credits Section -->
    <div v-if="availableCredits && availableCredits > 0" class="mb-6 p-4 border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
          <div>
            <h3 class="font-medium text-green-800 dark:text-green-200">Crédits de parrainage disponibles</h3>
            <p class="text-sm text-green-700 dark:text-green-300">{{ formatAmount(availableCredits * 100) }} de crédits disponibles</p>
          </div>
        </div>
        <label class="flex items-center">
          <input
            type="checkbox"
            v-model="useReferralCredits"
            @change="referralCreditsToUse = useReferralCredits ? maxCreditsUsable : 0"
            class="sr-only"
          >
          <div class="relative">
            <div :class="[
              'block w-10 h-6 rounded-full transition-colors duration-200',
              useReferralCredits ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
            ]"></div>
            <div :class="[
              'absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200',
              useReferralCredits ? 'translate-x-4' : 'translate-x-0'
            ]"></div>
          </div>
        </label>
      </div>

      <div v-if="useReferralCredits" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2">
            Montant à utiliser (maximum {{ formatAmount(maxCreditsUsable * 100) }})
          </label>
          <div class="flex items-center space-x-3">
            <input
              type="range"
              :min="0"
              :max="maxCreditsUsable"
              :step="0.01"
              v-model.number="referralCreditsToUse"
              class="flex-1 h-2 bg-green-200 rounded-lg appearance-none cursor-pointer slider"
            >
            <div class="flex items-center space-x-2">
              <input
                type="number"
                :min="0"
                :max="maxCreditsUsable"
                :step="0.01"
                v-model.number="referralCreditsToUse"
                class="w-20 px-2 py-1 text-sm border border-green-300 rounded focus:border-green-500 focus:ring focus:ring-green-200"
              >
              <span class="text-sm text-green-700 dark:text-green-300">€</span>
            </div>
          </div>
        </div>
        
        <div class="flex justify-between items-center text-sm">
          <span class="text-green-700 dark:text-green-300">Économies :</span>
          <span class="font-medium text-green-800 dark:text-green-200">{{ formatAmount(referralCreditDiscount) }}</span>
        </div>
        
        <div v-if="totalAmount === 0" class="p-3 bg-green-100 dark:bg-green-800/40 rounded-md">
          <p class="text-sm text-green-800 dark:text-green-200 font-medium">
            🎉 Votre location sera entièrement payée avec vos crédits de parrainage !
          </p>
        </div>
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
      :disabled="processing || (totalAmount > 0 && !selectedMethod)"
      :class="[
        'w-full font-medium py-3 px-4 rounded-lg transition-colors duration-200',
        totalAmount === 0 
          ? 'bg-green-600 hover:bg-green-700 disabled:bg-gray-400'
          : 'bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400',
        'text-white'
      ]"
    >
      <div v-if="processing" class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
        Traitement en cours...
      </div>
      <span v-else-if="totalAmount === 0">
        Confirmer la réservation gratuite
      </span>
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
  availableCredits?: number
  referralStats?: {
    total_referrals: number
    successful_referrals: number
    pending_referrals: number
    total_earned: number
    available_credits: number
    referral_rate: number
  }
}

const props = defineProps<Props>()

const selectedMethod = ref<'stripe' | 'paypal' | null>(null)
const processing = ref(false)
const stripe = ref<any>(null)
const cardElement = ref<any>(null)
const useReferralCredits = ref(false)
const referralCreditsToUse = ref(0)

// Calculate fees based on selected payment method
const baseAmount = computed(() => props.rental.total_amount * 100) // Convert to cents

const platformFee = computed(() => {
  return Math.round(baseAmount.value * 0.1) // 10% platform fee
})

const gatewayFee = computed(() => {
  if (selectedMethod.value === 'stripe') {
    return Math.round(baseAmount.value * 0.029) + 30 // 2.9% + 30 cents
  } else if (selectedMethod.value === 'paypal') {
    return Math.round(baseAmount.value * 0.034) + 35 // 3.4% + 35 cents
  }
  return 0
})

const totalAmountBeforeCredits = computed(() => {
  return baseAmount.value + platformFee.value + gatewayFee.value
})

const referralCreditDiscount = computed(() => {
  if (!useReferralCredits.value || referralCreditsToUse.value <= 0) return 0
  return Math.min(referralCreditsToUse.value * 100, totalAmountBeforeCredits.value) // Convert euros to cents
})

const totalAmount = computed(() => {
  return Math.max(0, totalAmountBeforeCredits.value - referralCreditDiscount.value)
})

const maxCreditsUsable = computed(() => {
  if (!props.availableCredits) return 0
  return Math.min(props.availableCredits, totalAmountBeforeCredits.value / 100) // Convert cents to euros
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
  processing.value = true
  
  try {
    // If total amount is 0, process as credit-only payment
    if (totalAmount.value === 0) {
      await processCreditsOnlyPayment()
      return
    }
    
    // Otherwise, process with selected payment method
    if (!selectedMethod.value) return
    
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

const processCreditsOnlyPayment = async () => {
  const response = await fetch(`/api/payments/stripe/create-intent`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
    },
    body: JSON.stringify({
      rental_id: props.rental.id,
      amount: totalAmountBeforeCredits.value,
      referral_credits: referralCreditsToUse.value,
    })
  })
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error(data.error || 'Failed to process credits payment')
  }

  // Redirect to success page
  router.visit('/payments/success', {
    data: { message: data.message }
  })
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
      amount: totalAmountBeforeCredits.value,
      referral_credits: useReferralCredits.value ? referralCreditsToUse.value : 0,
    })
  })
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error(data.error || 'Failed to create payment intent')
  }

  // Handle case where payment was covered entirely by credits
  if (data.payment_covered_by_credits) {
    router.visit('/payments/success', {
      data: { message: data.message }
    })
    return
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
      amount: totalAmountBeforeCredits.value,
      referral_credits: useReferralCredits.value ? referralCreditsToUse.value : 0,
    })
  })
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error(data.error || 'Failed to create PayPal order')
  }

  // Handle case where payment was covered entirely by credits
  if (data.payment_covered_by_credits) {
    router.visit('/payments/success', {
      data: { message: data.message }
    })
    return
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