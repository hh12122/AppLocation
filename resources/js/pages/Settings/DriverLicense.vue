<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
          Permis de conduire
        </h2>

    <div class="max-w-4xl">
      <!-- Statut actuel -->
      <div v-if="user.driving_license_status" class="mb-6">
        <div
          :class="[
            'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6',
            user.driving_license_status === 'verified' ? 'border-2 border-green-500' :
            user.driving_license_status === 'rejected' ? 'border-2 border-red-500' :
            user.driving_license_status === 'pending' ? 'border-2 border-yellow-500' : ''
          ]"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold mb-2">Statut de votre permis</h3>
              <div class="flex items-center space-x-2">
                <span
                  :class="[
                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                    user.driving_license_status === 'verified' ? 'bg-green-100 text-green-800' :
                    user.driving_license_status === 'rejected' ? 'bg-red-100 text-red-800' :
                    user.driving_license_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ getStatusLabel(user.driving_license_status) }}
                </span>
                <span v-if="user.driving_license_verified_at" class="text-sm text-gray-500">
                  Vérifié le {{ formatDate(user.driving_license_verified_at) }}
                </span>
              </div>
              <p v-if="user.driving_license_rejection_reason" class="mt-2 text-sm text-red-600">
                Raison du rejet: {{ user.driving_license_rejection_reason }}
              </p>
            </div>
            <div v-if="user.driving_license_status === 'verified'">
              <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Informations actuelles -->
      <div v-if="user.driving_license_number" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Informations actuelles</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Numéro de permis</p>
              <p class="text-gray-900 dark:text-gray-100">{{ user.driving_license_number }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Date d'expiration</p>
              <p :class="isExpiringSoon ? 'text-yellow-600' : 'text-gray-900 dark:text-gray-100'">
                {{ formatDate(user.driving_license_expiry) }}
                <span v-if="isExpiringSoon" class="text-sm"> (Expire bientôt)</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulaire de mise à jour -->
      <div v-if="!user.driving_license_status || user.driving_license_status === 'rejected' || isExpiringSoon" 
           class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">
            {{ user.driving_license_status === 'rejected' ? 'Soumettre à nouveau votre permis' : 
               isExpiringSoon ? 'Mettre à jour votre permis' : 
               'Ajouter votre permis de conduire' }}
          </h3>
          
          <form @submit.prevent="submitLicense">
            <div class="space-y-6">
              <div>
                <label for="driving_license_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Numéro de permis
                </label>
                <input
                  id="driving_license_number"
                  v-model="form.driving_license_number"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="Ex: 123456789"
                />
                <InputError :message="form.errors.driving_license_number" class="mt-2" />
              </div>

              <div>
                <label for="driving_license_expiry" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Date d'expiration
                </label>
                <input
                  id="driving_license_expiry"
                  v-model="form.driving_license_expiry"
                  type="date"
                  required
                  :min="minExpiryDate"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <InputError :message="form.errors.driving_license_expiry" class="mt-2" />
              </div>

              <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                  <strong>Note:</strong> Pour des raisons de sécurité, nous devons vérifier votre permis de conduire. 
                  Cette vérification est obligatoire pour pouvoir louer un véhicule.
                </p>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                >
                  Enregistrer
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Message de succès pour permis vérifié -->
      <div v-if="user.driving_license_status === 'verified' && !isExpiringSoon" 
           class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg text-center">
        <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-2">
          Votre permis est vérifié !
        </h3>
        <p class="text-sm text-green-700 dark:text-green-300">
          Vous pouvez maintenant louer des véhicules sur notre plateforme.
        </p>
      </div>
    </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
  driving_license_number: user.value.driving_license_number || '',
  driving_license_expiry: user.value.driving_license_expiry || ''
});

const minExpiryDate = computed(() => {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  return tomorrow.toISOString().split('T')[0];
});

const isExpiringSoon = computed(() => {
  if (!user.value.driving_license_expiry) return false;
  const expiryDate = new Date(user.value.driving_license_expiry);
  const threeMonthsFromNow = new Date();
  threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
  return expiryDate <= threeMonthsFromNow;
});

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'En cours de vérification',
    verified: 'Vérifié',
    rejected: 'Rejeté',
  };
  return labels[status] || 'Non vérifié';
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function submitLicense() {
  form.patch(route('settings.driver-license'), {
    preserveScroll: true
  });
}
</script>