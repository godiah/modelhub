<?php

namespace App\Services\Admin;

use App\Models\ModelJob;
use Illuminate\Database\Eloquent\Builder;

/** The staff view of every project on the board, whatever its state. */
class ProjectDirectoryService
{
    public const STATUSES = ['open' => 'Open', 'closed' => 'Closed', 'expired' => 'Expired', 'archived' => 'Archived', 'taken_down' => 'Taken down', 'all' => 'All'];

    public function query(string $status, string $term = ''): Builder
    {
        return ModelJob::query()
            ->with('user:id,name,avatar')
            ->withCount('applications')
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->tap(fn ($query) => $this->narrow($query, $status))
            ->latest();
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return collect(array_keys(self::STATUSES))->mapWithKeys(fn ($status) => [$status => $this->narrow(ModelJob::query(), $status)->count()])->all();
    }

    /** The state of a project as a label and badge tone. Taken down wins, then archived, then open. */
    public static function state(ModelJob $job): array
    {
        return match (true) {
            $job->isTakenDown() => ['Taken down', 'red'],
            $job->is_archived => ['Archived', 'neutral'],
            $job->isOpenForApplications() => ['Open', 'green'],
            $job->is_active => ['Expired', 'amber'],
            default => ['Closed', 'neutral'],
        };
    }

    private function narrow(Builder $query, string $status): Builder
    {
        match ($status) {
            'open' => $query->whereNull('taken_down_at')->where('is_archived', false)->where('is_active', true)
                ->where(fn ($q) => $q->where('no_deadline', true)->orWhereNull('deadline')->orWhere('deadline', '>=', today())),
            'expired' => $query->whereNull('taken_down_at')->where('is_archived', false)->where('is_active', true)
                ->where('no_deadline', false)->whereNotNull('deadline')->where('deadline', '<', today()),
            'closed' => $query->whereNull('taken_down_at')->where('is_archived', false)->where('is_active', false),
            'archived' => $query->whereNull('taken_down_at')->where('is_archived', true),
            'taken_down' => $query->whereNotNull('taken_down_at'),
            default => null,
        };

        return $query;
    }
}
