<?php

/**
 * NotificationPresenterHelper
 *
 * Resolves a stored DatabaseNotification into display data (title, icon, content,
 * action link) by delegating to that notification class's own static present() method.
 * Keeps per-type rendering next to the Notification class it describes, instead of
 * branching on the type string in the controller and every view partial.
 */

namespace App\Helpers;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationPresenterHelper
{
    public static function present(DatabaseNotification $notification): array
    {
        $class = $notification->type;

        if (class_exists($class) && method_exists($class, 'present')) {
            return array_merge(self::defaults(), $class::present($notification->data));
        }

        return array_merge(self::defaults(), [
            'title' => Str::headline(class_basename($class)),
            'content' => $notification->data['message'] ?? 'You have a new notification',
        ]);
    }

    protected static function defaults(): array
    {
        return [
            'icon' => 'info',
            'action_url' => null,
            'action_label' => 'View details',
        ];
    }
}
