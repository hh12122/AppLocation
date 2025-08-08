<template>
  <AppLayout :title="`Évaluations - ${user.name}`">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Évaluations de {{ user.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                  <span class="text-2xl font-semibold text-gray-600 dark:text-gray-400">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ user.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Membre depuis {{ formatDate(user.created_at) }}
                  </p>
                </div>
              </div>
              <div class="text-center">
                <div class="flex items-center mb-2">
                  <StarRating :rating="user.rating" :size="'lg'" />
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ user.rating_count }} évaluation{{ user.rating_count > 1 ? 's' : '' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="review in reviews.data"
            :key="review.id"
            class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
          >
            <div class="p-6">
              <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                      {{ review.reviewer.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ review.reviewer.name }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ formatDate(review.created_at) }}
                    </p>
                  </div>
                </div>
                <StarRating :rating="review.rating" />
              </div>

              <p class="text-gray-700 dark:text-gray-300 mb-4">
                {{ review.comment }}
              </p>

              <div v-if="review.criteria_ratings && Object.keys(review.criteria_ratings).length > 0" class="grid grid-cols-2 gap-3 mb-4">
                <div v-for="(value, key) in review.criteria_ratings" :key="key" class="flex items-center justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">{{ getCriterionLabel(key) }}</span>
                  <StarRating :rating="value" :size="'xs'" />
                </div>
              </div>

              <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Location: {{ review.rental.vehicle.brand }} {{ review.rental.vehicle.model }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Du {{ formatDate(review.rental.start_date) }} au {{ formatDate(review.rental.end_date) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="reviews.data.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
          <p class="text-gray-500 dark:text-gray-400">Aucune évaluation pour cet utilisateur.</p>
        </div>

        <div v-if="reviews.last_page > 1" class="mt-6">
          <Pagination :links="reviews.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import StarRating from '@/components/StarRating.vue';

interface Props {
  user: any;
  reviews: any;
}

defineProps<Props>();

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
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
</script>