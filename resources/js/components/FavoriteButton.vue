<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Heart } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { toast } from '@/composables/useToast'

interface Props {
    // New polymorphic API
    favoritableType?: 'Vehicle' | 'Equipment' | 'Property'
    favoritableId?: number
    isFavorited?: boolean

    // Old vehicle-only API (backward compatibility)
    vehicleId?: number
    initialIsFavorited?: boolean

    // Common props
    size?: 'sm' | 'md' | 'lg'
    showText?: boolean
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    initialIsFavorited: false,
    isFavorited: false,
    size: 'md',
    showText: false,
    disabled: false
})

const page = usePage()

// Auto-detect API mode and compute values
const itemType = computed(() => {
    if (props.favoritableType) {
        return props.favoritableType.toLowerCase()
    }
    return props.vehicleId ? 'vehicle' : 'vehicle' // Default to vehicle for backward compatibility
})

const itemId = computed(() => {
    return props.favoritableId ?? props.vehicleId ?? 0
})

// Use the correct initial favorited state
const isFavorited = ref(props.isFavorited ?? props.initialIsFavorited)
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

const toggleFavorite = () => {
    if (props.disabled || isLoading.value) return

    // Check if user is authenticated
    if (!page.props.auth?.user) {
        router.visit('/login')
        return
    }

    isLoading.value = true

    router.post(
        route('favorites.toggle'),
        {
            type: itemType.value,
            item_id: itemId.value
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (response) => {
                // Toggle the favorite state locally
                const wasFavorited = isFavorited.value
                isFavorited.value = !isFavorited.value

                // Show success toast
                const message = response.props?.flash?.success ||
                    (isFavorited.value ? 'Ajouté aux favoris' : 'Retiré des favoris')

                toast({
                    title: 'Succès',
                    description: message,
                    variant: 'success',
                    duration: 3000
                })
            },
            onError: (errors) => {
                console.error('Error toggling favorite:', errors)

                // Get error message from response
                const errorMessage = page.props?.flash?.error ||
                    errors?.message ||
                    Object.values(errors)[0] ||
                    'Impossible de modifier les favoris'

                toast({
                    title: 'Erreur',
                    description: errorMessage as string,
                    variant: 'destructive',
                    duration: 4000
                })
            },
            onFinish: () => {
                isLoading.value = false
            }
        }
    )
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
