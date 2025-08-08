<template>
  <AppLayout title="Modifier l'évaluation">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Modifier votre évaluation
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="mb-6">
              <h3 class="text-lg font-semibold mb-2">
                Modifier votre évaluation
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Location du {{ formatDate(review.rental.start_date) }} au {{ formatDate(review.rental.end_date) }}
              </p>
              <p class="text-gray-600 dark:text-gray-400">
                Véhicule: {{ review.rental.vehicle.brand }} {{ review.rental.vehicle.model }}
              </p>
            </div>

            <form @submit.prevent="updateReview">
              <div class="space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Note globale
                  </label>
                  <div class="flex items-center space-x-2">
                    <button
                      v-for="i in 5"
                      :key="i"
                      type="button"
                      @click="form.rating = i"
                      class="text-3xl focus:outline-none transition-colors"
                      :class="i <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
                    >
                      ★
                    </button>
                    <span class="ml-4 text-sm text-gray-600 dark:text-gray-400">
                      {{ getRatingText(form.rating) }}
                    </span>
                  </div>
                  <InputError :message="form.errors.rating" class="mt-2" />
                </div>

                <div v-if="review.type === 'vehicle'" class="space-y-4">
                  <h4 class="font-medium text-gray-700 dark:text-gray-300">Critères spécifiques</h4>
                  
                  <div v-for="criterion in criteriaList" :key="criterion.key">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      {{ criterion.label }}
                    </label>
                    <div class="flex items-center space-x-1">
                      <button
                        v-for="i in 5"
                        :key="i"
                        type="button"
                        @click="form.criteria_ratings[criterion.key] = i"
                        class="text-xl focus:outline-none transition-colors"
                        :class="i <= (form.criteria_ratings[criterion.key] || 0) ? 'text-yellow-400' : 'text-gray-300'"
                      >
                        ★
                      </button>
                    </div>
                  </div>
                </div>

                <div>
                  <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Votre commentaire
                  </label>
                  <textarea
                    id="comment"
                    v-model="form.comment"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Partagez votre expérience..."
                    required
                  ></textarea>
                  <InputError :message="form.errors.comment" class="mt-2" />
                </div>

                <div>
                  <label class="flex items-center">
                    <input
                      type="checkbox"
                      v-model="form.is_public"
                      class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                      Rendre cette évaluation publique
                    </span>
                  </label>
                </div>

                <div class="flex items-center justify-end space-x-4">
                  <Link
                    :href="route('reviews.show', review.id)"
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                  >
                    Annuler
                  </Link>
                  <button
                    type="submit"
                    :disabled="form.processing || !form.rating"
                    class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                  >
                    Mettre à jour
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';

interface Props {
  review: any;
}

const props = defineProps<Props>();

const form = useForm({
  rating: props.review.rating,
  comment: props.review.comment,
  criteria_ratings: props.review.criteria_ratings || {
    cleanliness: 0,
    communication: 0,
    condition: 0,
    value: 0
  },
  is_public: props.review.is_public
});

const criteriaList = [
  { key: 'cleanliness', label: 'Propreté' },
  { key: 'communication', label: 'Communication' },
  { key: 'condition', label: 'État du véhicule' },
  { key: 'value', label: 'Rapport qualité/prix' }
];

function getRatingText(rating: number): string {
  const texts = ['', 'Très mauvais', 'Mauvais', 'Correct', 'Bon', 'Excellent'];
  return texts[rating] || '';
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function updateReview() {
  form.put(route('reviews.update', props.review.id), {
    preserveScroll: true
  });
}
</script>