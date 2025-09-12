import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
  updateLocale, 
  updateMessages, 
  formatDate, 
  formatCurrency, 
  formatNumber, 
  getRelativeTime 
} from '@/plugins/i18n';

interface Language {
  code: string;
  name: string;
  native_name: string;
  flag: string;
  is_rtl: boolean;
}

interface LocaleInfo {
  current: string;
  available: Language[];
  is_rtl: boolean;
  info: {
    code: string;
    name: string;
    native_name: string;
    flag: string;
    is_rtl: boolean;
    date_format: string;
    currency_symbol: string;
  };
}

export function useLocalization() {
  const { t, locale } = useI18n();
  const page = usePage();
  
  // Get locale data from Inertia props
  const localeData = computed<LocaleInfo>(() => page.props.locale as LocaleInfo);
  const currentLocale = computed(() => localeData.value?.current || 'fr');
  const availableLanguages = computed(() => localeData.value?.available || []);
  const isRtl = computed(() => localeData.value?.is_rtl || false);
  
  // Loading state
  const isChangingLocale = ref(false);
  
  // Watch for locale changes
  watch(currentLocale, (newLocale) => {
    updateLocale(newLocale);
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
    document.documentElement.lang = newLocale;
  }, { immediate: true });
  
  // Watch for translation updates
  watch(() => page.props.translations, (newTranslations) => {
    if (newTranslations) {
      updateMessages(currentLocale.value, newTranslations as any);
    }
  }, { immediate: true });
  
  // Change locale
  const changeLocale = async (newLocale: string) => {
    if (newLocale === currentLocale.value || isChangingLocale.value) {
      return;
    }
    
    isChangingLocale.value = true;
    
    try {
      // Update locale on server
      await axios.post('/localization/change-locale', { locale: newLocale });
      
      // Reload page with new locale
      router.reload({
        preserveState: true,
        preserveScroll: true,
      });
    } catch (error) {
      console.error('Error changing locale:', error);
    } finally {
      isChangingLocale.value = false;
    }
  };
  
  // Get language by code
  const getLanguage = (code: string): Language | undefined => {
    return availableLanguages.value.find((lang: Language) => lang.code === code);
  };
  
  // Get current language
  const currentLanguage = computed(() => getLanguage(currentLocale.value));
  
  // Translation helper with fallback
  const translate = (key: string, params?: Record<string, any>, defaultValue?: string): string => {
    try {
      const translated = t(key, params);
      if (translated === key && defaultValue) {
        return defaultValue;
      }
      return translated;
    } catch {
      return defaultValue || key;
    }
  };
  
  // Plural translation helper
  const translatePlural = (key: string, count: number, params?: Record<string, any>): string => {
    const pluralKey = count === 1 ? `${key}_one` : `${key}_other`;
    return translate(pluralKey, { count, ...params }, translate(key, { count, ...params }));
  };
  
  // Check if translation exists
  const hasTranslation = (key: string): boolean => {
    try {
      const translated = t(key);
      return translated !== key;
    } catch {
      return false;
    }
  };
  
  // Format helpers
  const formatters = {
    date: formatDate,
    currency: formatCurrency,
    number: formatNumber,
    relativeTime: getRelativeTime,
    
    // Additional formatters
    percentage: (value: number, decimals = 0): string => {
      return formatNumber(value, decimals) + '%';
    },
    
    fileSize: (bytes: number): string => {
      const units = ['B', 'KB', 'MB', 'GB', 'TB'];
      let size = bytes;
      let unitIndex = 0;
      
      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
      }
      
      return formatNumber(size, unitIndex > 0 ? 2 : 0) + ' ' + units[unitIndex];
    },
    
    distance: (meters: number): string => {
      if (meters < 1000) {
        return formatNumber(meters, 0) + ' m';
      }
      return formatNumber(meters / 1000, 1) + ' km';
    },
    
    duration: (seconds: number): string => {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;
      
      if (hours > 0) {
        return `${hours}h ${minutes}m`;
      } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
      } else {
        return `${secs}s`;
      }
    },
  };
  
  // Get translation for a specific model field
  const getModelTranslation = async (
    modelType: string,
    modelId: number,
    field: string,
    locale?: string
  ): Promise<string> => {
    try {
      const response = await axios.get('/api/translations/model', {
        params: {
          type: modelType,
          id: modelId,
          field,
          locale: locale || currentLocale.value,
        },
      });
      return response.data.translation;
    } catch (error) {
      console.error('Error fetching model translation:', error);
      return '';
    }
  };
  
  // Direction helpers for RTL support
  const direction = {
    isRtl: isRtl,
    isLtr: computed(() => !isRtl.value),
    dir: computed(() => isRtl.value ? 'rtl' : 'ltr'),
    start: computed(() => isRtl.value ? 'right' : 'left'),
    end: computed(() => isRtl.value ? 'left' : 'right'),
    startClass: computed(() => isRtl.value ? 'mr' : 'ml'),
    endClass: computed(() => isRtl.value ? 'ml' : 'mr'),
  };
  
  return {
    // Current state
    currentLocale,
    currentLanguage,
    availableLanguages,
    isRtl,
    isChangingLocale,
    
    // Translation functions
    t: translate,
    translate,
    translatePlural,
    hasTranslation,
    getModelTranslation,
    
    // Locale management
    changeLocale,
    getLanguage,
    
    // Formatters
    formatters,
    formatDate,
    formatCurrency,
    formatNumber,
    getRelativeTime,
    
    // Direction support
    direction,
  };
}