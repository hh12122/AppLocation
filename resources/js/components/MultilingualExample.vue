<script setup lang="ts">
import { useLocalization } from '@/composables/useLocalization';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Home, Settings, User, Calendar, CreditCard, Globe } from 'lucide-vue-next';

const {
  t,
  currentLocale,
  currentLanguage,
  formatters,
  direction,
} = useLocalization();

// Sample data for demonstration
const sampleDate = new Date();
const samplePrice = 1234.56;
const sampleDistance = 5432;
const sampleFileSize = 2048576;
const samplePercentage = 75.5;
const sampleDuration = 3665;

// Navigation items with translations
const navigationItems = [
  { icon: Home, key: 'home' },
  { icon: Settings, key: 'settings' },
  { icon: User, key: 'profile' },
  { icon: Calendar, key: 'calendar' },
  { icon: CreditCard, key: 'payments' },
];

// Status examples
const statusExamples = [
  { key: 'active', variant: 'default' },
  { key: 'pending', variant: 'secondary' },
  { key: 'completed', variant: 'success' },
  { key: 'cancelled', variant: 'destructive' },
];
</script>

<template>
  <div :dir="direction.dir" class="p-8 space-y-8">
    <!-- Header with Language Switcher -->
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold">
        {{ t('multilingual_demo', 'Multilingual Support Demo') }}
      </h1>
      <LanguageSwitcher />
    </div>

    <!-- Current Language Info -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Globe class="w-5 h-5" />
          {{ t('current_language', 'Current Language') }}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-sm text-muted-foreground">{{ t('language', 'Language') }}</p>
            <p class="font-medium">
              {{ currentLanguage?.native_name }} {{ currentLanguage?.flag }}
            </p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('locale_code', 'Locale Code') }}</p>
            <p class="font-medium">{{ currentLocale }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('direction', 'Direction') }}</p>
            <p class="font-medium">{{ direction.isRtl ? 'RTL' : 'LTR' }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('alignment', 'Alignment') }}</p>
            <p class="font-medium">{{ direction.start }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Navigation Example -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('navigation', 'Navigation') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="item in navigationItems"
            :key="item.key"
            variant="outline"
          >
            <component :is="item.icon" class="w-4 h-4" :class="direction.startClass + '-2'" />
            {{ t(item.key) }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Common Actions -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('common_actions', 'Common Actions') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <Button variant="default">{{ t('save') }}</Button>
          <Button variant="secondary">{{ t('cancel') }}</Button>
          <Button variant="destructive">{{ t('delete') }}</Button>
          <Button variant="outline">{{ t('edit') }}</Button>
          <Button variant="ghost">{{ t('view') }}</Button>
          <Button variant="default">{{ t('create') }}</Button>
          <Button variant="secondary">{{ t('update') }}</Button>
          <Button variant="outline">{{ t('search') }}</Button>
        </div>
      </CardContent>
    </Card>

    <!-- Status Examples -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('status_examples', 'Status Examples') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-wrap gap-2">
          <Badge
            v-for="status in statusExamples"
            :key="status.key"
            :variant="status.variant"
          >
            {{ t(status.key) }}
          </Badge>
        </div>
      </CardContent>
    </Card>

    <!-- Formatting Examples -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('formatting_examples', 'Formatting Examples') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-muted-foreground">{{ t('date') }}</p>
            <p class="font-medium">{{ formatters.date(sampleDate) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('currency') }}</p>
            <p class="font-medium">{{ formatters.currency(samplePrice) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('number') }}</p>
            <p class="font-medium">{{ formatters.number(samplePrice, 2) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('percentage', 'Percentage') }}</p>
            <p class="font-medium">{{ formatters.percentage(samplePercentage, 1) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('distance') }}</p>
            <p class="font-medium">{{ formatters.distance(sampleDistance) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('file_size', 'File Size') }}</p>
            <p class="font-medium">{{ formatters.fileSize(sampleFileSize) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('duration') }}</p>
            <p class="font-medium">{{ formatters.duration(sampleDuration) }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">{{ t('relative_time', 'Relative Time') }}</p>
            <p class="font-medium">{{ formatters.relativeTime(sampleDate) }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Form Labels -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('form_labels', 'Form Labels') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('name') }}</label>
            <input type="text" class="w-full px-3 py-2 border rounded" :placeholder="t('name')" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('email') }}</label>
            <input type="email" class="w-full px-3 py-2 border rounded" :placeholder="t('email')}" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('phone') }}</label>
            <input type="tel" class="w-full px-3 py-2 border rounded" :placeholder="t('phone')" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('city') }}</label>
            <input type="text" class="w-full px-3 py-2 border rounded" :placeholder="t('city')" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('date') }}</label>
            <input type="date" class="w-full px-3 py-2 border rounded" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">{{ t('price') }}</label>
            <input type="number" class="w-full px-3 py-2 border rounded" :placeholder="t('price')" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Messages -->
    <Card>
      <CardHeader>
        <CardTitle>{{ t('messages') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <div class="p-3 bg-green-100 text-green-800 rounded">
            {{ t('success') }}: {{ t('operation_successful', 'Operation completed successfully') }}
          </div>
          <div class="p-3 bg-red-100 text-red-800 rounded">
            {{ t('error') }}: {{ t('something_went_wrong') }}
          </div>
          <div class="p-3 bg-yellow-100 text-yellow-800 rounded">
            {{ t('warning') }}: {{ t('are_you_sure') }}
          </div>
          <div class="p-3 bg-blue-100 text-blue-800 rounded">
            {{ t('info') }}: {{ t('please_wait') }}
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>