<template>
  <div v-if="shouldShowAlert" :class="alertClasses">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="iconComponent" class="h-5 w-5" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium" :class="titleClasses">
          {{ alertTitle }}
        </h3>
        <div class="mt-2 text-sm" :class="messageClasses">
          <p>{{ alertMessage }}</p>
        </div>
        <div v-if="showAction" class="mt-4">
          <div class="-mx-2 -my-1.5 flex">
            <Link
              :href="route('settings.driver-license')"
              :class="actionClasses"
            >
              {{ actionText }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

interface Props {
  context?: 'rental' | 'dashboard' | 'general';
}

const props = withDefaults(defineProps<Props>(), {
  context: 'general'
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const licenseStatus = computed(() => {
  if (!user.value.driving_license_number || !user.value.driving_license_expiry) {
    return 'missing';
  }
  
  const expiryDate = new Date(user.value.driving_license_expiry);
  const today = new Date();
  const threeMonthsFromNow = new Date();
  threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
  
  if (expiryDate < today) {
    return 'expired';
  }
  
  if (expiryDate <= threeMonthsFromNow) {
    return 'expiring_soon';
  }
  
  if (user.value.driving_license_status === 'rejected') {
    return 'rejected';
  }
  
  if (user.value.driving_license_status === 'pending') {
    return 'pending';
  }
  
  if (user.value.driving_license_status === 'verified') {
    return 'verified';
  }
  
  return 'unverified';
});

const shouldShowAlert = computed(() => {
  if (props.context === 'rental') {
    return ['missing', 'expired', 'rejected', 'expiring_soon', 'pending'].includes(licenseStatus.value);
  }
  
  if (props.context === 'dashboard') {
    return ['missing', 'expired', 'rejected', 'expiring_soon'].includes(licenseStatus.value);
  }
  
  return licenseStatus.value !== 'verified';
});

const alertType = computed(() => {
  switch (licenseStatus.value) {
    case 'missing':
    case 'expired':
    case 'rejected':
      return 'error';
    case 'expiring_soon':
      return 'warning';
    case 'pending':
      return 'info';
    case 'verified':
      return 'success';
    default:
      return 'info';
  }
});

const alertClasses = computed(() => {
  const base = 'rounded-md p-4 mb-4';
  switch (alertType.value) {
    case 'error':
      return `${base} bg-red-50 dark:bg-red-900/20`;
    case 'warning':
      return `${base} bg-yellow-50 dark:bg-yellow-900/20`;
    case 'info':
      return `${base} bg-blue-50 dark:bg-blue-900/20`;
    case 'success':
      return `${base} bg-green-50 dark:bg-green-900/20`;
    default:
      return `${base} bg-gray-50 dark:bg-gray-900/20`;
  }
});

const titleClasses = computed(() => {
  switch (alertType.value) {
    case 'error':
      return 'text-red-800 dark:text-red-200';
    case 'warning':
      return 'text-yellow-800 dark:text-yellow-200';
    case 'info':
      return 'text-blue-800 dark:text-blue-200';
    case 'success':
      return 'text-green-800 dark:text-green-200';
    default:
      return 'text-gray-800 dark:text-gray-200';
  }
});

const messageClasses = computed(() => {
  switch (alertType.value) {
    case 'error':
      return 'text-red-700 dark:text-red-300';
    case 'warning':
      return 'text-yellow-700 dark:text-yellow-300';
    case 'info':
      return 'text-blue-700 dark:text-blue-300';
    case 'success':
      return 'text-green-700 dark:text-green-300';
    default:
      return 'text-gray-700 dark:text-gray-300';
  }
});

const actionClasses = computed(() => {
  const base = 'rounded-md px-2 py-1.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2';
  switch (alertType.value) {
    case 'error':
      return `${base} bg-red-100 text-red-800 hover:bg-red-200 focus:ring-red-600 focus:ring-offset-red-50`;
    case 'warning':
      return `${base} bg-yellow-100 text-yellow-800 hover:bg-yellow-200 focus:ring-yellow-600 focus:ring-offset-yellow-50`;
    case 'info':
      return `${base} bg-blue-100 text-blue-800 hover:bg-blue-200 focus:ring-blue-600 focus:ring-offset-blue-50`;
    case 'success':
      return `${base} bg-green-100 text-green-800 hover:bg-green-200 focus:ring-green-600 focus:ring-offset-green-50`;
    default:
      return `${base} bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-gray-600 focus:ring-offset-gray-50`;
  }
});

const iconComponent = computed(() => {
  switch (alertType.value) {
    case 'error':
      return XCircleIcon;
    case 'warning':
      return ExclamationTriangleIcon;
    case 'info':
      return InformationCircleIcon;
    case 'success':
      return CheckCircleIcon;
    default:
      return InformationCircleIcon;
  }
});

const alertTitle = computed(() => {
  switch (licenseStatus.value) {
    case 'missing':
      return 'Permis de conduire requis';
    case 'expired':
      return 'Permis de conduire expiré';
    case 'rejected':
      return 'Permis de conduire rejeté';
    case 'expiring_soon':
      return 'Permis de conduire bientôt expiré';
    case 'pending':
      return 'Vérification en cours';
    case 'verified':
      return 'Permis vérifié';
    default:
      return 'Permis non vérifié';
  }
});

const alertMessage = computed(() => {
  switch (licenseStatus.value) {
    case 'missing':
      return 'Vous devez ajouter votre permis de conduire pour pouvoir louer un véhicule.';
    case 'expired':
      return 'Votre permis de conduire a expiré. Veuillez le mettre à jour pour continuer à louer des véhicules.';
    case 'rejected':
      return `Votre permis a été rejeté. ${user.value.driving_license_rejection_reason || 'Veuillez soumettre des documents valides.'}`;
    case 'expiring_soon':
      return 'Votre permis de conduire expire bientôt. Pensez à le renouveler.';
    case 'pending':
      return 'Votre permis est en cours de vérification. Vous pouvez louer mais la réservation pourrait être annulée si la vérification échoue.';
    case 'verified':
      return 'Votre permis de conduire est vérifié et valide.';
    default:
      return 'Votre permis de conduire doit être vérifié.';
  }
});

const showAction = computed(() => {
  return ['missing', 'expired', 'rejected', 'expiring_soon'].includes(licenseStatus.value);
});

const actionText = computed(() => {
  switch (licenseStatus.value) {
    case 'missing':
      return 'Ajouter mon permis';
    case 'expired':
    case 'expiring_soon':
      return 'Mettre à jour mon permis';
    case 'rejected':
      return 'Soumettre à nouveau';
    default:
      return 'Gérer mon permis';
  }
});
</script>