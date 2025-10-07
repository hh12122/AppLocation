<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';

interface Props {
  message?: string | string[];
  class?: string;
}

const props = defineProps<Props>();

const errorMessages = computed(() => {
  if (!props.message) return [];
  return Array.isArray(props.message) ? props.message : [props.message];
});
</script>

<template>
  <div v-if="errorMessages.length > 0" :class="cn('flex items-start gap-2 text-sm text-red-600 dark:text-red-400 mt-1.5', props.class)">
    <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
    <div class="flex-1">
      <p v-for="(error, index) in errorMessages" :key="index">
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

export default {
  name: 'FormError',
};
</script>
