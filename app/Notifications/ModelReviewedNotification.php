<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductReview;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Tells a seller a buyer reviewed one of their models. */
class ModelReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductReview $review, public Product $product) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('New review on your model')
            ->subject("{$this->review->rating}-star review on \"{$this->product->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->review->author->publicName()} rated \"{$this->product->title}\" {$this->review->rating} out of 5:")
            ->line('"'.Str::limit($this->review->comment, 280).'"')
            ->action('Read and reply', route('models.show', $this->product).'#reviews');
    }

    public function toDatabase($notifiable)
    {
        return [
            'review_id' => $this->review->id,
            'product_id' => $this->product->id,
            'title' => $this->product->title,
            'rating' => $this->review->rating,
            'reviewer' => $this->review->author->publicName(),
            'url' => route('models.show', $this->product).'#reviews',
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'New '.($data['rating'] ?? '').'-star review',
            'icon' => 'info',
            'content' => ($data['reviewer'] ?? 'A buyer').' reviewed "'.($data['title'] ?? 'your model').'"',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Read and reply',
        ];
    }
}
