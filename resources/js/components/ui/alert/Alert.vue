<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { alertVariants, type AlertVariants } from '.'

const props = defineProps<{
  variant?: AlertVariants['variant']
  class?: string
}>()

const classes = computed(() =>
  cn(alertVariants({ variant: props.variant }), props.class)
)
</script>

<template>
  <div :class="classes" role="alert">
    <slot />
  </div>
</template>
