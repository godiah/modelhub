@php
    $application = $engagement->application;
    $job = $application->job;
@endphp
<x-app-layout :crumb="__('Dispute payment')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Dispute payment') }}</p>
                    <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                    <p class="mt-1.5 text-sm text-tertiary">
                        {{ __('Payment of :amount processed by :name', ['amount' => \App\Support\Money::format($payment->amount), 'name' => $application->poster->name]) }}
                        @if ($payment->processed_at)
                            · {{ $payment->processed_at->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <x-btn variant="secondary" size="sm" href="{{ route('engagements.show-cancelled', $engagement->id) }}" class="shrink-0 self-start">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    {{ __('Back to settlement') }}
                </x-btn>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <form action="{{ route('engagements.process-dispute-partial-payment', $payment->id) }}" method="POST" enctype="multipart/form-data"
                x-data="{
                    file: null,
                    error: '',
                    dragging: false,
                    acknowledged: false,
                    allowed: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
                    pick(list) {
                        this.error = '';
                        const file = list[0];
                        if (!file) return;
                        const extension = file.name.split('.').pop().toLowerCase();
                        if (!this.allowed.includes(extension)) { this.error = @js(__('Use a JPG, PNG, PDF, DOC or DOCX file.')); return this.sync(); }
                        if (file.size > 10 * 1024 * 1024) { this.error = @js(__('The file must be 10 MB or smaller.')); return this.sync(); }
                        this.file = file;
                        this.sync();
                    },
                    clear() { this.file = null; this.error = ''; this.sync(); },
                    sync() {
                        const transfer = new DataTransfer();
                        if (this.file) transfer.items.add(this.file);
                        this.$refs.input.files = transfer.files;
                    },
                    size(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; },
                }">
                @csrf

                <x-panel :title="__('Tell us what went wrong')" :description="__('An administrator will read this, so be specific and factual.')">
                    <div class="space-y-6">
                        <x-field type="select" name="reason" error="reason" required :label="__('Reason for the dispute')">
                            <option value="">{{ __('Select a reason') }}</option>
                            @foreach (\App\Models\JobPaymentDispute::reasons() as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-field>

                        <x-field type="textarea" name="details" error="details" rows="6" required
                            :label="__('Detailed explanation')"
                            :hint="__('Include the timeline, what was agreed and anything that supports your case.')">{{ old('details') }}</x-field>

                        <div>
                            <span class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Supporting evidence') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></span>

                            <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
                                :class="dragging ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400'"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors">
                                <x-icon name="cloud-arrow-up" class="h-7 w-7 text-neutral-400" />
                                <span class="mt-2 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ __('Choose a file') }}</span> {{ __('or drag it here') }}</span>
                                <span class="mt-1 text-xs text-tertiary">{{ __('JPG, PNG, PDF, DOC or DOCX, up to 10 MB') }}</span>
                                <input type="file" name="evidence" x-ref="input" class="sr-only" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                    @change="pick($event.target.files)">
                            </label>

                            <p x-show="error" x-text="error" x-cloak class="mt-1.5 text-xs text-red-600" role="alert"></p>
                            @error('evidence')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror

                            <div x-show="file" x-cloak class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm">
                                <span class="min-w-0 truncate text-neutral-800" x-text="file?.name"></span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs text-tertiary" x-text="file ? size(file.size) : ''"></span>
                                    <button type="button" @click="clear()" class="text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:text-red-600"
                                        aria-label="{{ __('Remove file') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-tertiary">{{ __('Screenshots, conversation logs and samples of the delivered work help most.') }}</p>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5 text-sm">
                            <input type="checkbox" name="acknowledgment" x-model="acknowledged" required
                                class="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                            <span class="text-neutral-700">{{ __('I have provided accurate information, and I understand that filing frivolous disputes may affect my account standing.') }}</span>
                        </label>
                    </div>

                    <x-slot:footer>
                        <x-btn variant="secondary" href="{{ route('engagements.show-cancelled', $engagement->id) }}">{{ __('Cancel') }}</x-btn>
                        <x-btn type="submit" variant="danger" ::disabled="!acknowledged">
                            <x-icon name="scale" class="h-4 w-4" />
                            {{ __('Submit dispute') }}
                        </x-btn>
                    </x-slot:footer>
                </x-panel>
            </form>

            <div class="space-y-6">
                <x-panel :title="__('Payment being disputed')">
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-tertiary">{{ __('Amount') }}</dt>
                            <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$payment->amount" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-tertiary">{{ __('Agreed amount') }}</dt>
                            <dd class="mt-1 font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" /></dd>
                        </div>
                    </dl>
                </x-panel>

                <x-panel :title="__('What happens next')">
                    <ol class="space-y-4 text-sm text-neutral-700">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">1</span>
                            <span>{{ __('The engagement is frozen and the disputed amount stays in escrow.') }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">2</span>
                            <span>{{ __('An administrator reviews the engagement history, deliverables and messages.') }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold text-teal-700">3</span>
                            <span>{{ __('You and the client are notified of the decision and how the funds are distributed.') }}</span>
                        </li>
                    </ol>
                    <a href="{{ route('engagements.policy') }}#dispute" class="mt-4 inline-block text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline">{{ __('Read the dispute policy') }}</a>
                </x-panel>
            </div>
        </div>
    </div>
</x-app-layout>
