<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        // Ensure all Eloquent date serialization uses ISO-8601 with timezone.
        Carbon::serializeUsing(static function (Carbon $carbon) {
            return $carbon->toIso8601String();
        });
    }
}
