<template>
  <AppLayout title="Détails de l'évaluation">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Détails de l'évaluation
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-start justify-between mb-6">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                  <span class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                    {{ review.reviewer.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ review.reviewer.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(review.created_at) }}
                  </p>
                </div>
              </div>
              <div class="flex items-center">
                <StarRating :rating="review.rating" :size="'lg'" />
              </div>
            </div>

            <div class="mb-6">
              <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">
                À propos de: {{ review.type === 'vehicle' ? 'Véhicule' : review.type === 'renter' ? 'Locataire' : 'Propriétaire' }}
              </h4>
              <p class="text-gray-600 dark:text-gray-400">
                {{ getRevieweeInfo() }}
              </p>
            </div>

            <div class="prose prose-gray dark:prose-invert max-w-none mb-6">
              <p>{{ review.comment }}</p>
            </div>

            <div v-if="review.criteria_ratings && Object.keys(review.criteria_ratings).length > 0" class="mb-6">
              <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Évaluation détaillée</h4>
              <div class="space-y-3">
                <div v-for="(value, key) in review.criteria_ratings" :key="key" class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ getCriterionLabel(key) }}</span>
                  <div class="flex items-center space-x-2">
                    <StarRating :rating="value" :size="'sm'" />
                    <span class="text-sm text-gray-500">{{ value }}/5</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Location du {{ formatDate(review.rental.start_date) }} au {{ formatDate(review.rental.end_date) }}
                  </p>
                  <Link
                    :href="route('vehicles.show', review.rental.vehicle.id)"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                  >
                    {{ review.rental.vehicle.brand }} {{ review.rental.vehicle.model }}
                  </Link>
                </div>
                <div v-if="canEdit" class="flex items-center space-x-2">
                  <Link
                    :href="route('reviews.edit', review.id)"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                  >
                    Modifier
                  </Link>
                  <button
                    @click="deleteReview"
                    class="text-sm text-red-600 hover:text-red-500"
                  >
                    Supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex justify-between">
          <Link
            :href="route('reviews.index')"
            class="text-indigo-600 hover:text-indigo-500"
          >
            ← Retour aux évaluations
          </Link>
          <Link
            :href="route('rentals.show', review.rental.id)"
            class="text-indigo-600 hover:text-indigo-500"
          >
            Voir la location →
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import StarRating from '@/components/StarRating.vue';

interface Props {
  review: any;
}

const props = defineProps<Props>();
const page = usePage();

const canEdit = computed(() => {
  return page.props.auth.user?.id === props.review.reviewer_id;
});

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

function getRevieweeInfo(): string {
  if (props.review.type === 'vehicle') {
    return `${props.review.rental.vehicle.brand} ${props.review.rental.vehicle.model} - Propriétaire: ${props.review.reviewee.name}`;
  } else {
    return props.review.reviewee.name;
  }
}

function deleteReview() {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette évaluation ?')) {
    router.delete(route('reviews.destroy', props.review.id));
  }
}
</script>