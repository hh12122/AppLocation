<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LocalizationService;
use Illuminate\Support\Facades\App;

class Localization
{
    protected LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if locale is in URL
        if ($request->has('locale')) {
            $locale = $request->get('locale');
            $this->localizationService->setLocale($locale);
        } else {
            // Get current locale from service
            $locale = $this->localizationService->getCurrentLocale();
            App::setLocale($locale);
        }

        // Set locale for Carbon dates
        \Carbon\Carbon::setLocale($locale);

        // Add locale data to shared Inertia props
        if (class_exists('\Inertia\Inertia')) {
            \Inertia\Inertia::share([
                'locale' => [
                    'current' => $locale,
                    'available' => $this->localizationService->getActiveLanguages(),
                    'is_rtl' => $this->localizationService->isRtl($locale),
                    'info' => $this->localizationService->getLocaleInfo($locale),
                ],
                'translations' => function () use ($locale) {
                    // Load translations for current page
                    $route = request()->route();
                    $group = $route ? str_replace('.', '_', $route->getName()) : 'common';
                    
                    return array_merge(
                        $this->localizationService->getTranslations($locale, 'common'),
                        $this->localizationService->getTranslations($locale, $group)
                    );
                },
            ]);
        }

        return $next($request);
    }
}