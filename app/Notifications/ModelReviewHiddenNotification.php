<?php

namespace App\Notifications;

use App\Models\ProductReview;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a buyer a reviewer hid their review, and why. */
class ModelReviewHiddenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductReview $review) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your review was hidden')
            ->subject('Your review of "'.$this->review->product->title.'" was hidden')
            ->greeting("Hello {$notifiable->name},")
            ->line('A reviewer hid your review of "'.$this->review->product->title.'" because it broke the review rules.')
            ->line('Reason: '.$this->review->hidden_reason)
            ->line('You can edit your review so that it follows the rules, and write to support if you think this is a mistake.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'review_id' => $this->review->id,
            'title' => $this->review->product->title,
            'reason' => $this->review->hidden_reason,
            'url' => route('models.show', $this->review->product).'#reviews',
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Your review was hidden',
            'icon' => 'danger',
            'content' => '"'.($data['title'] ?? 'A model').'": '.($data['reason'] ?? 'it broke the review rules.'),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View',
        ];
    }
}
