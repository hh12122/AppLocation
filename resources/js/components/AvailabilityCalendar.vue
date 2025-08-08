<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        Calendrier de disponibilité
      </h3>
      <div class="flex items-center space-x-2">
        <button
          @click="previousMonth"
          class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ currentMonthYear }}
        </div>
        <button
          @click="nextMonth"
          class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-7 gap-1 mb-2">
      <div
        v-for="day in weekDays"
        :key="day"
        class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2"
      >
        {{ day }}
      </div>
    </div>

    <div class="grid grid-cols-7 gap-1">
      <div
        v-for="(day, index) in calendarDays"
        :key="index"
        :class="getDayClasses(day)"
        @click="selectDate(day)"
      >
        <div class="aspect-square flex flex-col items-center justify-center p-1">
          <span class="text-sm">{{ day.date }}</span>
          <div v-if="day.hasBooking" class="mt-1">
            <span class="text-xs text-red-600 dark:text-red-400">Réservé</span>
          </div>
          <div v-else-if="day.price" class="mt-1">
            <span class="text-xs text-green-600 dark:text-green-400">{{ day.price }}€</span>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 space-y-2">
      <div class="flex items-center space-x-2">
        <div class="w-4 h-4 bg-green-100 dark:bg-green-900 rounded"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Disponible</span>
      </div>
      <div class="flex items-center space-x-2">
        <div class="w-4 h-4 bg-red-100 dark:bg-red-900 rounded"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Réservé</span>
      </div>
      <div class="flex items-center space-x-2">
        <div class="w-4 h-4 bg-blue-100 dark:bg-blue-900 rounded"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Sélectionné</span>
      </div>
      <div class="flex items-center space-x-2">
        <div class="w-4 h-4 bg-gray-100 dark:bg-gray-700 rounded"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Non disponible</span>
      </div>
    </div>

    <div v-if="selectedRange.start && selectedRange.end" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
      <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
        Période sélectionnée
      </p>
      <p class="text-sm text-blue-700 dark:text-blue-300">
        Du {{ formatDate(selectedRange.start) }} au {{ formatDate(selectedRange.end) }}
      </p>
      <p class="text-sm text-blue-700 dark:text-blue-300">
        {{ calculateDays() }} jour(s) - Total: {{ calculatePrice() }}€
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface Props {
  vehicleId: number;
  dailyRate: number;
  weeklyRate?: number;
  monthlyRate?: number;
  bookings?: Array<{
    start_date: string;
    end_date: string;
    status: string;
  }>;
  initialStartDate?: string;
  initialEndDate?: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:startDate', 'update:endDate', 'dateRangeSelected']);

interface CalendarDay {
  date: number;
  month: number;
  year: number;
  isCurrentMonth: boolean;
  isAvailable: boolean;
  hasBooking: boolean;
  isSelected: boolean;
  isInRange: boolean;
  isPast: boolean;
  price?: number;
}

const currentDate = ref(new Date());
const selectedRange = ref<{
  start: Date | null;
  end: Date | null;
}>({
  start: props.initialStartDate ? new Date(props.initialStartDate) : null,
  end: props.initialEndDate ? new Date(props.initialEndDate) : null
});

const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

const currentMonthYear = computed(() => {
  const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long' };
  return currentDate.value.toLocaleDateString('fr-FR', options);
});

const calendarDays = computed((): CalendarDay[] => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const startDate = new Date(firstDay);
  const dayOfWeek = firstDay.getDay();
  const startOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
  startDate.setDate(startDate.getDate() - startOffset);

  const days: CalendarDay[] = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);
    
    const isCurrentMonth = date.getMonth() === month;
    const isPast = date < today;
    const hasBooking = checkBooking(date);
    const isSelected = isDateSelected(date);
    const isInRange = isDateInRange(date);
    const isAvailable = isCurrentMonth && !isPast && !hasBooking;

    days.push({
      date: date.getDate(),
      month: date.getMonth(),
      year: date.getFullYear(),
      isCurrentMonth,
      isAvailable,
      hasBooking,
      isSelected,
      isInRange,
      isPast,
      price: isAvailable ? calculateDayPrice(date) : undefined
    });
  }

  return days;
});

function checkBooking(date: Date): boolean {
  if (!props.bookings) return false;
  
  return props.bookings.some(booking => {
    if (!['confirmed', 'active'].includes(booking.status)) return false;
    const start = new Date(booking.start_date);
    const end = new Date(booking.end_date);
    return date >= start && date <= end;
  });
}

