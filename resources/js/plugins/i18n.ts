import { createI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';

// Define message type
type MessageSchema = {
  [key: string]: string | MessageSchema;
};

// Get translations from Inertia props
function getTranslations(): Record<string, MessageSchema> {
  const page = usePage();
  //const locale = page.props.locale?.current || 'fr';
  const translations = page.props.translations || {};

  return {
    [locale]: translations as MessageSchema
  };
}

// Get current locale from Inertia props
//function getCurrentLocale(): string {
//  const page = usePage();
//  return page.props.locale?.current || 'fr';
//}

export const i18n = createI18n({
    legacy: false,
    locale: 'fr', // default fallback
    fallbackLocale: 'fr',
    messages: {}, // start empty
    globalInjection: true,
    missingWarn: false,
    fallbackWarn: false,
  });
// Update locale when it changes
export function updateLocale(locale: string) {
  i18n.global.locale.value = locale;
}

// Update messages when they change
export function updateMessages(locale: string, messages: MessageSchema) {
  i18n.global.setLocaleMessage(locale, messages);
}

// Format date based on locale
export function formatDate(date: Date | string, format?: string): string {
  const locale = i18n.global.locale.value;
  const dateObj = typeof date === 'string' ? new Date(date) : date;

  const options: Intl.DateTimeFormatOptions = format ? {} : {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  };

  return new Intl.DateTimeFormat(locale, options).format(dateObj);
}

// Format currency based on locale
export function formatCurrency(amount: number, currency = 'EUR'): string {
  const locale = i18n.global.locale.value;

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency,
  }).format(amount);
}

// Format number based on locale
export function formatNumber(number: number, decimals = 0): string {
  const locale = i18n.global.locale.value;

  return new Intl.NumberFormat(locale, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(number);
}

// Get relative time
export function getRelativeTime(date: Date | string): string {
  const locale = i18n.global.locale.value;
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - dateObj.getTime()) / 1000);

  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

  if (diffInSeconds < 60) {
    return rtf.format(-diffInSeconds, 'second');
  } else if (diffInSeconds < 3600) {
    return rtf.format(-Math.floor(diffInSeconds / 60), 'minute');
  } else if (diffInSeconds < 86400) {
    return rtf.format(-Math.floor(diffInSeconds / 3600), 'hour');
  } else if (diffInSeconds < 2592000) {
    return rtf.format(-Math.floor(diffInSeconds / 86400), 'day');
  } else if (diffInSeconds < 31536000) {
    return rtf.format(-Math.floor(diffInSeconds / 2592000), 'month');
  } else {
    return rtf.format(-Math.floor(diffInSeconds / 31536000), 'year');
  }
}

export default i18n;
