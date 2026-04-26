<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Calendar, Euro, Clock, User, Shield, AlertCircle, CheckCircle } from 'lucide-vue-next';

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
  min_rental_duration: number;
  max_rental_duration?: number;
  security_deposit?: number;
  delivery_available: boolean;
  delivery_radius?: number;
  delivery_fee?: number;
  license_required: boolean;
  license_type?: string;
  min_age?: number;
  max_quantity: number;
  owner: {
    id: number;
    name: string;
    email: string;
    rating: number;
    rating_count: number;
  };
  images: Array<{
    id: number;
    image_path: string;
    alt_text: string;
    is_primary: boolean;
  }>;
}

interface Props {
  equipment: Equipment;
  categoryConfig?: { subcategories: Record<string, string> } | null;
  licenseRequired?: boolean;
}

const props = defineProps<Props>();

const form = useForm({
  start_datetime: '',
  end_datetime: '',
  duration_unit: props.equipment.rental_unit,
  pickup_address: '',
  delivery_address: '',
  special_requests: '',
  usage_purpose: '',
});

// Computed properties
const availableUnits = computed(() => {
  const units = [];
  if (props.equipment.hourly_rate != null) units.push({ value: 'hour', label: 'À l\'heure', rate: props.equipment.hourly_rate });
  if (props.equipment.daily_rate != null) units.push({ value: 'day', label: 'À la journée', rate: props.equipment.daily_rate });
  if (props.equipment.weekly_rate != null) units.push({ value: 'week', label: 'À la semaine', rate: props.equipment.weekly_rate });
  if (props.equipment.monthly_rate != null) units.push({ value: 'month', label: 'Au mois', rate: props.equipment.monthly_rate });
  return units;
});

const selectedUnit = computed(() => {
  return availableUnits.value.find(unit => unit.value === form.duration_unit);
});

const estimatedDuration = ref(0);
const estimatedPrice = ref(0);

// Calculate pricing when dates or quantity change
const calculatePricing = () => {
  if (!form.start_datetime || !form.end_datetime || !selectedUnit.value) {
    estimatedDuration.value = 0;
    estimatedPrice.value = 0;
    return;
  }

  const startDate = new Date(form.start_datetime);
  const endDate = new Date(form.end_datetime);
  const diffTime = Math.abs(endDate.getTime() - startDate.getTime());

  let duration = 0;
  switch (form.duration_unit) {
    case 'hour':
      duration = Math.ceil(diffTime / (1000 * 60 * 60));
      break;
    case 'day':
      duration = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
      break;
    case 'week':
      duration = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 7)));
      break;
    case 'month':
      duration = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30)));
      break;
  }

  estimatedDuration.value = duration;

  const basePrice = selectedUnit.value.rate * duration;

  let discountPercent = 0;
  if (form.duration_unit === 'day' && duration >= 7) discountPercent = 10;
  else if (form.duration_unit === 'day' && duration >= 3) discountPercent = 5;
  else if (form.duration_unit === 'week' && duration >= 4) discountPercent = 15;
  else if (form.duration_unit === 'week' && duration >= 2) discountPercent = 8;
  else if (form.duration_unit === 'month' && duration >= 3) discountPercent = 20;
  
  const discountAmount = basePrice * (discountPercent / 100);
  const finalPrice = basePrice - discountAmount;
  
  // Add platform and delivery fees
  const platformFee = finalPrice * 0.05;
  const deliveryFee = (form.pickup_location && props.equipment.delivery_available) ? (props.equipment.delivery_fee || 0) : 0;
  
  estimatedPrice.value = finalPrice + platformFee + deliveryFee;
};

// Watch for changes in form fields
watch([() => form.start_date, () => form.end_date, () => form.quantity, () => form.rental_unit], calculatePricing);

// Get equipment image
const getEquipmentImage = () => {
  const primaryImage = props.equipment.images.find(img => img.is_primary) || props.equipment.images[0];
  return primaryImage ? `/storage/${primaryImage.image_path}` : '/images/equipment-placeholder.jpg';
};

// Get today's date for min date validation
const today = new Date().toISOString().split('T')[0];

// Submit form
const submit = () => {
  form.post(route('equipment-bookings.store', props.equipment.id));
};

