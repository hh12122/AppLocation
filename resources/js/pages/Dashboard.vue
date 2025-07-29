<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
]

// Mock data - in real app this would come from props
const stats = {
    totalVehicles: 0,
    activeRentals: 0,
    totalEarnings: 0,
    completedRentals: 0
}

const recentActivity = []
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Welcome Section -->
            <div class="mb-2">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Bonjour, {{ $page.props.auth.user.name }} ! 👋
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Voici un aperçu de votre activité sur CarLocation
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Link 
                    :href="route('vehicles.index')"
                    class="bg-blue-600 text-white p-6 rounded-lg hover:bg-blue-700 transition-colors text-center"
                >
                    <div class="text-2xl mb-2">🔍</div>
                    <div class="font-medium">Chercher un véhicule</div>
                </Link>

                <Link 
                    :href="route('vehicles.create')"
                    class="bg-green-600 text-white p-6 rounded-lg hover:bg-green-700 transition-colors text-center"
                >
                    <div class="text-2xl mb-2">🚗</div>
                    <div class="font-medium">Ajouter mon véhicule</div>
                </Link>

                <Link 
                    :href="route('rentals.my')"
                    class="bg-purple-600 text-white p-6 rounded-lg hover:bg-purple-700 transition-colors text-center"
                >
                    <div class="text-2xl mb-2">📋</div>
                    <div class="font-medium">Mes réservations</div>
                </Link>

                <Link 
                    :href="route('rentals.bookings')"
                    class="bg-orange-600 text-white p-6 rounded-lg hover:bg-orange-700 transition-colors text-center"
                >
                    <div class="text-2xl mb-2">📊</div>
                    <div class="font-medium">Mes demandes</div>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mes véhicules</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.totalVehicles }}</p>
                            </div>
                            <div class="text-3xl">🚙</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Locations actives</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.activeRentals }}</p>
                            </div>
                            <div class="text-3xl">⏰</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gains totaux</p>
                                <p class="text-3xl font-bold text-green-600">{{ stats.totalEarnings }}€</p>
                            </div>
                            <div class="text-3xl">💰</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Locations terminées</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.completedRentals }}</p>
                            </div>
                            <div class="text-3xl">✅</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Getting Started -->
                <Card>
                    <CardHeader>
                        <CardTitle>Commencer avec CarLocation</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600">
                                    1
                                </div>
                                <div>
                                    <h4 class="font-medium">Complétez votre profil</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Ajoutez vos informations personnelles et votre permis de conduire
                                    </p>
                                    <Link 
                                        :href="route('profile.edit')"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Modifier mon profil →
                                    </Link>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600">
                                    2
                                </div>
                                <div>
                                    <h4 class="font-medium">Recherchez un véhicule</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Parcourez les véhicules disponibles près de chez vous
                                    </p>
                                    <Link 
                                        :href="route('vehicles.index')"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Voir tous les véhicules →
                                    </Link>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600">
                                    3
                                </div>
                                <div>
                                    <h4 class="font-medium">Partagez votre véhicule</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Gagnez de l'argent en louant votre véhicule
                                    </p>
                                    <Link 
                                        :href="route('vehicles.create')"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Ajouter mon véhicule →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity -->
                <Card>
                    <CardHeader>
                        <CardTitle>Activité récente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentActivity.length === 0" class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-4">📝</div>
                            <p class="text-gray-600 dark:text-gray-400">Aucune activité récente</p>
                            <p class="text-sm text-gray-500 mt-2">
                                Votre activité apparaîtra ici une fois que vous commencerez à utiliser l'application
                            </p>
                        </div>
                        <!-- Activity items would be rendered here when available -->
                    </CardContent>
                </Card>
            </div>

            <!-- Tips Section -->
            <Card class="mt-6">
                <CardHeader>
                    <CardTitle>💡 Conseils pour bien commencer</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl mb-3">🔐</div>
                            <h4 class="font-medium mb-2">Sécurité</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Vérifiez toujours l'identité des locataires et l'état du véhicule avant/après location
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-3xl mb-3">📸</div>
                            <h4 class="font-medium mb-2">Photos de qualité</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Ajoutez des photos nettes et variées de votre véhicule pour attirer plus de locataires
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-3xl mb-3">⭐</div>
                            <h4 class="font-medium mb-2">Bonnes évaluations</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Maintenez votre véhicule propre et soyez réactif pour obtenir de bonnes notes
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
