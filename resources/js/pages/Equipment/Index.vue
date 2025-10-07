<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, Euro, MapPin, Star, Plus } from 'lucide-vue-next';

interface Equipment {
  id: number;
  name: string;
  category: string;
  subcategory: string;
  description: string;
  city: string;
  daily_rate?: number;
  hourly_rate?: number;
  weekly_rate?: number;
  monthly_rate?: number;
  rental_unit: string;
  rating: number;
  rating_count: number;
  owner: {
    name: string;
    rating: number;
  };
  primary_image?: {
    image_path: string;
    alt_text: string;
  };
  delivery_available: boolean;
  instant_booking: boolean;
}

interface Props {
  equipment: {
    data: Equipment[];
    total: number;
    current_page: number;
    last_page: number;
    links: any[];
  };
  categories: Record<string, any>;
  filters: Record<string, any>;
  searchParams: Record<string, any>;
}

const props = defineProps<Props>();

// Search and filter state
const searchQuery = ref(props.searchParams.search || '');
const selectedCategory = ref(props.searchParams.category || 'all');
const selectedCity = ref(props.searchParams.city || 'all');
const minPrice = ref(props.searchParams.min_price || '');
const maxPrice = ref(props.searchParams.max_price || '');
const rentalUnit = ref(props.searchParams.rental_unit || 'day');
const deliveryOnly = ref(props.searchParams.delivery_available || false);
const instantBooking = ref(props.searchParams.instant_booking || false);