function isDateSelected(date: Date): boolean {
  const dateString = formatDateString(date);
  const startString = selectedRange.value.start ? formatDateString(selectedRange.value.start) : null;
  const endString = selectedRange.value.end ? formatDateString(selectedRange.value.end) : null;
  return dateString === startString || dateString === endString;
}

function isDateInRange(date: Date): boolean {
  if (!selectedRange.value.start || !selectedRange.value.end) return false;
  return date > selectedRange.value.start && date < selectedRange.value.end;
}

function calculateDayPrice(date: Date): number {
  // Simple pricing logic - could be enhanced with seasonal pricing
  const dayOfWeek = date.getDay();
  const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
  return isWeekend ? props.dailyRate * 1.2 : props.dailyRate;
}

function getDayClasses(day: CalendarDay): string {
  const classes = ['cursor-pointer', 'rounded-lg', 'transition-colors'];
  
  if (!day.isCurrentMonth || day.isPast) {
    classes.push('bg-gray-50', 'dark:bg-gray-800', 'text-gray-400', 'cursor-not-allowed');
  } else if (day.hasBooking) {
    classes.push('bg-red-100', 'dark:bg-red-900/30', 'text-red-600', 'dark:text-red-400', 'cursor-not-allowed');
  } else if (day.isSelected) {
    classes.push('bg-blue-500', 'text-white');
  } else if (day.isInRange) {
    classes.push('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-600', 'dark:text-blue-400');
  } else if (day.isAvailable) {
    classes.push('bg-green-50', 'dark:bg-green-900/20', 'hover:bg-green-100', 'dark:hover:bg-green-900/40');
  } else {
    classes.push('bg-gray-100', 'dark:bg-gray-700', 'text-gray-500');
  }
  
  return classes.join(' ');
}

function selectDate(day: CalendarDay) {
  if (!day.isAvailable || day.hasBooking || day.isPast || !day.isCurrentMonth) return;
  
  const date = new Date(day.year, day.month, day.date);
  
  if (!selectedRange.value.start || (selectedRange.value.start && selectedRange.value.end)) {
    // Start new selection
    selectedRange.value = { start: date, end: null };
  } else if (!selectedRange.value.end) {
    // Complete selection
    if (date < selectedRange.value.start) {
      selectedRange.value = { start: date, end: selectedRange.value.start };
    } else {
      selectedRange.value.end = date;
    }
    
    // Check if range is valid (no bookings in between)
    if (hasBookingsInRange()) {
      alert('Il y a des réservations dans la période sélectionnée');
      selectedRange.value = { start: null, end: null };
      return;
    }
    
    // Emit the selected range
    emit('dateRangeSelected', {
      start: formatDateString(selectedRange.value.start!),
      end: formatDateString(selectedRange.value.end)
    });
    emit('update:startDate', formatDateString(selectedRange.value.start!));
    emit('update:endDate', formatDateString(selectedRange.value.end));
  }
}

function hasBookingsInRange(): boolean {
  if (!selectedRange.value.start || !selectedRange.value.end || !props.bookings) return false;
  
  return props.bookings.some(booking => {
    if (!['confirmed', 'active'].includes(booking.status)) return false;
    const bookingStart = new Date(booking.start_date);
    const bookingEnd = new Date(booking.end_date);
    const rangeStart = selectedRange.value.start!;
    const rangeEnd = selectedRange.value.end!;
    
    return (
      (bookingStart >= rangeStart && bookingStart <= rangeEnd) ||
      (bookingEnd >= rangeStart && bookingEnd <= rangeEnd) ||
      (bookingStart <= rangeStart && bookingEnd >= rangeEnd)
    );
  });
}

function calculateDays(): number {
  if (!selectedRange.value.start || !selectedRange.value.end) return 0;
  const diff = selectedRange.value.end.getTime() - selectedRange.value.start.getTime();
  return Math.ceil(diff / (1000 * 3600 * 24)) + 1;
}

function calculatePrice(): number {
  const days = calculateDays();
  if (days === 0) return 0;
  
  if (props.monthlyRate && days >= 30) {
    return Math.floor(days / 30) * props.monthlyRate + (days % 30) * props.dailyRate;
  } else if (props.weeklyRate && days >= 7) {
    return Math.floor(days / 7) * props.weeklyRate + (days % 7) * props.dailyRate;
  } else {
    return days * props.dailyRate;
  }
}

function previousMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1);
}

function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1);
}

function formatDateString(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDate(date: Date): string {
  return date.toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  });
}

onMounted(() => {
  if (props.initialStartDate) {
    const startDate = new Date(props.initialStartDate);
    currentDate.value = new Date(startDate.getFullYear(), startDate.getMonth());
  }
});
</script>