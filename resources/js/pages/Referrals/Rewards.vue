<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'

interface User {
    id: number
    name: string
}

interface ReferralReward {
    id: number
    reward_type: string
    amount: number
    status: string
    title: string
    description: string
    expires_at?: string
    used_at?: string
    created_at: string
    referral?: {
        referred_user: User
    }
}

interface Summary {
    total_earned: number
    available_credits: number
    used_credits: number
    expired_credits: number
}

interface Props {
    rewards: {
        data: ReferralReward[]
        links: any[]
        meta: any
    }
    summary: Summary
}

const props = defineProps<Props>()

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatShortDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'En attente',
        awarded: 'Accordé',
        used: 'Utilisé',
        expired: 'Expiré'
    }
    return labels[status] || status
}

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        awarded: 'bg-green-100 text-green-800',
        used: 'bg-blue-100 text-blue-800',
        expired: 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getRewardTypeIcon = (type: string) => {
    const icons: Record<string, string> = {
        credit: '💳',
        bonus: '🎁',
        discount: '🎫',
        milestone: '🏆'
    }
    return icons[type] || '🎉'
}

const getRewardTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        credit: 'Crédit',
        bonus: 'Bonus',
        discount: 'Réduction',
        milestone: 'Jalon'
    }
    return labels[type] || type
}

