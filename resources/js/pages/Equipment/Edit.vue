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
import { FormError } from '@/components/ui/form';
import { Plus, Upload, X, MapPin, Euro, Settings, Camera, Shield } from 'lucide-vue-next';

interface EquipmentImage {
  id: number;
  image_path: string;
  is_primary: boolean;
  sort_order: number;
}

interface Equipment {
  id: number;
  name: string;
  description: string;
  category: string;
  subcategory: string;
  brand?: string;
  model?: string;
  year?: number;
  condition: string;
  length?: number;
  width?: number;
  height?: number;
  weight?: number;
  size?: string;
  capacity?: number;
  area_sqm?: number;
  features: string[];
  included_items: string[];
  safety_equipment: string[];
  usage_instructions?: string;
  safety_instructions?: string;
  address: string;
  city: string;
  postal_code: string;
  country: string;
  latitude?: number;
  longitude?: number;
  pickup_instructions?: string;
  delivery_available: boolean;
  delivery_radius?: number;
  delivery_fee?: number;
  hourly_rate?: number;
  daily_rate?: number;
  weekly_rate?: number;
  monthly_rate?: number;
  security_deposit?: number;
  cleaning_fee?: number;
  min_rental_duration: number;
  max_rental_duration?: number;
  rental_unit: string;
  pickup_type: string;
  min_age?: number;
  license_required: boolean;
  license_type?: string;
  experience_required: boolean;
  rental_requirements?: string;
  restrictions: string[];
  insurance_included: boolean;
  insurance_details?: string;
  liability_terms?: string;
  instant_booking: boolean;
  status: string;
  images: EquipmentImage[];
  category_attributes?: Record<string, any>;
}

const props = defineProps<{
  equipment: Equipment;
  categories: Record<string, any>;
}>();

const form = useForm({
  name: props.equipment.name,
  description: props.equipment.description,
  category: props.equipment.category,
  subcategory: props.equipment.subcategory,
  brand: props.equipment.brand || '',
  model: props.equipment.model || '',
  year: props.equipment.year || '',
  condition: props.equipment.condition,
  length: props.equipment.length || '',
  width: props.equipment.width || '',
  height: props.equipment.height || '',
  weight: props.equipment.weight || '',
  size: props.equipment.size || '',
  capacity: props.equipment.capacity || '',
  area_sqm: props.equipment.area_sqm || '',
  features: props.equipment.features || [],
  included_items: props.equipment.included_items || [],
  safety_equipment: props.equipment.safety_equipment || [],
  usage_instructions: props.equipment.usage_instructions || '',
  safety_instructions: props.equipment.safety_instructions || '',
  address: props.equipment.address,
  city: props.equipment.city,
  postal_code: props.equipment.postal_code,
  country: props.equipment.country,
  latitude: props.equipment.latitude || '',
  longitude: props.equipment.longitude || '',
  pickup_instructions: props.equipment.pickup_instructions || '',
  delivery_available: props.equipment.delivery_available,
  delivery_radius: props.equipment.delivery_radius || '',
  delivery_fee: props.equipment.delivery_fee || '',
  hourly_rate: props.equipment.hourly_rate || '',
  daily_rate: props.equipment.daily_rate || '',
  weekly_rate: props.equipment.weekly_rate || '',
  monthly_rate: props.equipment.monthly_rate || '',
  security_deposit: props.equipment.security_deposit || '',
  cleaning_fee: props.equipment.cleaning_fee || '',
  min_rental_duration: props.equipment.min_rental_duration,
  max_rental_duration: props.equipment.max_rental_duration || '',
  rental_unit: props.equipment.rental_unit,
  pickup_type: props.equipment.pickup_type,
  min_age: props.equipment.min_age || '',
  license_required: props.equipment.license_required,
  license_type: props.equipment.license_type || '',
  experience_required: props.equipment.experience_required,
  rental_requirements: props.equipment.rental_requirements || '',
  restrictions: props.equipment.restrictions || [],
  insurance_included: props.equipment.insurance_included,
  insurance_details: props.equipment.insurance_details || '',
  liability_terms: props.equipment.liability_terms || '',
  instant_booking: props.equipment.instant_booking,
  status: props.equipment.status,
  new_images: [] as File[],
  deleted_images: [] as number[],
  category_attributes: props.equipment.category_attributes || {},
});

// Current category config
const currentCategoryConfig = computed(() => {
  return props.categories[form.category] || {};
});

// Available subcategories
const availableSubcategories = computed(() => {
  return currentCategoryConfig.value.subcategories || {};
});

// Existing images
const existingImages = ref<EquipmentImage[]>([...props.equipment.images]);

const markImageForDeletion = (imageId: number) => {
  form.deleted_images.push(imageId);
  existingImages.value = existingImages.value.filter(img => img.id !== imageId);
};

