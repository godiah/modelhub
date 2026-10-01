<?php

namespace App\Notifications;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a member the outcome of their seller application (approved, not approved) or that they were suspended. */
class SellerReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SellerProfile $seller) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mail = BrandedMail::message(match ($this->seller->status) {
            SellerStatus::Approved => 'You are approved to sell',
            SellerStatus::Suspended => 'Your seller account was suspended',
            default => 'About your seller application',
        })->greeting("Hello {$notifiable->name},");

        match ($this->seller->status) {
            SellerStatus::Approved => $mail->subject('You are approved to sell on '.config('app.name'))
                ->line("Your application to sell 3D models as \"{$this->seller->display_name}\" was approved.")
                ->line('You will be able to add your first model as soon as the marketplace opens. We will let you know.')
                ->action('Open your seller page', route('seller.index')),
            SellerStatus::Suspended => $mail->subject('Your seller account was suspended')
                ->line("Your seller account \"{$this->seller->display_name}\" was suspended.")
                ->line('Reason: '.$this->seller->review_notes)
                ->line('If you think this is a mistake, reply to this email and we will look again.'),
            default => $mail->subject('About your seller application')
                ->line("We could not approve your application to sell as \"{$this->seller->display_name}\" this time.")
                ->line('Reason: '.$this->seller->review_notes)
                ->line('You can improve your application and send it again.')
                ->action('Update your application', route('seller.index')),
        };

        return $mail;
    }

    public function toDatabase($notifiable)
    {
        return [
            'seller_id' => $this->seller->id,
            'status' => $this->seller->status->value,
            'display_name' => $this->seller->display_name,
            'notes' => $this->seller->review_notes,
            'url' => route('seller.index'),
        ];
    }

    public static function present(array $data): array
    {
        $status = $data['status'] ?? 'rejected';

        return [
            'title' => match ($status) {
                'approved' => 'You are approved to sell',
                'suspended' => 'Seller account suspended',
                default => 'Seller application not approved',
            },
            'icon' => $status === 'approved' ? 'success' : 'danger',
            'content' => match ($status) {
                'approved' => 'Your seller application as '.($data['display_name'] ?? 'your store').' was approved.',
                'suspended' => 'Your seller account '.($data['display_name'] ?? '').' was suspended: '.($data['notes'] ?? ''),
                default => 'Your application was not approved: '.($data['notes'] ?? 'see your seller page for details.'),
            },
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open seller page',
        ];
    }
}
