<?php

namespace App\Notifications;

use App\Models\ProductReview;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Tells a buyer the seller replied to their review. */
class ModelReviewReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductReview $review) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $store = $this->review->product->sellerProfile?->display_name ?? 'The seller';

        return BrandedMail::message('The seller replied to your review')
            ->subject("{$store} replied to your review")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$store} replied to your review of \"{$this->review->product->title}\":")
            ->line('"'.Str::limit($this->review->seller_reply, 280).'"')
            ->action('See the reply', route('models.show', $this->review->product).'#reviews');
    }

    public function toDatabase($notifiable)
    {
        return [
            'review_id' => $this->review->id,
            'title' => $this->review->product->title,
            'store' => $this->review->product->sellerProfile?->display_name,
            'url' => route('models.show', $this->review->product).'#reviews',
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'The seller replied to your review',
            'icon' => 'message',
            'content' => ($data['store'] ?? 'The seller').' replied about "'.($data['title'] ?? 'a model').'"',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See the reply',
        ];
    }
}
