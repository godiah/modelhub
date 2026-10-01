<?php

namespace App\Support\Staff;

use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\User;

/**
 * Every bulk action staff can run on a list, and who may run it. Each is the one-at-a-time action done to several items with one
 * click, so it follows the same rules: the same permission, the same checks per item, the same audit entry and notification.
 * An action that tells the person affected why (reject, hide, suspend, take down) takes one reason for the whole batch.
 */
final class BulkActions
{
    /** The most items one batch may hold, so a click can never touch an unbounded number of accounts. */
    public const MAX = 50;

    /**
     * @return array<string, array{label: string, noun: string, past: string, permission: ?string, reason: ?string, tone: string, model: ?class-string, with: list<string>}>
     *                                                                                                                                                                       reason: null (none) or the label of the reason field when one is required.
     */
    public static function all(): array
    {
        return [
            'model.publish' => ['label' => 'Publish', 'noun' => 'model|models', 'past' => 'published', 'permission' => 'review models', 'reason' => null, 'tone' => 'primary', 'model' => Product::class, 'with' => ['seller']],
            'model.reject' => ['label' => 'Ask for changes', 'noun' => 'model|models', 'past' => 'sent back', 'permission' => 'review models', 'reason' => 'What should the sellers change?', 'tone' => 'danger', 'model' => Product::class, 'with' => ['seller']],
            'seller.approve' => ['label' => 'Approve', 'noun' => 'application|applications', 'past' => 'approved', 'permission' => 'review sellers', 'reason' => null, 'tone' => 'primary', 'model' => SellerProfile::class, 'with' => ['user']],
            'seller.reject' => ['label' => 'Reject', 'noun' => 'application|applications', 'past' => 'rejected', 'permission' => 'review sellers', 'reason' => 'Why are these applications rejected?', 'tone' => 'danger', 'model' => SellerProfile::class, 'with' => ['user']],
            'review.dismiss' => ['label' => 'Dismiss reports', 'noun' => 'review|reviews', 'past' => 'cleared of reports', 'permission' => 'moderate reviews', 'reason' => null, 'tone' => 'primary', 'model' => ProductReview::class, 'with' => ['product', 'author']],
            'review.hide' => ['label' => 'Hide reviews', 'noun' => 'review|reviews', 'past' => 'hidden', 'permission' => 'moderate reviews', 'reason' => 'Why are these reviews hidden? The authors are shown this.', 'tone' => 'danger', 'model' => ProductReview::class, 'with' => ['product', 'author']],
            'dispute.assign' => ['label' => 'Assign to me', 'noun' => 'dispute|disputes', 'past' => 'taken on', 'permission' => 'resolve disputes', 'reason' => null, 'tone' => 'primary', 'model' => JobPaymentDispute::class, 'with' => ['cancellation.engagement.application.job:id,title', 'assignedAdmin:id,name']],
            'member.suspend' => ['label' => 'Suspend', 'noun' => 'member|members', 'past' => 'suspended', 'permission' => 'manage members', 'reason' => 'Why are these accounts suspended? They are emailed this.', 'tone' => 'danger', 'model' => User::class, 'with' => []],
            'member.reinstate' => ['label' => 'Reinstate', 'noun' => 'member|members', 'past' => 'reinstated', 'permission' => 'manage members', 'reason' => null, 'tone' => 'primary', 'model' => User::class, 'with' => []],
            'project.takedown' => ['label' => 'Take down', 'noun' => 'project|projects', 'past' => 'taken down', 'permission' => 'moderate projects', 'reason' => 'Why are these projects taken down? The posters are shown this.', 'tone' => 'danger', 'model' => ModelJob::class, 'with' => ['user']],
            'project.restore' => ['label' => 'Restore', 'noun' => 'project|projects', 'past' => 'restored', 'permission' => 'moderate projects', 'reason' => null, 'tone' => 'primary', 'model' => ModelJob::class, 'with' => ['user']],
            'staff.deactivate' => ['label' => 'Deactivate', 'noun' => 'account|accounts', 'past' => 'deactivated', 'permission' => 'manage staff', 'reason' => null, 'tone' => 'danger', 'model' => Staff::class, 'with' => []],
            'staff.reactivate' => ['label' => 'Reactivate', 'noun' => 'account|accounts', 'past' => 'reactivated', 'permission' => 'manage staff', 'reason' => null, 'tone' => 'primary', 'model' => Staff::class, 'with' => []],
            'notification.read' => ['label' => 'Mark as read', 'noun' => 'notification|notifications', 'past' => 'marked as read', 'permission' => null, 'reason' => null, 'tone' => 'primary', 'model' => null, 'with' => []],
        ];
    }

    /** The lists that offer bulk actions, and which actions each offers (in the order shown). */
    public const PAGES = [
        'members' => ['member.suspend', 'member.reinstate'],
        'projects' => ['project.takedown', 'project.restore'],
        'staff' => ['staff.deactivate', 'staff.reactivate'],
        'models' => ['model.publish', 'model.reject'],
        'sellers' => ['seller.approve', 'seller.reject'],
        'reviews' => ['review.dismiss', 'review.hide'],
        'disputes' => ['dispute.assign'],
        'notifications' => ['notification.read'],
    ];

    /** "5 models", "1 model": a count with the right form of the noun ('model|models'). */
    public static function count(string $noun, int $count): string
    {
        [$one, $many] = explode('|', $noun);

        return number_format($count).' '.($count === 1 ? $one : $many);
    }

    public static function get(string $action): ?array
    {
        return self::all()[$action] ?? null;
    }

    public static function allows(Staff $staff, string $action): bool
    {
        $definition = self::get($action);

        return $definition !== null && ($definition['permission'] === null || $staff->can($definition['permission']));
    }

    /**
     * What a list offers this person: its actions, minus any they may not run.
     *
     * @return list<array<string, mixed>>
     */
    public static function forPage(string $page, Staff $staff): array
    {
        return collect(self::PAGES[$page] ?? [])
            ->filter(fn (string $action) => self::allows($staff, $action))
            ->map(fn (string $action) => ['key' => $action] + self::all()[$action])
            ->values()->all();
    }
}
