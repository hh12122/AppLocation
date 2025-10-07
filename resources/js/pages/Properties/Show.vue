<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
  Home, MapPin, Users, Bed, Bath, Euro, Calendar, Clock,
  Star, Shield, Check, X, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

interface PropertyImage {
  id: number;
  image_path: string;
  is_primary: boolean;
}

interface Property {
  id: number;
  title: string;
  description: string;
  property_type: string;
  room_type: string;
  bedrooms: number;
  bathrooms: number;
  beds: number;
  max_guests: number;
  area_sqm?: number;
  has_parking: boolean;
  has_balcony: boolean;
  has_terrace: boolean;
  has_garden: boolean;
  amenities?: string[];
  safety_features?: string[];
  house_rules?: string[];
  address: string;
  city: string;
  postal_code: string;
  nightly_rate: number;
  weekly_rate?: number;
  monthly_rate?: number;
  cleaning_fee?: number;
  security_deposit?: number;
  min_nights: number;
  checkin_start: string;
  checkin_end: string;
  checkout_time: string;
  host_about?: string;
  host_languages?: string[];
  rating: number;
  rating_count: number;
  images: PropertyImage[];
  owner: {
    id: number;
    name: string;
    rating: number;
    rating_count: number;
  };
  reviews?: any[];
}

const props = defineProps<{
  property: Property;
  similarProperties?: Property[];
  isFavorite?: boolean;
}>();

const currentImageIndex = ref(0);

const nextImage = () => {
  if (currentImageIndex.value < props.property.images.length - 1) {
    currentImageIndex.value++;
  }
};

const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--;
  }
};

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(price);
};

const propertyTypeLabel = (type: string) => {
  const types: Record<string, string> = {
    apartment: 'Appartement',
    house: 'Maison',
    studio: 'Studio',
    villa: 'Villa',
    loft: 'Loft',
    townhouse: 'Maison de ville',
    cottage: 'Cottage',
    chalet: 'Chalet',
    castle: 'Château',
    other: 'Autre',
  };
  return types[type] || type;
};

const roomTypeLabel = (type: string) => {
  const types: Record<string, string> = {
    entire_place: 'Logement entier',
    private_room: 'Chambre privée',
    shared_room: 'Chambre partagée',
  };
  return types[type] || type;
};
</script>

