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

const form = useForm({
  title: '',
  description: '',
  property_type: 'apartment',
  room_type: 'entire_place',
  bedrooms: 1,
  bathrooms: 1,
  beds: 1,
  max_guests: 2,
  area_sqm: '',
  floor_level: '',
  has_elevator: false,
  has_parking: false,
  has_balcony: false,
  has_terrace: false,
  has_garden: false,

  // Amenities & Features
  amenities: [] as string[],
  safety_features: [] as string[],
  house_rules: [] as string[],

  // Location
  address: '',
  city: '',
  postal_code: '',
  country: 'France',
  latitude: '',
  longitude: '',
  location_description: '',

  // Pricing
  nightly_rate: '',
  weekly_rate: '',
  monthly_rate: '',
  cleaning_fee: '',
  security_deposit: '',

  // Rental settings
  min_nights: 1,
  max_nights: '',
  instant_booking: false,

  // Check-in/Check-out
  checkin_start: '14:00',
  checkin_end: '20:00',
  checkout_time: '11:00',
  checkin_method: 'self_checkin',
  checkin_instructions: '',

  // Host information
  host_about: '',
  host_languages: [] as string[],

  // Images
  images: [] as File[],
});

// Property type options
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

// Room type options
const roomTypes = [
  { value: 'entire_place', label: 'Logement entier' },
  { value: 'private_room', label: 'Chambre privée' },
  { value: 'shared_room', label: 'Chambre partagée' },
];

// Check-in method options
const checkinMethods = [
  { value: 'self_checkin', label: 'Arrivée autonome' },
  { value: 'host_greeting', label: 'Accueil par l\'hôte' },
  { value: 'concierge', label: 'Conciergerie' },
  { value: 'lockbox', label: 'Boîte à clés' },
  { value: 'smart_lock', label: 'Serrure connectée' },
];

// Available amenities
const availableAmenities = [
  'WiFi', 'Cuisine', 'Lave-linge', 'Télévision', 'Climatisation', 'Chauffage',
  'Piscine', 'Jacuzzi', 'Salle de sport', 'Espace de travail', 'Cheminée', 'Barbecue',
  'Animaux acceptés', 'Fumeurs acceptés', 'Lave-vaisselle', 'Four', 'Micro-ondes',
  'Machine à café', 'Sèche-cheveux', 'Fer à repasser', 'Parking gratuit'
];

// Safety features
const availableSafetyFeatures = [
  'Détecteur de fumée', 'Extincteur', 'Trousse de premiers secours',
  'Sortie de secours', 'Détecteur de monoxyde de carbone', 'Alarme de sécurité'
];

// Languages
const availableLanguages = [
  'Français', 'Anglais', 'Espagnol', 'Allemand', 'Italien', 'Portugais', 'Arabe', 'Chinois'
];

// Amenities management
const toggleAmenity = (amenity: string) => {
  const index = form.amenities.indexOf(amenity);
  if (index > -1) {
    form.amenities.splice(index, 1);
  } else {
    form.amenities.push(amenity);
  }
};

// Safety features management
const toggleSafetyFeature = (feature: string) => {
  const index = form.safety_features.indexOf(feature);
  if (index > -1) {
    form.safety_features.splice(index, 1);
  } else {
    form.safety_features.push(feature);
  }
};

// Languages management
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

// Image handling
const fileInput = ref<HTMLInputElement>();
const previewUrls = ref<string[]>([]);

