<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useAIRecommendations } from '@/composables/useAIRecommendations';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Star, TrendingUp, MapPin, Heart, ThumbsUp, ThumbsDown, X, Sparkles } from 'lucide-vue-next';

interface Props {
  type?: 'all' | 'vehicles' | 'properties' | 'equipment';
  limit?: number;
  title?: string;
  showFeedback?: boolean;
  horizontal?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'all',
  limit: 6,
  title: 'Recommandé pour vous',
  showFeedback: true,
  horizontal: false,
});

const {
  recommendations,
  isLoading,
  error,
  getRecommendations,
  trackClick,
  provideFeedback,
  markRecommendationViewed,
} = useAIRecommendations();

const dismissedItems = ref<Set<number>>(new Set());

onMounted(async () => {
  await getRecommendations(props.type, props.limit);
  
  // Mark all as viewed after a short delay
  setTimeout(() => {
    recommendations.value.forEach(rec => {
      markRecommendationViewed(rec.id);
    });
  }, 2000);
});

// Reload when type changes
watch(() => props.type, async (newType) => {
  await getRecommendations(newType, props.limit);
});

// Get item image
const getItemImage = (item: any) => {
  if (item.images && item.images.length > 0) {
    const primaryImage = item.images.find((img: any) => img.is_primary) || item.images[0];
    return `/storage/${primaryImage.image_path}`;
  }
  return '/images/placeholder.jpg';
};

// Get item price
const getItemPrice = (item: any) => {
  if (item.daily_rate) return `${item.daily_rate}€/jour`;
  if (item.price_per_night) return `${item.price_per_night}€/nuit`;
  if (item.hourly_rate) return `${item.hourly_rate}€/heure`;
  return 'Prix variable';
};

// Get item route
const getItemRoute = (rec: any) => {
  switch (rec.type) {
    case 'vehicle':
      return route('vehicles.show', rec.entity.id);
    case 'property':
      return route('properties.show', rec.entity.id);
    case 'equipment':
      return route('equipment.show', rec.entity.id);
    default:
      return '#';
  }
};

// Handle item click
const handleItemClick = (rec: any) => {
  trackClick(rec.type, rec.entity.id, rec.id);
};

// Handle feedback
const handleFeedback = async (rec: any, type: 'helpful' | 'not_helpful') => {
  await provideFeedback(rec.id, type);
  dismissedItems.value.add(rec.id);
};

// Dismiss recommendation
const dismissRecommendation = (rec: any) => {
  provideFeedback(rec.id, 'irrelevant');
  dismissedItems.value.add(rec.id);
};

// Get recommendation icon
const getRecommendationIcon = (type: string) => {
  switch (type) {
    case 'trending':
      return TrendingUp;
    case 'location_based':
      return MapPin;
    case 'personalized':
      return Star;
    default:
      return Sparkles;
  }
};

// Filter out dismissed items
const visibleRecommendations = computed(() => {
  return recommendations.value.filter(rec => !dismissedItems.value.has(rec.id));
});
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2">
          <Sparkles class="w-5 h-5 text-yellow-500" />
          {{ title }}
        </CardTitle>
        
        <Badge variant="secondary" class="text-xs">
          IA
        </Badge>
      </div>
    </CardHeader>
    
    <CardContent>
      <!-- Loading State -->
      <div v-if="isLoading" class="grid gap-4" :class="horizontal ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'">
        <div v-for="i in props.limit" :key="i" class="space-y-2">
          <Skeleton class="h-40 w-full" />
          <Skeleton class="h-4 w-3/4" />
          <Skeleton class="h-4 w-1/2" />
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8 text-gray-500">
        <p>{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="visibleRecommendations.length === 0" class="text-center py-8">
        <Sparkles class="w-12 h-12 mx-auto text-gray-300 mb-4" />
        <p class="text-gray-500">Aucune recommandation disponible</p>
        <p class="text-sm text-gray-400 mt-2">
          Explorez notre catalogue pour améliorer vos suggestions
        </p>
      </div>

      <!-- Recommendations -->
      <ScrollArea v-else :class="horizontal ? 'w-full' : ''">
        <div 
          class="grid gap-4" 
          :class="horizontal ? 'grid-flow-col auto-cols-[300px]' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'"
        >
          <div
            v-for="rec in visibleRecommendations"
            :key="rec.id"
            class="group relative bg-white dark:bg-gray-800 rounded-lg border hover:shadow-lg transition-all"
          >
            <!-- Dismiss Button -->
            <button
              v-if="showFeedback"
              @click="dismissRecommendation(rec)"
              class="absolute top-2 right-2 z-10 bg-white/90 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <X class="w-4 h-4" />
            </button>

            <Link
              :href="getItemRoute(rec)"
              @click="handleItemClick(rec)"
              class="block"
            >
              <!-- Image -->
              <div class="relative h-40 overflow-hidden rounded-t-lg">
                <img
                  :src="getItemImage(rec.entity)"
                  :alt="rec.entity.name || rec.entity.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                />
                
                <!-- Score Badge -->
                <div class="absolute top-2 left-2">
                  <Badge class="bg-black/50 text-white text-xs">
                    <component :is="getRecommendationIcon(rec.recommendation_type)" class="w-3 h-3 mr-1" />
                    {{ Math.round(rec.score * 100) }}%
                  </Badge>
                </div>

                <!-- Price -->
                <div class="absolute bottom-2 right-2">
                  <Badge class="bg-white/90 text-black">
                    {{ getItemPrice(rec.entity) }}
                  </Badge>
                </div>
              </div>

              <!-- Content -->
              <div class="p-4">
                <h3 class="font-semibold text-sm mb-1 line-clamp-1">
                  {{ rec.entity.name || rec.entity.title }}
                </h3>
                
                <p class="text-xs text-gray-500 mb-2 line-clamp-1">
                  <MapPin class="inline w-3 h-3 mr-1" />
                  {{ rec.entity.city }}
                </p>

                <p class="text-xs text-gray-600 dark:text-gray-400 italic">
                  {{ rec.reason }}
                </p>

                <!-- Rating -->
                <div v-if="rec.entity.rating" class="flex items-center gap-1 mt-2">
                  <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                  <span class="text-xs">{{ rec.entity.rating?.toFixed(1) }}</span>
                  <span class="text-xs text-gray-500">({{ rec.entity.rating_count || 0 }})</span>
                </div>
              </div>
            </Link>

            <!-- Feedback Buttons -->
            <div v-if="showFeedback" class="px-4 pb-3 flex gap-2">
              <Button
                size="sm"
                variant="ghost"
                class="flex-1 h-7 text-xs"
                @click="handleFeedback(rec, 'helpful')"
              >
                <ThumbsUp class="w-3 h-3 mr-1" />
                Utile
              </Button>
              <Button
                size="sm"
                variant="ghost"
                class="flex-1 h-7 text-xs"
                @click="handleFeedback(rec, 'not_helpful')"
              >
                <ThumbsDown class="w-3 h-3 mr-1" />
                Pas utile
              </Button>
            </div>
          </div>
        </div>
        
        <ScrollBar v-if="horizontal" orientation="horizontal" />
      </ScrollArea>

      <!-- View More -->
      <div v-if="visibleRecommendations.length > 0" class="mt-6 text-center">
        <Link :href="route('ai.dashboard')">
          <Button variant="outline" size="sm">
            Voir plus de recommandations
          </Button>
        </Link>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}