<?php

namespace App\Notifications;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a seller the outcome of a listing review: published, needs changes, or taken down. */
class ProductReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mail = BrandedMail::message(match ($this->product->status) {
            ProductStatus::Published => 'Your model is live',
            ProductStatus::Unpublished => 'Your model was taken down',
            default => 'Your model needs changes',
        })->greeting("Hello {$notifiable->name},");

        match ($this->product->status) {
            ProductStatus::Published => $mail->subject("\"{$this->product->title}\" is published")
                ->line("Your model \"{$this->product->title}\" passed review and is now visible to buyers.")
                ->action('View your model', route('models.show', $this->product)),
            ProductStatus::Unpublished => $mail->subject("\"{$this->product->title}\" was taken down")
                ->line("Your model \"{$this->product->title}\" was taken down by a reviewer.")
                ->line('Reason: '.$this->product->review_notes)
                ->action('Open your models', route('seller.models.index')),
            default => $mail->subject("\"{$this->product->title}\" needs changes")
                ->line("A reviewer asked for changes to \"{$this->product->title}\" before it can be published.")
                ->line('Reason: '.$this->product->review_notes)
                ->action('Update your model', route('seller.models.edit', $this->product)),
        };

        return $mail;
    }

    public function toDatabase($notifiable)
    {
        return [
            'product_id' => $this->product->id,
            'title' => $this->product->title,
            'status' => $this->product->status->value,
            'notes' => $this->product->review_notes,
            'url' => $this->product->status === ProductStatus::Published ? route('models.show', $this->product) : route('seller.models.edit', $this->product),
        ];
    }

    public static function present(array $data): array
    {
        $status = $data['status'] ?? 'rejected';

        return [
            'title' => match ($status) {
                'published' => 'Your model is live',
                'unpublished' => 'Your model was taken down',
                default => 'Your model needs changes',
            },
            'icon' => $status === 'published' ? 'success' : 'danger',
            'content' => '"'.($data['title'] ?? 'Your model').'"'.match ($status) {
                'published' => ' passed review and is visible to buyers.',
                default => ': '.($data['notes'] ?? 'see the listing for details.'),
            },
            'action_url' => $data['url'] ?? null,
            'action_label' => $status === 'published' ? 'View model' : 'Update model',
        ];
    }
}