<template>
  <Head :title="property.title" />

  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              {{ property.title }}
            </h1>
            <div class="flex items-center gap-4 text-gray-600 dark:text-gray-300">
              <span class="flex items-center gap-1">
                <MapPin class="w-4 h-4" />
                {{ property.city }}, {{ property.postal_code }}
              </span>
              <span v-if="property.rating > 0" class="flex items-center gap-1">
                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                {{ Number(property.rating || 0).toFixed(1) }} ({{ property.rating_count }} avis)
              </span>
            </div>
          </div>
          <FavoriteButton
            v-if="isFavorite !== undefined"
            :favoritable-type="'Property'"
            :favoritable-id="property.id"
            :is-favorited="isFavorite"
          />
        </div>
      </div>

      <!-- Image Gallery -->
      <Card class="mb-8">
        <CardContent class="p-0">
          <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
            <img
              v-if="property.images && property.images.length > 0"
              :src="`/storage/${property.images[currentImageIndex].image_path}`"
              :alt="property.title"
              class="w-full h-full object-cover"
            />
            <div v-else class="flex items-center justify-center h-full">
              <Home class="w-16 h-16 text-gray-400" />
            </div>

            <!-- Navigation Arrows -->
            <div v-if="property.images && property.images.length > 1" class="absolute inset-0 flex items-center justify-between p-4">
              <Button
                variant="ghost"
                size="icon"
                :disabled="currentImageIndex === 0"
                @click="prevImage"
                class="bg-white/80 hover:bg-white"
              >
                <ChevronLeft class="w-6 h-6" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                :disabled="currentImageIndex === property.images.length - 1"
                @click="nextImage"
                class="bg-white/80 hover:bg-white"
              >
                <ChevronRight class="w-6 h-6" />
              </Button>
            </div>

            <!-- Image Counter -->
            <div v-if="property.images && property.images.length > 0" class="absolute bottom-4 right-4 bg-black/60 text-white px-3 py-1 rounded-full text-sm">
              {{ currentImageIndex + 1 }} / {{ property.images.length }}
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Property Info -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center justify-between">
                <span>{{ propertyTypeLabel(property.property_type) }} - {{ roomTypeLabel(property.room_type) }}</span>
                <Badge>{{ property.owner.name }}</Badge>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center gap-6 text-gray-700 dark:text-gray-300">
                <span class="flex items-center gap-2">
                  <Users class="w-5 h-5" />
                  {{ property.max_guests }} voyageurs
                </span>
                <span class="flex items-center gap-2">
                  <Home class="w-5 h-5" />
                  {{ property.bedrooms }} chambres
                </span>
                <span class="flex items-center gap-2">
                  <Bed class="w-5 h-5" />
                  {{ property.beds }} lits
                </span>
                <span class="flex items-center gap-2">
                  <Bath class="w-5 h-5" />
                  {{ property.bathrooms }} salles de bain
                </span>
              </div>

              <Separator />

              <div>
                <h3 class="font-semibold mb-2">Description</h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ property.description }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Amenities -->
          <Card v-if="property.amenities && property.amenities.length > 0">
            <CardHeader>
              <CardTitle>Équipements</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div v-for="amenity in property.amenities" :key="amenity" class="flex items-center gap-2">
                  <Check class="w-4 h-4 text-green-600" />
                  <span>{{ amenity }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Safety Features -->
          <Card v-if="property.safety_features && property.safety_features.length > 0">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Shield class="w-5 h-5" />
                Sécurité
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="feature in property.safety_features" :key="feature" class="flex items-center gap-2">
                  <Check class="w-4 h-4 text-green-600" />
                  <span>{{ feature }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- House Rules -->
          <Card v-if="property.house_rules && property.house_rules.length > 0">
            <CardHeader>
              <CardTitle>Règlement intérieur</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2">
                <li v-for="rule in property.house_rules" :key="rule" class="flex items-start gap-2">
                  <X class="w-4 h-4 text-red-600 mt-0.5" />
                  <span>{{ rule }}</span>
                </li>
              </ul>
            </CardContent>
          </Card>

          <!-- Host Info -->
          <Card v-if="property.host_about">
            <CardHeader>
              <CardTitle>À propos de l'hôte</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
                  <span class="text-lg font-semibold">{{ property.owner.name.charAt(0) }}</span>
                </div>
                <div>
                  <p class="font-semibold">{{ property.owner.name }}</p>
                  <p v-if="property.owner.rating > 0" class="text-sm text-gray-600">
                    ⭐ {{ Number(property.owner.rating || 0).toFixed(1) }} ({{ property.owner.rating_count }} avis)
                  </p>
                </div>
              </div>
              <p class="text-gray-700 dark:text-gray-300">{{ property.host_about }}</p>
              <div v-if="property.host_languages && property.host_languages.length > 0">
                <span class="text-sm text-gray-600">Langues: {{ property.host_languages.join(', ') }}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Booking Card -->
        <div class="lg:col-span-1">
          <Card class="sticky top-4">
            <CardHeader>
              <CardTitle class="flex items-center justify-between">
                <span class="text-2xl flex items-center gap-1">
                  <Euro class="w-6 h-6" />
                  {{ formatPrice(property.nightly_rate) }}
                </span>
                <span class="text-sm text-gray-600">/ nuit</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Pricing -->
              <div class="space-y-2 text-sm">
                <div v-if="property.weekly_rate" class="flex justify-between">
                  <span>Tarif hebdomadaire</span>
                  <span class="font-semibold">{{ formatPrice(property.weekly_rate) }}</span>
                </div>
                <div v-if="property.monthly_rate" class="flex justify-between">
                  <span>Tarif mensuel</span>
                  <span class="font-semibold">{{ formatPrice(property.monthly_rate) }}</span>
                </div>
                <div v-if="property.cleaning_fee" class="flex justify-between text-gray-600">
                  <span>Frais de ménage</span>
                  <span>{{ formatPrice(property.cleaning_fee) }}</span>
                </div>
                <div v-if="property.security_deposit" class="flex justify-between text-gray-600">
                  <span>Caution</span>
                  <span>{{ formatPrice(property.security_deposit) }}</span>
                </div>
              </div>

              <Separator />

              <!-- Check-in Info -->
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                  <Clock class="w-4 h-4" />
                  <span>Arrivée: {{ property.checkin_start }} - {{ property.checkin_end }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Clock class="w-4 h-4" />
                  <span>Départ: {{ property.checkout_time }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Calendar class="w-4 h-4" />
                  <span>Séjour minimum: {{ property.min_nights }} nuits</span>
                </div>
              </div>

              <Separator />

              <!-- Book Button -->
              <Link :href="route('property-bookings.create', property.id)">
                <Button class="w-full" size="lg">
                  Réserver maintenant
                </Button>
              </Link>

              <!-- Edit Button (if owner) -->
              <Link
                v-if="$page.props.auth?.user?.id === property.owner.id"
                :href="route('properties.edit', property.id)"
              >
                <Button variant="outline" class="w-full">
                  Modifier l'annonce
                </Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Similar Properties -->
      <div v-if="similarProperties && similarProperties.length > 0" class="mt-12">
        <h2 class="text-2xl font-bold mb-6">Propriétés similaires</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Link
            v-for="similar in similarProperties"
            :key="similar.id"
            :href="route('properties.show', similar.id)"
            class="group"
          >
            <Card class="overflow-hidden hover:shadow-lg transition-shadow">
              <div class="aspect-video bg-gray-200 relative">
                <img
                  v-if="similar.images && similar.images[0]"
                  :src="`/storage/${similar.images[0].image_path}`"
                  :alt="similar.title"
                  class="w-full h-full object-cover"
                />
                <div class="absolute top-2 right-2">
                  <Badge>{{ formatPrice(similar.nightly_rate) }}/nuit</Badge>
                </div>
              </div>
              <CardContent class="p-4">
                <h3 class="font-semibold line-clamp-2 mb-2">{{ similar.title }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ similar.city }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <span>{{ similar.bedrooms }} ch</span>
                  <span>•</span>
                  <span>{{ similar.max_guests }} voyageurs</span>
                </div>
              </CardContent>
            </Card>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
