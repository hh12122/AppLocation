<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Plus, Upload, X, MapPin, Euro, Settings, Camera, Shield } from 'lucide-vue-next';

interface Props {
  categories: Record<string, any>;
  selectedCategory: string;
}

const props = defineProps<Props>();

const form = useForm({
  name: '',
  description: '',
  category: props.selectedCategory,
  subcategory: '',
  brand: '',
  model: '',
  year: '',
  condition: 'good',
  
  // Dimensions
  length: '',
  width: '',
  height: '',
  weight: '',
  size: '',
  capacity: '',
  area_sqm: '',
  
  // Features & Items
  features: [] as string[],
  included_items: [] as string[],
  safety_equipment: [] as string[],
  
  // Instructions
  usage_instructions: '',
  safety_instructions: '',
  
  // Location
  address: '',
  city: '',
  postal_code: '',
  country: 'France',
  latitude: '',
  longitude: '',
  pickup_instructions: '',
  
  // Delivery
  delivery_available: false,
  delivery_radius: '',
  delivery_fee: '',
  
  // Pricing
  hourly_rate: '',
  daily_rate: '',
  weekly_rate: '',
  monthly_rate: '',
  security_deposit: '',
  cleaning_fee: '',
  
  // Rental settings
  min_rental_duration: 1,
  max_rental_duration: '',
  rental_unit: 'day',
  pickup_type: 'both',
  
  // Requirements
  min_age: '',
  license_required: false,
  license_type: '',
  experience_required: false,
  rental_requirements: '',
  restrictions: [] as string[],
  
  // Insurance
  insurance_included: false,
  insurance_details: '',
  liability_terms: '',
  
  // Settings
  instant_booking: false,
  
  // Images
  images: [] as File[],
  
  // Category-specific attributes
  category_attributes: {} as Record<string, any>,
});

// Current category config
const currentCategoryConfig = computed(() => {
  return props.categories[form.category] || {};
});

// Available subcategories
const availableSubcategories = computed(() => {
  return currentCategoryConfig.value.subcategories || {};
});

// Feature management
const newFeature = ref('');
const newIncludedItem = ref('');
const newSafetyEquipment = ref('');
const newRestriction = ref('');

const addFeature = () => {
  if (newFeature.value.trim()) {
    form.features.push(newFeature.value.trim());
    newFeature.value = '';
  }
};

const removeFeature = (index: number) => {
  form.features.splice(index, 1);
};

const addIncludedItem = () => {
  if (newIncludedItem.value.trim()) {
    form.included_items.push(newIncludedItem.value.trim());
    newIncludedItem.value = '';
  }
};

const removeIncludedItem = (index: number) => {
  form.included_items.splice(index, 1);
};

const addSafetyEquipment = () => {
  if (newSafetyEquipment.value.trim()) {
    form.safety_equipment.push(newSafetyEquipment.value.trim());
    newSafetyEquipment.value = '';
  }
};

const removeSafetyEquipment = (index: number) => {
  form.safety_equipment.splice(index, 1);
};

const addRestriction = () => {
  if (newRestriction.value.trim()) {
    form.restrictions.push(newRestriction.value.trim());
    newRestriction.value = '';
  }
};

const removeRestriction = (index: number) => {
  form.restrictions.splice(index, 1);
};

// Image handling
const fileInput = ref<HTMLInputElement>();
const previewUrls = ref<string[]>([]);

const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;
  
  if (files) {
    Array.from(files).forEach(file => {
      if (form.images.length < 15) {
        form.images.push(file);
        
        // Create preview URL
        const url = URL.createObjectURL(file);
        previewUrls.value.push(url);
      }
    });
  }
};

const removeImage = (index: number) => {
  // Revoke the object URL to prevent memory leaks
  URL.revokeObjectURL(previewUrls.value[index]);
  
  form.images.splice(index, 1);
  previewUrls.value.splice(index, 1);
};

// Watch category changes to update subcategory
watch(() => form.category, () => {
  form.subcategory = '';
  form.category_attributes = {};
});

// Submit form
const submit = () => {
  form.post(route('equipment.store'), {
    forceFormData: true,
  });
};

// Condition options
const conditionOptions = [
  { value: 'new', label: 'Neuf' },
  { value: 'excellent', label: 'Excellent' },
  { value: 'good', label: 'Bon' },
  { value: 'fair', label: 'Correct' },
  { value: 'poor', label: 'Mauvais' },
];

// Pickup type options
const pickupTypeOptions = [
  { value: 'pickup_only', label: 'Retrait uniquement' },
  { value: 'delivery_only', label: 'Livraison uniquement' },
  { value: 'both', label: 'Retrait et livraison' },
];