const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;

  if (files) {
    Array.from(files).forEach(file => {
      if (form.images.length < 20) {
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

// Submit form
const submit = () => {
  form.post(route('properties.store'), {
    forceFormData: true,
  });
};
</script>

<template>
  <Head title="Ajouter une propriété" />

  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Ajouter ma propriété
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
          Créez une annonce pour louer votre propriété à des voyageurs
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

          <!-- Basic Information -->
          <TabsContent value="basic" class="space-y-6" forceMount>
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
                    placeholder="ex: Appartement cosy au centre-ville"
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
                    placeholder="Décrivez votre propriété en détail..."
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

                <!-- Capacity -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <Label for="bedrooms">Chambres *</Label>
                    <Input
                      id="bedrooms"
                      v-model.number="form.bedrooms"
                      type="number"
                      min="0"
                      max="20"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="bathrooms">Salles de bain *</Label>
                    <Input
                      id="bathrooms"
                      v-model.number="form.bathrooms"
                      type="number"
                      min="1"
                      max="20"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="beds">Lits *</Label>
                    <Input
                      id="beds"
                      v-model.number="form.beds"
                      type="number"
                      min="1"
                      max="50"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="max_guests">Voyageurs max *</Label>
                    <Input
                      id="max_guests"
                      v-model.number="form.max_guests"
                      type="number"
                      min="1"
                      max="50"
                      class="mt-1"
                    />
                  </div>
                </div>

                <!-- Property Details -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="area_sqm">Surface (m²)</Label>
                    <Input
                      id="area_sqm"
                      v-model="form.area_sqm"
                      type="number"
                      min="10"
                      max="2000"
                      placeholder="75"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="floor_level">Étage</Label>
                    <Input
                      id="floor_level"
                      v-model="form.floor_level"
                      type="number"
                      min="-5"
                      max="200"
                      placeholder="2"
                      class="mt-1"
                    />
                  </div>

                  <div class="flex items-end">
                    <div class="flex items-center gap-2">
                      <Switch id="has_elevator" v-model="form.has_elevator" />
                      <Label for="has_elevator">Ascenseur</Label>
                    </div>
                  </div>
                </div>

                <!-- Features -->
                <div class="space-y-3">
                  <Label>Caractéristiques</Label>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="flex items-center gap-2">
                      <Switch id="has_parking" v-model="form.has_parking" />
                      <Label for="has_parking">Parking</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch id="has_balcony" v-model="form.has_balcony" />
                      <Label for="has_balcony">Balcon</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch id="has_terrace" v-model="form.has_terrace" />
                      <Label for="has_terrace">Terrasse</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch id="has_garden" v-model="form.has_garden" />
                      <Label for="has_garden">Jardin</Label>
                    </div>
                  </div>
                </div>

                <!-- Location -->
                <Separator />
                <div class="space-y-4">
                  <h3 class="text-lg font-semibold flex items-center gap-2">
                    <MapPin class="w-4 h-4" />
                    Localisation
                  </h3>

                  <div class="grid grid-cols-1 gap-4">
                    <div>
                      <Label for="address">Adresse *</Label>
                      <Input
                        id="address"
                        v-model="form.address"
                        placeholder="123 Rue de la Paix"
                        class="mt-1"
                        :class="{ 'border-red-500': form.errors.address }"
                      />
                      <FormError :message="form.errors.address" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <Label for="city">Ville *</Label>
                        <Input
                          id="city"
                          v-model="form.city"
                          placeholder="Paris"
                          class="mt-1"
                          :class="{ 'border-red-500': form.errors.city }"
                        />
                        <FormError :message="form.errors.city" />
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
                        <FormError :message="form.errors.postal_code" />
                      </div>
                    </div>

                    <div>
                      <Label for="location_description">Description du quartier</Label>
                      <Textarea
                        id="location_description"
                        v-model="form.location_description"
                        placeholder="Décrivez le quartier, les transports, commerces..."
                        rows="3"
                        class="mt-1"
                      />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Amenities -->
          <TabsContent value="amenities" class="space-y-6" forceMount>
            <Card>
              <CardHeader>
                <CardTitle>Équipements</CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div
                    v-for="amenity in availableAmenities"
                    :key="amenity"
                    class="flex items-center gap-2"
                  >
                    <Switch
                      :key="`switch-${amenity}-${form.amenities.includes(amenity)}`"
                      :id="`amenity-${amenity}`"
                      :checked="form.amenities.includes(amenity)"
                      @update:checked="toggleAmenity(amenity)"
                    />
                    <Label :for="`amenity-${amenity}`">{{ amenity }}</Label>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Sécurité</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div
                    v-for="feature in availableSafetyFeatures"
                    :key="feature"
                    class="flex items-center gap-2"
                  >
                    <Switch
                      :key="`switch-${feature}-${form.safety_features.includes(feature)}`"
                      :id="`safety-${feature}`"
                      :checked="form.safety_features.includes(feature)"
                      @update:checked="toggleSafetyFeature(feature)"
                    />
                    <Label :for="`safety-${feature}`">{{ feature }}</Label>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Règlement intérieur</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="flex gap-2">
                  <Input
                    v-model="newHouseRule"
                    placeholder="ex: Pas de fête"
                    @keydown.enter.prevent="addHouseRule"
                  />
                  <Button type="button" @click="addHouseRule">
                    <Plus class="w-4 h-4" />
                  </Button>
                </div>

                <div class="flex flex-wrap gap-2">
                  <Badge
                    v-for="(rule, index) in form.house_rules"
                    :key="index"
                    variant="secondary"
                    class="flex items-center gap-1"
                  >
                    {{ rule }}
                    <button
                      type="button"
                      @click="removeHouseRule(index)"
                      class="text-xs hover:text-red-500"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </Badge>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Pricing -->
          <TabsContent value="pricing" class="space-y-6" forceMount>
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Euro class="w-5 h-5" />
                  Tarification
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="nightly_rate">Tarif par nuit (€) *</Label>
                    <Input
                      id="nightly_rate"
                      v-model="form.nightly_rate"
                      type="number"
                      step="0.01"
                      min="10"
                      max="10000"
                      placeholder="80.00"
                      class="mt-1"
                      :class="{ 'border-red-500': form.errors.nightly_rate }"
                    />
                    <FormError :message="form.errors.nightly_rate" />
                  </div>

                  <div>
                    <Label for="weekly_rate">Tarif par semaine (€)</Label>
                    <Input
                      id="weekly_rate"
                      v-model="form.weekly_rate"
                      type="number"
                      step="0.01"
                      min="50"
                      max="50000"
                      placeholder="500.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="monthly_rate">Tarif par mois (€)</Label>
                    <Input
                      id="monthly_rate"
                      v-model="form.monthly_rate"
                      type="number"
                      step="0.01"
                      min="200"
                      max="200000"
                      placeholder="2000.00"
                      class="mt-1"
                    />
                  </div>
                </div>

                <Separator />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="cleaning_fee">Frais de ménage (€)</Label>
                    <Input
                      id="cleaning_fee"
                      v-model="form.cleaning_fee"
                      type="number"
                      step="0.01"
                      min="0"
                      max="1000"
                      placeholder="50.00"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="security_deposit">Caution (€)</Label>
                    <Input
                      id="security_deposit"
                      v-model="form.security_deposit"
                      type="number"
                      step="0.01"
                      min="0"
                      max="5000"
                      placeholder="300.00"
                      class="mt-1"
                    />
                  </div>
                </div>

                <Separator />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="min_nights">Durée min. (nuits) *</Label>
                    <Input
                      id="min_nights"
                      v-model.number="form.min_nights"
                      type="number"
                      min="1"
                      max="365"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="max_nights">Durée max. (nuits)</Label>
                    <Input
                      id="max_nights"
                      v-model="form.max_nights"
                      type="number"
                      min="1"
                      max="365"
                      placeholder="90"
                      class="mt-1"
                    />
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <Switch id="instant_booking" v-model="form.instant_booking" />
                  <Label for="instant_booking">Réservation instantanée</Label>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Check-in -->
          <TabsContent value="checkin" class="space-y-6" forceMount>
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Clock class="w-5 h-5" />
                  Arrivée et départ
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label for="checkin_start">Arrivée à partir de *</Label>
                    <Input
                      id="checkin_start"
                      v-model="form.checkin_start"
                      type="time"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="checkin_end">Arrivée jusqu'à *</Label>
                    <Input
                      id="checkin_end"
                      v-model="form.checkin_end"
                      type="time"
                      class="mt-1"
                    />
                  </div>

                  <div>
                    <Label for="checkout_time">Départ avant *</Label>
                    <Input
                      id="checkout_time"
                      v-model="form.checkout_time"
                      type="time"
                      class="mt-1"
                    />
                  </div>
                </div>

                <div>
                  <Label for="checkin_method">Méthode d'arrivée *</Label>
                  <Select v-model="form.checkin_method">
                    <SelectTrigger class="mt-1">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="method in checkinMethods"
                        :key="method.value"
                        :value="method.value"
                      >
                        {{ method.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="checkin_instructions">Instructions d'arrivée</Label>
                  <Textarea
                    id="checkin_instructions"
                    v-model="form.checkin_instructions"
                    placeholder="Comment accéder au logement, où récupérer les clés..."
                    rows="4"
                    class="mt-1"
                  />
                </div>

                <Separator />

                <div>
                  <Label for="host_about">À propos de vous (hôte)</Label>
                  <Textarea
                    id="host_about"
                    v-model="form.host_about"
                    placeholder="Présentez-vous aux voyageurs..."
                    rows="3"
                    class="mt-1"
                  />
                </div>

                <div>
                  <Label>Langues parlées</Label>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
                    <div
                      v-for="language in availableLanguages"
                      :key="language"
                      class="flex items-center gap-2"
                    >
                      <Switch
                        :key="`switch-${language}-${form.host_languages.includes(language)}`"
                        :id="`lang-${language}`"
                        :checked="form.host_languages.includes(language)"
                        @update:checked="toggleLanguage(language)"
                      />
                      <Label :for="`lang-${language}`">{{ language }}</Label>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Images -->
          <TabsContent value="images" class="space-y-6" forceMount>
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Camera class="w-5 h-5" />
                  Photos de la propriété
                </CardTitle>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Ajoutez jusqu'à 20 photos de votre propriété. La première photo sera utilisée comme photo principale.
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
                    :disabled="form.images.length >= 20"
                    @click="fileInput?.click()"
                  >
                    <Upload class="w-4 h-4 mr-2" />
                    Ajouter des photos ({{ form.images.length }}/20)
                  </Button>
                </div>

                <!-- Error -->
                <FormError :message="form.errors.images" />

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
                  <p class="text-gray-600 dark:text-gray-400 mb-2">Aucune photo ajoutée</p>
                  <p class="text-sm text-gray-500 dark:text-gray-500">
                    Ajoutez des photos de qualité pour attirer plus de voyageurs
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
            @click="$inertia.visit(route('properties.index'))"
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
