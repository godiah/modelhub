<?php

namespace App\Providers;

use App\Models\JobApplication;
use App\Observers\JobApplicationObserver;
use Illuminate\Http\Resources\Json\JsonResource;
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
        JobApplication::observe(JobApplicationObserver::class);

        // No API Resource in this app wraps intentionally in a "data" envelope -
        // keep JSON responses flat to match what existing frontend JS (e.g.
        // resources/js/templates.js) already expects.
        JsonResource::withoutWrapping();
    }
}
