<?php

namespace App\Notifications;

use App\Models\SellerProfile;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells reviewers that someone applied to sell 3D models. */
class SellerApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SellerProfile $seller) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('New seller application')
            ->subject("New seller application: {$this->seller->display_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->seller->user->name} applied to sell 3D models as \"{$this->seller->display_name}\".")
            ->action('Review the application', route('admin.sellers.index', ['status' => 'pending']));
    }

    public function toDatabase($notifiable)
    {
        return [
            'seller_id' => $this->seller->id,
            'display_name' => $this->seller->display_name,
            'applicant_name' => $this->seller->user->name,
            'url' => route('admin.sellers.index', ['status' => 'pending']),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'New seller application',
            'icon' => 'info',
            'content' => ($data['applicant_name'] ?? 'A member').' applied to sell models as '.($data['display_name'] ?? 'a new seller'),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Review application',
        ];
    }
}
