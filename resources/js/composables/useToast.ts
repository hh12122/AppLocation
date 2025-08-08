import { ref, reactive } from 'vue'

export interface Toast {
    id: string
    title: string
    description?: string
    variant?: 'default' | 'destructive' | 'success'
    duration?: number
}

const toasts = ref<Toast[]>([])
const toastCount = ref(0)

export function toast({ title, description, variant = 'default', duration = 5000 }: Omit<Toast, 'id'>) {
    const id = `toast-${++toastCount.value}`
    
    const newToast: Toast = {
        id,
        title,
        description,
        variant,
        duration
    }
    
    toasts.value.push(newToast)
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            removeToast(id)
        }, duration)
    }
    
    return id
}

export function removeToast(id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

export function useToast() {
    return {
        toast,
        toasts: toasts.value,
        removeToast
    }
}