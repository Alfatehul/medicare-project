<?php

namespace App\Providers;

use App\Services\CurrencyService;
use App\Services\MidtransService;
use App\Services\NutritionService;
use App\Services\WeatherService;
use App\Services\WhatsappService;
use Illuminate\Support\ServiceProvider;

class ExternalApiServiceProvider extends ServiceProvider
{
    /**
     * Register external API services into the container.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsappService::class, function () {
            return new WhatsappService(
                apiKey: config('services.fonnte.api_key', ''),
            );
        });

        $this->app->singleton(WeatherService::class, function () {
            return new WeatherService(
                apiKey: config('services.openweather.api_key', ''),
            );
        });

        $this->app->singleton(MidtransService::class, function () {
            return new MidtransService(
                serverKey: config('services.midtrans.server_key', ''),
            );
        });

        $this->app->singleton(CurrencyService::class, function () {
            return new CurrencyService(
                apiKey: config('services.abstract.exchange_key', ''),
            );
        });

        $this->app->singleton(NutritionService::class, function () {
            return new NutritionService(
                appId: config('services.edamam.app_id', ''),
                appKey: config('services.edamam.app_key', ''),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
