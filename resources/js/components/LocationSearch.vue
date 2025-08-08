<template>
  <div class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      Recherche par proximité
    </label>
    <div class="flex items-center space-x-2">
      <input
        v-model="searchAddress"
        @input="debouncedGeocode"
        type="text"
        placeholder="Entrez une adresse ou une ville..."
        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      />
      <button
        @click="getCurrentLocation"
        :disabled="gettingLocation"
        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        title="Utiliser ma position actuelle"
      >
        <svg v-if="!gettingLocation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </button>
    </div>

    <div v-if="suggestions.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 rounded-md shadow-lg">
      <ul class="py-1">
        <li
          v-for="suggestion in suggestions"
          :key="suggestion.place_id"
          @click="selectSuggestion(suggestion)"
          class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm"
        >
          {{ suggestion.display_name }}
        </li>
      </ul>
    </div>

    <div v-if="selectedLocation" class="mt-2">
      <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-md">
        <div class="text-sm">
          <p class="font-medium text-blue-900 dark:text-blue-100">{{ selectedLocation.name }}</p>
          <div class="flex items-center space-x-4 mt-1">
            <label class="flex items-center">
              <span class="text-blue-700 dark:text-blue-300">Rayon:</span>
              <input
                v-model.number="radius"
                type="number"
                min="1"
                max="100"
                class="ml-2 w-16 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
              />
              <span class="ml-1 text-blue-700 dark:text-blue-300">km</span>
            </label>
          </div>
        </div>
        <button
          @click="clearLocation"
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface Location {
  name: string;
  latitude: number;
  longitude: number;
}

interface Props {
  modelValue?: Location | null;
  defaultRadius?: number;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  defaultRadius: 10
});

const emit = defineEmits(['update:modelValue', 'locationChanged']);

const searchAddress = ref('');
const suggestions = ref<any[]>([]);
const selectedLocation = ref<Location | null>(props.modelValue);
const radius = ref(props.defaultRadius);
const gettingLocation = ref(false);

// Simulated geocoding function (in production, use a real geocoding API)
async function geocodeAddress(address: string) {
  if (address.length < 3) {
    suggestions.value = [];
    return;
  }

  // Simulate API call with French cities
  const cities = [
    { place_id: 1, display_name: 'Paris, France', lat: 48.8566, lon: 2.3522 },
    { place_id: 2, display_name: 'Lyon, France', lat: 45.7640, lon: 4.8357 },
    { place_id: 3, display_name: 'Marseille, France', lat: 43.2965, lon: 5.3698 },
    { place_id: 4, display_name: 'Toulouse, France', lat: 43.6047, lon: 1.4442 },
    { place_id: 5, display_name: 'Nice, France', lat: 43.7102, lon: 7.2620 },
    { place_id: 6, display_name: 'Nantes, France', lat: 47.2184, lon: -1.5536 },
    { place_id: 7, display_name: 'Strasbourg, France', lat: 48.5734, lon: 7.7521 },
    { place_id: 8, display_name: 'Montpellier, France', lat: 43.6108, lon: 3.8767 },
    { place_id: 9, display_name: 'Bordeaux, France', lat: 44.8378, lon: -0.5792 },
    { place_id: 10, display_name: 'Lille, France', lat: 50.6292, lon: 3.0573 }
  ];

  suggestions.value = cities.filter(city => 
    city.display_name.toLowerCase().includes(address.toLowerCase())
  );
}

// Simple debounce implementation
let debounceTimer: any;
const debouncedGeocode = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    geocodeAddress(searchAddress.value);
  }, 300);
};

function selectSuggestion(suggestion: any) {
  selectedLocation.value = {
    name: suggestion.display_name,
    latitude: suggestion.lat,
    longitude: suggestion.lon
  };
  searchAddress.value = suggestion.display_name;
  suggestions.value = [];
  emitLocationChange();
}

function getCurrentLocation() {
  if (!navigator.geolocation) {
    alert('La géolocalisation n\'est pas supportée par votre navigateur');
    return;
  }

  gettingLocation.value = true;
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      selectedLocation.value = {
        name: 'Ma position actuelle',
        latitude: position.coords.latitude,
        longitude: position.coords.longitude
      };
      searchAddress.value = 'Ma position actuelle';
      gettingLocation.value = false;
      emitLocationChange();
    },
    (error) => {
      console.error('Erreur de géolocalisation:', error);
      alert('Impossible d\'obtenir votre position actuelle');
      gettingLocation.value = false;
    }
  );
}

function clearLocation() {
  selectedLocation.value = null;
  searchAddress.value = '';
  suggestions.value = [];
  emitLocationChange();
}

function emitLocationChange() {
  emit('update:modelValue', selectedLocation.value);
  emit('locationChanged', {
    location: selectedLocation.value,
    radius: radius.value
  });
}

watch(radius, () => {
  if (selectedLocation.value) {
    emitLocationChange();
  }
});

watch(() => props.modelValue, (newValue) => {
  selectedLocation.value = newValue;
  if (newValue) {
    searchAddress.value = newValue.name;
  }
});
</script>