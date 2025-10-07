<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { Plus, Upload, X, MapPin, Euro, Home, Camera, Clock } from 'lucide-vue-next';

interface PropertyImage {
  id: number;
  image_path: string;
  is_primary: boolean;
  sort_order: number;
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
  floor_level?: number;
  has_elevator: boolean;
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
  country: string;
  latitude?: number;
  longitude?: number;
  location_description?: string;
  nightly_rate: number;
  weekly_rate?: number;
  monthly_rate?: number;
  cleaning_fee?: number;
  security_deposit?: number;
  min_nights: number;
  max_nights?: number;
  instant_booking: boolean;
  checkin_start: string;
  checkin_end: string;
  checkout_time: string;
  checkin_method: string;
  checkin_instructions?: string;
  host_about?: string;
  host_languages?: string[];
  status: string;
  images: PropertyImage[];
}

const props = defineProps<{
  property: Property;
}>();

const form = useForm({
  title: props.property.title,
  description: props.property.description,
  property_type: props.property.property_type,
  room_type: props.property.room_type,
  bedrooms: props.property.bedrooms,
  bathrooms: props.property.bathrooms,
  beds: props.property.beds,
  max_guests: props.property.max_guests,
  area_sqm: props.property.area_sqm || '',
  floor_level: props.property.floor_level || '',
  has_elevator: props.property.has_elevator,
  has_parking: props.property.has_parking,
  has_balcony: props.property.has_balcony,
  has_terrace: props.property.has_terrace,
  has_garden: props.property.has_garden,
  amenities: props.property.amenities || [],
  safety_features: props.property.safety_features || [],
  house_rules: props.property.house_rules || [],
  address: props.property.address,
  city: props.property.city,
  postal_code: props.property.postal_code,
  country: props.property.country,
  latitude: props.property.latitude || '',
  longitude: props.property.longitude || '',
  location_description: props.property.location_description || '',
  nightly_rate: props.property.nightly_rate,
  weekly_rate: props.property.weekly_rate || '',
  monthly_rate: props.property.monthly_rate || '',
  cleaning_fee: props.property.cleaning_fee || '',
  security_deposit: props.property.security_deposit || '',
  min_nights: props.property.min_nights,
  max_nights: props.property.max_nights || '',
  instant_booking: props.property.instant_booking,
  checkin_start: props.property.checkin_start,
  checkin_end: props.property.checkin_end,
  checkout_time: props.property.checkout_time,
  checkin_method: props.property.checkin_method,
  checkin_instructions: props.property.checkin_instructions || '',
  host_about: props.property.host_about || '',
  host_languages: props.property.host_languages || [],
  status: props.property.status,
  new_images: [] as File[],
  deleted_images: [] as number[],
});

// Property type options (same as Create)
const propertyTypes = [
  { value: 'apartment', label: 'Appartement' },
  { value: 'house', label: 'Maison' },
  { value: 'studio', label: 'Studio' },
  { value: 'villa', label: 'Villa' },
  { value: 'loft', label: 'Loft' },
  { value: 'townhouse', label: 'Maison de ville' },
  { value: 'cottage', label: 'Cottage' },
  { value: 'chalet', label: 'Chalet' },
  { value: 'castle', label: 'Château' },
  { value: 'other', label: 'Autre' },
];

const roomTypes = [
  { value: 'entire_place', label: 'Logement entier' },
  { value: 'private_room', label: 'Chambre privée' },
  { value: 'shared_room', label: 'Chambre partagée' },
];

const checkinMethods = [
  { value: 'self_checkin', label: 'Arrivée autonome' },
  { value: 'host_greeting', label: 'Accueil par l\'hôte' },
  { value: 'concierge', label: 'Conciergerie' },
  { value: 'lockbox', label: 'Boîte à clés' },
  { value: 'smart_lock', label: 'Serrure connectée' },
];

const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'inactive', label: 'Inactif' },
  { value: 'maintenance', label: 'Maintenance' },
];

