<template>
  <AppLayout title="Vérification du permis de conduire">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Vérification du permis de conduire
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div v-if="user.driving_license_status === 'verified'" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                  <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                    Permis vérifié
                  </h3>
                  <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                    Votre permis de conduire a été vérifié avec succès le {{ formatDate(user.driving_license_verified_at) }}
                  </p>
                </div>
              </div>
            </div>

            <div v-else-if="user.driving_license_status === 'rejected'" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-red-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                  <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    Permis rejeté
                  </h3>
                  <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                    {{ user.driving_license_rejection_reason }}
                  </p>
                  <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    Veuillez soumettre à nouveau votre permis avec des documents valides.
                  </p>
                </div>
              </div>
            </div>

            <div v-else-if="user.driving_license_status === 'pending'" class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                  <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Vérification en cours
                  </h3>
                  <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                    Votre permis est en cours de vérification. Cela peut prendre 24 à 48 heures.
                  </p>
                </div>
              </div>
            </div>

            <form @submit.prevent="submitLicense" class="space-y-6">
              <div>
                <label for="license_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Numéro de permis
                </label>
                <input
                  id="license_number"
                  v-model="form.driving_license_number"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <InputError :message="form.errors.driving_license_number" class="mt-2" />
              </div>

              <div>
                <label for="license_expiry" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Date d'expiration
                </label>
                <input
                  id="license_expiry"
                  v-model="form.driving_license_expiry"
                  type="date"
                  required
                  :min="tomorrow"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <InputError :message="form.errors.driving_license_expiry" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Photo recto du permis
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                  <div class="space-y-1 text-center">
                    <svg v-if="!frontPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                      <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <img v-else :src="frontPreview" class="mx-auto h-32 object-cover rounded" />
                    <div class="flex text-sm text-gray-600 dark:text-gray-400">
                      <label for="license-front" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                        <span>Télécharger une image</span>
                        <input
                          id="license-front"
                          type="file"
                          @change="handleFrontUpload"
                          accept="image/*"
                          class="sr-only"
                          required
                        />
                      </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG jusqu'à 5MB</p>
                  </div>
                </div>
                <InputError :message="form.errors.driving_license_front" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Photo verso du permis
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                  <div class="space-y-1 text-center">
                    <svg v-if="!backPreview" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                      <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <img v-else :src="backPreview" class="mx-auto h-32 object-cover rounded" />
                    <div class="flex text-sm text-gray-600 dark:text-gray-400">
                      <label for="license-back" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                        <span>Télécharger une image</span>
                        <input
                          id="license-back"
                          type="file"
                          @change="handleBackUpload"
                          accept="image/*"
                          class="sr-only"
                          required
                        />
                      </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG jusqu'à 5MB</p>
                  </div>
                </div>
                <InputError :message="form.errors.driving_license_back" class="mt-2" />
              </div>

              <div class="flex items-center justify-end space-x-4">
                <Link
                  :href="route('settings.driver-license')"
                  class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                >
                  Annuler
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                >
                  Soumettre pour vérification
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
            Informations importantes
          </h4>
          <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>• Le permis doit être valide et lisible</li>
            <li>• Les deux côtés du permis doivent être visibles</li>
            <li>• La vérification prend généralement 24 à 48 heures</li>
            <li>• Vous serez notifié par email une fois la vérification terminée</li>
            <li>• Un permis vérifié est requis pour effectuer une location</li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';

interface Props {
  user: {
    id: number;
    name: string;
    email: string;
    driving_license_number?: string;
    driving_license_expiry?: string;
    driving_license_front?: string;
    driving_license_back?: string;
    driving_license_status?: string;
    driving_license_verified_at?: string;
    driving_license_rejection_reason?: string;
  };
}

const props = defineProps<Props>();

const form = useForm({
  driving_license_number: props.user.driving_license_number || '',
  driving_license_expiry: props.user.driving_license_expiry || '',
  driving_license_front: null as File | null,
  driving_license_back: null as File | null
});

const frontPreview = ref<string | null>(null);
const backPreview = ref<string | null>(null);

const tomorrow = computed(() => {
  const date = new Date();
  date.setDate(date.getDate() + 1);
  return date.toISOString().split('T')[0];
});

function handleFrontUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (file) {
    form.driving_license_front = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      frontPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function handleBackUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (file) {
    form.driving_license_back = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      backPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function submitLicense() {
  form.post(route('license.upload'), {
    preserveScroll: true,
    forceFormData: true
  });
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}
</script>