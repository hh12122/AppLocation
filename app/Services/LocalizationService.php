<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class LocalizationService
{
    private array $supportedLocales = ['fr', 'en', 'es', 'de', 'it', 'ar'];
    private string $defaultLocale = 'fr';
    
    /**
     * Get current locale
     */
    public function getCurrentLocale(): string
    {
        // Priority: Session > User preference > Browser > Default
        if (Session::has('locale')) {
            return Session::get('locale');
        }
        
        if (Auth::check() && Auth::user()->locale) {
            return Auth::user()->locale;
        }
        
        return $this->detectBrowserLocale() ?: $this->defaultLocale;
    }
    
    /**
     * Set locale for the current session
     */
    public function setLocale(string $locale): void
    {
        if (!in_array($locale, $this->supportedLocales)) {
            $locale = $this->defaultLocale;
        }
        
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        // Update user preference if logged in
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }
    }
    
    /**
     * Detect browser language
     */
    public function detectBrowserLocale(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        foreach ($languages as $lang) {
            $locale = substr($lang, 0, 2);
            if (in_array($locale, $this->supportedLocales)) {
                return $locale;
            }
        }
        
        return null;
    }
    
    /**
     * Get all active languages
     */
    public function getActiveLanguages(): array
    {
        return Cache::remember('active_languages', 3600, function () {
            return Language::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        });
    }
    
    /**
     * Get translations for a specific locale and group
     */
    public function getTranslations(string $locale, string $group = '*'): array
    {
        $cacheKey = "translations.{$locale}.{$group}";
        
        return Cache::remember($cacheKey, 3600, function () use ($locale, $group) {
            $query = Translation::where('locale', $locale);
            
            if ($group !== '*') {
                $query->where('group', $group);
            }
            
            return $query->pluck('value', 'key')->toArray();
        });
    }
    
    /**
     * Save or update a translation
     */
    public function saveTranslation(string $locale, string $group, string $key, string $value): void
    {
        Translation::updateOrCreate(
            [
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
        
        // Clear cache
        Cache::forget("translations.{$locale}.{$group}");
        Cache::forget("translations.{$locale}.*");
    }
    
    /**
     * Get translated content for models
     */
    public function translateModel($model, string $field, ?string $locale = null): string
    {
        $locale = $locale ?: $this->getCurrentLocale();
        
        // Check if model has translations
        if (method_exists($model, 'translations')) {
            $translation = $model->translations()
                ->where('locale', $locale)
                ->where('field', $field)
                ->first();
            
            if ($translation) {
                return $translation->value;
            }
        }
        
        // Fallback to default field value
        return $model->{$field} ?? '';
    }
    
    /**
     * Format date according to locale
     */
    public function formatDate($date, string $format = null): string
    {
        $locale = $this->getCurrentLocale();
        $userFormat = Auth::check() ? Auth::user()->date_format : null;
        
        $format = $format ?: $userFormat ?: $this->getDateFormat($locale);
        
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }
    
    /**
     * Format currency according to locale
     */
    public function formatCurrency(float $amount, ?string $currency = null): string
    {
        $locale = $this->getCurrentLocale();
        $currency = $currency ?: (Auth::check() ? Auth::user()->currency : 'EUR');
        
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currency);
    }
    
    /**
     * Format number according to locale
     */
    public function formatNumber(float $number, int $decimals = 0): string
    {
        $locale = $this->getCurrentLocale();
        
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
        
        return $formatter->format($number);
    }
    
    /**
     * Get date format for locale
     */
    private function getDateFormat(string $locale): string
    {
        $formats = [
            'fr' => 'd/m/Y',
            'en' => 'm/d/Y',
            'es' => 'd/m/Y',
            'de' => 'd.m.Y',
            'it' => 'd/m/Y',
            'ar' => 'Y/m/d',
        ];
        
        return $formats[$locale] ?? 'd/m/Y';
    }
    
    /**
     * Get RTL languages
     */
    public function getRtlLanguages(): array
    {
        return Cache::remember('rtl_languages', 86400, function () {
            return Language::where('is_rtl', true)
                ->pluck('code')
                ->toArray();
        });
    }
    
    /**
     * Check if current locale is RTL
     */
    public function isRtl(?string $locale = null): bool
    {
        $locale = $locale ?: $this->getCurrentLocale();
        return in_array($locale, $this->getRtlLanguages());
    }
    
    /**
     * Get locale info
     */
    public function getLocaleInfo(string $locale): ?array
    {
        return Cache::remember("locale_info.{$locale}", 86400, function () use ($locale) {
            $language = Language::where('code', $locale)->first();
            
            if (!$language) {
                return null;
            }
            
            return [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'flag' => $language->flag,
                'is_rtl' => $language->is_rtl,
                'date_format' => $this->getDateFormat($locale),
                'currency_symbol' => $this->getCurrencySymbol($locale),
            ];
        });
    }
    
    /**
     * Get currency symbol for locale
     */
    private function getCurrencySymbol(string $locale): string
    {
        $symbols = [
            'fr' => '€',
            'en' => '£',
            'es' => '€',
            'de' => '€',
            'it' => '€',
            'ar' => 'ر.س',
        ];
        
        return $symbols[$locale] ?? '€';
    }
    
    /**
     * Clear all translation caches
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}