const availableAmenities = [
  'WiFi', 'Cuisine', 'Lave-linge', 'Télévision', 'Climatisation', 'Chauffage',
  'Piscine', 'Jacuzzi', 'Salle de sport', 'Espace de travail', 'Cheminée', 'Barbecue',
  'Animaux acceptés', 'Fumeurs acceptés', 'Lave-vaisselle', 'Four', 'Micro-ondes',
  'Machine à café', 'Sèche-cheveux', 'Fer à repasser', 'Parking gratuit'
];

const availableSafetyFeatures = [
  'Détecteur de fumée', 'Extincteur', 'Trousse de premiers secours',
  'Sortie de secours', 'Détecteur de monoxyde de carbone', 'Alarme de sécurité'
];

const availableLanguages = [
  'Français', 'Anglais', 'Espagnol', 'Allemand', 'Italien', 'Portugais', 'Arabe', 'Chinois'
];

// Existing images management
const existingImages = ref<PropertyImage[]>([...props.property.images]);

const markImageForDeletion = (imageId: number) => {
  form.deleted_images.push(imageId);
  existingImages.value = existingImages.value.filter(img => img.id !== imageId);
};

// New images management
const fileInput = ref<HTMLInputElement>();
const newImagePreviews = ref<string[]>([]);

const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;

  if (files) {
    Array.from(files).forEach(file => {
      const totalImages = existingImages.value.length + form.new_images.length;
      if (totalImages < 20) {
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

// Toggle functions
const toggleAmenity = (amenity: string) => {
  const index = form.amenities.indexOf(amenity);
  if (index > -1) {
    form.amenities.splice(index, 1);
  } else {
    form.amenities.push(amenity);
  }
};

const toggleSafetyFeature = (feature: string) => {
  const index = form.safety_features.indexOf(feature);
  if (index > -1) {
    form.safety_features.splice(index, 1);
  } else {
    form.safety_features.push(feature);
  }
};

const toggleLanguage = (language: string) => {
  const index = form.host_languages.indexOf(language);
  if (index > -1) {
    form.host_languages.splice(index, 1);
  } else {
    form.host_languages.push(language);
  }
};

// House rules
const newHouseRule = ref('');

const addHouseRule = () => {
  if (newHouseRule.value.trim()) {
    form.house_rules.push(newHouseRule.value.trim());
    newHouseRule.value = '';
  }
};

const removeHouseRule = (index: number) => {
  form.house_rules.splice(index, 1);
};

// Submit form
const submit = () => {
  form.post(route('properties.update', props.property.id), {
    forceFormData: true,
    _method: 'PUT',
  });
};
</script>

<template>
  <Head :title="`Modifier ${property.title}`" />

  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Modifier ma propriété
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
          {{ property.title }}
        </p>
      </div>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <Tabs default-value="basic" class="space-y-6">
          <TabsList class="grid w-full grid-cols-5">
            <TabsTrigger value="basic">Informations</TabsTrigger>
            <TabsTrigger value="amenities">Équipements</TabsTrigger>
            <TabsTrigger value="pricing">Tarifs</TabsTrigger>
            <TabsTrigger value="checkin">Arrivée</TabsTrigger>
            <TabsTrigger value="images">Photos</TabsTrigger>
          </TabsList>

          <!-- Basic Information (same structure as Create but with v-model values) -->
          <TabsContent value="basic" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Home class="w-5 h-5" />
                  Informations de base
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <!-- Title -->
                <div>
                  <Label for="title">Titre de l'annonce *</Label>
                  <Input
                    id="title"
                    v-model="form.title"
                    required
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.title }"
                  />
                  <FormError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div>
                  <Label for="description">Description *</Label>
                  <Textarea
                    id="description"
                    v-model="form.description"
                    rows="5"
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.description }"
                  />
                  <FormError :message="form.errors.description" />
                </div>

                <!-- Property & Room Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="property_type">Type de propriété *</Label>
                    <Select v-model="form.property_type">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="type in propertyTypes"
                          :key="type.value"
                          :value="type.value"
                        >
                          {{ type.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div>
                    <Label for="room_type">Type de logement *</Label>
                    <Select v-model="form.room_type">
                      <SelectTrigger class="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="type in roomTypes"
                          :key="type.value"
                          :value="type.value"
                        >
                          {{ type.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Rest of fields same as Create... (continuing with key fields) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <Label for="bedrooms">Chambres *</Label>
                    <Input id="bedrooms" v-model.number="form.bedrooms" type="number" min="0" max="20" class="mt-1" />
                  </div>
                  <div>
                    <Label for="bathrooms">Salles de bain *</Label>
                    <Input id="bathrooms" v-model.number="form.bathrooms" type="number" min="1" max="20" class="mt-1" />
                  </div>
                  <div>
                    <Label for="beds">Lits *</Label>
                    <Input id="beds" v-model.number="form.beds" type="number" min="1" max="50" class="mt-1" />
                  </div>
                  <div>
                    <Label for="max_guests">Voyageurs max *</Label>
                    <Input id="max_guests" v-model.number="form.max_guests" type="number" min="1" max="50" class="mt-1" />
                  </div>
                </div>

                <!-- Status -->
                <div>
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

                <!-- Location fields same as Create -->
                <Separator />
                <div class="space-y-4">
                  <h3 class="text-lg font-semibold flex items-center gap-2">
                    <MapPin class="w-4 h-4" />
                    Localisation
                  </h3>
                  <div class="grid grid-cols-1 gap-4">
                    <Input id="address" v-model="form.address" placeholder="Adresse" />
                    <div class="grid grid-cols-2 gap-4">
                      <Input id="city" v-model="form.city" placeholder="Ville" />
                      <Input id="postal_code" v-model="form.postal_code" placeholder="Code postal" />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Amenities Tab -->
          <TabsContent value="amenities" class="space-y-6">
            <Card>
              <CardHeader><CardTitle>Équipements</CardTitle></CardHeader>
              <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div v-for="amenity in availableAmenities" :key="amenity" class="flex items-center gap-2">
                    <Switch
                      :id="`amenity-${amenity}`"
                      :checked="form.amenities.includes(amenity)"
                      @update:checked="toggleAmenity(amenity)"
                    />
                    <Label :for="`amenity-${amenity}`">{{ amenity }}</Label>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Pricing Tab -->
          <TabsContent value="pricing" class="space-y-6">
            <Card>
              <CardHeader><CardTitle class="flex items-center gap-2"><Euro class="w-5 h-5" />Tarification</CardTitle></CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="nightly_rate">Tarif par nuit (€) *</Label>
                    <Input id="nightly_rate" v-model="form.nightly_rate" type="number" step="0.01" min="10" class="mt-1" />
                  </div>
                  <div>
                    <Label for="weekly_rate">Tarif par semaine (€)</Label>
                    <Input id="weekly_rate" v-model="form.weekly_rate" type="number" step="0.01" class="mt-1" />
                  </div>
                  <div>
                    <Label for="monthly_rate">Tarif par mois (€)</Label>
                    <Input id="monthly_rate" v-model="form.monthly_rate" type="number" step="0.01" class="mt-1" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Check-in Tab -->
          <TabsContent value="checkin" class="space-y-6">
            <Card>
              <CardHeader><CardTitle class="flex items-center gap-2"><Clock class="w-5 h-5" />Arrivée et départ</CardTitle></CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="checkin_start">Arrivée à partir de *</Label>
                    <Input id="checkin_start" v-model="form.checkin_start" type="time" class="mt-1" />
                  </div>
                  <div>
                    <Label for="checkin_end">Arrivée jusqu'à *</Label>
                    <Input id="checkin_end" v-model="form.checkin_end" type="time" class="mt-1" />
                  </div>
                  <div>
                    <Label for="checkout_time">Départ avant *</Label>
                    <Input id="checkout_time" v-model="form.checkout_time" type="time" class="mt-1" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Images Tab -->
          <TabsContent value="images" class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Camera class="w-5 h-5" />
                  Photos de la propriété
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
                        :alt="property.title"
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
                    :disabled="existingImages.length + form.new_images.length >= 20"
                    @click="fileInput?.click()"
                  >
                    <Upload class="w-4 h-4 mr-2" />
                    Ajouter des photos ({{ existingImages.length + form.new_images.length }}/20)
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
          <Button type="button" variant="outline" @click="$inertia.visit(route('properties.show', property.id))">
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
