<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const unreadCount = ref(0)
const isLoading = ref(false)
const page = usePage()

const hasUnreadMessages = computed(() => unreadCount.value > 0)

const userId = computed(() => page.props.auth?.user?.id)

const fetchUnreadCount = async () => {
    if (isLoading.value) return

    isLoading.value = true
    try {
        const response = await fetch(route('api.chat.unread-count'))
        if (response.ok) {
            const data = await response.json()
            unreadCount.value = data.unread_count
        }
    } catch (error) {
        console.error('Error fetching unread count:', error)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchUnreadCount()

    const echo = (window as any).Echo
    if (echo && userId.value) {
        echo.private(`App.Models.User.${userId.value}`)
            .notification(() => {
                fetchUnreadCount()
            })
    }
})

onUnmounted(() => {
    const echo = (window as any).Echo
    if (echo && userId.value) {
        echo.leave(`App.Models.User.${userId.value}`)
    }
})
</script>

<template>
    <Link :href="route('chat.index')" class="relative">
        <Button
            variant="ghost"
            size="sm"
            class="relative p-2"
            :class="{ 'text-blue-600': hasUnreadMessages }"
        >
            <!-- Chat icon -->
            <svg 
                class="w-5 h-5" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
            </svg>
            
            <!-- Unread count badge -->
            <Badge 
                v-if="hasUnreadMessages && !isLoading"
                variant="destructive"
                class="absolute -top-1 -right-1 min-w-[1.25rem] h-5 text-xs flex items-center justify-center p-0"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </Badge>
        </Button>
    </Link>
</template>