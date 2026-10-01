<?php

namespace App\Services\Admin;

use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\User;
use App\Support\Staff\Masking;

/**
 * The staff portal's global search (the Ctrl+K palette). One query across members, projects, hires, models, stores, disputes
 * and staff, where each group is only searched for someone who holds the permission to view that area. Contact details
 * follow the same rule as the member list: masked for everyone, and email addresses are only matched for people who may see them.
 */
class StaffSearchService
{
    public const PER_GROUP = 5;

    public const MIN_LENGTH = 2;

    /** @return list<array{label: string, icon: string, items: list<array{title: string, subtitle: string, url: string}>}> */
    public function search(Staff $staff, string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return [];
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $groups = [];

        if ($staff->can('view members')) {
            $canSeeContact = $staff->can('view contact details');
            $groups[] = $this->group('Members', 'user-group', User::where(fn ($q) => $q->where('name', 'like', $like)->when($canSeeContact, fn ($e) => $e->orWhere('email', 'like', $like)))
                ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'email']),
                fn (User $member) => [$member->name, Masking::email($member->email, $canSeeContact), route('admin.members.show', $member)]);
        }

        if ($staff->can('view projects')) {
            $groups[] = $this->group('Projects', 'briefcase', ModelJob::with('user:id,name')->where(fn ($q) => $q->where('title', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)))
                ->latest()->limit(self::PER_GROUP)->get(),
                fn (ModelJob $job) => [$job->title, $job->user?->name ?? __('Deleted account'), route('admin.projects.show', $job)]);
        }

        if ($staff->can('view engagements')) {
            $groups[] = $this->group('Hires', 'chat-bubble-left-right', JobEngagement::with(['application.job:id,title', 'application.poster:id,name', 'application.applicant:id,name'])
                ->whereHas('application.job', fn ($j) => $j->where('title', 'like', $like))->latest()->limit(self::PER_GROUP)->get(),
                fn (JobEngagement $hire) => [$hire->application->job?->title ?? __('A deleted project'), ($hire->application->poster?->name ?? '—').' → '.($hire->application->applicant?->name ?? '—'), route('admin.engagements.show', $hire)]);
        }

        if ($staff->can('view models')) {
            $groups[] = $this->group('Models', 'cube', Product::with('sellerProfile:id,user_id,display_name')->where(fn ($q) => $q->where('title', 'like', $like)->orWhereHas('sellerProfile', fn ($s) => $s->where('display_name', 'like', $like)))
                ->latest()->limit(self::PER_GROUP)->get(),
                fn (Product $model) => [$model->title, ($model->sellerProfile?->display_name ?? '—').' · '.$model->status->label(), route('admin.catalogue.show', $model)]);
        }

        if ($staff->can('view sellers')) {
            $groups[] = $this->group('Stores', 'tag', SellerProfile::with('user:id,name')->where(fn ($q) => $q->where('display_name', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)))
                ->latest()->limit(self::PER_GROUP)->get(),
                fn (SellerProfile $store) => [$store->display_name, ($store->user?->name ?? '—').' · '.ucfirst($store->status->value), route('admin.stores.show', $store)]);
        }

        if ($staff->can('view disputes')) {
            $groups[] = $this->group('Disputes', 'scale', JobPaymentDispute::with('cancellation.engagement.application.job:id,title')
                ->whereHas('cancellation.engagement.application.job', fn ($j) => $j->where('title', 'like', $like))->latest()->limit(self::PER_GROUP)->get(),
                fn (JobPaymentDispute $dispute) => [$dispute->cancellation->engagement->application->job->title, $dispute->status->label(), route('admin.disputes.show', $dispute->cancellation_id)]);
        }

        if ($staff->can('manage staff')) {
            $groups[] = $this->group('Staff', 'users', Staff::where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'email']),
                fn (Staff $person) => [$person->name, $person->email, route('admin.staff.edit', $person)]);
        }

        return array_values(array_filter($groups, fn ($group) => $group['items'] !== []));
    }

    /** @param  callable(mixed): array{0: string, 1: string, 2: string}  $present  model => [title, subtitle, url] */
    private function group(string $label, string $icon, iterable $models, callable $present): array
    {
        $items = [];

        foreach ($models as $model) {
            [$title, $subtitle, $url] = $present($model);
            $items[] = ['title' => $title, 'subtitle' => $subtitle, 'url' => $url];
        }

        return ['label' => $label, 'icon' => $icon, 'items' => $items];
    }
}
