{{--
    The application form, shared by the apply page (a new application) and the draft page (an existing draft).
    Expects $job and $application (the draft, or null). Submitting posts to applications.store, which saves
    either a draft or the sent application depending on the button pressed.
--}}
@php
    $feePercent = \App\Helpers\Applications\ApplicationCalculationHelper::getServiceFeePercentage();
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $existingFiles = collect($application?->portfolio ?? [])->map(fn ($path) => [
        'path' => $path,
        'name' => basename($path),
        'url' => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true) ? asset('storage/'.$path) : null,
    ])->values();
    $draftOffer = $application && (float) $application->offer_amount > 0 ? rtrim(rtrim((string) $application->offer_amount, '0'), '.') : '';
    $canSend = $job->isOpenForApplications();
@endphp
    <form action="{{ route('applications.store') }}" method="POST" enctype="multipart/form-data"
        x-data="{
            offer: @js((string) old('offer', $draftOffer)),
            proposal: @js((string) old('proposal', $application?->proposal ?? '')),
            terms: {{ old('terms', $application?->terms_accepted) ? 'true' : 'false' }},
            existing: @js($existingFiles),
            removed: [],
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
                    if (this.existing.length + this.files.length >= 5) { this.error = @js(__('You can attach up to 5 files.')); break; }
                    this.files.push({ file, url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
                }
                this.sync();
            },
            dropExisting(index) {
                this.removed.push(this.existing[index].path);
                this.existing.splice(index, 1);
                this.error = '';
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

                    <template x-for="item in existing" :key="'keep-' + item.path"><input type="hidden" name="existing_portfolio[]" :value="item.path"></template>
                    <template x-for="path in removed" :key="'drop-' + path"><input type="hidden" name="removed_files[]" :value="path"></template>
                    <ul class="mt-3 space-y-2" x-show="existing.length" x-cloak>
                        <template x-for="(item, index) in existing" :key="item.path">
                            <li class="flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-2 text-sm">
                                <template x-if="item.url"><img :src="item.url" alt="" class="h-10 w-10 shrink-0 rounded-md object-cover"></template>
                                <template x-if="!item.url"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-xs font-semibold text-tertiary">PDF</span></template>
                                <span class="min-w-0 flex-1 truncate text-neutral-800" x-text="item.name"></span>
                                <span class="shrink-0 text-xs text-tertiary">{{ __('Saved') }}</span>
                                <button type="button" @click="dropExisting(index)" class="shrink-0 text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:text-red-600" aria-label="{{ __('Remove this file') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
                            </li>
                        </template>
                    </ul>
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
                <x-btn type="submit" name="action" value="draft" variant="secondary" :disabled="! $canSend">{{ __('Save draft') }}</x-btn>
                <x-btn type="submit" name="action" value="submitted" ::disabled="!(amount > 0 && terms) || {{ $canSend ? 'false' : 'true' }}">
                    <x-icon name="paper-airplane" class="h-4 w-4" />
                    {{ __('Submit application') }}
                </x-btn>
            </x-slot:footer>
        </x-panel>
    </form>