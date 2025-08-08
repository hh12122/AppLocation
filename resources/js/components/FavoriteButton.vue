<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Heart } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
// import { toast } from '@/composables/useToast'

interface Props {
    vehicleId: number
    initialIsFavorited?: boolean
    size?: 'sm' | 'md' | 'lg'
    showText?: boolean
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    initialIsFavorited: false,
    size: 'md',
    showText: false,
    disabled: false
})

const page = usePage()
const isFavorited = ref(props.initialIsFavorited)
const isLoading = ref(false)

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'w-4 h-4'
        case 'lg':
            return 'w-6 h-6'
        default:
            return 'w-5 h-5'
    }
})

const buttonSizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'p-1'
        case 'lg':
            return 'p-3'
        default:
            return 'p-2'
    }
})

const toggleFavorite = async () => {
    if (props.disabled || isLoading.value) return

    // Check if user is authenticated
    if (!page.props.auth?.user) {
        router.visit('/login')
        return
    }

    isLoading.value = true

    try {
        const response = await fetch('/favorites/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                vehicle_id: props.vehicleId
            })
        })

        const data = await response.json()

        if (response.ok && data.success) {
            isFavorited.value = data.is_favorited
            
            // Simple notification without toast for now
            console.log(data.message)
        } else {
            throw new Error(data.message || 'Une erreur est survenue')
        }
    } catch (error) {
        console.error('Error toggling favorite:', error)
        
        // Simple error handling without toast for now
        alert('Erreur: ' + (error instanceof Error ? error.message : 'Impossible de modifier les favoris'))
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <Button
        @click="toggleFavorite"
        :disabled="disabled || isLoading"
        :variant="isFavorited ? 'default' : 'ghost'"
        :size="size === 'sm' ? 'sm' : size === 'lg' ? 'lg' : 'default'"
        :class="[
            'transition-all duration-200',
            isFavorited 
                ? 'text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100' 
                : 'text-gray-400 hover:text-red-500 hover:bg-red-50',
            buttonSizeClasses,
            { 'opacity-50 cursor-not-allowed': disabled || isLoading }
        ]"
    >
        <Heart 
            :class="[
                sizeClasses,
                'transition-all duration-200',
                isFavorited ? 'fill-current' : ''
            ]"
        />
        <span v-if="showText" class="ml-2">
            {{ isFavorited ? 'Favori' : 'Ajouter aux favoris' }}
        </span>
    </Button>
</template>