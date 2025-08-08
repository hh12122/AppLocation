<template>
  <div class="flex items-center">
    <div class="flex items-center">
      <span
        v-for="i in 5"
        :key="i"
        class="text-sm"
        :class="i <= Math.floor(rating) ? 'text-yellow-400' : 'text-gray-300'"
      >
        ★
      </span>
    </div>
    <span v-if="showValue" class="ml-1 text-sm text-gray-600 dark:text-gray-400">
      {{ rating.toFixed(1) }}
    </span>
    <span v-if="showCount && count > 0" class="ml-1 text-sm text-gray-600 dark:text-gray-400">
      ({{ count }} {{ count === 1 ? 'avis' : 'avis' }})
    </span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  rating: number;
  count?: number;
  showValue?: boolean;
  showCount?: boolean;
}

withDefaults(defineProps<Props>(), {
  count: 0,
  showValue: true,
  showCount: false
});
</script>