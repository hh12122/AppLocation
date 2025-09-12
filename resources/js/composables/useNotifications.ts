import { ref, onMounted, onUnmounted } from 'vue'

export interface ChatNotification {
    id: string
    message_id: number
    conversation_id: number
    sender_id: number
    sender_name: string
    sender_avatar?: string
    message: string
    rental_info: {
        id: number
        vehicle: {
            brand: string
            model: string
            year: number
        }
    }
    created_at: string
    type: string
}

export function useNotifications() {
    const notifications = ref<ChatNotification[]>([])
    const unreadCount = ref(0)
    
    const addNotification = (notification: ChatNotification) => {
        notifications.value.unshift(notification)
        unreadCount.value++
        
        // Show browser notification if permission granted
        showBrowserNotification(notification)
        
        // Auto-remove after 5 seconds if not manually dismissed
        setTimeout(() => {
            removeNotification(notification.id)
        }, 5000)
    }
    
    const removeNotification = (notificationId: string) => {
        const index = notifications.value.findIndex(n => n.id === notificationId)
        if (index > -1) {
            notifications.value.splice(index, 1)
            if (unreadCount.value > 0) {
                unreadCount.value--
            }
        }
    }
    
    const clearAllNotifications = () => {
        notifications.value = []
        unreadCount.value = 0
    }
    
    const markAsRead = (notificationId: string) => {
        removeNotification(notificationId)
    }
    
    const showBrowserNotification = (notification: ChatNotification) => {
        if ('Notification' in window && Notification.permission === 'granted') {
            const browserNotification = new Notification(
                `Nouveau message de ${notification.sender_name}`,
                {
                    body: notification.message,
                    icon: notification.sender_avatar || '/favicon.ico',
                    tag: `chat-${notification.conversation_id}`,
                    requireInteraction: false,
                }
            )
            
            browserNotification.onclick = () => {
                window.focus()
                // Navigate to chat
                window.location.href = `/chat/${notification.conversation_id}`
                browserNotification.close()
            }
            
            setTimeout(() => {
                browserNotification.close()
            }, 5000)
        }
    }
    
    const requestNotificationPermission = async () => {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission()
            return permission === 'granted'
        }
        return Notification.permission === 'granted'
    }
    
    const setupEchoListeners = () => {
        const echo = (window as any).Echo
        const user = (window as any).$page?.props?.auth?.user
        
        if (echo && user) {
            // Listen for new message notifications on user's private channel
            echo.private(`App.Models.User.${user.id}`)
                .listen('.notification.new-message', (e: ChatNotification) => {
                    addNotification(e)
                })
        }
    }
    
    onMounted(() => {
        setupEchoListeners()
        requestNotificationPermission()
    })
    
    return {
        notifications,
        unreadCount,
        addNotification,
        removeNotification,
        clearAllNotifications,
        markAsRead,
        requestNotificationPermission
    }
}