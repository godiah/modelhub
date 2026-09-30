@use('App\Enums\ApplicationStatus')
@php
    $applicant = $application->applicant;
    $status = $application->status;
    $statusMap = [
        ApplicationStatus::Submitted->value => ['amber', __('New')],
        ApplicationStatus::Reviewed->value => ['blue', __('Reviewed')],
        ApplicationStatus::Hired->value => ['green', __('Hired')],
        ApplicationStatus::Rejected->value => ['red', __('Rejected')],
        ApplicationStatus::Withdrawn->value => ['neutral', __('Withdrawn')],
    ];
    [$statusTone, $statusLabel] = $statusMap[$status->value] ?? ['neutral', $status->label()];

    $budget = (float) $job->budget;
    $offer = (float) $application->offer_amount;
    $diff = $budget > 0 && $offer > 0 ? (int) round(($offer - $budget) / $budget * 100) : null;

    $hireBlocker = $application->hireBlocker();
    $canHire = $hireBlocker === null && in_array($status, [ApplicationStatus::Submitted, ApplicationStatus::Reviewed, ApplicationStatus::Rejected], true);
    $canReject = ! $application->isStatusLocked() && $status !== ApplicationStatus::Rejected;
    $engagement = $application->engagement;

    $files = array_values(array_filter((array) $application->portfolio));
    $isImage = fn (string $f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $images = array_values(array_filter($files, $isImage));
    $documents = array_values(array_filter($files, fn ($f) => ! $isImage($f)));

    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $openHire = $canHire && (request()->boolean('hire') || $errors->has('deliverables.*'));

    $events = collect([
        ['at' => $application->created_at, 'label' => __('Application submitted'), 'dot' => 'bg-amber-500'],
    ]);
    if (in_array($status, [ApplicationStatus::Reviewed, ApplicationStatus::Rejected, ApplicationStatus::Hired], true)) {
        $events->push(['at' => $application->updated_at, 'label' => match ($status) {
            ApplicationStatus::Rejected => __('Application rejected'),
            ApplicationStatus::Hired => __('Applicant hired'),
            default => __('Marked as reviewed'),
        }, 'dot' => match ($status) { ApplicationStatus::Rejected => 'bg-red-500', ApplicationStatus::Hired => 'bg-green-500', default => 'bg-blue-500' }]);
    }
    if ($engagement?->started_at) {
        $events->push(['at' => $engagement->started_at, 'label' => __('Offer accepted, work started'), 'dot' => 'bg-teal-500']);
    }
    if ($status === ApplicationStatus::Withdrawn) {
        $events->push(['at' => $application->updated_at, 'label' => __('Applicant withdrew'), 'dot' => 'bg-neutral-400']);
    }
    $events = $events->sortBy('at')->values();
@endphp
<x-app-layout crumb="Application details">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <x-user-avatar :user="$applicant" size="h-16 w-16" class="!text-lg" />
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $applicant->name }}</h1>
                            <x-badge :tone="$statusTone" class="px-2.5 py-0.5 text-xs font-medium">{{ $statusLabel }}</x-badge>
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-tertiary">
                            <span class="truncate">{{ $applicant->email }}</span>
                            <span>{{ __('Applied :date', ['date' => $application->created_at->format('M j, Y')]) }}</span>
                            @if ($totalReviews > 0)
                                <span class="inline-flex items-center gap-1 text-neutral-700"><x-icon name="star-solid" class="h-4 w-4 text-amber-400" />{{ number_format($averageRating, 1) }} <span class="text-tertiary">({{ $totalReviews }})</span></span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-btn variant="secondary" size="sm" href="{{ route('my-jobs.applications.index', $job->slug) }}">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('All applications') }}
                    </x-btn>
                    @if ($status === ApplicationStatus::Submitted && ! $application->isStatusLocked())
                        <form action="{{ route('my-jobs.applications.update-status', $application) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="reviewed">
                            <x-btn variant="secondary" size="sm" type="submit">
                                <x-icon name="check" class="h-4 w-4" />
                                {{ __('Mark reviewed') }}
                            </x-btn>
                        </form>
                    @endif
                    @if ($status === ApplicationStatus::Rejected)
                        <form action="{{ route('my-jobs.applications.update-status', $application) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="reviewed">
                            <x-btn variant="secondary" size="sm" type="submit">{{ __('Reconsider') }}</x-btn>
                        </form>
                    @endif
                    @if ($canReject)
                        <x-btn variant="danger-outline" size="sm" type="button" @click="$dispatch('open-modal', 'reject-application')">
                            <x-icon name="x-mark" class="h-4 w-4" />
                            {{ __('Reject') }}
                        </x-btn>
                    @endif
                    @if ($canHire)
                        <x-btn size="sm" type="button" @click="$dispatch('open-modal', 'hire-applicant')">
                            <x-icon name="user-group" class="h-4 w-4" />
                            {{ __('Hire') }}
                        </x-btn>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-2 divide-neutral-100 border-t border-neutral-100 sm:grid-cols-4 sm:divide-x">
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Offer') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900">
                        @if ($offer > 0)
                            <x-money :amount="$application->offer_amount" />
                        @else
                            <span class="text-base font-normal text-tertiary">{{ __('Not specified') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Compared with your budget') }}</dt>
                    <dd class="mt-1.5">
                        @if ($diff !== null)
                            <x-badge :tone="$diff < 0 ? 'green' : ($diff > 0 ? 'amber' : 'neutral')" class="px-2.5 py-0.5 text-xs font-medium">
                                {{ $diff === 0 ? __('On budget') : ($diff < 0 ? __(':percent% below', ['percent' => abs($diff)]) : __(':percent% above', ['percent' => $diff])) }}
                            </x-badge>
                            <span class="ml-1 text-xs text-tertiary">(<x-money :amount="$job->budget" :decimals="0" />)</span>
                        @else
                            <span class="text-sm text-tertiary">—</span>
                        @endif
                    </dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Freelancer receives') }}</dt>
                    <dd class="mt-1 text-base font-semibold tabular-nums text-neutral-900">@if ($application->net_amount)<x-money :amount="$application->net_amount" />@else — @endif</dd>
                    @if ($application->service_fee)
                        <p class="text-xs text-tertiary">{{ __('after a :amount service fee', ['amount' => \App\Support\Money::format($application->service_fee)]) }}</p>
                    @endif
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Most recognised for') }}</dt>
                    <dd class="mt-1.5 text-sm font-medium text-neutral-900">{{ $topSkill ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        @if ($status === ApplicationStatus::Withdrawn)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                <p>{{ __('This applicant withdrew their application, so its status can no longer change.') }}</p>
            </div>
        @elseif ($status === ApplicationStatus::Hired && $engagement)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900" role="status">
                <p>{{ __('You hired :name for this project.', ['name' => $applicant->name]) }}</p>
                <a href="{{ route('engagements.show', $engagement) }}" class="font-medium underline">{{ __('Open the engagement') }}</a>
            </div>
        @elseif ($hireBlocker && $status !== ApplicationStatus::Hired)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                <p>{{ $hireBlocker }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-6">
                <!-- Proposal -->
                <x-panel :title="__('Proposal')">
                    @if ($application->proposal)
                        <p class="whitespace-pre-line break-words text-sm leading-relaxed text-neutral-700">{{ $application->proposal }}</p>
                    @else
                        <p class="text-sm text-tertiary">{{ __('No proposal was written.') }}</p>
                    @endif
                </x-panel>

                <!-- Portfolio -->
                <x-panel :title="__('Portfolio')" x-data="{ src: null }" @keydown.escape.window="src = null">
                    @if ($files)
                        @if ($images)
                            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($images as $image)
                                    <li>
                                        <button type="button" @click="src = @js(asset('storage/'.$image))" class="group block w-full overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                            aria-label="{{ __('View :name full size', ['name' => basename($image)]) }}">
                                            <img src="{{ asset('storage/'.$image) }}" alt="{{ basename($image) }}" loading="lazy" class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($documents)
                            <ul class="@if ($images) mt-4 @endif space-y-2">
                                @foreach ($documents as $document)
                                    <li>
                                        <a href="{{ asset('storage/'.$document) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm text-neutral-700 transition-colors hover:border-neutral-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                            <x-icon name="document" class="h-5 w-5 shrink-0 text-neutral-400" />
                                            <span class="min-w-0 flex-1 truncate">{{ basename($document) }}</span>
                                            <x-icon name="cloud-arrow-down" class="h-4 w-4 shrink-0 text-neutral-400" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div x-show="src" x-cloak x-transition.opacity @click.self="src = null" role="dialog" aria-modal="true" aria-label="{{ __('Portfolio image') }}"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/80 p-4 backdrop-blur-sm">
                            <div class="relative max-h-full max-w-5xl">
                                <img :src="src" alt="" class="max-h-[85vh] rounded-xl bg-white object-contain shadow-2xl">
                                <button type="button" @click="src = null" class="absolute right-3 top-3 rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Close') }}"><x-icon name="x-mark" class="h-5 w-5" /></button>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-tertiary">{{ __('No portfolio files were attached.') }}</p>
                    @endif
                </x-panel>

                <!-- Message -->
                <x-panel :title="__('Message the applicant')" :description="__('They will get this as a notification and an email.')">
                    <form action="{{ route('my-jobs.applications.send-message', $application) }}" method="POST" class="space-y-4"
                        x-data="{ subject: @js((string) old('subject', '')), message: @js((string) old('message', '')) }"
                        @message-template-selected.window="subject = $event.detail.subject; message = $event.detail.message">
                        @csrf

                        <div>
                            <x-btn variant="secondary" size="sm" type="button" @click="$dispatch('open-modal', 'message-templates')">
                                <x-icon name="chat-bubble-text" class="h-4 w-4" />
                                {{ __('Quick templates') }}
                            </x-btn>
                        </div>

                        <div>
                            <label for="subject" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Subject') }}</label>
                            <input type="text" id="subject" name="subject" x-model="subject" maxlength="255" required placeholder="{{ __('Re: Your application for :title', ['title' => $job->title]) }}"
                                class="{{ $fieldClass }} {{ $errors->has('subject') ? $badField : $okField }}">
                            @error('subject')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="message" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Message') }}</label>
                            <textarea id="message" name="message" x-model="message" rows="5" required placeholder="{{ __('Write your message to :name…', ['name' => $applicant->name]) }}"
                                class="{{ $fieldClass }} {{ $errors->has('message') ? $badField : $okField }} resize-y"></textarea>
                            @error('message')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end">
                            <x-btn type="submit">
                                <x-icon name="paper-airplane" class="h-4 w-4" />
                                {{ __('Send message') }}
                            </x-btn>
                        </div>
                    </form>

                    @if ($sentMessages->isNotEmpty())
                        <div class="mt-6 border-t border-neutral-100 pt-5">
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Sent to :name', ['name' => $applicant->name]) }}</h3>
                            <ul class="space-y-3">
                                @foreach ($sentMessages as $sent)
                                    <li class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                        <p class="flex items-baseline justify-between gap-3 text-sm font-medium text-neutral-900"><span class="truncate">{{ $sent->subject }}</span><span class="shrink-0 text-xs font-normal text-tertiary">{{ $sent->created_at->diffForHumans() }}</span></p>
                                        <p class="mt-1 line-clamp-3 whitespace-pre-line text-sm text-neutral-700">{{ $sent->message }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-panel>

                <!-- Reviews -->
                <x-panel :title="__('Reviews')" :description="$totalReviews ? trans_choice(':count public review|:count public reviews', $totalReviews, ['count' => $totalReviews]) : null">
                    @if ($reviews->count() > 0)
                        <ul class="space-y-4">
                            @foreach ($reviews as $review)
                                <li><x-jobs.review-card :review="$review" /></li>
                            @endforeach
                        </ul>
                        @if ($reviews->hasPages())
                            <div class="mt-6">{{ $reviews->links() }}</div>
                        @endif
                    @else
                        <p class="text-sm text-tertiary">{{ __('This applicant has not received any public reviews yet.') }}</p>
                    @endif
                </x-panel>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel :title="__('The project')">
                    <a href="{{ route('jobs.show', $job->slug) }}" class="font-tertiary text-base font-semibold text-neutral-900 hover:text-teal-700 focus:outline-none focus-visible:underline">{{ $job->title }}</a>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                            <dd class="mt-0.5 font-medium text-neutral-900">{{ $job->no_deadline || ! $job->deadline ? __('None') : $job->deadline->format('M j, Y') }}</dd>
                        </div>
                    </dl>
                </x-panel>

                <x-panel :title="__('Private notes')" :description="__('Only you can see these.')">
                    <form action="{{ route('my-jobs.applications.update-status', $application) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status->value }}">
                        <label for="notes" class="sr-only">{{ __('Private notes') }}</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="{{ __('What stood out about this applicant?') }}"
                            class="{{ $fieldClass }} {{ $errors->has('notes') ? $badField : $okField }} resize-none">{{ old('notes', $application->additional_notes) }}</textarea>
                        @error('notes')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <x-btn block variant="secondary" type="submit" :disabled="$status === ApplicationStatus::Withdrawn">{{ __('Save notes') }}</x-btn>
                    </form>
                </x-panel>

                <x-panel :title="__('Timeline')">
                    <ol class="space-y-4">
                        @foreach ($events as $event)
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $event['dot'] }}"></span>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">{{ $event['label'] }}</p>
                                    <p class="text-xs text-tertiary">{{ $event['at']->format('M j, Y · g:i A') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-panel>
            </aside>
        </div>
    </div>

    <x-messages.template-picker />

    @if ($canReject)
        <x-confirm-dialog name="reject-application" :title="__('Reject :name?', ['name' => $applicant->name])"
            :message="__('They will see that their application was not successful. You can reconsider it later.')"
            icon="exclamation-triangle" tone="danger" confirm-icon="x-mark" confirm-label="Reject application" method="PATCH"
            :action="route('my-jobs.applications.update-status', $application)">
            <input type="hidden" name="status" value="rejected">
        </x-confirm-dialog>
    @endif

    @if ($canHire)
        <x-modal name="hire-applicant" max-width="2xl" :show="$openHire" focusable>
            <form action="{{ route('my-jobs.applications.confirm-hire', $application) }}" method="POST"
                x-data="{ deliverables: @js(array_values((array) old('deliverables', []))) }">
                @csrf
                <x-modal.header :title="__('Hire :name', ['name' => $applicant->name])" icon="user-group" />

                <div class="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5">
                    <p class="text-sm text-neutral-700">{{ __('This sends :name an offer of :amount and opens an engagement between you. The project stops taking applications once they accept.', ['name' => $applicant->name, 'amount' => \App\Support\Money::format($application->offer_amount)]) }}</p>

                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-neutral-900">{{ __('Deliverables') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></h3>
                            <x-btn size="sm" variant="secondary" type="button" @click="deliverables.push({ title: '', description: '', due_date: '' })">
                                <x-icon name="plus" class="h-4 w-4" />
                                {{ __('Add') }}
                            </x-btn>
                        </div>
                        <p class="mt-1 text-xs text-tertiary">{{ __('Break the work into pieces that are reviewed and approved one at a time. You can also add them later.') }}</p>

                        <ul class="mt-3 space-y-3">
                            <template x-for="(deliverable, index) in deliverables" :key="index">
                                <li class="rounded-xl border border-neutral-200 bg-neutral-50 p-3.5">
                                    <div class="flex items-start gap-3">
                                        <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(0,1fr)_9rem]">
                                            <input type="text" :name="`deliverables[${index}][title]`" x-model="deliverable.title" maxlength="255" required placeholder="{{ __('What needs to be delivered?') }}" aria-label="{{ __('Deliverable title') }}"
                                                class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                                            <input type="date" :name="`deliverables[${index}][due_date]`" x-model="deliverable.due_date" aria-label="{{ __('Due date') }}"
                                                class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                                            <textarea :name="`deliverables[${index}][description]`" x-model="deliverable.description" rows="2" placeholder="{{ __('Details or acceptance criteria (optional)') }}" aria-label="{{ __('Deliverable description') }}"
                                                class="block w-full resize-none rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 sm:col-span-2"></textarea>
                                        </div>
                                        <button type="button" @click="deliverables.splice(index, 1)" class="shrink-0 rounded-lg p-1.5 text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Remove deliverable') }}"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        @error('deliverables.*.title')<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        @error('deliverables.*.due_date')<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-modal.footer>
                    <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ __('Cancel') }}</x-btn>
                    <x-btn type="submit">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ __('Confirm hire') }}
                    </x-btn>
                </x-modal.footer>
            </form>
        </x-modal>
    @endif
</x-app-layout>
