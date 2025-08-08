<script setup lang="ts">
import { ref } from 'vue'
import { useNavigation } from '@/composables/useNavigation'
import { Button } from '@/components/ui/button'
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Navigation, ChevronDown } from 'lucide-vue-next'

interface Props {
    latitude: number
    longitude: number
    address?: string
    name?: string
    buttonSize?: 'sm' | 'default' | 'lg'
    buttonVariant?: 'default' | 'outline' | 'secondary' | 'ghost'
    showLabel?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    buttonSize: 'default',
    buttonVariant: 'outline',
    showLabel: true
})

const { navigationServices, navigateTo } = useNavigation()

const handleNavigate = (service: typeof navigationServices[0]) => {
    const destination = {
        latitude: props.latitude,
        longitude: props.longitude,
        address: props.address,
        name: props.name
    }
    
    navigateTo(service, destination)
}

// Filter out the copy coordinates option for quick navigation
const quickServices = navigationServices.filter(s => s.name !== 'Copier les coordonnées')
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button 
                :size="buttonSize" 
                :variant="buttonVariant"
                class="gap-2"
            >
                <Navigation class="w-4 h-4" />
                <span v-if="showLabel">Itinéraire</span>
                <ChevronDown class="w-3 h-3 ml-1" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuItem
                v-for="service in quickServices"
                :key="service.name"
                @click="handleNavigate(service)"
                class="cursor-pointer"
            >
                <span class="mr-2">{{ service.icon }}</span>
                {{ service.name }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>