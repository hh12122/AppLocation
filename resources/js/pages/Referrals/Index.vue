<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

interface ReferralStats {
    total_referrals: number
    successful_referrals: number
    pending_referrals: number
    total_earned: number
    available_credits: number
    referral_rate: number
}

interface User {
    id: number
    name: string
    email: string
    created_at: string
}

interface Referral {
    id: number
    status: string
    conversion_type?: string
    converted_at?: string
    reward_amount: number
    created_at: string
    referred_user: User
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

interface Props {
    stats: ReferralStats
    referrals: {
        data: Referral[]
        links: any[]
        meta: any
    }
    rewards: {
        data: ReferralReward[]
        links: any[]
        meta: any
    }
    expiringRewards: number
    referralUrl: string
    referralCode: string
}

const props = defineProps<Props>()

const copied = ref(false)
const showShareOptions = ref(false)

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    } catch (err) {
        console.error('Failed to copy: ', err)
    }
}

const shareViaWhatsApp = () => {
    const message = encodeURIComponent(`Rejoignez CarLocation avec mon code de parrainage et recevez 10€ de crédit ! ${props.referralUrl}`)
    window.open(`https://wa.me/?text=${message}`, '_blank')
}

const shareViaFacebook = () => {
    const url = encodeURIComponent(props.referralUrl)
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank')
}

const shareViaTwitter = () => {
    const text = encodeURIComponent(`Rejoignez CarLocation avec mon code de parrainage et recevez 10€ de crédit !`)
    const url = encodeURIComponent(props.referralUrl)
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank')
}

const shareViaEmail = () => {
    const subject = encodeURIComponent('Invitation CarLocation')
    const body = encodeURIComponent(`Salut ! Je t'invite à rejoindre CarLocation, la plateforme de location de voitures entre particuliers.\n\nUtilise mon code de parrainage ${props.referralCode} ou clique sur ce lien : ${props.referralUrl}\n\nTu recevras 10€ de crédit de bienvenue !\n\nÀ bientôt sur CarLocation !`)
    window.open(`mailto:?subject=${subject}&body=${body}`)
}

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
        day: 'numeric'
    })
}

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'En attente',
        completed: 'Terminé',
        expired: 'Expiré',
        awarded: 'Accordé',
        used: 'Utilisé'
    }
    return labels[status] || status
}

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        expired: 'bg-red-100 text-red-800',
        awarded: 'bg-blue-100 text-blue-800',
        used: 'bg-gray-100 text-gray-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}
</script>

