<template>
  <AppLayout title="Évaluations">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Toutes les évaluations
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex gap-4">
          <button
            v-for="type in ['all', 'vehicle', 'owner', 'renter']"
            :key="type"
            @click="filterType = type"
            :class="[
              'px-4 py-2 rounded-md text-sm font-medium transition-colors',
              filterType === type
                ? 'bg-indigo-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ getTypeLabel(type) }}
          </button>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="review in reviews.data"
            :key="review.id"
            class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
          >
            <div class="p-6">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ review.reviewer.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(review.created_at) }}
                  </p>
                </div>
                <div class="flex items-center">
                  <span class="text-yellow-400">{{ '★'.repeat(review.rating) }}</span>
                  <span class="text-gray-300">{{ '★'.repeat(5 - review.rating) }}</span>
                </div>
              </div>

              <p class="text-gray-700 dark:text-gray-300 mb-4">
                {{ review.comment }}
              </p>

              <div v-if="review.criteria_ratings" class="space-y-2 mb-4">
                <div v-for="(value, key) in review.criteria_ratings" :key="key" class="flex items-center justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">{{ getCriterionLabel(key) }}</span>
                  <div class="flex items-center">
                    <span class="text-yellow-400 text-xs">{{ '★'.repeat(value) }}</span>
                    <span class="text-gray-300 text-xs">{{ '★'.repeat(5 - value) }}</span>
                  </div>
                </div>
              </div>

              <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ getReviewContext(review) }}
                </p>
                <Link
                  :href="route('reviews.show', review.id)"
                  class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                >
                  Voir les détails →
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div v-if="reviews.data.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
          <p class="text-gray-500 dark:text-gray-400">Aucune évaluation trouvée.</p>
        </div>

        <div v-if="reviews.last_page > 1" class="mt-6">
          <Pagination :links="reviews.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';

interface Props {
  reviews: any;
  filters: {
    type?: string;
    user_id?: number;
  };
}

const props = defineProps<Props>();
const filterType = ref(props.filters.type || 'all');

watch(filterType, (newType) => {
  router.get(route('reviews.index'), {
    type: newType === 'all' ? null : newType
  }, {
    preserveState: true,
    preserveScroll: true
  });
});

function getTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    all: 'Toutes',
    vehicle: 'Véhicules',
    owner: 'Propriétaires',
    renter: 'Locataires'
  };
  return labels[type] || type;
}

function getCriterionLabel(key: string): string {
  const labels: Record<string, string> = {
    cleanliness: 'Propreté',
    communication: 'Communication',
    condition: 'État',
    value: 'Rapport qualité/prix'
  };
  return labels[key] || key;
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function getReviewContext(review: any): string {
  if (review.type === 'vehicle') {
    return `Véhicule: ${review.rental.vehicle.brand} ${review.rental.vehicle.model}`;
  } else if (review.type === 'renter') {
    return `Locataire: ${review.reviewee.name}`;
  } else {
    return `Propriétaire: ${review.reviewee.name}`;
  }
}
</script>