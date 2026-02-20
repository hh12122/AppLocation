<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { computed } from 'vue'

// TypeScript Interfaces
interface DashboardStats {
    total_recommendations: number
    viewed_recommendations: number
    conversions: number
    recent_searches: number
    totalVehicles: number
    activeRentals: number
    totalEarnings: number
    completedRentals: number
}

interface VehicleEntity {
    id: number
    brand: string
    model: string
    year: number
    daily_rate: number
    city: string
    images?: Array<{ image_path: string }>
}

interface PropertyEntity {
    id: number
    title: string
    property_type: string
    price_per_night: number
    city: string
    images?: Array<{ image_path: string }>
}

interface EquipmentEntity {
    id: number
    name: string
    category: string
    daily_rate: number
    city: string
    images?: Array<{ image_path: string }>
}

type Entity = VehicleEntity | PropertyEntity | EquipmentEntity

interface RecommendationItem {
    id: number
    entity: Entity | null
    type: 'vehicle' | 'property' | 'equipment'
    score: number
    reason: string
    is_viewed: boolean
    is_clicked: boolean
}

interface TrendingItem {
    entity: Entity | null
    type: 'vehicle' | 'property' | 'equipment'
    score: number
    views: number
}

// Props
const props = defineProps<{
    stats: DashboardStats
    recentRecommendations: RecommendationItem[]
    trending: TrendingItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
]

// Helper functions
const getEntityLink = (type: string, id: number): string => {
    switch (type) {
        case 'vehicle':
            return `/vehicles/${id}`
        case 'property':
            return `/properties/${id}`
        case 'equipment':
            return `/equipment/${id}`
        default:
            return '#'
    }
}

const getEntityName = (entity: Entity | null, type: string): string => {
    if (!entity) return 'Item supprimé'

    if (type === 'vehicle' && 'brand' in entity) {
        return `${entity.year} ${entity.brand} ${entity.model}`
    } else if (type === 'property' && 'title' in entity) {
        return entity.title
    } else if (type === 'equipment' && 'name' in entity) {
        return entity.name
    }

    return 'Item'
}

const getEntityImage = (entity: Entity | null): string => {
    if (!entity || !entity.images || entity.images.length === 0) {
        return '/images/placeholder.jpg'
    }
    return entity.images[0].image_path
}

const getTrendingIcon = (score: number): string => {
    if (score >= 0.8) return '🔥🔥🔥'
    if (score >= 0.6) return '🔥🔥'
    if (score >= 0.4) return '🔥'
    return '📈'
}

const getConversionRate = computed(() => {
    if (props.stats.viewed_recommendations === 0) return 0
    return Math.round((props.stats.conversions / props.stats.viewed_recommendations) * 100)
})
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

            <!-- AI Metrics Section -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    🤖 Métriques IA & Recommandations
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Recommandations</p>
                                    <p class="text-3xl font-bold text-blue-600">{{ stats.total_recommendations }}</p>
                                </div>
                                <div class="text-3xl">💡</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Vues</p>
                                    <p class="text-3xl font-bold text-purple-600">{{ stats.viewed_recommendations }}</p>
                                </div>
                                <div class="text-3xl">👁️</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversions</p>
                                    <p class="text-3xl font-bold text-green-600">{{ stats.conversions }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ getConversionRate }}% taux</p>
                                </div>
                                <div class="text-3xl">✨</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Recherches (7j)</p>
                                    <p class="text-3xl font-bold text-orange-600">{{ stats.recent_searches }}</p>
                                </div>
                                <div class="text-3xl">🔍</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
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

                <!-- AI Recommendations -->
                <Card>
                    <CardHeader>
                        <CardTitle>⭐ Recommandations personnalisées</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentRecommendations.length === 0" class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-4">🎯</div>
                            <p class="text-gray-600 dark:text-gray-400">Aucune recommandation pour le moment</p>
                            <p class="text-sm text-gray-500 mt-2">
                                Continuez à naviguer pour obtenir des recommandations personnalisées
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="rec in recentRecommendations"
                                :key="rec.id"
                                class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                <div class="flex-1">
                                    <Link
                                        :href="getEntityLink(rec.type, rec.entity?.id || 0)"
                                        class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600"
                                    >
                                        {{ getEntityName(rec.entity, rec.type) }}
                                    </Link>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ rec.reason }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <Badge variant="secondary" class="text-xs">
                                            Score: {{ Math.round(rec.score * 100) }}%
                                        </Badge>
                                        <Badge v-if="rec.is_viewed" variant="outline" class="text-xs">
                                            👁️ Vue
                                        </Badge>
                                        <Badge v-if="rec.is_clicked" variant="outline" class="text-xs bg-blue-50">
                                            🖱️ Cliqué
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Trending Items Section -->
            <Card class="mt-6" v-if="trending.length > 0">
                <CardHeader>
                    <CardTitle>🔥 Tendances du moment</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link
                            v-for="(item, index) in trending"
                            :key="index"
                            :href="getEntityLink(item.type, item.entity?.id || 0)"
                            class="block group"
                        >
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                                <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative">
                                    <img
                                        :src="getEntityImage(item.entity)"
                                        :alt="getEntityName(item.entity, item.type)"
                                        class="w-full h-full object-cover"
                                        @error="$event.target.src = '/images/placeholder.jpg'"
                                    />
                                    <div class="absolute top-2 right-2 bg-white dark:bg-gray-800 px-2 py-1 rounded-full text-sm">
                                        {{ getTrendingIcon(item.score) }}
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition-colors">
                                        {{ getEntityName(item.entity, item.type) }}
                                    </h3>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ item.views }} vues
                                        </span>
                                        <Badge variant="secondary" class="text-xs">
                                            {{ Math.round(item.score * 100) }}% populaire
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

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