const isExpiringSoon = (expiresAt: string) => {
    const now = new Date()
    const expiryDate = new Date(expiresAt)
    const diffInDays = Math.ceil((expiryDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    return diffInDays <= 30 && diffInDays > 0
}

const getDaysUntilExpiry = (expiresAt: string) => {
    const now = new Date()
    const expiryDate = new Date(expiresAt)
    const diffInDays = Math.ceil((expiryDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    return diffInDays
}

const usagePercentage = (summary.used_credits / summary.total_earned) * 100 || 0
const availablePercentage = (summary.available_credits / summary.total_earned) * 100 || 0
</script>

<template>
    <Head title="Mes récompenses de parrainage" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Mes récompenses</h1>
                        <p class="text-gray-600 mt-2">
                            Consultez toutes vos récompenses de parrainage et leurs détails
                        </p>
                    </div>
                    <Link 
                        :href="route('referrals.index')"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        ← Retour au parrainage
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ formatPrice(summary.total_earned) }}
                                </div>
                                <div class="ml-2 text-3xl">💰</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Total gagné</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ formatPrice(summary.available_credits) }}
                                </div>
                                <div class="ml-2 text-3xl">💳</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Crédits disponibles</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-purple-600">
                                    {{ formatPrice(summary.used_credits) }}
                                </div>
                                <div class="ml-2 text-3xl">✅</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Crédits utilisés</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-red-600">
                                    {{ formatPrice(summary.expired_credits) }}
                                </div>
                                <div class="ml-2 text-3xl">⏰</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Crédits expirés</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Usage Progress -->
                <Card class="mb-8" v-if="summary.total_earned > 0">
                    <CardHeader>
                        <CardTitle>Utilisation de vos récompenses</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Crédits disponibles</span>
                                    <span class="font-medium">{{ Math.round(availablePercentage) }}%</span>
                                </div>
                                <Progress :value="availablePercentage" class="bg-gray-200">
                                    <div class="bg-blue-600 h-full rounded-full" :style="{ width: `${availablePercentage}%` }"></div>
                                </Progress>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Crédits utilisés</span>
                                    <span class="font-medium">{{ Math.round(usagePercentage) }}%</span>
                                </div>
                                <Progress :value="usagePercentage" class="bg-gray-200">
                                    <div class="bg-green-600 h-full rounded-full" :style="{ width: `${usagePercentage}%` }"></div>
                                </Progress>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Rewards List -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>Historique des récompenses</span>
                            <Badge variant="secondary">{{ rewards.meta.total }} récompenses</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="rewards.data.length > 0" class="space-y-4">
                            <div 
                                v-for="reward in rewards.data" 
                                :key="reward.id"
                                class="border border-gray-200 rounded-lg p-6 hover:shadow-sm transition-shadow"
                                :class="{
                                    'border-orange-300 bg-orange-50': reward.expires_at && isExpiringSoon(reward.expires_at),
                                    'border-red-300 bg-red-50': reward.status === 'expired'
                                }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4">
                                        <div class="text-3xl">{{ getRewardTypeIcon(reward.reward_type) }}</div>
                                        
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ reward.title }}
                                                </h3>
                                                <Badge :class="getStatusColor(reward.status)">
                                                    {{ getStatusLabel(reward.status) }}
                                                </Badge>
                                                <Badge variant="outline" class="text-xs">
                                                    {{ getRewardTypeLabel(reward.reward_type) }}
                                                </Badge>
                                            </div>
                                            
                                            <p class="text-gray-600 mb-3">{{ reward.description }}</p>
                                            
                                            <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                                <span>📅 Reçu le {{ formatShortDate(reward.created_at) }}</span>
                                                
                                                <span v-if="reward.referral?.referred_user">
                                                    👤 Grâce à {{ reward.referral.referred_user.name }}
                                                </span>
                                                
                                                <span v-if="reward.used_at">
                                                    ✅ Utilisé le {{ formatShortDate(reward.used_at) }}
                                                </span>
                                                
                                                <span v-if="reward.expires_at && reward.status === 'awarded'">
                                                    ⏰ Expire le {{ formatShortDate(reward.expires_at) }}
                                                    <span v-if="isExpiringSoon(reward.expires_at)" class="text-orange-600 font-medium">
                                                        ({{ getDaysUntilExpiry(reward.expires_at) }} jour{{ getDaysUntilExpiry(reward.expires_at) > 1 ? 's' : '' }} restant{{ getDaysUntilExpiry(reward.expires_at) > 1 ? 's' : '' }})
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right">
                                        <div class="text-2xl font-bold" :class="{
                                            'text-green-600': reward.status === 'awarded',
                                            'text-blue-600': reward.status === 'used',
                                            'text-red-600': reward.status === 'expired',
                                            'text-yellow-600': reward.status === 'pending'
                                        }">
                                            {{ formatPrice(reward.amount) }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Expiring Soon Warning -->
                                <div 
                                    v-if="reward.expires_at && isExpiringSoon(reward.expires_at) && reward.status === 'awarded'"
                                    class="mt-4 p-3 bg-orange-100 border border-orange-300 rounded-md"
                                >
                                    <div class="flex items-center">
                                        <span class="text-orange-600 mr-2">⚠️</span>
                                        <span class="text-orange-800 text-sm">
                                            Cette récompense expire dans {{ getDaysUntilExpiry(reward.expires_at) }} jour{{ getDaysUntilExpiry(reward.expires_at) > 1 ? 's' : '' }} !
                                            Utilisez-la rapidement lors de votre prochaine location.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="text-gray-400 text-6xl mb-4">🎁</div>
                            <h3 class="text-xl font-medium text-gray-900 mb-2">
                                Aucune récompense pour le moment
                            </h3>
                            <p class="text-gray-600 mb-6">
                                Vos récompenses de parrainage apparaîtront ici dès que vous commencerez à inviter des amis.
                            </p>
                            <Link 
                                :href="route('referrals.index')"
                                class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                            >
                                Commencer à parrainer
                            </Link>
                        </div>

                        <!-- Pagination -->
                        <div v-if="rewards.links && rewards.links.length > 3" class="mt-8">
                            <nav class="flex justify-center">
                                <div class="flex space-x-1">
                                    <template v-for="link in rewards.links" :key="link.label">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            class="px-3 py-2 text-sm border rounded-md"
                                            :class="{
                                                'bg-blue-600 text-white border-blue-600': link.active,
                                                'text-gray-700 border-gray-300 hover:bg-gray-50': !link.active
                                            }"
                                            v-html="link.label"
                                        />
                                        <span
                                            v-else
                                            class="px-3 py-2 text-sm text-gray-400 border border-gray-300 rounded-md"
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </nav>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tips Card -->
                <Card class="mt-8">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <span class="mr-2">💡</span>
                            Conseils pour optimiser vos récompenses
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-gray-600">
                        <p>• 📱 Partagez votre code sur les réseaux sociaux pour toucher plus de monde</p>
                        <p>• ⏰ Utilisez vos crédits avant qu'ils n'expirent (1 an après attribution)</p>
                        <p>• 🎯 Les récompenses sont plus importantes quand vos filleuls font leur première location</p>
                        <p>• 👥 Plus vous parrainez, plus vous grimpez dans le classement !</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>