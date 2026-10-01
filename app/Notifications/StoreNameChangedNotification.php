<?php

namespace App\Notifications;

use App\Models\SellerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells reviewers a seller renamed their store, so a misleading or copied name can be dealt with. In-app only. */
class StoreNameChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SellerProfile $store, public string $oldName) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'seller_id' => $this->store->id,
            'old_name' => $this->oldName,
            'new_name' => $this->store->display_name,
            'url' => route('admin.sellers.index', ['status' => 'approved']),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Store renamed',
            'icon' => 'info',
            'content' => '"'.($data['old_name'] ?? 'A store').'" is now "'.($data['new_name'] ?? 'a new name').'"',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View sellers',
        ];
    }
}
