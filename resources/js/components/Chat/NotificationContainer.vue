<script setup lang="ts">
import { useNotifications } from '@/composables/useNotifications'
import NotificationToast from './NotificationToast.vue'

const { notifications, removeNotification } = useNotifications()

const handleDismiss = (notificationId: string) => {
    removeNotification(notificationId)
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-50 w-80 max-h-screen overflow-y-auto">
            <TransitionGroup
                name="notification"
                tag="div"
                class="space-y-2"
            >
                <NotificationToast
                    v-for="notification in notifications"
                    :key="notification.id"
                    :notification="notification"
                    @dismiss="handleDismiss"
                />
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.3s ease;
}
</style>