// Rental unit options
const rentalUnitOptions = [
  { value: 'hour', label: 'À l\'heure' },
  { value: 'day', label: 'À la journée' },
  { value: 'week', label: 'À la semaine' },
  { value: 'month', label: 'Au mois' },
];
</script>

<template>
  <Head title="Ajouter du matériel" />
  
  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Ajouter mon matériel
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
          Créez une annonce pour louer votre matériel à d'autres utilisateurs
        </p>
      </div>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <Tabs default-value="basic" class="space-y-6">
          <TabsList class="grid w-full grid-cols-5">
            <TabsTrigger value="basic">Informations</TabsTrigger>
            <TabsTrigger value="details">Détails</TabsTrigger>
            <TabsTrigger value="pricing">Tarifs</TabsTrigger>
            <TabsTrigger value="requirements">Conditions</TabsTrigger>
            <TabsTrigger value="images">Photos</TabsTrigger>
          </TabsList>

          <!-- Basic Information -->
          <TabsContent value="basic" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Settings class="w-5 h-5" />
                  Informations de base
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Name -->
                  <div class="md:col-span-2">
                    <Label for="name">Nom du matériel *</Label>
                    <Input
                      id="name"
                      v-model="form.name"
                      placeholder="ex: VTT Trek X-Caliber 7"
                      required
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.name }"
                    />
                    <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                      {{ form.errors.name }}
                    </div>
                  </div>

                  <!-- Category -->
                  <div>
                    <Label for="category">Catégorie *</Label>
                    <Select v-model="form.category">
                      <SelectTrigger class="mt-1">
                        <SelectValue placeholder="Sélectionnez une catégorie" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="(category, key) in categories"
                          :key="key"
                          :value="key"
                        >
                          {{ category.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <div v-if="form.errors.category" class="text-red-500 text-sm mt-1">
                      {{ form.errors.category }}
                    </div>
                  </div>

                  <!-- Subcategory -->
                  <div>
                    <Label for="subcategory">Sous-catégorie *</Label>
                    <Select v-model="form.subcategory">
                      <SelectTrigger class="mt-1">
                        <SelectValue placeholder="Sélectionnez une sous-catégorie" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="(label, key) in availableSubcategories"
                          :key="key"
                          :value="key"
                        >
                          {{ label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <div v-if="form.errors.subcategory" class="text-red-500 text-sm mt-1">
                      {{ form.errors.subcategory }}
                    </div>
                  </div>

                  <!-- Brand & Model -->
                  <div>
                    <Label for="brand">Marque</Label>
                    <Input
                      id="brand"
                      v-model="form.brand"
                      placeholder="ex: Trek"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="model">Modèle</Label>
                    <Input
                      id="model"
                      v-model="form.model"
                      placeholder="ex: X-Caliber 7"
                      class="mt-1"
                    />
                  </div>

                  <!-- Year & Condition -->
                  <div>
                    <Label for="year">Année</Label>
                    <Input
                      id="year"
                      v-model="form.year"
                      type="number"
                      min="1900"
                      max="2030"
                      placeholder="2023"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="condition">État *</Label>
                    <Select v-model="form.condition">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="option in conditionOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Description -->
                <div>
                  <Label for="description">Description *</Label>
                  <Textarea
                    id="description"
                    v-model="form.description"
                    placeholder="Décrivez votre matériel en détail..."
                    rows="4"
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.description }"
                  />
                  <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                    {{ form.errors.description }}
                  </div>
                </div>

                <!-- Location -->
                <div class="space-y-4">
                  <h3 class="text-lg font-semibold flex items-center gap-2">
                    <MapPin class="w-4 h-4" />
                    Localisation
                  </h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                      <Label for="address">Adresse *</Label>
                      <Input
                        id="address"
                        v-model="form.address"
                        placeholder="123 Rue de la Paix"
                        class="mt-1"
                        :class="{ 'border-red-500': form.errors.address }"
                      />
                    </div>

                    <div>
                      <Label for="city">Ville *</Label>
                      <Input
                        id="city"
                        v-model="form.city"
                        placeholder="Paris"
                        class="mt-1"
                        :class="{ 'border-red-500': form.errors.city }"
                      />
                    </div>

                    <div>
                      <Label for="postal_code">Code postal *</Label>
                      <Input
                        id="postal_code"
                        v-model="form.postal_code"
                        placeholder="75001"
                        class="mt-1"
                        :class="{ 'border-red-500': form.errors.postal_code }"
                      />
                    </div>
                  </div>

                  <div>
                    <Label for="pickup_instructions">Instructions de retrait</Label>
                    <Textarea
                      id="pickup_instructions"
                      v-model="form.pickup_instructions"
                      placeholder="Où et comment récupérer le matériel..."
                      rows="2"
                      class="mt-1"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Details -->
          <TabsContent value="details" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Features -->
              <Card>
                <CardHeader>
                  <CardTitle>Caractéristiques</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex gap-2">
                    <Input
                      v-model="newFeature"
                      placeholder="Ajouter une caractéristique"
                      @keydown.enter.prevent="addFeature"
                    />
                    <Button type="button" @click="addFeature">
                      <Plus class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <Badge
                      v-for="(feature, index) in form.features"
                      :key="index"
                      variant="secondary"
                      class="flex items-center gap-1"
                    >
                      {{ feature }}
                      <button
                        type="button"
                        @click="removeFeature(index)"
                        class="text-xs hover:text-red-500"
                      >
                        <X class="w-3 h-3" />
                      </button>
                    </Badge>
                  </div>
                </CardContent>
              </Card>

              <!-- Included Items -->
              <Card>
                <CardHeader>
                  <CardTitle>Inclus dans la location</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex gap-2">
                    <Input
                      v-model="newIncludedItem"
                      placeholder="ex: Casque, pompe..."
                      @keydown.enter.prevent="addIncludedItem"
                    />
                    <Button type="button" @click="addIncludedItem">
                      <Plus class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <Badge
                      v-for="(item, index) in form.included_items"
                      :key="index"
                      variant="secondary"
                      class="flex items-center gap-1"
                    >
                      {{ item }}
                      <button
                        type="button"
                        @click="removeIncludedItem(index)"
                        class="text-xs hover:text-red-500"
                      >
                        <X class="w-3 h-3" />
                      </button>
                    </Badge>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Instructions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>Instructions d'utilisation</CardTitle>
                </CardHeader>
                <CardContent>
                  <Textarea
                    v-model="form.usage_instructions"
                    placeholder="Comment utiliser ce matériel..."
                    rows="4"
                  />
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Consignes de sécurité</CardTitle>
                </CardHeader>
                <CardContent>
                  <Textarea
                    v-model="form.safety_instructions"
                    placeholder="Précautions à prendre..."
                    rows="4"
                  />
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <!-- Pricing -->
          <TabsContent value="pricing" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Euro class="w-5 h-5" />
                  Tarification
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                  <div>
                    <Label for="hourly_rate">Tarif horaire (€)</Label>
                    <Input
                      id="hourly_rate"
                      v-model="form.hourly_rate"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="25.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="daily_rate">Tarif journalier (€)</Label>
                    <Input
                      id="daily_rate"
                      v-model="form.daily_rate"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="50.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="weekly_rate">Tarif hebdomadaire (€)</Label>
                    <Input
                      id="weekly_rate"
                      v-model="form.weekly_rate"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="300.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="monthly_rate">Tarif mensuel (€)</Label>
                    <Input
                      id="monthly_rate"
                      v-model="form.monthly_rate"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="1000.00"
                      class="mt-1"
                    />
                  </div>
                </div>

                <Separator />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="security_deposit">Caution (€)</Label>
                    <Input
                      id="security_deposit"
                      v-model="form.security_deposit"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="100.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="cleaning_fee">Frais de nettoyage (€)</Label>
                    <Input
                      id="cleaning_fee"
                      v-model="form.cleaning_fee"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="25.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="rental_unit">Unité de location principale</Label>
                    <Select v-model="form.rental_unit">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="option in rentalUnitOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Delivery Settings -->
                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <Switch
                      id="delivery_available"
                      v-model="form.delivery_available"
                    />
                    <Label for="delivery_available">Livraison disponible</Label>
                  </div>

                  <div v-if="form.delivery_available" class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-6">
                    <div>
                      <Label for="delivery_radius">Rayon de livraison (km)</Label>
                      <Input
                        id="delivery_radius"
                        v-model="form.delivery_radius"
                        type="number"
                        min="1"
                        max="100"
                        placeholder="10"
                        class="mt-1"
                      />
                    </div>

                    <div>
                      <Label for="delivery_fee">Frais de livraison (€)</Label>
                      <Input
                        id="delivery_fee"
                        v-model="form.delivery_fee"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="15.00"
                        class="mt-1"
                      />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Requirements -->
          <TabsContent value="requirements" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Shield class="w-5 h-5" />
                  Conditions de location
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="min_rental_duration">Durée min. (jours) *</Label>
                    <Input
                      id="min_rental_duration"
                      v-model="form.min_rental_duration"
                      type="number"
                      min="1"
                      max="365"
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.min_rental_duration }"
                    />
                  </div>

                  <div>
                    <Label for="max_rental_duration">Durée max. (jours)</Label>
                    <Input
                      id="max_rental_duration"
                      v-model="form.max_rental_duration"
                      type="number"
                      min="1"
                      max="365"
                      placeholder="30"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="min_age">Âge minimum</Label>
                    <Input
                      id="min_age"
                      v-model="form.min_age"
                      type="number"
                      min="16"
                      max="99"
                      placeholder="18"
                      class="mt-1"
                    />
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <Switch
                      id="license_required"
                      v-model="form.license_required"
                    />
                    <Label for="license_required">Permis requis</Label>
                  </div>

                  <div v-if="form.license_required" class="ml-6">
                    <Label for="license_type">Type de permis</Label>
                    <Input
                      id="license_type"
                      v-model="form.license_type"
                      placeholder="ex: Permis B, Permis bateau..."
                      class="mt-1"
                    />
                  </div>

                  <div class="flex items-center gap-2">
                    <Switch
                      id="experience_required"
                      v-model="form.experience_required"
                    />
                    <Label for="experience_required">Expérience requise</Label>
                  </div>

                  <div class="flex items-center gap-2">
                    <Switch
                      id="instant_booking"
                      v-model="form.instant_booking"
                    />
                    <Label for="instant_booking">Réservation instantanée</Label>
                  </div>
                </div>

                <div>
                  <Label for="rental_requirements">Conditions spéciales</Label>
                  <Textarea
                    id="rental_requirements"
                    v-model="form.rental_requirements"
                    placeholder="Conditions particulières pour la location..."
                    rows="3"
                    class="mt-1"
                  />
                </div>

                <!-- Restrictions -->
                <div class="space-y-4">
                  <Label>Restrictions d'usage</Label>
                  
                  <div class="flex gap-2">
                    <Input
                      v-model="newRestriction"
                      placeholder="ex: Pas d'utilisation par temps de pluie"
                      @keydown.enter.prevent="addRestriction"
                    />
                    <Button type="button" @click="addRestriction">
                      <Plus class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <Badge
                      v-for="(restriction, index) in form.restrictions"
                      :key="index"
                      variant="destructive"
                      class="flex items-center gap-1"
                    >
                      {{ restriction }}
                      <button
                        type="button"
                        @click="removeRestriction(index)"
                        class="text-xs hover:text-red-300"
                      >
                        <X class="w-3 h-3" />
                      </button>
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Images -->
          <TabsContent value="images" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Camera class="w-5 h-5" />
                  Photos du matériel
                </CardTitle>
                <p class="text-sm text-gray-600">
                  Ajoutez jusqu'à 15 photos de votre matériel. La première photo sera utilisée comme photo principale.
                </p>
              </CardHeader>
              <CardContent>
                <!-- Upload Button -->
                <div class="mb-6">
                  <input
                    ref="fileInput"
                    type="file"
                    multiple
                    accept="image/*"
                    class="hidden"
                    @change="handleImageSelect"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    :disabled="form.images.length >= 15"
                    @click="fileInput?.click()"
                  >
                    <Upload class="w-4 h-4 mr-2" />
                    Ajouter des photos ({{ form.images.length }}/15)
                  </Button>
                </div>

                <!-- Error -->
                <div v-if="form.errors.images" class="text-red-500 text-sm mb-4">
                  {{ form.errors.images }}
                </div>

                <!-- Image Previews -->
                <div v-if="form.images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div
                    v-for="(image, index) in form.images"
                    :key="index"
                    class="relative group"
                  >
                    <img
                      :src="previewUrls[index]"
                      :alt="`Image ${index + 1}`"
                      class="w-full h-32 object-cover rounded-lg border"
                    />
                    
                    <!-- Primary badge -->
                    <div v-if="index === 0" class="absolute top-2 left-2">
                      <Badge class="text-xs">Principal</Badge>
                    </div>
                    
                    <!-- Remove button -->
                    <button
                      type="button"
                      class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click="removeImage(index)"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </div>
                </div>

                <!-- Empty state -->
                <div v-if="form.images.length === 0" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
                  <Camera class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                  <p class="text-gray-600 mb-2">Aucune photo ajoutée</p>
                  <p class="text-sm text-gray-500">
                    Ajoutez des photos pour attirer plus de locataires
                  </p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        <!-- Submit -->
        <div class="flex justify-end gap-4 mt-8">
          <Button
            type="button"
            variant="outline"
            @click="$inertia.visit(route('equipment.index'))"
          >
            Annuler
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="min-w-32"
          >
            {{ form.processing ? 'Création...' : 'Créer l\'annonce' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>