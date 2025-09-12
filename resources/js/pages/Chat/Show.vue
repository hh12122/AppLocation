<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, nextTick, onMounted, onUnmounted, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

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
    status: string
    start_date: string
    end_date: string
    vehicle: Vehicle
}

interface Message {
    id: number
    message: string
    message_type: string
    created_at: string
    formatted_date: string
    sender: User
    is_read?: boolean
}

interface Conversation {
    id: number
    rental: Rental
    renter: User
    owner: User
}

interface Props {
    conversation: Conversation
    otherParticipant: User
    messages: Message[]
    rental: Rental
}

const props = defineProps<Props>()

const messageInput = ref('')
const messagesContainer = ref<HTMLElement>()
const isLoading = ref(false)
const localMessages = ref<Message[]>([...props.messages])

const currentUser = computed(() => (window as any).$page?.props?.auth?.user)

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

const sendMessage = async () => {
    if (!messageInput.value.trim() || isLoading.value) return

    const messageContent = messageInput.value.trim()
    messageInput.value = ''
    isLoading.value = true

    try {
        const response = await fetch(route('api.chat.send-message', props.conversation.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                message: messageContent,
                message_type: 'text'
            })
        })

        if (response.ok) {
            const data = await response.json()
            // Message will be added via Echo broadcast
        } else {
            // Handle error
            messageInput.value = messageContent
            alert('Erreur lors de l\'envoi du message')
        }
    } catch (error) {
        console.error('Error sending message:', error)
        messageInput.value = messageContent
        alert('Erreur lors de l\'envoi du message')
    } finally {
        isLoading.value = false
    }
}

const handleKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessage()
    }
}

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const formatMessageTime = (timestamp: string) => {
    const date = new Date(timestamp)
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const isMessageFromCurrentUser = (message: Message) => {
    return message.sender.id === currentUser.value?.id
}

const getRentalStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'pending': 'En attente',
        'confirmed': 'Confirmée',
        'active': 'En cours',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
    }
    return labels[status] || status
}

const getRentalStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'active': 'bg-green-100 text-green-800',
        'completed': 'bg-gray-100 text-gray-800',
        'cancelled': 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

// Real-time messaging setup
onMounted(() => {
    scrollToBottom()

    // Join private conversation channel
    const echo = (window as any).Echo
    if (echo) {
        echo.private(`conversation.${props.conversation.id}`)
            .listen('.message.sent', (e: any) => {
                // Add new message to local messages
                localMessages.value.push(e.message)
                scrollToBottom()
            })

        // Mark messages as read when viewing the conversation
        fetch(route('api.chat.mark-read', props.conversation.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            }
        })
    }
})

onUnmounted(() => {
    const echo = (window as any).Echo
    if (echo) {
        echo.leaveChannel(`conversation.${props.conversation.id}`)
    }
})

// Watch for new messages to scroll to bottom
watch(localMessages, () => {
    scrollToBottom()
}, { deep: true })
</script>

