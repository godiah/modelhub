<?php

namespace App\Providers;

use App\Helpers\EmailCssInlinerHelper;
use App\Models\JobApplication;
use App\Observers\JobApplicationObserver;
use App\Support\Mail\BrandedMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Pagination\Paginator;
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

        // One pagination design everywhere: ->links() renders <x-pager> (resources/views/pagination/app.blade.php)
        Paginator::defaultView('pagination.app');
        Paginator::defaultSimpleView('pagination.app');

        // Fail loudly on N+1s in local/testing instead of shipping them silently.
        Model::preventLazyLoading(! app()->isProduction());

        // Every email renders through emails.layouts.master (see its header comment). Laravel's own account
        // emails would otherwise use its default markdown theme, so they are rebuilt on the same template.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return BrandedMail::message('Reset your password')
                ->subject('Reset your '.config('app.name').' password')
                ->greeting("Hello {$notifiable->name},")
                ->line('We received a request to reset the password for your account.')
                ->action('Reset password', $url)
                ->line("This link expires in {$minutes} minutes.")
                ->line('If you did not ask for this, you can ignore this email and your password stays the same.');
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return BrandedMail::message('Verify your email address')
                ->subject('Verify your email address')
                ->greeting("Hello {$notifiable->name},")
                ->line('Confirm your email address to finish setting up your '.config('app.name').' account.')
                ->action('Verify email address', $url)
                ->line('If you did not create an account, you can ignore this email.');
        });

        // Inline each email's <style> block into style="" attributes for clients that strip <head><style> (Laravel
        // only does this on the markdown path, so this closes the gap for every outgoing email).
        Event::listen(function (MessageSending $event) {
            $event->message->html(
                EmailCssInlinerHelper::inline($event->message->getHtmlBody())
            );
        });
    }
}
