<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LocalizationService;
use App\Services\AIRecommendationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register LocalizationService as singleton
        $this->app->singleton(LocalizationService::class, function ($app) {
            return new LocalizationService();
        });
        
        // Register AIRecommendationService as singleton
        $this->app->singleton(AIRecommendationService::class, function ($app) {
            return new AIRecommendationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default locale from database or config
        $localizationService = $this->app->make(LocalizationService::class);
        $locale = $localizationService->getCurrentLocale();
        app()->setLocale($locale);
    }
}