<template>
    <Head title="Programme de parrainage" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Programme de parrainage</h1>
                    <p class="text-gray-600 mt-2">
                        Invitez vos amis et gagnez des crédits pour chaque inscription réussie !
                    </p>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-blue-600">{{ stats.total_referrals }}</div>
                                <div class="ml-2 text-3xl">👥</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Total invités</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-green-600">{{ stats.successful_referrals }}</div>
                                <div class="ml-2 text-3xl">✅</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Inscriptions réussies</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-purple-600">{{ formatPrice(stats.available_credits) }}</div>
                                <div class="ml-2 text-3xl">💳</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Crédits disponibles</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-orange-600">{{ Math.round(stats.referral_rate) }}%</div>
                                <div class="ml-2 text-3xl">📊</div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Taux de conversion</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Expiring Rewards Alert -->
                <div v-if="expiringRewards > 0" class="mb-6">
                    <Card class="border-orange-200 bg-orange-50">
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="text-orange-600 mr-3">⚠️</div>
                                <div>
                                    <p class="text-orange-800 font-medium">
                                        Attention ! {{ expiringRewards }} récompense{{ expiringRewards > 1 ? 's' : '' }} 
                                        expire{{ expiringRewards > 1 ? 'nt' : '' }} dans les 30 prochains jours.
                                    </p>
                                    <Link 
                                        :href="route('referrals.rewards')" 
                                        class="text-orange-600 text-sm font-medium hover:text-orange-800"
                                    >
                                        Voir mes récompenses →
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Share Section -->
                    <div class="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <span class="mr-2">🎁</span>
                                    Inviter des amis
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Referral Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Votre code de parrainage
                                    </label>
                                    <div class="flex space-x-2">
                                        <Input 
                                            :value="referralCode" 
                                            readonly
                                            class="font-mono font-bold text-center"
                                        />
                                        <Button
                                            @click="copyToClipboard(referralCode)"
                                            variant="outline"
                                            size="sm"
                                        >
                                            {{ copied ? '✓' : '📋' }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Referral URL -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Lien de parrainage
                                    </label>
                                    <div class="flex space-x-2">
                                        <Input 
                                            :value="referralUrl" 
                                            readonly
                                            class="text-xs"
                                        />
                                        <Button
                                            @click="copyToClipboard(referralUrl)"
                                            variant="outline"
                                            size="sm"
                                        >
                                            {{ copied ? '✓' : '📋' }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Share Buttons -->
                                <div class="space-y-2">
                                    <Button
                                        @click="shareViaWhatsApp"
                                        class="w-full bg-green-600 hover:bg-green-700"
                                    >
                                        💬 Partager via WhatsApp
                                    </Button>
                                    
                                    <div class="grid grid-cols-3 gap-2">
                                        <Button
                                            @click="shareViaFacebook"
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            📘 Facebook
                                        </Button>
                                        <Button
                                            @click="shareViaTwitter"
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            🐦 Twitter
                                        </Button>
                                        <Button
                                            @click="shareViaEmail"
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            📧 Email
                                        </Button>
                                    </div>
                                </div>

                                <!-- How it works -->
                                <div class="pt-4 border-t">
                                    <h3 class="font-medium text-gray-900 mb-2">Comment ça marche ?</h3>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>1. 👥 Partagez votre code avec vos amis</p>
                                        <p>2. 📝 Ils s'inscrivent avec votre code</p>
                                        <p>3. 🎁 Vous recevez 20€, ils reçoivent 10€</p>
                                        <p>4. 💰 Bonus supplémentaires pour leur première location !</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Quick Actions -->
                        <Card class="mt-6">
                            <CardHeader>
                                <CardTitle>Actions rapides</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <Link
                                    :href="route('referrals.rewards')"
                                    class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                                >
                                    💳 Voir mes récompenses
                                </Link>
                                <Link
                                    :href="route('referrals.leaderboard')"
                                    class="block w-full text-center border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium"
                                >
                                    🏆 Classement
                                </Link>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Referrals and Rewards Lists -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Recent Referrals -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Vos invitations récentes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="referrals.data.length > 0" class="space-y-4">
                                    <div 
                                        v-for="referral in referrals.data" 
                                        :key="referral.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <Avatar class="w-10 h-10">
                                                <AvatarFallback>
                                                    {{ getInitials(referral.referred_user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="font-medium">{{ referral.referred_user.name }}</p>
                                                <p class="text-sm text-gray-600">
                                                    Invité le {{ formatDate(referral.created_at) }}
                                                </p>
                                                <div v-if="referral.converted_at" class="text-sm text-green-600">
                                                    ✅ Converti le {{ formatDate(referral.converted_at) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <Badge :class="getStatusColor(referral.status)">
                                                {{ getStatusLabel(referral.status) }}
                                            </Badge>
                                            <div v-if="referral.reward_amount > 0" class="text-sm text-gray-600 mt-1">
                                                {{ formatPrice(referral.reward_amount) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8">
                                    <div class="text-gray-400 text-4xl mb-4">👥</div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                                        Aucune invitation pour le moment
                                    </h3>
                                    <p class="text-gray-600">
                                        Commencez à inviter vos amis pour gagner des récompenses !
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Recent Rewards -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Récompenses récentes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="rewards.data.length > 0" class="space-y-4">
                                    <div 
                                        v-for="reward in rewards.data" 
                                        :key="reward.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <div class="text-2xl">
                                                {{ reward.reward_type === 'credit' ? '💳' : 
                                                   reward.reward_type === 'bonus' ? '🎁' : '🏆' }}
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ reward.title }}</p>
                                                <p class="text-sm text-gray-600">{{ reward.description }}</p>
                                                <div v-if="reward.expires_at" class="text-xs text-gray-500 mt-1">
                                                    Expire le {{ formatDate(reward.expires_at) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-green-600">
                                                {{ formatPrice(reward.amount) }}
                                            </div>
                                            <Badge :class="getStatusColor(reward.status)" class="text-xs">
                                                {{ getStatusLabel(reward.status) }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8">
                                    <div class="text-gray-400 text-4xl mb-4">🎁</div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                                        Aucune récompense pour le moment
                                    </h3>
                                    <p class="text-gray-600">
                                        Vos récompenses apparaîtront ici dès que vos amis s'inscriront !
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>