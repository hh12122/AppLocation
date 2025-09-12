<script setup lang="ts">
import { computed } from 'vue';
import { useLocalization } from '@/composables/useLocalization';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
  DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Globe, Check, Loader2 } from 'lucide-vue-next';

interface Props {
  variant?: 'default' | 'ghost' | 'outline';
  size?: 'sm' | 'default' | 'lg';
  showLabel?: boolean;
  showFlag?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'ghost',
  size: 'default',
  showLabel: true,
  showFlag: true,
});

const {
  currentLocale,
  currentLanguage,
  availableLanguages,
  changeLocale,
  isChangingLocale,
  direction,
} = useLocalization();

// Get button label
const buttonLabel = computed(() => {
  if (!props.showLabel) {
    return '';
  }
  
  if (currentLanguage.value) {
    return currentLanguage.value.native_name;
  }
  
  return currentLocale.value.toUpperCase();
});

// Get flag emoji or icon
const getFlagDisplay = (flag: string | undefined) => {
  return flag || '🌐';
};

// Handle language change
const handleLanguageChange = async (locale: string) => {
  if (locale !== currentLocale.value && !isChangingLocale.value) {
    await changeLocale(locale);
  }
};

// Sort languages by name
const sortedLanguages = computed(() => {
  return [...availableLanguages.value].sort((a, b) => 
    a.native_name.localeCompare(b.native_name)
  );
});

// Group languages by region (optional)
const groupedLanguages = computed(() => {
  const european = ['fr', 'en', 'es', 'de', 'it'];
  const middleEastern = ['ar'];
  
  return {
    european: sortedLanguages.value.filter(lang => european.includes(lang.code)),
    middleEastern: sortedLanguages.value.filter(lang => middleEastern.includes(lang.code)),
    other: sortedLanguages.value.filter(lang => 
      !european.includes(lang.code) && !middleEastern.includes(lang.code)
    ),
  };
});
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        :variant="variant"
        :size="size"
        :disabled="isChangingLocale"
        class="gap-2"
      >
        <Loader2 v-if="isChangingLocale" class="h-4 w-4 animate-spin" />
        <Globe v-else class="h-4 w-4" />
        
        <span v-if="showFlag && currentLanguage" class="text-lg">
          {{ getFlagDisplay(currentLanguage.flag) }}
        </span>
        
        <span v-if="showLabel" class="hidden sm:inline-block">
          {{ buttonLabel }}
        </span>
      </Button>
    </DropdownMenuTrigger>
    
    <DropdownMenuContent 
      align="end" 
      class="w-56"
      :dir="direction.dir"
    >
      <DropdownMenuLabel>
        {{ $t('select_language', 'Select Language') }}
      </DropdownMenuLabel>
      
      <DropdownMenuSeparator />
      
      <!-- European Languages -->
      <div v-if="groupedLanguages.european.length > 0">
        <DropdownMenuLabel class="text-xs text-muted-foreground">
          {{ $t('european_languages', 'European') }}
        </DropdownMenuLabel>
        
        <DropdownMenuItem
          v-for="language in groupedLanguages.european"
          :key="language.code"
          @click="handleLanguageChange(language.code)"
          class="cursor-pointer"
          :class="{ 'bg-accent': language.code === currentLocale }"
        >
          <span class="flex items-center justify-between w-full">
            <span class="flex items-center gap-2">
              <span class="text-lg">{{ getFlagDisplay(language.flag) }}</span>
              <span>{{ language.native_name }}</span>
            </span>
            
            <Check 
              v-if="language.code === currentLocale" 
              class="h-4 w-4 text-primary"
            />
          </span>
        </DropdownMenuItem>
      </div>
      
      <!-- Middle Eastern Languages -->
      <div v-if="groupedLanguages.middleEastern.length > 0">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs text-muted-foreground">
          {{ $t('middle_eastern_languages', 'Middle Eastern') }}
        </DropdownMenuLabel>
        
        <DropdownMenuItem
          v-for="language in groupedLanguages.middleEastern"
          :key="language.code"
          @click="handleLanguageChange(language.code)"
          class="cursor-pointer"
          :class="{ 'bg-accent': language.code === currentLocale }"
        >
          <span class="flex items-center justify-between w-full">
            <span class="flex items-center gap-2">
              <span class="text-lg">{{ getFlagDisplay(language.flag) }}</span>
              <span>{{ language.native_name }}</span>
              <Badge v-if="language.is_rtl" variant="secondary" class="text-xs">
                RTL
              </Badge>
            </span>
            
            <Check 
              v-if="language.code === currentLocale" 
              class="h-4 w-4 text-primary"
            />
          </span>
        </DropdownMenuItem>
      </div>
      
      <!-- Other Languages -->
      <div v-if="groupedLanguages.other.length > 0">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs text-muted-foreground">
          {{ $t('other_languages', 'Other') }}
        </DropdownMenuLabel>
        
        <DropdownMenuItem
          v-for="language in groupedLanguages.other"
          :key="language.code"
          @click="handleLanguageChange(language.code)"
          class="cursor-pointer"
          :class="{ 'bg-accent': language.code === currentLocale }"
        >
          <span class="flex items-center justify-between w-full">
            <span class="flex items-center gap-2">
              <span class="text-lg">{{ getFlagDisplay(language.flag) }}</span>
              <span>{{ language.native_name }}</span>
            </span>
            
            <Check 
              v-if="language.code === currentLocale" 
              class="h-4 w-4 text-primary"
            />
          </span>
        </DropdownMenuItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>