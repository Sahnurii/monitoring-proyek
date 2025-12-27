<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Services\Inventory\StockMovementService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register bindings for the application.
     */
    public array $singletons = [
        StockMovementService::class => StockMovementService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
