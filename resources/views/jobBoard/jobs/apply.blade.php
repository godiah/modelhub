@use('App\Enums\ApplicationStatus')
@php
    $isOwner = auth()->id() === $job->user_id;
    $canApply = auth()->check() && ! $isOwner && $applicationStatus === null;
    $feePercent = \App\Helpers\Applications\ApplicationCalculationHelper::getServiceFeePercentage();
    $skills = array_values(array_filter((array) $job->skills));
    $software = array_values(array_filter((array) $job->software));
    $deadlineSoon = $job->deadlineIsSoon();

    $gallery = collect([$job->images])
        ->merge($job->jobImages->pluck('image_path'))
        ->filter()
        ->map(fn ($path) => asset('storage/'.$path))
        ->values();

    $statusCopy = [
        ApplicationStatus::Submitted->value => __('Your application is waiting for the client to review it.'),
        ApplicationStatus::Reviewed->value => __('The client has looked at your application.'),
        ApplicationStatus::Hired->value => __('You were hired for this project. Check your engagements.'),
        ApplicationStatus::Rejected->value => __('The client chose another freelancer for this project.'),
        ApplicationStatus::Withdrawn->value => __('You withdrew this application.'),
    ];
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
@endphp
<x-app-layout title="Project details" :crumb="$job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <!-- The project -->
            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    <div class="p-6 sm:p-8">
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Project') }}</p>
                        <h1 class="mt-1 font-tertiary text-2xl font-semibold leading-snug text-neutral-900 sm:text-3xl">{{ $job->title }}</h1>

                        @if ($job->user)
                            <div class="mt-4 flex items-center gap-3">
                                <x-user-avatar :user="$job->user" size="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ $job->user->name }}</p>
                                    <p class="text-xs text-tertiary">{{ __('Posted :time', ['time' => $job->created_at->diffForHumans()]) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 divide-y divide-neutral-100 border-t border-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                            <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                        </div>
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                            <dd @class(['mt-1 text-base font-semibold', 'text-amber-700' => $deadlineSoon, 'text-neutral-900' => ! $deadlineSoon])>
                                {{ $job->no_deadline ? __('No fixed deadline') : $job->deadline->format('M j, Y') }}
                            </dd>
                        </div>
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Proposals') }}</dt>
                            <dd class="mt-1 text-base font-semibold text-neutral-900">{{ trans_choice(':count applicant|:count applicants', (int) $job->applicants_count, ['count' => (int) $job->applicants_count]) }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-panel :title="__('About this project')">
                    <x-jobs.markdown-description :content="$job->description" />
                </x-panel>

                @if ($skills || $software)
                    <x-panel :title="__('What the client is looking for')" :description="__('Preferred, not mandatory for every applicant.')">
                        <div class="space-y-5">
                            @foreach ([__('Skills') => [$skills, 'border-neutral-200 bg-neutral-50 text-neutral-700'], __('Software') => [$software, 'border-teal-100 bg-teal-50 text-teal-800']] as $label => [$items, $tone])
                                @if ($items)
                                    <div>
                                        <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-tertiary">{{ $label }}</h3>
                                        <ul class="flex flex-wrap gap-2">
                                            @foreach ($items as $item)
                                                <li class="rounded-full border px-3 py-1 text-xs font-medium {{ $tone }}">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </x-panel>
                @endif

                @if ($gallery->isNotEmpty())
                    <x-panel :title="__('Project preview')" x-data="{ src: null }" @keydown.escape.window="src = null">
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($gallery as $image)
                                <li>
                                    <button type="button" @click="src = @js($image)" class="group block w-full overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                        aria-label="{{ __('View image :number full size', ['number' => $loop->iteration]) }}">
                                        <img src="{{ $image }}" alt="{{ __('Project preview :number', ['number' => $loop->iteration]) }}" loading="lazy"
                                            class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div x-show="src" x-cloak x-transition.opacity @click.self="src = null" role="dialog" aria-modal="true" aria-label="{{ __('Project preview') }}"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/80 p-4 backdrop-blur-sm">
                            <div class="relative max-h-full max-w-5xl">
                                <img :src="src" alt="" class="max-h-[85vh] rounded-xl bg-white object-contain shadow-2xl">
                                <div class="absolute right-3 top-3 flex gap-2">
                                    <a :href="src" download class="rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Download image') }}"><x-icon name="cloud-arrow-down" class="h-5 w-5" /></a>
                                    <button type="button" @click="src = null" class="rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Close') }}"><x-icon name="x-mark" class="h-5 w-5" /></button>
                                </div>
                            </div>
                        </div>
                    </x-panel>
                @endif
            </div>

            <!-- Apply -->
            <aside class="lg:sticky lg:top-24">
                @if (! auth()->check())
                    <x-panel :title="__('Interested in this project?')" :description="__('Sign in to send the client your offer and proposal.')">
                        <div class="space-y-3">
                            <x-btn block href="{{ route('login') }}">{{ __('Sign in to apply') }}</x-btn>
                            <x-btn block variant="secondary" href="{{ route('register') }}">{{ __('Create an account') }}</x-btn>
                        </div>
                    </x-panel>
                @elseif ($isOwner)
                    <x-panel :title="__('This is your project')" :description="__('Freelancers see this page when they open your project.')">
                        <div class="space-y-3">
                            <x-btn block href="{{ route('my-jobs.applications.index', $job->slug) }}">{{ __('Review applications') }}</x-btn>
                            <x-btn block variant="secondary" href="{{ route('jobs.show', $job->slug) }}">{{ __('Open your project page') }}</x-btn>
                        </div>
                    </x-panel>
                @elseif ($applicationStatus === ApplicationStatus::Draft)
                    <x-panel :title="__('You have a saved draft')" :description="__('Pick up where you left off and send it when you are ready.')">
                        <x-btn block href="{{ route('applications.continue', $job->slug) }}">{{ __('Continue draft') }}</x-btn>
                    </x-panel>
                @elseif ($applicationStatus)
                    <x-panel :title="__('You applied to this project')">
                        <x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium">{{ $applicationStatus->label() }}</x-badge>
                        <p class="mt-3 text-sm text-neutral-700">{{ $statusCopy[$applicationStatus->value] ?? '' }}</p>
                        <x-btn block variant="secondary" class="mt-4" href="{{ route('applications.show', $job->slug) }}">{{ __('View your application') }}</x-btn>
                    </x-panel>
                @else
                    <form action="{{ route('applications.store') }}" method="POST" enctype="multipart/form-data"
                        x-data="{
                            offer: @js((string) old('offer', '')),
                            proposal: @js((string) old('proposal', '')),
                            terms: {{ old('terms') ? 'true' : 'false' }},
                            files: [],
                            error: '',
                            dragging: false,
                            fee: {{ $feePercent }},
                            symbol: @js(config('app.currency_symbol')),
                            get amount() { return parseFloat(this.offer) || 0; },
                            get serviceFee() { return this.amount * this.fee; },
                            money(value) { return this.symbol + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                            pick(list) {
                                this.error = '';
                                for (const file of Array.from(list)) {
                                    const extension = file.name.split('.').pop().toLowerCase();
                                    if (!['jpg', 'jpeg', 'png', 'pdf'].includes(extension)) { this.error = @js(__('Use JPG, PNG or PDF files.')); continue; }
                                    if (file.size > 10 * 1024 * 1024) { this.error = @js(__('Each file must be 10 MB or smaller.')); continue; }
                                    if (this.files.length >= 5) { this.error = @js(__('You can attach up to 5 files.')); break; }
                                    this.files.push({ file, url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
                                }
                                this.sync();
                            },
                            remove(index) {
                                if (this.files[index].url) URL.revokeObjectURL(this.files[index].url);
                                this.files.splice(index, 1);
                                this.error = '';
                                this.sync();
                            },
                            sync() {
                                const transfer = new DataTransfer();
                                this.files.forEach(item => transfer.items.add(item.file));
                                this.$refs.input.files = transfer.files;
                            },
                            size(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; },
                        }">
                        @csrf
                        <input type="hidden" name="job_id" value="{{ $job->id }}">

                        <x-panel :title="__('Send your proposal')" :description="__('Tell the client what you would charge and why you are the right fit.')">
                            <div class="space-y-5">
                                <div>
                                    <label for="offer" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Your offer') }}</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-neutral-500">{{ config('app.currency_symbol') }}</span>
                                        <input type="number" id="offer" name="offer" x-model="offer" min="1" step="0.01" inputmode="decimal" placeholder="{{ (int) $job->budget }}"
                                            class="{{ $fieldClass }} {{ $errors->has('offer') ? $badField : $okField }} pl-12 tabular-nums" @if ($errors->has('offer')) aria-invalid="true" @endif>
                                    </div>
                                    @error('offer')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    <p class="mt-1.5 text-xs text-tertiary">{{ __('The client’s budget is :amount.', ['amount' => \App\Support\Money::format($job->budget, 0)]) }}</p>
                                </div>

                                <dl class="space-y-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5 text-sm" aria-live="polite">
                                    <div class="flex items-center justify-between">
                                        <dt class="text-tertiary">{{ __('Your offer') }}</dt>
                                        <dd class="font-medium tabular-nums text-neutral-900" x-text="amount ? money(amount) : '—'">—</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-tertiary">{{ __('Service fee (:percent%)', ['percent' => rtrim(rtrim(number_format($feePercent * 100, 1), '0'), '.')]) }}</dt>
                                        <dd class="tabular-nums text-neutral-700" x-text="amount ? '− ' + money(serviceFee) : '—'">—</dd>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-neutral-200 pt-2">
                                        <dt class="font-medium text-neutral-900">{{ __('You receive') }}</dt>
                                        <dd class="font-tertiary text-base font-semibold tabular-nums text-teal-700" x-text="amount ? money(amount - serviceFee) : '—'">—</dd>
                                    </div>
                                </dl>

                                <div>
                                    <label for="proposal" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                                        <span>{{ __('Your proposal') }}</span>
                                        <span class="text-xs font-normal tabular-nums text-tertiary" :class="proposal.length > 2000 && 'text-amber-700'" x-text="proposal.length + ' / 2500'">0 / 2500</span>
                                    </label>
                                    <textarea id="proposal" name="proposal" rows="6" maxlength="2500" x-model="proposal"
                                        placeholder="{{ __('Describe your experience, how you would approach the project and how long it would take…') }}"
                                        class="{{ $fieldClass }} {{ $errors->has('proposal') ? $badField : $okField }} resize-none"></textarea>
                                    @error('proposal')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <span class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Portfolio samples') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></span>
                                    <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
                                        :class="dragging ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400'"
                                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-5 text-center transition-colors">
                                        <x-icon name="cloud-arrow-up" class="h-6 w-6 text-neutral-400" />
                                        <span class="mt-1.5 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ __('Choose files') }}</span> {{ __('or drag them here') }}</span>
                                        <span class="mt-0.5 text-xs text-tertiary">{{ __('JPG, PNG or PDF · up to 5 files, 10 MB each') }}</span>
                                        <input type="file" name="portfolio[]" x-ref="input" multiple accept=".jpg,.jpeg,.png,.pdf" class="sr-only" @change="pick($event.target.files)">
                                    </label>
                                    <p x-show="error" x-text="error" x-cloak class="mt-1.5 text-xs text-red-600" role="alert"></p>
                                    @error('portfolio')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    @error('portfolio.*')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror

                                    <ul class="mt-3 space-y-2" x-show="files.length" x-cloak>
                                        <template x-for="(item, index) in files" :key="item.file.name + index">
                                            <li class="flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-2 text-sm">
                                                <template x-if="item.url"><img :src="item.url" alt="" class="h-10 w-10 shrink-0 rounded-md object-cover"></template>
                                                <template x-if="!item.url"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-xs font-semibold text-tertiary">PDF</span></template>
                                                <span class="min-w-0 flex-1 truncate text-neutral-800" x-text="item.file.name"></span>
                                                <span class="shrink-0 text-xs text-tertiary" x-text="size(item.file.size)"></span>
                                                <button type="button" @click="remove(index)" class="shrink-0 text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:text-red-600" aria-label="{{ __('Remove file') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div>
                                    <label class="flex items-start gap-2.5 text-sm text-neutral-700">
                                        <input type="checkbox" name="terms" value="1" x-model="terms" class="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                        <span>{{ __('I agree to the Terms of Service and Privacy Policy, and that my offer is binding if the client hires me.') }}</span>
                                    </label>
                                    @error('terms')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <x-slot:footer>
                                <x-btn type="submit" name="action" value="draft" variant="secondary">{{ __('Save draft') }}</x-btn>
                                <x-btn type="submit" name="action" value="submitted" ::disabled="!(amount > 0 && terms)">
                                    <x-icon name="paper-airplane" class="h-4 w-4" />
                                    {{ __('Submit application') }}
                                </x-btn>
                            </x-slot:footer>
                        </x-panel>
                    </form>
                @endif
            </aside>
        </div>

        @if ($similarJobs->isNotEmpty())
            <section class="mt-10 border-t border-neutral-200 pt-8" aria-labelledby="similar-projects">
                <h2 id="similar-projects" class="mb-4 font-tertiary text-lg font-semibold text-neutral-900">{{ __('Similar projects') }}</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($similarJobs->take(3) as $similarJob)
                        <x-jobs.card :job="$similarJob" :poster="false" :compact="true" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