<template>
    <Head :title="`Chat avec ${otherParticipant.name}`" />

    <AppLayout>
        <div class="h-screen flex flex-col">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="max-w-6xl mx-auto flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link 
                            :href="route('chat.index')"
                            class="text-blue-600 hover:text-blue-800"
                        >
                            ← Retour
                        </Link>
                        
                        <div class="flex items-center space-x-3">
                            <Avatar class="w-10 h-10">
                                <AvatarImage 
                                    :src="otherParticipant.avatar || ''"
                                    :alt="otherParticipant.name"
                                />
                                <AvatarFallback>
                                    {{ getInitials(otherParticipant.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-lg font-semibold text-gray-900">
                                    {{ otherParticipant.name }}
                                </h1>
                                <p class="text-sm text-gray-600">
                                    {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <Badge :class="getRentalStatusColor(rental.status)">
                            {{ getRentalStatusLabel(rental.status) }}
                        </Badge>
                        <Link 
                            :href="route('rentals.show', rental.id)"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Voir la réservation
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="flex-1 overflow-hidden bg-gray-50">
                <div class="max-w-6xl mx-auto h-full flex">
                    <!-- Messages Area -->
                    <div class="flex-1 flex flex-col">
                        <!-- Messages List -->
                        <div 
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto p-6 space-y-4"
                        >
                            <div 
                                v-for="message in localMessages" 
                                :key="message.id"
                                class="flex"
                                :class="{
                                    'justify-end': isMessageFromCurrentUser(message),
                                    'justify-start': !isMessageFromCurrentUser(message)
                                }"
                            >
                                <div class="max-w-xs lg:max-w-md">
                                    <!-- System messages -->
                                    <div 
                                        v-if="message.message_type === 'system'"
                                        class="text-center text-sm text-gray-500 py-2"
                                    >
                                        {{ message.message }}
                                    </div>
                                    
                                    <!-- Regular messages -->
                                    <div 
                                        v-else
                                        class="rounded-lg px-4 py-2 shadow-sm"
                                        :class="{
                                            'bg-blue-600 text-white': isMessageFromCurrentUser(message),
                                            'bg-white text-gray-900': !isMessageFromCurrentUser(message)
                                        }"
                                    >
                                        <p class="text-sm">{{ message.message }}</p>
                                        <p 
                                            class="text-xs mt-1"
                                            :class="{
                                                'text-blue-100': isMessageFromCurrentUser(message),
                                                'text-gray-500': !isMessageFromCurrentUser(message)
                                            }"
                                        >
                                            {{ formatMessageTime(message.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state for no messages -->
                            <div v-if="localMessages.length === 0" class="text-center py-12">
                                <div class="text-gray-400 text-4xl mb-4">💬</div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    Commencer la conversation
                                </h3>
                                <p class="text-gray-600">
                                    Envoyez votre premier message à {{ otherParticipant.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="border-t border-gray-200 bg-white p-4">
                            <div class="flex space-x-3">
                                <div class="flex-1">
                                    <Input
                                        v-model="messageInput"
                                        placeholder="Tapez votre message..."
                                        @keypress="handleKeyPress"
                                        :disabled="isLoading"
                                        class="resize-none"
                                    />
                                </div>
                                <Button
                                    @click="sendMessage"
                                    :disabled="!messageInput.trim() || isLoading"
                                    class="px-6"
                                >
                                    <span v-if="isLoading">...</span>
                                    <span v-else>Envoyer</span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar with rental info -->
                    <div class="w-80 bg-white border-l border-gray-200 p-6">
                        <div class="space-y-6">
                            <!-- Rental Details -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Détails de la location</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Véhicule</p>
                                        <p class="text-sm text-gray-600">
                                            {{ rental.vehicle.year }} {{ rental.vehicle.brand }} {{ rental.vehicle.model }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Période</p>
                                        <p class="text-sm text-gray-600">
                                            {{ new Date(rental.start_date).toLocaleDateString('fr-FR') }} - 
                                            {{ new Date(rental.end_date).toLocaleDateString('fr-FR') }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Statut</p>
                                        <Badge :class="getRentalStatusColor(rental.status)" class="text-xs">
                                            {{ getRentalStatusLabel(rental.status) }}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Quick Actions -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Actions rapides</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <Link
                                        :href="route('rentals.show', rental.id)"
                                        class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                                    >
                                        Voir la réservation
                                    </Link>
                                    <Link
                                        :href="route('vehicles.show', rental.vehicle.id)"
                                        class="block w-full text-center border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium"
                                    >
                                        Voir le véhicule
                                    </Link>
                                    <a
                                        v-if="['confirmed', 'active', 'completed'].includes(rental.status)"
                                        :href="route('rentals.contract', rental.id)"
                                        target="_blank"
                                        class="block w-full text-center border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium"
                                    >
                                        📄 Contrat PDF
                                    </a>
                                </CardContent>
                            </Card>

                            <!-- Guidelines -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Conseils</CardTitle>
                                </CardHeader>
                                <CardContent class="text-xs text-gray-600 space-y-2">
                                    <p>• Soyez poli et respectueux</p>
                                    <p>• Précisez les détails importants</p>
                                    <p>• Répondez rapidement</p>
                                    <p>• Gardez vos échanges sur la plateforme</p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>