// Apply filters
const applyFilters = () => {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (selectedCategory.value && selectedCategory.value !== 'all') params.category = selectedCategory.value;
  if (selectedCity.value && selectedCity.value !== 'all') params.city = selectedCity.value;
  if (minPrice.value) params.min_price = minPrice.value;
  if (maxPrice.value) params.max_price = maxPrice.value;
  if (rentalUnit.value !== 'day') params.rental_unit = rentalUnit.value;
  if (deliveryOnly.value) params.delivery_available = true;
  if (instantBooking.value) params.instant_booking = true;

  router.get(route('equipment.index'), params, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Clear filters
const clearFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = 'all';
  selectedCity.value = 'all';
  minPrice.value = '';
  maxPrice.value = '';
  rentalUnit.value = 'day';
  deliveryOnly.value = false;
  instantBooking.value = false;

  router.get(route('equipment.index'));
};

// Watch for changes and apply filters with debounce
let debounceTimeout: NodeJS.Timeout;
watch([searchQuery, selectedCategory, selectedCity, minPrice, maxPrice, rentalUnit], () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

watch([deliveryOnly, instantBooking], () => {
  applyFilters();
});

// Get price for display
const getPrice = (equipment: Equipment) => {
  const unit = rentalUnit.value || equipment.rental_unit;
  switch (unit) {
    case 'hour':
      return equipment.hourly_rate;
    case 'week':
      return equipment.weekly_rate;
    case 'month':
      return equipment.monthly_rate;
    default:
      return equipment.daily_rate;
  }
};

const getRentalUnitLabel = (unit: string) => {
  const labels = {
    hour: '/heure',
    day: '/jour',
    week: '/semaine',
    month: '/mois'
  };
  return labels[unit as keyof typeof labels] || '/jour';
};

// Get category label
const getCategoryLabel = (category: string) => {
  return props.categories[category]?.label || category;
};

// Get equipment image
const getEquipmentImage = (equipment: Equipment) => {
  return equipment.primary_image?.image_path
    ? `/storage/${equipment.primary_image.image_path}`
    : '/images/equipment-placeholder.jpg';
};
</script>

<template>
  <Head title="Matériel à louer" />

  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Matériel à louer
          </h1>
          <p class="text-gray-600 dark:text-gray-300 mt-2">
            Trouvez le matériel parfait pour vos besoins
          </p>
        </div>

        <Link :href="route('equipment.create')">
          <Button>
            <Plus class="w-4 h-4 mr-2" />
            Ajouter mon matériel
          </Button>
        </Link>
      </div>

      <!-- Filters -->
      <Card class="mb-8">
        <CardHeader>
          <div class="flex flex-wrap gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
              <Input
                v-model="searchQuery"
                placeholder="Rechercher du matériel..."
                class="w-full"
              />
            </div>

            <!-- Category -->
            <Select v-model="selectedCategory">
              <SelectTrigger class="w-48">
                <SelectValue placeholder="Toutes catégories" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Toutes catégories</SelectItem>
                <SelectItem
                  v-for="(category, key) in categories"
                  :key="key"
                  :value="key"
                >
                  {{ category.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- City -->
            <Select v-model="selectedCity">
              <SelectTrigger class="w-48">
                <SelectValue placeholder="Toutes villes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Toutes villes</SelectItem>
                <SelectItem
                  v-for="city in filters.cities"
                  :key="city"
                  :value="city"
                >
                  {{ city }}
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- Rental Unit -->
            <Select v-model="rentalUnit">
              <SelectTrigger class="w-32">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="(label, unit) in filters.rental_units"
                  :key="unit"
                  :value="unit"
                >
                  {{ label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex flex-wrap gap-4 mt-4">
            <!-- Price Range -->
            <div class="flex gap-2 items-center">
              <Input
                v-model="minPrice"
                type="number"
                placeholder="Prix min"
                class="w-24"
              />
              <span class="text-gray-500">-</span>
              <Input
                v-model="maxPrice"
                type="number"
                placeholder="Prix max"
                class="w-24"
              />
              <span class="text-sm text-gray-500">€{{ getRentalUnitLabel(rentalUnit) }}</span>
            </div>

            <!-- Quick Filters -->
            <div class="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                :class="deliveryOnly ? 'bg-primary text-primary-foreground' : ''"
                @click="deliveryOnly = !deliveryOnly"
              >
                Livraison disponible
              </Button>
              <Button
                variant="outline"
                size="sm"
                :class="instantBooking ? 'bg-primary text-primary-foreground' : ''"
                @click="instantBooking = !instantBooking"
              >
                Réservation instantanée
              </Button>
              <Button
                variant="ghost"
                size="sm"
                @click="clearFilters"
              >
                Réinitialiser
              </Button>
            </div>
          </div>
        </CardHeader>
      </Card>

      <!-- Results Count -->
      <div class="mb-6">
        <p class="text-gray-600 dark:text-gray-300">
          {{ equipment.total }} matériel{{ equipment.total > 1 ? 's' : '' }} trouvé{{ equipment.total > 1 ? 's' : '' }}
        </p>
      </div>

      <!-- Equipment Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <Card
          v-for="item in equipment.data"
          :key="item.id"
          class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="$inertia.visit(route('equipment.show', item.id))"
        >
          <div class="relative">
            <img
              :src="getEquipmentImage(item)"
              :alt="item.primary_image?.alt_text || item.name"
              class="w-full h-48 object-cover rounded-t-lg"
            />

            <!-- Badges -->
            <div class="absolute top-3 left-3 flex gap-2">
              <Badge v-if="item.instant_booking" class="bg-green-500">
                Instantané
              </Badge>
              <Badge v-if="item.delivery_available" class="bg-blue-500">
                Livraison
              </Badge>
            </div>

            <!-- Price -->
            <div class="absolute top-3 right-3 bg-white/90 px-2 py-1 rounded-lg">
              <span class="font-bold text-lg">{{ getPrice(item) }}€</span>
              <span class="text-sm text-gray-600">{{ getRentalUnitLabel(item.rental_unit) }}</span>
            </div>
          </div>

          <CardContent class="p-4">
            <div class="mb-2">
              <Badge variant="secondary" class="text-xs">
                {{ getCategoryLabel(item.category) }}
              </Badge>
            </div>

            <h3 class="font-semibold text-lg mb-2 line-clamp-2">{{ item.name }}</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-2">
              {{ item.description }}
            </p>

            <div class="flex items-center justify-between text-sm">
              <div class="flex items-center gap-1 text-gray-500">
                <MapPin class="w-4 h-4" />
                {{ item.city }}
              </div>

              <div class="flex items-center gap-1">
                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                <span>{{ Number(item.rating || 0).toFixed(1) }}</span>
                <span class="text-gray-500">({{ item.rating_count || 0 }})</span>
              </div>
            </div>

            <div class="mt-3 pt-3 border-t">
              <div class="text-sm text-gray-600 dark:text-gray-300">
                Par {{ item.owner.name }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-if="equipment.data.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <Calendar class="w-16 h-16 mx-auto" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
          Aucun matériel trouvé
        </h3>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
          Essayez de modifier vos critères de recherche ou explorez d'autres catégories.
        </p>
        <Button @click="clearFilters">
          Réinitialiser les filtres
        </Button>
      </div>

      <!-- Pagination -->
      <div v-if="equipment.last_page > 1" class="flex justify-center">
        <nav class="flex items-center gap-2">
          <template v-for="link in equipment.links" :key="link.label">
            <Button
              v-if="link.url"
              variant="outline"
              size="sm"
              :class="link.active ? 'bg-primary text-primary-foreground' : ''"
              @click="$inertia.visit(link.url)"
              v-html="link.label"
            />
            <span
              v-else
              class="px-3 py-1 text-sm text-gray-400"
              v-html="link.label"
            />
          </template>
        </nav>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
