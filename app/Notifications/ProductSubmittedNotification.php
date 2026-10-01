<?php

namespace App\Notifications;

use App\Models\Product;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells reviewers a seller sent a model listing for review. */
class ProductSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('A model is waiting for review')
            ->subject("Model for review: {$this->product->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->product->seller->name} submitted \"{$this->product->title}\" for review.")
            ->action('Open the review queue', route('admin.models.index', ['status' => 'in_review']));
    }

    public function toDatabase($notifiable)
    {
        return [
            'product_id' => $this->product->id,
            'title' => $this->product->title,
            'seller_name' => $this->product->seller->name,
            'url' => route('admin.models.index', ['status' => 'in_review']),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Model submitted for review',
            'icon' => 'info',
            'content' => ($data['seller_name'] ?? 'A seller').' submitted "'.($data['title'] ?? 'a model').'"',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Review',
        ];
    }
}
