<template>
  <AppLayout title="Vérifications de permis">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Vérifications de permis de conduire
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Permis en attente de vérification</h3>
            
            <div v-if="users.data.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Utilisateur
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Numéro de permis
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Date d'expiration
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Soumis le
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="user in users.data" :key="user.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ user.name.charAt(0).toUpperCase() }}
                          </span>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ user.name }}
                          </div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ user.email }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                      {{ user.driving_license_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span :class="isExpiringSoon(user.driving_license_expiry) ? 'text-yellow-600' : 'text-gray-900 dark:text-gray-100'">
                        {{ formatDate(user.driving_license_expiry) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(user.updated_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        @click="openVerificationModal(user)"
                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                      >
                        Vérifier
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div v-else class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">Aucun permis en attente de vérification.</p>
            </div>

            <div v-if="users.last_page > 1" class="mt-6">
              <Pagination :links="users.links" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de vérification -->
    <Modal :show="showModal" @close="closeModal">
      <div v-if="selectedUser" class="p-6">
        <h3 class="text-lg font-semibold mb-4">Vérifier le permis de conduire</h3>
        
        <div class="mb-6">
          <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Utilisateur:</p>
            <p class="text-gray-900 dark:text-gray-100">{{ selectedUser.name }} ({{ selectedUser.email }})</p>
          </div>
          
          <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Numéro de permis:</p>
            <p class="text-gray-900 dark:text-gray-100">{{ selectedUser.driving_license_number }}</p>
          </div>
          
          <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Date d'expiration:</p>
            <p class="text-gray-900 dark:text-gray-100">{{ formatDate(selectedUser.driving_license_expiry) }}</p>
          </div>
          
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recto du permis:</p>
              <img
                v-if="selectedUser.driving_license_front"
                :src="`/storage/${selectedUser.driving_license_front}`"
                alt="Recto du permis"
                class="w-full h-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600"
                @click="openImagePreview(`/storage/${selectedUser.driving_license_front}`)"
              />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Verso du permis:</p>
              <img
                v-if="selectedUser.driving_license_back"
                :src="`/storage/${selectedUser.driving_license_back}`"
                alt="Verso du permis"
                class="w-full h-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600"
                @click="openImagePreview(`/storage/${selectedUser.driving_license_back}`)"
              />
            </div>
          </div>
        </div>

        <form @submit.prevent="submitVerification">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Décision
            </label>
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="verificationForm.status"
                  value="verified"
                  class="mr-2"
                  required
                />
                <span class="text-green-600">Approuver</span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="verificationForm.status"
                  value="rejected"
                  class="mr-2"
                  required
                />
                <span class="text-red-600">Rejeter</span>
              </label>
            </div>
          </div>

          <div v-if="verificationForm.status === 'rejected'" class="mb-4">
            <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Raison du rejet
            </label>
            <textarea
              id="rejection_reason"
              v-model="verificationForm.rejection_reason"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Expliquez pourquoi le permis est rejeté..."
              required
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="verificationForm.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
              Confirmer
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Modal de prévisualisation d'image -->
    <Modal :show="showImagePreview" @close="closeImagePreview" max-width="4xl">
      <div class="p-4">
        <img
          :src="previewImageUrl"
          alt="Prévisualisation"
          class="w-full h-auto"
        />
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';

interface Props {
  users: any;
}

const props = defineProps<Props>();

const showModal = ref(false);
const selectedUser = ref<any>(null);
const showImagePreview = ref(false);
const previewImageUrl = ref('');

const verificationForm = useForm({
  status: '',
  rejection_reason: ''
});

function openVerificationModal(user: any) {
  selectedUser.value = user;
  verificationForm.reset();
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedUser.value = null;
  verificationForm.reset();
}

function openImagePreview(url: string) {
  previewImageUrl.value = url;
  showImagePreview.value = true;
}

function closeImagePreview() {
  showImagePreview.value = false;
  previewImageUrl.value = '';
}

function submitVerification() {
  verificationForm.post(route('admin.license-verifications.verify', selectedUser.value.id), {
    onSuccess: () => {
      closeModal();
    }
  });
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function isExpiringSoon(date: string): boolean {
  const expiryDate = new Date(date);
  const threeMonthsFromNow = new Date();
  threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
  return expiryDate <= threeMonthsFromNow;
}
</script>