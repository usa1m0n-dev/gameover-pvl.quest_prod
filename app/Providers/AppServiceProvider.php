<?php

namespace App\Providers;

use App\Models\InventoryLog;
use App\Models\PurchaseLog;
use App\Observers\InventoryLogObserver;
use App\Observers\PurchaseLogObserver;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InventoryLog::observe(InventoryLogObserver::class);
        PurchaseLog::observe(PurchaseLogObserver::class);
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render(<<<'HTML'
                <script src="https://unpkg.com/libphonenumber-js@1.10.44/bundle/libphonenumber-js.min.js"></script>
                
                <script>
                    if (typeof libphonenumber !== 'undefined') {
                        window.AsYouType = libphonenumber.AsYouType;
                    }
                </script>
            HTML)
        );
    }
}
