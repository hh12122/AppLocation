<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

interface TopReferrer {
    rank: number
    name: string
    avatar?: string
    referral_count: number
    is_current_user: boolean
}

interface ReferralStats {
    total_referrals: number
    successful_referrals: number
    pending_referrals: number
    total_earned: number
    available_credits: number
    referral_rate: number
}

interface Props {
    topReferrers: TopReferrer[]
    currentUserRank?: number
    currentUserStats?: ReferralStats
}

const props = defineProps<Props>()

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRankIcon = (rank: number) => {
    switch (rank) {
        case 1: return '🥇'
        case 2: return '🥈'
        case 3: return '🥉'
        default: return `#${rank}`
    }
}

const getRankColor = (rank: number) => {
    switch (rank) {
        case 1: return 'text-yellow-600'
        case 2: return 'text-gray-500'
        case 3: return 'text-orange-600'
        default: return 'text-gray-700'
    }
}

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}
</script>

<template>
    <Head title="Classement des parrainages" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">🏆 Classement des parrainages</h1>
                        <p class="text-gray-600 mt-2">
                            Découvrez qui sont les meilleurs ambassadeurs de CarLocation !
                        </p>
                    </div>
                    <Link 
                        :href="route('referrals.index')"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        ← Retour au parrainage
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- User's Current Stats -->
                    <div class="lg:col-span-1">
                        <Card v-if="currentUserStats">
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <span class="mr-2">📊</span>
                                    Votre position
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="text-center">
                                    <div class="text-4xl font-bold text-blue-600 mb-2">
                                        {{ currentUserRank ? `#${currentUserRank}` : 'N/A' }}
                                    </div>
                                    <p class="text-sm text-gray-600">Votre rang</p>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Invitations réussies</span>
                                        <span class="font-medium">{{ currentUserStats.successful_referrals }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">En attente</span>
                                        <span class="font-medium">{{ currentUserStats.pending_referrals }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Taux de conversion</span>
                                        <span class="font-medium">{{ Math.round(currentUserStats.referral_rate) }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Crédits disponibles</span>
                                        <span class="font-medium text-green-600">
                                            {{ formatPrice(currentUserStats.available_credits) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-4 border-t">
                                    <Link
                                        :href="route('referrals.index')"
                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-center block text-sm font-medium"
                                    >
                                        Améliorer mon score
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Info Card -->
                        <Card class="mt-6">
                            <CardHeader>
                                <CardTitle class="flex items-center text-base">
                                    <span class="mr-2">ℹ️</span>
                                    Comment ça marche ?
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm text-gray-600">
                                <p>🥇 Plus vous parrainez d'amis, plus vous montez dans le classement</p>
                                <p>✅ Seules les inscriptions réussies comptent pour votre score</p>
                                <p>🔄 Le classement est mis à jour en temps réel</p>
                                <p>🎁 Des récompenses spéciales pour les top performers !</p>
                            </CardContent>
                        </Card>

                        <!-- Rewards Info -->
                        <Card class="mt-6">
                            <CardHeader>
                                <CardTitle class="flex items-center text-base">
                                    <span class="mr-2">🎁</span>
                                    Récompenses
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex items-center space-x-2">
                                    <span>🥇</span>
                                    <span>Top 1 : Badge exclusif + 100€ bonus</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span>🥈</span>
                                    <span>Top 2-3 : Badge + 50€ bonus</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span>🥉</span>
                                    <span>Top 4-10 : Badge + 25€ bonus</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-3">
                                    Récompenses distribuées chaque mois !
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Leaderboard -->
                    <div class="lg:col-span-3">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    <span>Top des ambassadeurs</span>
                                    <Badge variant="secondary">{{ topReferrers.length }} participants</Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="topReferrers.length > 0" class="space-y-4">
                                    <!-- Top 3 Podium -->
                                    <div class="grid grid-cols-3 gap-4 mb-8">
                                        <!-- 2nd Place -->
                                        <div v-if="topReferrers.length > 1" class="text-center">
                                            <div class="relative">
                                                <div class="w-20 h-20 mx-auto mb-3 relative">
                                                    <Avatar class="w-20 h-20">
                                                        <AvatarImage 
                                                            :src="topReferrers[1].avatar || ''"
                                                            :alt="topReferrers[1].name"
                                                        />
                                                        <AvatarFallback class="text-lg">
                                                            {{ getInitials(topReferrers[1].name) }}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div class="absolute -bottom-2 -right-2 bg-gray-400 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg">
                                                        🥈
                                                    </div>
                                                </div>
                                                <h3 class="font-bold text-gray-700" 
                                                    :class="{ 'text-blue-600': topReferrers[1].is_current_user }">
                                                    {{ topReferrers[1].name }}
                                                </h3>
                                                <p class="text-sm text-gray-500">{{ topReferrers[1].referral_count }} invitations</p>
                                            </div>
                                        </div>

                                        <!-- 1st Place -->
                                        <div v-if="topReferrers.length > 0" class="text-center transform scale-110">
                                            <div class="relative">
                                                <div class="w-24 h-24 mx-auto mb-3 relative">
                                                    <Avatar class="w-24 h-24">
                                                        <AvatarImage 
                                                            :src="topReferrers[0].avatar || ''"
                                                            :alt="topReferrers[0].name"
                                                        />
                                                        <AvatarFallback class="text-xl">
                                                            {{ getInitials(topReferrers[0].name) }}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div class="absolute -bottom-2 -right-2 bg-yellow-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-xl">
                                                        🥇
                                                    </div>
                                                </div>
                                                <h3 class="font-bold text-yellow-600 text-lg"
                                                    :class="{ '!text-blue-600': topReferrers[0].is_current_user }">
                                                    {{ topReferrers[0].name }}
                                                </h3>
                                                <p class="text-sm text-gray-500">{{ topReferrers[0].referral_count }} invitations</p>
                                                <Badge class="mt-2 bg-yellow-100 text-yellow-800">👑 Champion</Badge>
                                            </div>
                                        </div>

                                        <!-- 3rd Place -->
                                        <div v-if="topReferrers.length > 2" class="text-center">
                                            <div class="relative">
                                                <div class="w-20 h-20 mx-auto mb-3 relative">
                                                    <Avatar class="w-20 h-20">
                                                        <AvatarImage 
                                                            :src="topReferrers[2].avatar || ''"
                                                            :alt="topReferrers[2].name"
                                                        />
                                                        <AvatarFallback class="text-lg">
                                                            {{ getInitials(topReferrers[2].name) }}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div class="absolute -bottom-2 -right-2 bg-orange-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg">
                                                        🥉
                                                    </div>
                                                </div>
                                                <h3 class="font-bold text-orange-600"
                                                    :class="{ '!text-blue-600': topReferrers[2].is_current_user }">
                                                    {{ topReferrers[2].name }}
                                                </h3>
                                                <p class="text-sm text-gray-500">{{ topReferrers[2].referral_count }} invitations</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rest of the Rankings -->
                                    <div v-if="topReferrers.length > 3" class="space-y-3">
                                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">
                                            Autres classements
                                        </h3>
                                        
                                        <div 
                                            v-for="referrer in topReferrers.slice(3)" 
                                            :key="referrer.rank"
                                            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                            :class="{ 'bg-blue-50 border border-blue-200': referrer.is_current_user }"
                                        >
                                            <div class="flex items-center space-x-4">
                                                <div class="text-xl font-bold w-8 text-center" :class="getRankColor(referrer.rank)">
                                                    {{ getRankIcon(referrer.rank) }}
                                                </div>
                                                
                                                <Avatar class="w-12 h-12">
                                                    <AvatarImage 
                                                        :src="referrer.avatar || ''"
                                                        :alt="referrer.name"
                                                    />
                                                    <AvatarFallback>
                                                        {{ getInitials(referrer.name) }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                
                                                <div>
                                                    <h3 class="font-semibold" 
                                                        :class="{ 'text-blue-600': referrer.is_current_user }">
                                                        {{ referrer.name }}
                                                        <Badge v-if="referrer.is_current_user" variant="secondary" class="ml-2 text-xs">
                                                            C'est vous !
                                                        </Badge>
                                                    </h3>
                                                </div>
                                            </div>
                                            
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-gray-900">
                                                    {{ referrer.referral_count }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    invitation{{ referrer.referral_count > 1 ? 's' : '' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-12">
                                    <div class="text-gray-400 text-6xl mb-4">🏆</div>
                                    <h3 class="text-xl font-medium text-gray-900 mb-2">
                                        Le classement est vide
                                    </h3>
                                    <p class="text-gray-600 mb-6">
                                        Soyez le premier à inviter vos amis et à apparaître dans le classement !
                                    </p>
                                    <Link 
                                        :href="route('referrals.index')"
                                        class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                                    >
                                        Commencer à parrainer
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <!-- Competition Info -->
                        <Card class="mt-6">
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <span class="mr-2">🏅</span>
                                    Compétition mensuelle
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                        🎯 Défi du mois : Invitez 5 amis !
                                    </h3>
                                    <div class="space-y-2 text-sm text-gray-700">
                                        <p>🎁 <strong>Grand prix :</strong> 200€ de crédits + accès VIP</p>
                                        <p>⏰ <strong>Temps restant :</strong> 12 jours</p>
                                        <p>👥 <strong>Participants :</strong> {{ topReferrers.length }} personnes</p>
                                    </div>
                                    <div class="mt-4">
                                        <Link 
                                            :href="route('referrals.index')"
                                            class="inline-block bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 text-sm font-medium"
                                        >
                                            Participer maintenant
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>