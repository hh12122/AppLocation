<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

interface User {
    id: number
    name: string
    avatar?: string
}

interface Vehicle {
    id: number
    brand: string
    model: string
    year: number
}

interface Rental {
    id: number
    vehicle: Vehicle
}

interface Message {
    id: number
    message: string
    created_at: string
    sender: User
}

interface Conversation {
    id: number
    rental: Rental
    renter: User
    owner: User
    other_participant: User
    unread_count: number
    last_message_at: string
    latest_message?: Message[]
}

interface Props {
    conversations: Conversation[]
}

const props = defineProps<Props>()

const formatLastMessageTime = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
    
    if (diffInHours < 1) {
        return 'Il y a moins d\'une heure'
    } else if (diffInHours < 24) {
        return `Il y a ${diffInHours}h`
    } else if (diffInHours < 48) {
        return 'Hier'
    } else {
        return date.toLocaleDateString('fr-FR')
    }
}

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const hasUnreadMessages = computed(() => {
    return props.conversations.some(c => c.unread_count > 0)
})

const totalUnreadCount = computed(() => {
    return props.conversations.reduce((sum, c) => sum + c.unread_count, 0)
})
</script>

<template>
    <Head title="Messages" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Messages</h1>
                            <p class="text-gray-600 mt-2">
                                Communiquez avec les propriétaires et locataires
                            </p>
                        </div>
                        <div v-if="hasUnreadMessages" class="flex items-center">
                            <Badge variant="destructive" class="px-3 py-1">
                                {{ totalUnreadCount }} non lu{{ totalUnreadCount > 1 ? 's' : '' }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Conversations List -->
                <div v-if="conversations.length > 0" class="space-y-4">
                    <Card 
                        v-for="conversation in conversations" 
                        :key="conversation.id"
                        class="hover:shadow-md transition-shadow cursor-pointer"
                        @click="router.visit(route('chat.show', conversation.id))"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center space-x-4">
                                <!-- Avatar -->
                                <Avatar class="w-12 h-12">
                                    <AvatarImage 
                                        :src="conversation.other_participant.avatar || ''"
                                        :alt="conversation.other_participant.name"
                                    />
                                    <AvatarFallback>
                                        {{ getInitials(conversation.other_participant.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 truncate">
                                                {{ conversation.other_participant.name }}
                                            </h3>
                                            <p class="text-sm text-gray-600 truncate">
                                                {{ conversation.rental.vehicle.year }} 
                                                {{ conversation.rental.vehicle.brand }} 
                                                {{ conversation.rental.vehicle.model }}
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Badge 
                                                v-if="conversation.unread_count > 0"
                                                variant="destructive"
                                                class="text-xs"
                                            >
                                                {{ conversation.unread_count }}
                                            </Badge>
                                            <span class="text-xs text-gray-500">
                                                {{ formatLastMessageTime(conversation.last_message_at) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Last Message Preview -->
                                    <div 
                                        v-if="conversation.latest_message && conversation.latest_message.length > 0"
                                        class="mt-2"
                                    >
                                        <p class="text-sm text-gray-600 truncate">
                                            <span 
                                                v-if="conversation.latest_message[0].sender.id === $page.props.auth.user.id"
                                                class="font-medium"
                                            >
                                                Vous: 
                                            </span>
                                            {{ conversation.latest_message[0].message }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Arrow Icon -->
                                <div class="text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">💬</div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune conversation</h3>
                    <p class="text-gray-600 mb-6">
                        Vos conversations avec les propriétaires et locataires apparaîtront ici.
                    </p>
                    <div class="space-y-3">
                        <Link 
                            :href="route('vehicles.index')"
                            class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium"
                        >
                            Rechercher un véhicule
                        </Link>
                        <br>
                        <Link 
                            :href="route('rentals.my')"
                            class="inline-block text-blue-600 hover:text-blue-800 font-medium"
                        >
                            Voir mes réservations
                        </Link>
                    </div>
                </div>

                <!-- Info Card -->
                <Card class="mt-8">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <span class="mr-2">💡</span>
                            Conseils pour bien communiquer
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-gray-600">
                        <p>• Soyez poli et respectueux dans vos messages</p>
                        <p>• Précisez les détails importants (lieu de rendez-vous, horaires, etc.)</p>
                        <p>• Répondez rapidement aux questions des autres utilisateurs</p>
                        <p>• En cas de problème, contactez le support client</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>