// Get rental unit label
const getRentalUnitLabel = (unit: string) => {
  const labels = {
    hour: '/heure',
    day: '/jour', 
    week: '/semaine',
    month: '/mois'
  };
  return labels[unit as keyof typeof labels] || '/jour';
};
</script>

<template>
  <Head :title="`Réserver ${equipment.name}`" />
  
  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          Réserver ce matériel
        </h1>
        <p class="text-gray-600 dark:text-gray-300">
          Complétez les détails de votre réservation ci-dessous
        </p>
      </div>

      <!-- License Alert -->
      <Alert v-if="licenseRequired" class="mb-6 border-orange-200 bg-orange-50">
        <Shield class="h-4 w-4 text-orange-500" />
        <AlertDescription class="text-orange-700">
          Un permis de conduire vérifié est requis pour louer ce matériel. 
          <Link :href="route('license.verification')" class="underline font-medium">Vérifiez votre permis</Link>
          avant de continuer.
        </AlertDescription>
      </Alert>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Booking Form -->
        <div class="lg:col-span-2">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Equipment Summary -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Calendar class="w-5 h-5" />
                  Matériel sélectionné
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex gap-4">
                  <img
                    :src="getEquipmentImage()"
                    :alt="equipment.name"
                    class="w-24 h-24 object-cover rounded-lg"
                  />
                  <div class="flex-1">
                    <h3 class="font-semibold text-lg">{{ equipment.name }}</h3>
                    <p class="text-gray-600 mb-2">{{ equipment.city }}</p>
                    <div class="flex items-center gap-2">
                      <Badge>{{ categoryConfig?.subcategories[equipment.subcategory] || equipment.subcategory }}</Badge>
                      <Badge v-if="equipment.delivery_available" variant="secondary">
                        Livraison disponible
                      </Badge>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Dates & Duration -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Clock class="w-5 h-5" />
                  Dates et durée
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="start_datetime">Date de début *</Label>
                    <Input
                      id="start_datetime"
                      v-model="form.start_datetime"
                      type="datetime-local"
                      :min="today"
                      required
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.start_datetime }"
                    />
                    <div v-if="form.errors.start_datetime" class="text-red-500 text-sm mt-1">
                      {{ form.errors.start_datetime }}
                    </div>
                  </div>

                  <div>
                    <Label for="end_datetime">Date de fin *</Label>
                    <Input
                      id="end_datetime"
                      v-model="form.end_datetime"
                      type="datetime-local"
                      :min="form.start_datetime || today"
                      required
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.end_datetime }"
                    />
                    <div v-if="form.errors.end_datetime" class="text-red-500 text-sm mt-1">
                      {{ form.errors.end_datetime }}
                    </div>
                  </div>
                </div>

                <div>
                  <Label for="duration_unit">Unité de location</Label>
                  <Select v-model="form.duration_unit">
                    <SelectTrigger class="mt-1">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="unit in availableUnits"
                        :key="unit.value"
                        :value="unit.value"
                      >
                        {{ unit.label }} - {{ unit.rate }}€{{ getRentalUnitLabel(unit.value) }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div v-if="estimatedDuration > 0" class="p-3 bg-blue-50 rounded-lg">
                  <p class="text-sm text-blue-700">
                    <CheckCircle class="inline w-4 h-4 mr-1" />
                    Durée estimée: {{ estimatedDuration }} {{ form.duration_unit === 'hour' ? 'heure' : form.duration_unit === 'day' ? 'jour' : form.duration_unit === 'week' ? 'semaine' : 'mois' }}{{ estimatedDuration > 1 ? 's' : '' }}
                  </p>
                </div>
              </CardContent>
            </Card>

            <!-- Location & Delivery -->
            <Card>
              <CardHeader>
                <CardTitle>Retrait et livraison</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div v-if="equipment.delivery_available">
                  <Label for="delivery_address">Adresse de livraison (optionnel)</Label>
                  <Textarea
                    id="delivery_address"
                    v-model="form.delivery_address"
                    placeholder="Laissez vide pour un retrait sur place..."
                    rows="2"
                    class="mt-1"
                  />
                  <p class="text-sm text-gray-600 mt-1">
                    Frais de livraison: {{ equipment.delivery_fee || 0 }}€ 
                    (dans un rayon de {{ equipment.delivery_radius || 10 }}km)
                  </p>
                </div>
                <div v-else>
                  <p class="text-sm text-gray-600">
                    Retrait uniquement - {{ equipment.city }}
                  </p>
                </div>
              </CardContent>
            </Card>

            <!-- Special Requests -->
            <Card>
              <CardHeader>
                <CardTitle>Demandes spéciales</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div>
                  <Label for="usage_purpose">Motif de la location</Label>
                  <Input
                    id="usage_purpose"
                    v-model="form.usage_purpose"
                    placeholder="ex: Travaux de rénovation, événement..."
                    class="mt-1"
                  />
                </div>
                <div>
                  <Label for="special_requests">Message pour le propriétaire (optionnel)</Label>
                  <Textarea
                    id="special_requests"
                    v-model="form.special_requests"
                    placeholder="Questions, demandes spéciales, horaires préférés..."
                    rows="3"
                    class="mt-1"
                  />
                </div>
              </CardContent>
            </Card>

            <!-- Requirements Warning -->
            <Alert v-if="equipment.license_required || equipment.min_age" class="border-yellow-200 bg-yellow-50">
              <AlertCircle class="h-4 w-4 text-yellow-500" />
              <AlertDescription class="text-yellow-700">
                <p class="font-medium mb-1">Conditions requises:</p>
                <ul class="list-disc list-inside space-y-1 text-sm">
                  <li v-if="equipment.min_age">Âge minimum: {{ equipment.min_age }} ans</li>
                  <li v-if="equipment.license_required">
                    Permis {{ equipment.license_type || 'de conduire' }} requis
                  </li>
                </ul>
              </AlertDescription>
            </Alert>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <Button
                type="submit"
                :disabled="form.processing || licenseRequired"
                class="min-w-32"
              >
                {{ form.processing ? 'Envoi...' : 'Envoyer la demande' }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Pricing Summary -->
        <div>
          <Card class="sticky top-4">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Euro class="w-5 h-5" />
                Résumé des coûts
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Equipment Info -->
              <div class="flex items-center gap-2 mb-4">
                <User class="w-4 h-4 text-gray-400" />
                <div class="text-sm">
                  <p class="font-medium">{{ equipment.owner.name }}</p>
                  <div class="flex items-center gap-1 text-gray-600">
                    <span>⭐</span>
                    <span>{{ Number(equipment.owner.rating || 0).toFixed(1) }}</span>
                    <span>({{ equipment.owner.rating_count || 0 }})</span>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Pricing Details -->
              <div v-if="estimatedDuration > 0" class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>{{ selectedUnit?.label }}</span>
                  <span>{{ selectedUnit?.rate }}€</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span>Durée</span>
                  <span>{{ estimatedDuration }} {{ form.duration_unit === 'hour' ? 'h' : form.duration_unit === 'day' ? 'j' : form.duration_unit === 'week' ? 'sem' : 'mois' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span>Sous-total</span>
                  <span>{{ Number(selectedUnit?.rate * estimatedDuration || 0).toFixed(2) }}€</span>
                </div>

                <Separator />

                <div class="flex justify-between text-sm">
                  <span>Frais de service (5%)</span>
                  <span>{{ Number(selectedUnit?.rate * estimatedDuration * 0.05 || 0).toFixed(2) }}€</span>
                </div>
                <div v-if="form.delivery_address && equipment.delivery_available" class="flex justify-between text-sm">
                  <span>Frais de livraison</span>
                  <span>{{ equipment.delivery_fee || 0 }}€</span>
                </div>
                <div v-if="equipment.security_deposit" class="flex justify-between text-sm text-gray-600">
                  <span>Caution (remboursable)</span>
                  <span>{{ equipment.security_deposit }}€</span>
                </div>
                
                <Separator />
                
                <div class="flex justify-between font-bold">
                  <span>Total</span>
                  <span>{{ Number(estimatedPrice || 0).toFixed(2) }}€</span>
                </div>
              </div>

              <div v-else class="text-center text-gray-500 py-4">
                <p class="text-sm">Sélectionnez des dates pour voir le prix</p>
              </div>

              <!-- Cancellation Policy -->
              <div class="mt-6 p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-600 font-medium mb-1">Politique d'annulation</p>
                <p class="text-xs text-gray-600">
                  Annulation gratuite jusqu'à 24h avant le début de la location.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>