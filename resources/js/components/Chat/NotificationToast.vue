<script setup lang="ts">
import { computed } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { router } from '@inertiajs/vue3'
import type { ChatNotification } from '@/composables/useNotifications'

interface Props {
    notification: ChatNotification
}

const props = defineProps<Props>()
const emit = defineEmits<{
    dismiss: [notificationId: string]
}>()

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const handleClick = () => {
    router.visit(route('chat.show', props.notification.conversation_id))
    emit('dismiss', props.notification.id)
}

const handleDismiss = (e: Event) => {
    e.stopPropagation()
    emit('dismiss', props.notification.id)
}

const truncatedMessage = computed(() => {
    if (props.notification.message.length > 60) {
        return props.notification.message.substring(0, 60) + '...'
    }
    return props.notification.message
})
</script>

<template>
    <Card 
        class="mb-3 cursor-pointer hover:shadow-lg transition-shadow duration-200 border-l-4 border-l-blue-500"
        @click="handleClick"
    >
        <CardContent class="p-4">
            <div class="flex items-start space-x-3">
                <!-- Avatar -->
                <Avatar class="w-10 h-10">
                    <AvatarImage 
                        :src="notification.sender_avatar || ''"
                        :alt="notification.sender_name"
                    />
                    <AvatarFallback>
                        {{ getInitials(notification.sender_name) }}
                    </AvatarFallback>
                </Avatar>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ notification.sender_name }}
                            </p>
                            <p class="text-xs text-gray-600">
                                {{ notification.rental_info.vehicle.year }} 
                                {{ notification.rental_info.vehicle.brand }} 
                                {{ notification.rental_info.vehicle.model }}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="p-1 h-auto text-gray-400 hover:text-gray-600"
                            @click="handleDismiss"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </Button>
                    </div>
                    
                    <p class="text-sm text-gray-700 mt-1">
                        {{ truncatedMessage }}
                    </p>
                    
                    <p class="text-xs text-gray-500 mt-1">
                        {{ new Date(notification.created_at).toLocaleTimeString('fr-FR', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>