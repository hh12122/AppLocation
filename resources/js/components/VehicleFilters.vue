<template>
  <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        Filtres de recherche
      </h3>
      <button
        @click="resetFilters"
        class="text-sm text-indigo-600 hover:text-indigo-500"
      >
        Réinitialiser
      </button>
    </div>

    <div class="space-y-4">
      <!-- Search Bar -->
      <div>
        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Recherche
        </label>
        <input
          id="search"
          v-model="localFilters.search"
          type="text"
          placeholder="Marque, modèle, description..."
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>

      <!-- Date Range -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Date début
          </label>
          <input
            id="start_date"
            v-model="localFilters.start_date"
            type="date"
            :min="minDate"
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
        <div>
          <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Date fin
          </label>
          <input
            id="end_date"
            v-model="localFilters.end_date"
            type="date"
            :min="localFilters.start_date || minDate"
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Price Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Prix par jour (€)
        </label>
        <div class="flex items-center space-x-2">
          <input
            v-model.number="localFilters.min_price"
            type="number"
            min="0"
            placeholder="Min"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <span class="text-gray-500">-</span>
          <input
            v-model.number="localFilters.max_price"
            type="number"
            min="0"
            placeholder="Max"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Location -->
      <div>
        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Ville
        </label>
        <select
          id="city"
          v-model="localFilters.city"
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">Toutes les villes</option>
          <option v-for="city in filterOptions.cities" :key="city" :value="city">
            {{ city }}
          </option>
        </select>
      </div>

      <!-- Brand -->
      <div>
        <label for="brand" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Marque
        </label>
        <select
          id="brand"
          v-model="localFilters.brand"
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">Toutes les marques</option>
          <option v-for="brand in filterOptions.brands" :key="brand" :value="brand">
            {{ brand }}
          </option>
        </select>
      </div>

      <!-- Fuel Type -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Type de carburant
        </label>
        <div class="space-y-2">
          <label v-for="fuel in fuelTypeOptions" :key="fuel.value" class="flex items-center">
            <input
              type="checkbox"
              :value="fuel.value"
              v-model="localFilters.fuel_type"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ fuel.label }}</span>
          </label>
        </div>
      </div>

      <!-- Transmission -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Transmission
        </label>
        <div class="space-y-2">
          <label v-for="trans in transmissionOptions" :key="trans.value" class="flex items-center">
            <input
              type="checkbox"
              :value="trans.value"
              v-model="localFilters.transmission"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ trans.label }}</span>
          </label>
        </div>
      </div>

      <!-- Seats Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Nombre de places
        </label>
        <div class="flex items-center space-x-2">
          <input
            v-model.number="localFilters.min_seats"
            type="number"
            min="1"
            max="9"
            placeholder="Min"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <span class="text-gray-500">-</span>
          <input
            v-model.number="localFilters.max_seats"
            type="number"
            min="1"
            max="9"
            placeholder="Max"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Year Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Année
        </label>
        <div class="flex items-center space-x-2">
          <input
            v-model.number="localFilters.min_year"
            type="number"
            min="2000"
            :max="currentYear"
            placeholder="Min"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <span class="text-gray-500">-</span>
          <input
            v-model.number="localFilters.max_year"
            type="number"
            min="2000"
            :max="currentYear"
            placeholder="Max"
            class="w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Color -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Couleur
        </label>
        <select
          v-model="localFilters.color"
          multiple
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option v-for="color in filterOptions.colors" :key="color" :value="color">
            {{ color }}
          </option>
        </select>
      </div>

      <!-- Features -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Équipements
        </label>
        <div class="space-y-2">
          <label v-for="feature in filterOptions.features" :key="feature" class="flex items-center">
            <input
              type="checkbox"
              :value="feature"
              v-model="localFilters.features"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ feature }}</span>
          </label>
        </div>
      </div>

      <!-- Minimum Rating -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Note minimale
        </label>
        <div class="flex items-center space-x-2">
          <button
            v-for="rating in [1, 2, 3, 4, 5]"
            :key="rating"
            @click="localFilters.min_rating = localFilters.min_rating === rating ? null : rating"
            :class="[
              'p-1',
              localFilters.min_rating >= rating ? 'text-yellow-400' : 'text-gray-300'
            ]"
          >
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </button>
          <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ localFilters.min_rating ? `${localFilters.min_rating} étoiles et plus` : 'Toutes' }}
          </span>
        </div>
      </div>

      <!-- Sort By -->
      <div>
        <label for="sort_by" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Trier par
        </label>
        <select
          id="sort_by"
          v-model="localFilters.sort_by"
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="created_at">Plus récent</option>
          <option value="price_low">Prix croissant</option>
          <option value="price_high">Prix décroissant</option>
          <option value="rating">Meilleures notes</option>
          <option value="reviews">Plus d'avis</option>
          <option value="year">Année</option>
        </select>
      </div>

      <!-- Apply Button -->
      <button
        @click="applyFilters"
        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      >
        Appliquer les filtres
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
  filters: Record<string, any>;
  filterOptions: {
    brands: string[];
    cities: string[];
    colors: string[];
    features: string[];
    fuel_types: string[];
    transmissions: string[];
  };
}

const props = defineProps<Props>();

const localFilters = ref({
  search: props.filters.search || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
  min_price: props.filters.min_price || null,
  max_price: props.filters.max_price || null,
  city: props.filters.city || '',
  brand: props.filters.brand || '',
  fuel_type: props.filters.fuel_type || [],
  transmission: props.filters.transmission || [],
  min_seats: props.filters.min_seats || null,
  max_seats: props.filters.max_seats || null,
  min_year: props.filters.min_year || null,
  max_year: props.filters.max_year || null,
  color: props.filters.color || [],
  features: props.filters.features || [],
  min_rating: props.filters.min_rating || null,
  sort_by: props.filters.sort_by || 'created_at'
});

const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const currentYear = new Date().getFullYear();

const fuelTypeOptions = [
  { value: 'gasoline', label: 'Essence' },
  { value: 'diesel', label: 'Diesel' },
  { value: 'electric', label: 'Électrique' },
  { value: 'hybrid', label: 'Hybride' }
];

const transmissionOptions = [
  { value: 'manual', label: 'Manuelle' },
  { value: 'automatic', label: 'Automatique' }
];

function applyFilters() {
  const cleanFilters = Object.entries(localFilters.value).reduce((acc, [key, value]) => {
    if (value !== null && value !== '' && (!Array.isArray(value) || value.length > 0)) {
      acc[key] = value;
    }
    return acc;
  }, {} as Record<string, any>);

  router.get(route('vehicles.index'), cleanFilters, {
    preserveState: true,
    preserveScroll: true
  });
}

function resetFilters() {
  localFilters.value = {
    search: '',
    start_date: '',
    end_date: '',
    min_price: null,
    max_price: null,
    city: '',
    brand: '',
    fuel_type: [],
    transmission: [],
    min_seats: null,
    max_seats: null,
    min_year: null,
    max_year: null,
    color: [],
    features: [],
    min_rating: null,
    sort_by: 'created_at'
  };
  applyFilters();
}

// Auto-apply filters when sort changes
watch(() => localFilters.value.sort_by, () => {
  applyFilters();
});
</script>