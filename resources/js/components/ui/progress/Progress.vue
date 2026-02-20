<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    modelValue?: number
    class?: string
  }>(),
  {
    modelValue: 0,
  },
)

const progressValue = computed(() => {
  return Math.min(Math.max(props.modelValue, 0), 100)
})
</script>

<template>
  <div
    :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800', props.class)"
    role="progressbar"
    :aria-valuenow="progressValue"
    aria-valuemin="0"
    aria-valuemax="100"
  >
    <div
      class="h-full w-full flex-1 bg-blue-600 dark:bg-blue-500 transition-all duration-300"
      :style="{ transform: `translateX(-${100 - progressValue}%)` }"
    />
  </div>
</template>
