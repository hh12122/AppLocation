<script setup lang="ts">
import { computed } from 'vue'
import { X, CheckCircle, AlertCircle, Info } from 'lucide-vue-next'
import type { Toast } from '@/composables/useToast'

interface Props {
    toast: Toast
}

const props = defineProps<Props>()
const emit = defineEmits<{
    close: []
}>()

const variantClasses = computed(() => {
    switch (props.toast.variant) {
        case 'success':
            return 'bg-green-50 border-green-200 text-green-900'
        case 'destructive':
            return 'bg-red-50 border-red-200 text-red-900'
        default:
            return 'bg-white border-gray-200 text-gray-900'
    }
})

const icon = computed(() => {
    switch (props.toast.variant) {
        case 'success':
            return CheckCircle
        case 'destructive':
            return AlertCircle
        default:
            return Info
    }
})

const iconColor = computed(() => {
    switch (props.toast.variant) {
        case 'success':
            return 'text-green-600'
        case 'destructive':
            return 'text-red-600'
        default:
            return 'text-blue-600'
    }
})
</script>

<template>
    <div
        :class="[
            'pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg transition-all',
            variantClasses
        ]"
        role="alert"
    >
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <component :is="icon" :class="['h-5 w-5', iconColor]" />
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium">
                        {{ toast.title }}
                    </p>
                    <p v-if="toast.description" class="mt-1 text-sm opacity-90">
                        {{ toast.description }}
                    </p>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button
                        @click="emit('close')"
                        type="button"
                        class="inline-flex rounded-md hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >
                        <span class="sr-only">Close</span>
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
