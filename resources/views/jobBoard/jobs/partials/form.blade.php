{{--
    The project brief form, shared by "Post a project" and "Edit project".
    Expects: $skills, $software (active options), $action, $submitLabel, $cancelUrl, and optionally
    $job (editing) and $locked (a freelancer is already working, so the project cannot take applications again).
    Everything interactive is one inline Alpine component (Livewire starts Alpine before module scripts run).
--}}
@php
    $editing = isset($job) && $job;
    $locked = $locked ?? false;
    $extras = $editing ? $job->jobImages : collect();
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $pickerItems = fn ($options) => $options->map(fn ($option) => ['id' => $option->name, 'name' => $option->name])->values()->all();
    $deadlineValue = old('deadline', $editing && $job->deadline ? $job->deadline->format('Y-m-d') : '');
    $noDeadline = old('no_deadline', $editing ? (bool) $job->no_deadline || $job->deadline === null : false);
    $isActive = old('is_active', $editing ? (bool) $job->is_active : true);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="projectForm" novalidate
    x-data="{
        title: @js((string) old('title', $editing ? $job->title : '')),
        titleTaken: false,
        titleTimer: null,
        checkTitle: {{ $editing ? 'false' : 'true' }},
        description: @js((string) old('description', $editing ? $job->description : '')),
        tab: 'write',
        previewHtml: '',
        previewing: false,
        previewFailed: false,
        noDeadline: {{ $noDeadline ? 'true' : 'false' }},
        isActive: {{ $isActive ? 'true' : 'false' }},
        cover: null,
        coverError: '',
        coverDrag: false,
        extras: [],
        extrasError: '',
        extrasDrag: false,
        existingExtras: {{ $extras->count() }},
        removed: [],
        submitting: false,
        get extrasAllowed() { return 5 - (this.existingExtras - this.removed.length); },
        get titleLeft() { return 255 - this.title.length; },
        lookupTitle() {
            if (!this.checkTitle) return;
            clearTimeout(this.titleTimer);
            const title = this.title.trim();
            if (!title) { this.titleTaken = false; return; }
            this.titleTimer = setTimeout(async () => {
                try {
                    const response = await fetch(@js(route('jobs.check-title')) + '?title=' + encodeURIComponent(title), { headers: { 'Accept': 'application/json' } });
                    this.titleTaken = response.ok && (await response.json()).exists === true;
                } catch (e) { this.titleTaken = false; }
            }, 350);
        },
        async showPreview() {
            this.tab = 'preview';
            this.previewing = true;
            this.previewFailed = false;
            try {
                const response = await fetch(@js(route('jobs.preview')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ description: this.description }),
                });
                if (!response.ok) throw new Error(response.status);
                this.previewHtml = (await response.json()).html;
            } catch (e) { this.previewFailed = true; } finally { this.previewing = false; }
        },
        image(file) {
            if (!file.type.startsWith('image/')) return @js(__('Use a JPG or PNG image.'));
            if (file.size > 5 * 1024 * 1024) return @js(__('Each image must be 5 MB or smaller.'));
            return '';
        },
        pickCover(list) {
            const file = list[0];
            if (!file) return;
            this.coverError = this.image(file);
            if (this.coverError) { this.syncCover(); return; }
            if (this.cover) URL.revokeObjectURL(this.cover.url);
            this.cover = { file, url: URL.createObjectURL(file) };
            this.syncCover();
        },
        clearCover() { if (this.cover) URL.revokeObjectURL(this.cover.url); this.cover = null; this.coverError = ''; this.syncCover(); },
        syncCover() {
            const transfer = new DataTransfer();
            if (this.cover) transfer.items.add(this.cover.file);
            this.$refs.cover.files = transfer.files;
        },
        pickExtras(list) {
            this.extrasError = '';
            for (const file of Array.from(list)) {
                const problem = this.image(file);
                if (problem) { this.extrasError = problem; continue; }
                if (this.extras.length >= this.extrasAllowed) { this.extrasError = @js(__('A project can have up to 5 extra images.')); break; }
                this.extras.push({ file, url: URL.createObjectURL(file) });
            }
            this.syncExtras();
        },
        removeExtra(index) { URL.revokeObjectURL(this.extras[index].url); this.extras.splice(index, 1); this.extrasError = ''; this.syncExtras(); },
        syncExtras() {
            const transfer = new DataTransfer();
            this.extras.forEach(item => transfer.items.add(item.file));
            this.$refs.extras.files = transfer.files;
        },
        toggleRemoved(id) {
            this.removed = this.removed.includes(id) ? this.removed.filter(value => value !== id) : [...this.removed, id];
            while (this.extras.length > this.extrasAllowed) this.removeExtra(this.extras.length - 1);
        },
    }" @submit="submitting = true">
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <!-- The brief -->
            <x-panel :title="__('The brief')" :description="__('What freelancers read first. Be specific about the deliverables and how you will judge the work.')">
                <div class="space-y-6">
                    <div>
                        <label for="title" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                            <span>{{ __('Project title') }}</span>
                            <span class="text-xs font-normal tabular-nums" :class="titleLeft < 30 ? 'text-amber-700' : 'text-tertiary'" x-text="titleLeft + ' left'"></span>
                        </label>
                        <input type="text" id="title" name="title" x-model="title" @input="lookupTitle()" maxlength="255" required autocomplete="off"
                            placeholder="{{ __('e.g. Photoreal exterior renders for a four-unit villa') }}"
                            :class="titleTaken ? @js($badField) : @js($errors->has('title') ? $badField : $okField)"
                            class="{{ $fieldClass }}">
                        <p x-show="titleTaken" x-cloak class="mt-1.5 text-xs text-red-600" role="alert">{{ __('This title is already in use. Try making it more specific.') }}</p>
                        @error('title')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="description" class="text-sm font-medium text-neutral-800">{{ __('Description') }}</label>
                            <div class="inline-flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xs font-medium" role="tablist" aria-label="{{ __('Editor mode') }}">
                                <button type="button" role="tab" :aria-selected="(tab === 'write').toString()" @click="tab = 'write'"
                                    :class="tab === 'write' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'"
                                    class="rounded-md px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Write') }}</button>
                                <button type="button" role="tab" :aria-selected="(tab === 'preview').toString()" @click="showPreview()"
                                    :class="tab === 'preview' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'"
                                    class="rounded-md px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Preview') }}</button>
                            </div>
                        </div>

                        <textarea id="description" name="description" x-model="description" x-show="tab === 'write'" rows="12" maxlength="20000" required
                            placeholder="{{ __("Describe the project: what you need built, the deliverables, file formats, timeline and anything you will supply (plans, references, brand assets).") }}"
                            class="{{ $fieldClass }} {{ $errors->has('description') ? $badField : $okField }} resize-y font-mono text-[13px] leading-relaxed"></textarea>

                        <div x-show="tab === 'preview'" x-cloak class="min-h-[18rem] rounded-xl border border-neutral-200 bg-neutral-50/60 px-4 py-3">
                            <p x-show="previewing" class="text-sm text-tertiary">{{ __('Rendering preview…') }}</p>
                            <p x-show="previewFailed" class="text-sm text-red-600" role="alert">{{ __('The preview could not be loaded. Try again.') }}</p>
                            <p x-show="!previewing && !previewFailed && !description.trim()" class="text-sm text-tertiary">{{ __('Nothing to preview yet.') }}</p>
                            <x-jobs.prose x-show="!previewing && !previewFailed"><div x-html="previewHtml"></div></x-jobs.prose>
                        </div>

                        @error('description')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-tertiary">{{ __('Markdown works: **bold**, - bullet lists, ## headings. Raw HTML is removed.') }}</p>
                    </div>
                </div>
            </x-panel>

            <!-- Requirements -->
            <x-panel :title="__('What you need')" :description="__('Freelancers filter projects by these, so pick what genuinely matters.')">
                <div class="space-y-6">
                    <x-tag-picker field="skills" name="skills[]" tone="neutral" :label="__('Skills')" :items="$pickerItems($skills)"
                        :selected="array_values((array) old('skills', $editing ? (array) $job->skills : []))" :placeholder="__('Search skills…')" />
                    @error('skills')<p class="-mt-4 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    @error('skills.*')<p class="-mt-4 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror

                    <x-tag-picker field="software" name="software[]" tone="teal" :label="__('Software')" :items="$pickerItems($software)"
                        :selected="array_values((array) old('software', $editing ? (array) $job->software : []))" :placeholder="__('Search software…')" />
                    @error('software')<p class="-mt-4 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    @error('software.*')<p class="-mt-4 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                </div>
            </x-panel>

            <!-- Images -->
            <x-panel :title="__('Images')" :description="__('A cover image and up to five extra references (plans, mood boards, sketches). JPG or PNG, 5 MB each.')">
                <div class="space-y-6">
                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Cover image') }}</span>

                        @if ($editing && $job->images)
                            <div class="mb-3 flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-2.5" x-show="!cover">
                                <img src="{{ asset('storage/'.$job->images) }}" alt="" class="h-16 w-24 rounded-lg object-cover">
                                <p class="text-sm text-neutral-700">{{ __('Current cover. Choose a new image below to replace it.') }}</p>
                            </div>
                        @endif

                        <label @dragover.prevent="coverDrag = true" @dragleave.prevent="coverDrag = false" @drop.prevent="coverDrag = false; pickCover($event.dataTransfer.files)" x-show="!cover"
                            :class="coverDrag ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400'"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-7 text-center transition-colors">
                            <x-icon name="photo" class="h-7 w-7 text-neutral-400" />
                            <span class="mt-2 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ $editing ? __('Replace the cover') : __('Choose a cover image') }}</span> {{ __('or drag it here') }}</span>
                            <input type="file" name="image" x-ref="cover" accept="image/jpeg,image/png" class="sr-only" @change="pickCover($event.target.files)">
                        </label>

                        <div x-show="cover" x-cloak class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-2.5">
                            <img :src="cover?.url" alt="" class="h-16 w-24 rounded-lg object-cover">
                            <span class="min-w-0 flex-1 truncate text-sm text-neutral-800" x-text="cover?.file.name"></span>
                            <button type="button" @click="clearCover()" class="shrink-0 rounded-lg p-2 text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Remove cover image') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
                        </div>
                        <p x-show="coverError" x-text="coverError" x-cloak class="mt-1.5 text-xs text-red-600" role="alert"></p>
                        @error('image')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <span class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                            <span>{{ __('Extra images') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></span>
                            <span class="text-xs font-normal tabular-nums text-tertiary" x-text="(existingExtras - removed.length + extras.length) + ' / 5'">0 / 5</span>
                        </span>

                        @if ($extras->isNotEmpty())
                            <ul class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                                @foreach ($extras as $image)
                                    <li class="relative">
                                        <img src="{{ asset('storage/'.$image->image_path) }}" alt="" class="aspect-square w-full rounded-lg border border-neutral-200 object-cover transition-opacity" :class="removed.includes({{ $image->id }}) && 'opacity-30'">
                                        <button type="button" @click="toggleRemoved({{ $image->id }})" :aria-pressed="removed.includes({{ $image->id }}).toString()"
                                            class="absolute right-1 top-1 rounded-full bg-white/95 p-1 text-neutral-600 shadow hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                            :aria-label="removed.includes({{ $image->id }}) ? @js(__('Keep this image')) : @js(__('Remove this image'))">
                                            <x-icon name="x-mark" class="h-3.5 w-3.5" x-show="!removed.includes({{ $image->id }})" />
                                            <x-icon name="arrow-path" class="h-3.5 w-3.5" x-show="removed.includes({{ $image->id }})" x-cloak />
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <template x-for="id in removed" :key="id"><input type="hidden" name="remove_images[]" :value="id"></template>
                        @endif

                        <label @dragover.prevent="extrasDrag = true" @dragleave.prevent="extrasDrag = false" @drop.prevent="extrasDrag = false; pickExtras($event.dataTransfer.files)"
                            :class="[extrasDrag ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400', extrasAllowed <= extras.length ? 'pointer-events-none opacity-50' : '']"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-5 text-center transition-colors">
                            <x-icon name="cloud-arrow-up" class="h-6 w-6 text-neutral-400" />
                            <span class="mt-1.5 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ __('Add images') }}</span> {{ __('or drag them here') }}</span>
                            <input type="file" name="additional_images[]" x-ref="extras" accept="image/jpeg,image/png" multiple class="sr-only" @change="pickExtras($event.target.files)">
                        </label>
                        <p x-show="extrasError" x-text="extrasError" x-cloak class="mt-1.5 text-xs text-red-600" role="alert"></p>
                        @error('additional_images')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        @error('additional_images.*')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror

                        <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5" x-show="extras.length" x-cloak>
                            <template x-for="(item, index) in extras" :key="item.file.name + index">
                                <li class="relative">
                                    <img :src="item.url" alt="" class="aspect-square w-full rounded-lg border border-neutral-200 object-cover">
                                    <button type="button" @click="removeExtra(index)" class="absolute right-1 top-1 rounded-full bg-white/95 p-1 text-neutral-600 shadow hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Remove image') }}"><x-icon name="x-mark" class="h-3.5 w-3.5" /></button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </x-panel>
        </div>

        <!-- Budget, timing and publishing -->
        <aside class="lg:sticky lg:top-24">
            <x-panel :title="__('Budget & timing')">
                <div class="space-y-5">
                    <div>
                        <label for="budget" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Budget') }}</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-neutral-500">{{ config('app.currency_symbol') }}</span>
                            <input type="number" id="budget" name="budget" min="1" step="1" inputmode="numeric" required
                                value="{{ old('budget', $editing ? (int) $job->budget : '') }}" placeholder="50000"
                                class="{{ $fieldClass }} {{ $errors->has('budget') ? $badField : $okField }} pl-12 tabular-nums">
                        </div>
                        @error('budget')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-tertiary">{{ __('In Kenyan shillings. Freelancers send their own offer against this.') }}</p>
                    </div>

                    <div>
                        <label for="deadline" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Deadline') }}</label>
                        <input type="date" id="deadline" name="deadline" value="{{ $deadlineValue }}" :disabled="noDeadline" @if (! $editing) min="{{ today()->toDateString() }}" @endif
                            class="{{ $fieldClass }} {{ $errors->has('deadline') ? $badField : $okField }} disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-400">
                        <input type="hidden" name="no_deadline" value="0">
                        <label class="mt-2.5 flex items-center gap-2.5 text-sm text-neutral-700">
                            <input type="checkbox" name="no_deadline" value="1" x-model="noDeadline" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                            {{ __('No fixed deadline') }}
                        </label>
                        @error('deadline')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-tertiary">{{ __('Applications are accepted through the end of this day.') }}</p>
                    </div>

                    @if ($editing)
                        <div class="border-t border-neutral-100 pt-5">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-start justify-between gap-4 text-sm @if ($locked) opacity-60 @else cursor-pointer @endif">
                                <span>
                                    <span class="block font-medium text-neutral-800">{{ __('Accepting applications') }}</span>
                                    <span class="mt-0.5 block text-xs text-tertiary" x-text="isActive ? @js(__('Visible on Browse projects.')) : @js(__('Hidden from Browse projects. Applicants can no longer apply.'))"></span>
                                </span>
                                <span class="relative mt-0.5 inline-flex shrink-0">
                                    <input type="checkbox" name="is_active" value="1" x-model="isActive" @disabled($locked) class="peer sr-only">
                                    <span class="h-6 w-11 rounded-full bg-neutral-300 transition-colors peer-checked:bg-teal-600 peer-focus-visible:ring-2 peer-focus-visible:ring-secondary/40"></span>
                                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            @if ($locked)
                                <p class="mt-2 text-xs text-amber-700">{{ __('A freelancer is already working on this project, so it cannot take new applications.') }}</p>
                            @endif
                            @error('is_active')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <x-slot:footer>
                    <x-btn variant="secondary" href="{{ $cancelUrl }}">{{ __('Cancel') }}</x-btn>
                    <x-btn type="submit" ::disabled="submitting || titleTaken">
                        <x-icon name="{{ $editing ? 'check' : 'paper-airplane' }}" class="h-4 w-4" />
                        {{ $submitLabel }}
                    </x-btn>
                </x-slot:footer>
            </x-panel>
        </aside>
    </div>
</form>
