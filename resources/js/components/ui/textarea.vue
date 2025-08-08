<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    modelValue?: string
    placeholder?: string
    rows?: number
    cols?: number
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    maxlength?: number
    class?: string
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
    modelValue: ''
})

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const classes = computed(() => [
    'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
    'disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed',
    'resize-y',
    props.class
])

const handleInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement
    emit('update:modelValue', target.value)
}
</script>

<template>
    <textarea
        :value="modelValue"
        @input="handleInput"
        :class="classes"
        :placeholder="placeholder"
        :rows="rows"
        :cols="cols"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :maxlength="maxlength"
    />
</template>