// New images
const fileInput = ref<HTMLInputElement>();
const newImagePreviews = ref<string[]>([]);

const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;

  if (files) {
    Array.from(files).forEach(file => {
      const totalImages = existingImages.value.length + form.new_images.length;
      if (totalImages < 15) {
        form.new_images.push(file);
        const url = URL.createObjectURL(file);
        newImagePreviews.value.push(url);
      }
    });
  }
};

const removeNewImage = (index: number) => {
  URL.revokeObjectURL(newImagePreviews.value[index]);
  form.new_images.splice(index, 1);
  newImagePreviews.value.splice(index, 1);
};

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

// Submit form
const submit = () => {
  form.post(route('equipment.update', props.equipment.id), {
    forceFormData: true,
    _method: 'PUT',
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

// Status options
const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
  { value: 'maintenance', label: 'Maintenance' },
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

// Watch category changes
watch(() => form.category, () => {
  form.category_attributes = {};
});
</script>

<template>
  <Head :title="`Modifier ${equipment.name}`" />

  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Modifier mon matériel
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
          {{ equipment.name }}
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
                      required
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.name }"
                    />
                    <FormError :message="form.errors.name" />
                  </div>

                  <!-- Category (Read-only for edit) -->
                  <div>
                    <Label for="category">Catégorie</Label>
                    <Input
                      id="category"
                      :value="categories[form.category]?.label || form.category"
                      disabled
                      class="mt-1 bg-gray-100 dark:bg-gray-800"
                    />
                  </div>

                  <!-- Subcategory -->
                  <div>
                    <Label for="subcategory">Sous-catégorie *</Label>
                    <Select v-model="form.subcategory">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
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
                  </div>

                  <!-- Brand & Model -->
                  <div>
                    <Label for="brand">Marque</Label>
                    <Input id="brand" v-model="form.brand" class="mt-1" />
                  </div>

                  <div>
                    <Label for="model">Modèle</Label>
                    <Input id="model" v-model="form.model" class="mt-1" />
                  </div>

                  <!-- Year & Condition -->
                  <div>
                    <Label for="year">Année</Label>
                    <Input id="year" v-model="form.year" type="number" min="1900" max="2030" class="mt-1" />
                  </div>

                  <div>
                    <Label for="condition">État *</Label>
                    <Select v-model="form.condition">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="option in conditionOptions" :key="option.value" :value="option.value">
                          {{ option.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Status -->
                  <div class="md:col-span-2">
                    <Label for="status">Statut *</Label>
                    <Select v-model="form.status">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                          {{ status.label }}
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
                    rows="4"
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.description }"
                  />
                  <FormError :message="form.errors.description" />
                </div>

                <!-- Location -->
                <div class="space-y-4">
                  <Separator />
                  <h3 class="text-lg font-semibold flex items-center gap-2">
                    <MapPin class="w-4 h-4" />
                    Localisation
                  </h3>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                      <Label for="address">Adresse *</Label>
                      <Input id="address" v-model="form.address" class="mt-1" />
                    </div>

                    <div>
                      <Label for="city">Ville *</Label>
                      <Input id="city" v-model="form.city" class="mt-1" />
                    </div>

                    <div>
                      <Label for="postal_code">Code postal *</Label>
                      <Input id="postal_code" v-model="form.postal_code" class="mt-1" />
                    </div>
                  </div>

                  <div>
                    <Label for="pickup_instructions">Instructions de retrait</Label>
                    <Textarea id="pickup_instructions" v-model="form.pickup_instructions" rows="2" class="mt-1" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Details (similar to Create) -->
          <TabsContent value="details" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Features -->
              <Card>
                <CardHeader><CardTitle>Caractéristiques</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex gap-2">
                    <Input v-model="newFeature" placeholder="Ajouter une caractéristique" @keydown.enter.prevent="addFeature" />
                    <Button type="button" @click="addFeature"><Plus class="w-4 h-4" /></Button>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <Badge v-for="(feature, index) in form.features" :key="index" variant="secondary" class="flex items-center gap-1">
                      {{ feature }}
                      <button type="button" @click="removeFeature(index)" class="text-xs hover:text-red-500">
                        <X class="w-3 h-3" />
                      </button>
                    </Badge>
                  </div>
                </CardContent>
              </Card>

              <!-- Included Items -->
              <Card>
                <CardHeader><CardTitle>Inclus dans la location</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex gap-2">
                    <Input v-model="newIncludedItem" placeholder="ex: Casque, pompe..." @keydown.enter.prevent="addIncludedItem" />
                    <Button type="button" @click="addIncludedItem"><Plus class="w-4 h-4" /></Button>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <Badge v-for="(item, index) in form.included_items" :key="index" variant="secondary" class="flex items-center gap-1">
                      {{ item }}
                      <button type="button" @click="removeIncludedItem(index)" class="text-xs hover:text-red-500">
                        <X class="w-3 h-3" />
                      </button>
                    </Badge>
                  </div>
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
                    <Input id="hourly_rate" v-model="form.hourly_rate" type="number" step="0.01" min="0" class="mt-1" />
                  </div>
                  <div>
                    <Label for="daily_rate">Tarif journalier (€)</Label>
                    <Input id="daily_rate" v-model="form.daily_rate" type="number" step="0.01" min="0" class="mt-1" />
                  </div>
                  <div>
                    <Label for="weekly_rate">Tarif hebdomadaire (€)</Label>
                    <Input id="weekly_rate" v-model="form.weekly_rate" type="number" step="0.01" min="0" class="mt-1" />
                  </div>
                  <div>
                    <Label for="monthly_rate">Tarif mensuel (€)</Label>
                    <Input id="monthly_rate" v-model="form.monthly_rate" type="number" step="0.01" min="0" class="mt-1" />
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <Switch id="delivery_available" v-model="form.delivery_available" />
                    <Label for="delivery_available">Livraison disponible</Label>
                  </div>

                  <div v-if="form.delivery_available" class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-6">
                    <div>
                      <Label for="delivery_radius">Rayon de livraison (km)</Label>
                      <Input id="delivery_radius" v-model="form.delivery_radius" type="number" min="1" max="100" class="mt-1" />
                    </div>
                    <div>
                      <Label for="delivery_fee">Frais de livraison (€)</Label>
                      <Input id="delivery_fee" v-model="form.delivery_fee" type="number" step="0.01" min="0" class="mt-1" />
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
                    <Input id="min_rental_duration" v-model="form.min_rental_duration" type="number" min="1" max="365" class="mt-1" />
                  </div>
                  <div>
                    <Label for="max_rental_duration">Durée max. (jours)</Label>
                    <Input id="max_rental_duration" v-model="form.max_rental_duration" type="number" min="1" max="365" class="mt-1" />
                  </div>
                  <div>
                    <Label for="min_age">Âge minimum</Label>
                    <Input id="min_age" v-model="form.min_age" type="number" min="16" max="99" class="mt-1" />
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <Switch id="license_required" v-model="form.license_required" />
                    <Label for="license_required">Permis requis</Label>
                  </div>

                  <div v-if="form.license_required" class="ml-6">
                    <Label for="license_type">Type de permis</Label>
                    <Input id="license_type" v-model="form.license_type" class="mt-1" />
                  </div>

                  <div class="flex items-center gap-2">
                    <Switch id="instant_booking" v-model="form.instant_booking" />
                    <Label for="instant_booking">Réservation instantanée</Label>
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
              </CardHeader>
              <CardContent class="space-y-6">
                <!-- Existing Images -->
                <div v-if="existingImages.length > 0">
                  <Label>Photos actuelles</Label>
                  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-2">
                    <div v-for="image in existingImages" :key="image.id" class="relative group">
                      <img
                        :src="`/storage/${image.image_path}`"
                        :alt="equipment.name"
                        class="w-full h-32 object-cover rounded-lg border"
                      />
                      <div v-if="image.is_primary" class="absolute top-2 left-2">
                        <Badge class="text-xs">Principal</Badge>
                      </div>
                      <button
                        type="button"
                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        @click="markImageForDeletion(image.id)"
                      >
                        <X class="w-3 h-3" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- New Images Upload -->
                <Separator />
                <div>
                  <input ref="fileInput" type="file" multiple accept="image/*" class="hidden" @change="handleImageSelect" />
                  <Button
                    type="button"
                    variant="outline"
                    :disabled="existingImages.length + form.new_images.length >= 15"
                    @click="fileInput?.click()"
                  >
                    <Upload class="w-4 h-4 mr-2" />
                    Ajouter des photos ({{ existingImages.length + form.new_images.length }}/15)
                  </Button>
                </div>

                <!-- New Image Previews -->
                <div v-if="form.new_images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div v-for="(image, index) in form.new_images" :key="index" class="relative group">
                    <img :src="newImagePreviews[index]" alt="Nouvelle image" class="w-full h-32 object-cover rounded-lg border" />
                    <Badge class="absolute top-2 left-2 text-xs" variant="secondary">Nouveau</Badge>
                    <button
                      type="button"
                      class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                      @click="removeNewImage(index)"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        <!-- Submit -->
        <div class="flex justify-end gap-4 mt-8">
          <Button type="button" variant="outline" @click="$inertia.visit(route('equipment.show', equipment.id))">
            Annuler
          </Button>
          <Button type="submit" :disabled="form.processing" class="min-w-32">
            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
