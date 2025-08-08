<template>
  <nav class="flex items-center justify-between">
    <div class="flex flex-1 justify-between sm:hidden">
      <Link
        v-if="prevUrl"
        :href="prevUrl"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Précédent
      </Link>
      <Link
        v-if="nextUrl"
        :href="nextUrl"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Suivant
      </Link>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Affichage de
          <span class="font-medium">{{ from }}</span>
          à
          <span class="font-medium">{{ to }}</span>
          sur
          <span class="font-medium">{{ total }}</span>
          résultats
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url"
            :class="[
              link.active
                ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                : 'text-gray-900 dark:text-gray-100 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-20 focus:outline-offset-0',
              link.label.includes('Previous') ? 'rounded-l-md' : '',
              link.label.includes('Next') ? 'rounded-r-md' : '',
              'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20'
            ]"
            :disabled="!link.url"
          >
            {{ getLabel(link.label) }}
          </Link>
        </nav>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  links?: PaginationLink[];
  from?: number;
  to?: number;
  total?: number;
  prevUrl?: string | null;
  nextUrl?: string | null;
}

withDefaults(defineProps<Props>(), {
  links: () => [],
  from: 0,
  to: 0,
  total: 0,
  prevUrl: null,
  nextUrl: null
});

function getLabel(label: string): string {
  if (label.includes('Previous')) return '«';
  if (label.includes('Next')) return '»';
  return label;
}
</script>