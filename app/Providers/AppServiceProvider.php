<?php

namespace App\Providers;

use App\Helpers\EmailCssInlinerHelper;
use App\Models\JobApplication;
use App\Observers\JobApplicationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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

        // Fail loudly on N+1s in local/testing instead of shipping them silently.
        Model::preventLazyLoading(! app()->isProduction());

        // Every transactional email in this app extends emails.layouts.master, which embeds
        // a <style> block. Mail classes using ->markdown() already get that block inlined by
        // Laravel; Notification classes using MailMessage->view() don't (Laravel only inlines
        // CSS on the markdown-render path) and depend entirely on the recipient's client
        // keeping <head><style> content. Inlining here, for every outgoing email, closes that
        // gap app-wide instead of per-class.
        Event::listen(function (MessageSending $event) {
            $event->message->html(
                EmailCssInlinerHelper::inline($event->message->getHtmlBody())
            );
        });
    }
}
