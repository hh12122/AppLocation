<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import FavoriteButton from '@/components/FavoriteButton.vue';
import {
  Calendar,
  Euro,
  MapPin,
  Star,
  User,
  Clock,
  Package,
  Truck,
  Shield,
  Check,
  Info,
  Heart,
  Share2,
  ArrowLeft,
  Camera,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next';

interface Equipment {
  id: number;
  name: string;
  category: string;
  subcategory: string;
  description: string;
  brand?: string;
  model?: string;
  year?: number;
  condition: string;
  city: string;
  address: string;
  daily_rate?: number;
  hourly_rate?: number;
  weekly_rate?: number;
  monthly_rate?: number;
  rental_unit: string;
  min_rental_duration: number;
  max_rental_duration?: number;
  security_deposit?: number;
  delivery_available: boolean;
  delivery_radius?: number;
  delivery_fee?: number;
  instant_booking: boolean;
  license_required: boolean;
  license_type?: string;
  min_age?: number;
  features?: string[];
  included_items?: string[];
  safety_equipment?: string[];
  usage_instructions?: string;
  safety_instructions?: string;
  pickup_instructions?: string;
  rental_requirements?: string;
  restrictions?: string[];
  insurance_included: boolean;
  insurance_details?: string;
  rating: number;
  rating_count: number;
  view_count: number;
  owner: {
    id: number;
    name: string;
    email: string;
    rating: number;
    rating_count: number;
    avatar?: string;
  };
  images: Array<{
    id: number;
    image_path: string;
    alt_text: string;
    sort_order: number;
    is_primary: boolean;
  }>;
  reviews?: Array<{
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    reviewer: {
      name: string;
      avatar?: string;
    };
  }>;
}

interface Props {
  equipment: Equipment;
  categoryConfig: {
    label: string;
    subcategories: Record<string, string>;
  };
  similarEquipment: Equipment[];
  isFavorite: boolean;
}

const props = defineProps<Props>();

const currentImageIndex = ref(0);
const showImageModal = ref(false);

// Get equipment images
const equipmentImages = computed(() => {
  if (props.equipment.images && props.equipment.images.length > 0) {
    return props.equipment.images
      .sort((a, b) => a.sort_order - b.sort_order)
      .map(img => ({
        ...img,
        url: `/storage/${img.image_path}`
      }));
  }
  return [{
    id: 0,
    image_path: 'placeholder',
    alt_text: props.equipment.name,
    sort_order: 0,
    is_primary: true,
    url: '/images/equipment-placeholder.jpg'
  }];
});

// Get pricing info
const getPricing = () => {
  const prices = [];
  if (props.equipment.hourly_rate) {
    prices.push({ rate: props.equipment.hourly_rate, unit: 'heure', period: '/h' });
  }
  if (props.equipment.daily_rate) {
    prices.push({ rate: props.equipment.daily_rate, unit: 'jour', period: '/jour' });
  }
  if (props.equipment.weekly_rate) {
    prices.push({ rate: props.equipment.weekly_rate, unit: 'semaine', period: '/semaine' });
  }
  if (props.equipment.monthly_rate) {
    prices.push({ rate: props.equipment.monthly_rate, unit: 'mois', period: '/mois' });
  }
  return prices;
};

// Get condition label
const getConditionLabel = (condition: string) => {
  const labels = {
    new: 'Neuf',
    excellent: 'Excellent',
    good: 'Bon',
    fair: 'Correct',
    poor: 'Mauvais'
  };
  return labels[condition as keyof typeof labels] || condition;
};

// Get condition color
const getConditionColor = (condition: string) => {
  const colors = {
    new: 'bg-green-100 text-green-800',
    excellent: 'bg-green-100 text-green-700',
    good: 'bg-blue-100 text-blue-700',
    fair: 'bg-yellow-100 text-yellow-700',
    poor: 'bg-red-100 text-red-700'
  };
  return colors[condition as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};

// Image navigation
const previousImage = () => {
  currentImageIndex.value = currentImageIndex.value === 0
    ? equipmentImages.value.length - 1
    : currentImageIndex.value - 1;
};

const nextImage = () => {
  currentImageIndex.value = currentImageIndex.value === equipmentImages.value.length - 1
    ? 0
    : currentImageIndex.value + 1;
};

const goToImage = (index: number) => {
  currentImageIndex.value = index;
};

// Similar equipment image
const getSimilarEquipmentImage = (equipment: Equipment) => {
  return equipment.primary_image?.image_path
    ? `/storage/${equipment.primary_image.image_path}`
    : '/images/equipment-placeholder.jpg';
};
</script>

<template>
  <Head :title="equipment.name" />

  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Back Button -->
      <div class="mb-6">
        <Button
          variant="ghost"
          size="sm"
          @click="$inertia.visit(route('equipment.category', equipment.category))"
        >
          <ArrowLeft class="w-4 h-4 mr-2" />
          Retour à {{ categoryConfig.label }}
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Images -->
        <div class="lg:col-span-2">
          <div class="relative">
            <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-4">
              <img
                :src="equipmentImages[currentImageIndex].url"
                :alt="equipmentImages[currentImageIndex].alt_text"
                class="w-full h-full object-cover cursor-pointer"
                @click="showImageModal = true"
              />

              <!-- Image Navigation -->
              <div v-if="equipmentImages.length > 1" class="absolute inset-0 flex items-center justify-between p-4">
                <Button
                  variant="outline"
                  size="sm"
                  class="bg-white/80 hover:bg-white"
                  @click="previousImage"
                >
                  <ChevronLeft class="w-4 h-4" />
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  class="bg-white/80 hover:bg-white"
                  @click="nextImage"
                >
                  <ChevronRight class="w-4 h-4" />
                </Button>
              </div>

              <!-- Image Counter -->
              <div class="absolute bottom-4 right-4 bg-black/50 text-white px-2 py-1 rounded text-sm">
                {{ currentImageIndex + 1 }} / {{ equipmentImages.length }}
              </div>

              <!-- View Full Size -->
              <div class="absolute top-4 right-4">
                <Button
                  variant="outline"
                  size="sm"
                  class="bg-white/80 hover:bg-white"
                  @click="showImageModal = true"
                >
                  <Camera class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Thumbnails -->
            <div v-if="equipmentImages.length > 1" class="flex gap-2 overflow-x-auto">
              <button
                v-for="(image, index) in equipmentImages"
                :key="image.id"
                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-colors"
                :class="index === currentImageIndex ? 'border-primary' : 'border-gray-200'"
                @click="goToImage(index)"
              >
                <img
                  :src="image.url"
                  :alt="image.alt_text"
                  class="w-full h-full object-cover"
                />
              </button>
            </div>
          </div>
        </div>

        <!-- Details & Booking -->
        <div class="space-y-6">
          <!-- Title & Actions -->
          <div>
            <div class="flex items-start justify-between mb-2">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ equipment.name }}
              </h1>
              <div class="flex gap-2">
                <FavoriteButton
                  v-if="$page.props.auth?.user"
                  favoritable-type="Equipment"
                  :favoritable-id="equipment.id"
                  :is-favorited="isFavorite || false"
                  size="sm"
                />
                <Button variant="outline" size="sm">
                  <Share2 class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
              <div class="flex items-center gap-1">
                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                <span>{{ Number(equipment.rating || 0).toFixed(1) }}</span>
                <span>({{ equipment.rating_count || 0 }} avis)</span>
              </div>
              <div class="flex items-center gap-1">
                <MapPin class="w-4 h-4" />
                {{ equipment.city }}
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
              <Badge>{{ categoryConfig.subcategories[equipment.subcategory] || equipment.subcategory }}</Badge>
              <Badge :class="getConditionColor(equipment.condition)">
                {{ getConditionLabel(equipment.condition) }}
              </Badge>
              <Badge v-if="equipment.instant_booking" class="bg-green-100 text-green-800">
                Réservation instantanée
              </Badge>
              <Badge v-if="equipment.delivery_available" class="bg-blue-100 text-blue-800">
                Livraison disponible
              </Badge>
            </div>
          </div>

          <!-- Pricing -->
          <Card>
            <CardHeader>
              <h3 class="font-semibold">Tarifs</h3>
            </CardHeader>
            <CardContent class="space-y-2">
              <div v-for="price in getPricing()" :key="price.unit" class="flex justify-between">
                <span>{{ price.unit }}</span>
                <span class="font-semibold">{{ price.rate }}€{{ price.period }}</span>
              </div>

              <Separator />

              <div v-if="equipment.security_deposit" class="flex justify-between text-sm">
                <span>Caution</span>
                <span>{{ equipment.security_deposit }}€</span>
              </div>

              <div v-if="equipment.delivery_available && equipment.delivery_fee" class="flex justify-between text-sm">
                <span>Frais de livraison</span>
                <span>{{ equipment.delivery_fee }}€</span>
              </div>
            </CardContent>
          </Card>

          <!-- Owner -->
          <Card>
            <CardContent class="pt-6">
              <div class="flex items-center gap-4">
                <Avatar class="h-12 w-12">
                  <AvatarImage :src="equipment.owner.avatar" />
                  <AvatarFallback>{{ equipment.owner.name.charAt(0) }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-medium">{{ equipment.owner.name }}</p>
                  <div class="flex items-center gap-1 text-sm text-gray-600">
                    <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                    <span>{{ Number(equipment.owner.rating || 0).toFixed(1) }}</span>
                    <span>({{ equipment.owner.rating_count || 0 }} avis)</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Book Button -->
          <Link :href="route('equipment-bookings.create', equipment.id)">
            <Button class="w-full" size="lg">
              <Calendar class="w-4 h-4 mr-2" />
              Réserver ce matériel
            </Button>
          </Link>
        </div>
      </div>

      <!-- Description & Details -->
      <div class="mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
          <!-- Description -->
          <Card>
            <CardHeader>
              <h3 class="text-xl font-semibold">Description</h3>
            </CardHeader>
            <CardContent>
              <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                {{ equipment.description }}
              </p>
            </CardContent>
          </Card>

          <!-- Features & Included Items -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card v-if="equipment.features && equipment.features.length > 0">
              <CardHeader>
                <h3 class="font-semibold flex items-center gap-2">
                  <Check class="w-4 h-4" />
                  Caractéristiques
                </h3>
              </CardHeader>
              <CardContent>
                <ul class="space-y-2">
                  <li v-for="feature in equipment.features" :key="feature" class="flex items-center gap-2">
                    <Check class="w-3 h-3 text-green-500" />
                    {{ feature }}
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card v-if="equipment.included_items && equipment.included_items.length > 0">
              <CardHeader>
                <h3 class="font-semibold flex items-center gap-2">
                  <Package class="w-4 h-4" />
                  Inclus dans la location
                </h3>
              </CardHeader>
              <CardContent>
                <ul class="space-y-2">
                  <li v-for="item in equipment.included_items" :key="item" class="flex items-center gap-2">
                    <Check class="w-3 h-3 text-green-500" />
                    {{ item }}
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>

          <!-- Instructions -->
          <div v-if="equipment.usage_instructions || equipment.safety_instructions" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card v-if="equipment.usage_instructions">
              <CardHeader>
                <h3 class="font-semibold flex items-center gap-2">
                  <Info class="w-4 h-4" />
                  Instructions d'utilisation
                </h3>
              </CardHeader>
              <CardContent>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                  {{ equipment.usage_instructions }}
                </p>
              </CardContent>
            </Card>

            <Card v-if="equipment.safety_instructions">
              <CardHeader>
                <h3 class="font-semibold flex items-center gap-2">
                  <Shield class="w-4 h-4" />
                  Consignes de sécurité
                </h3>
              </CardHeader>
              <CardContent>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                  {{ equipment.safety_instructions }}
                </p>
              </CardContent>
            </Card>
          </div>

          <!-- Reviews -->
          <Card v-if="equipment.reviews && equipment.reviews.length > 0">
            <CardHeader>
              <h3 class="text-xl font-semibold">Avis des locataires</h3>
            </CardHeader>
            <CardContent class="space-y-6">
              <div v-for="review in equipment.reviews" :key="review.id" class="border-b pb-4 last:border-b-0">
                <div class="flex items-start gap-4">
                  <Avatar>
                    <AvatarImage :src="review.reviewer.avatar" />
                    <AvatarFallback>{{ review.reviewer.name.charAt(0) }}</AvatarFallback>
                  </Avatar>
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <p class="font-medium">{{ review.reviewer.name }}</p>
                      <div class="flex items-center">
                        <Star
                          v-for="i in 5"
                          :key="i"
                          class="w-3 h-3"
                          :class="i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"
                        />
                      </div>
                      <span class="text-sm text-gray-500">
                        {{ new Date(review.created_at).toLocaleDateString('fr-FR') }}
                      </span>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300">{{ review.comment }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Equipment Details -->
          <Card>
            <CardHeader>
              <h3 class="font-semibold">Détails du matériel</h3>
            </CardHeader>
            <CardContent class="space-y-3">
              <div v-if="equipment.brand" class="flex justify-between">
                <span class="text-gray-600">Marque</span>
                <span class="font-medium">{{ equipment.brand }}</span>
              </div>
              <div v-if="equipment.model" class="flex justify-between">
                <span class="text-gray-600">Modèle</span>
                <span class="font-medium">{{ equipment.model }}</span>
              </div>
              <div v-if="equipment.year" class="flex justify-between">
                <span class="text-gray-600">Année</span>
                <span class="font-medium">{{ equipment.year }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Durée min.</span>
                <span class="font-medium">{{ equipment.min_rental_duration }} jour{{ equipment.min_rental_duration > 1 ? 's' : '' }}</span>
              </div>
              <div v-if="equipment.max_rental_duration" class="flex justify-between">
                <span class="text-gray-600">Durée max.</span>
                <span class="font-medium">{{ equipment.max_rental_duration }} jour{{ equipment.max_rental_duration > 1 ? 's' : '' }}</span>
              </div>
            </CardContent>
          </Card>

          <!-- Requirements -->
          <Card v-if="equipment.license_required || equipment.min_age || equipment.rental_requirements">
            <CardHeader>
              <h3 class="font-semibold">Conditions de location</h3>
            </CardHeader>
            <CardContent class="space-y-3">
              <div v-if="equipment.license_required" class="flex items-center gap-2">
                <Shield class="w-4 h-4 text-orange-500" />
                <span class="text-sm">
                  Permis {{ equipment.license_type || 'de conduire' }} requis
                </span>
              </div>
              <div v-if="equipment.min_age" class="flex items-center gap-2">
                <User class="w-4 h-4 text-blue-500" />
                <span class="text-sm">Âge minimum: {{ equipment.min_age }} ans</span>
              </div>
              <div v-if="equipment.rental_requirements">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                  {{ equipment.rental_requirements }}
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Delivery Info -->
          <Card v-if="equipment.delivery_available">
            <CardHeader>
              <h3 class="font-semibold flex items-center gap-2">
                <Truck class="w-4 h-4" />
                Livraison
              </h3>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                Livraison disponible dans un rayon de {{ equipment.delivery_radius || 10 }}km
              </p>
              <p class="text-sm font-medium">
                Frais: {{ equipment.delivery_fee || 0 }}€
              </p>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Similar Equipment -->
      <div v-if="similarEquipment.length > 0" class="mt-12">
        <h2 class="text-2xl font-bold mb-6">Matériel similaire</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card
            v-for="similar in similarEquipment"
            :key="similar.id"
            class="hover:shadow-lg transition-shadow cursor-pointer"
            @click="$inertia.visit(route('equipment.show', similar.id))"
          >
            <div class="relative">
              <img
                :src="getSimilarEquipmentImage(similar)"
                :alt="similar.name"
                class="w-full h-32 object-cover rounded-t-lg"
              />
              <div class="absolute top-2 right-2 bg-white/90 px-2 py-1 rounded text-sm font-medium">
                {{ similar.daily_rate || similar.hourly_rate }}€
              </div>
            </div>
            <CardContent class="p-3">
              <h3 class="font-medium mb-1 line-clamp-2">{{ similar.name }}</h3>
              <p class="text-sm text-gray-600 mb-2">{{ similar.city }}</p>
              <div class="flex items-center gap-1 text-xs">
                <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                <span>{{ Number(similar.rating || 0).toFixed(1) }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <Dialog v-model:open="showImageModal">
      <DialogContent class="max-w-4xl">
        <div class="relative">
          <img
            :src="equipmentImages[currentImageIndex].url"
            :alt="equipmentImages[currentImageIndex].alt_text"
            class="w-full h-auto max-h-[80vh] object-contain"
          />

          <!-- Navigation in Modal -->
          <div v-if="equipmentImages.length > 1" class="absolute inset-0 flex items-center justify-between p-4">
            <Button
              variant="outline"
              size="sm"
              class="bg-white/80 hover:bg-white"
              @click="previousImage"
            >
              <ChevronLeft class="w-4 h-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="bg-white/80 hover:bg-white"
              @click="nextImage"
            >
              <ChevronRight class="w-4 h-4" />
            </Button>
          </div>

          <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded">
            {{ currentImageIndex + 1 }} / {{ equipmentImages.length }}
          </div>
        </div>
      </DialogContent>
    </Dialog>
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
