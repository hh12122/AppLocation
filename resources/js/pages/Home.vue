<template>
  <AppLayout title="Bienvenue sur CarLocation">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
      <!-- Hero Section -->
      <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
          <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-gray-100 mb-6">
              Trouvez votre 
              <span class="text-blue-600 dark:text-blue-400">location parfaite</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-300 mb-12 max-w-3xl mx-auto">
              Découvrez notre plateforme de location complète : véhicules pour vos déplacements et propriétés pour vos séjours.
            </p>
          </div>

          <!-- Main Categories -->
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
            <!-- Vehicles Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-2xl">
                    <Car class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Véhicules</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Louez des voitures pour tous vos besoins : city cars, SUV, véhicules de luxe et plus encore.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Large gamme de véhicules</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Assurance incluse</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Réservation instantanée</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Support 24/7</span>
                  </div>
                </div>

                <Link 
                  :href="route('vehicles.index')"
                  class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Explorer les véhicules
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>

            <!-- Sports Equipment Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-orange-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-2xl">
                    <Bike class="w-8 h-8 text-orange-600 dark:text-orange-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Sport</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Équipements de sport pour toutes vos activités : ski, vélos, camping, fitness et plus.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Matériel de qualité</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Toutes saisons</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Livraison possible</span>
                  </div>
                </div>

                <Link 
                  :href="route('equipment.category', 'sports_equipment')"
                  class="block w-full bg-orange-600 hover:bg-orange-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Explorer le sport
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>

            <!-- Tools & Materials Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-purple-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-2xl">
                    <Wrench class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Outils</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Outils et matériel pour vos projets : bricolage, jardinage, événements et plus.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Outils professionnels</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Instructions incluses</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Location flexible</span>
                  </div>
                </div>

                <Link 
                  :href="route('equipment.category', 'tools_material')"
                  class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Explorer les outils
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>

            <!-- Boats Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-cyan-500 to-cyan-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-cyan-100 dark:bg-cyan-900 rounded-2xl">
                    <Ship class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Bateaux</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Embarcations pour vos sorties nautiques : bateaux, jet-skis, voiliers et kayaks.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Flotte variée</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Équipement de sécurité</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Capitaines disponibles</span>
                  </div>
                </div>

                <Link 
                  :href="route('equipment.category', 'boats')"
                  class="block w-full bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Explorer les bateaux
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>

            <!-- Spaces Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-indigo-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-2xl">
                    <MapPin class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Espaces</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Espaces pour vos besoins : salles de réunion, stockage, ateliers et studios.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Espaces équipés</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Accès sécurisé</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Réservation à l'heure</span>
                  </div>
                </div>

                <Link 
                  :href="route('equipment.category', 'spaces')"
                  class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Explorer les espaces
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>

            <!-- Properties Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
              <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-green-600 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
              <div class="p-8">
                <div class="flex items-center mb-6">
                  <div class="p-3 bg-green-100 dark:bg-green-900 rounded-2xl">
                    <Home class="w-8 h-8 text-green-600 dark:text-green-400" />
                  </div>
                  <h2 class="ml-4 text-3xl font-bold text-gray-900 dark:text-gray-100">Propriétés</h2>
                </div>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6 text-lg">
                  Séjournez dans des appartements et maisons uniques partout en France pour vos vacances ou voyages d'affaires.
                </p>

                <div class="space-y-3 mb-8">
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Logements entiers ou chambres</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Hôtes vérifiés</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Annulation flexible</span>
                  </div>
                  <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <Check class="w-5 h-5 text-green-500 mr-3" />
                    <span>Expériences authentiques</span>
                  </div>
                </div>

                <Link 
                  :href="route('properties.index')"
                  class="block w-full bg-green-600 hover:bg-green-700 text-white font-medium py-4 px-6 rounded-xl text-center transition-colors duration-200 group"
                >
                  <span class="flex items-center justify-center">
                    Découvrir les propriétés
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-200" />
                  </span>
                </Link>
              </div>
            </div>
          </div>

          <!-- Stats Section -->
          <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">500+</div>
              <div class="text-gray-600 dark:text-gray-300">Véhicules</div>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-green-600 dark:text-green-400 mb-2">1000+</div>
              <div class="text-gray-600 dark:text-gray-300">Propriétés</div>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">50k+</div>
              <div class="text-gray-600 dark:text-gray-300">Utilisateurs</div>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-bold text-orange-600 dark:text-orange-400 mb-2">4.9★</div>
              <div class="text-gray-600 dark:text-gray-300">Note moyenne</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Features Section -->
      <div class="bg-white dark:bg-gray-800 py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              Pourquoi choisir CarLocation ?
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
              Une plateforme complète qui simplifie vos locations avec des fonctionnalités innovantes.
            </p>
          </div>

          <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center p-6">
              <div class="p-4 bg-blue-100 dark:bg-blue-900 rounded-full w-16 h-16 mx-auto mb-6 flex items-center justify-center">
                <Shield class="w-8 h-8 text-blue-600 dark:text-blue-400" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Sécurisé</h3>
              <p class="text-gray-600 dark:text-gray-300">
                Paiements sécurisés, vérification d'identité et assurance complète pour votre tranquillité.
              </p>
            </div>

            <div class="text-center p-6">
              <div class="p-4 bg-green-100 dark:bg-green-900 rounded-full w-16 h-16 mx-auto mb-6 flex items-center justify-center">
                <MapPin class="w-8 h-8 text-green-600 dark:text-green-400" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Partout en France</h3>
              <p class="text-gray-600 dark:text-gray-300">
                Des milliers de véhicules et propriétés disponibles dans toute la France, près de chez vous.
              </p>
            </div>

            <div class="text-center p-6">
              <div class="p-4 bg-purple-100 dark:bg-purple-900 rounded-full w-16 h-16 mx-auto mb-6 flex items-center justify-center">
                <Users class="w-8 h-8 text-purple-600 dark:text-purple-400" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Communauté</h3>
              <p class="text-gray-600 dark:text-gray-300">
                Partagez avec une communauté bienveillante et gagnez des crédits grâce à notre système de parrainage.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-gradient-to-r from-blue-600 to-green-600 py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
            Prêt à commencer votre aventure ?
          </h2>
          <p class="text-xl text-blue-100 mb-8">
            Rejoignez des milliers d'utilisateurs qui nous font confiance pour leurs locations.
          </p>
          
          <div v-if="!$page.props.auth.user" class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              :href="route('register')"
              class="bg-white text-blue-600 font-medium py-4 px-8 rounded-xl hover:bg-gray-100 transition-colors duration-200"
            >
              Créer un compte
            </Link>
            <Link
              :href="route('login')"
              class="border-2 border-white text-white font-medium py-4 px-8 rounded-xl hover:bg-white hover:text-blue-600 transition-colors duration-200"
            >
              Se connecter
            </Link>
          </div>
          
          <div v-else>
            <Link
              :href="route('dashboard')"
              class="inline-block bg-white text-blue-600 font-medium py-4 px-8 rounded-xl hover:bg-gray-100 transition-colors duration-200"
            >
              Aller au tableau de bord
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Car, Home, Check, ArrowRight, Shield, MapPin, Users, Bike, Wrench, Ship } from 'lucide-vue-next'
</script>