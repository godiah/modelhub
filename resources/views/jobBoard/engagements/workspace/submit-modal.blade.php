{{--
    One submit/resubmit modal for every deliverable. Opened with
    $dispatch('open-modal', { name: 'submit-deliverable', id, title, resubmit }). Limits mirror SubmitDeliverableRequest.
--}}
<x-modal name="submit-deliverable" focusable>
    <form method="POST" enctype="multipart/form-data"
        :action="@js(route('engagements.deliverables.submit', 'DELIVERABLE_ID')).replace('DELIVERABLE_ID', payload.id)"
        x-data="{
            files: [],
            notes: '',
            error: '',
            dragging: false,
            max: 5,
            maxBytes: 10 * 1024 * 1024,
            reset() { this.files = []; this.notes = ''; this.error = ''; this.sync(); },
            pick(list) {
                this.error = '';
                for (const file of Array.from(list)) {
                    if (this.files.length >= this.max) { this.error = @js(__('You can attach up to 5 files.')); break; }
                    if (file.size > this.maxBytes) { this.error = @js(__('Each file must be 10 MB or smaller.')); continue; }
                    this.files.push(file);
                }
                this.sync();
            },
            remove(index) { this.files.splice(index, 1); this.error = ''; this.sync(); },
            sync() {
                const transfer = new DataTransfer();
                this.files.forEach(file => transfer.items.add(file));
                this.$refs.input.files = transfer.files;
            },
            size(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; },
        }"
        x-effect="payload.id; $nextTick(() => reset())">
        @csrf

        <x-modal.header :title="__('Submit deliverable')" icon="cloud-arrow-up" />

        <div class="space-y-5 p-6">
            <p class="text-sm text-neutral-600">
                {{ __('Delivering:') }} <span class="font-medium text-neutral-900" x-text="payload.title"></span>
            </p>
            <p x-show="payload.resubmit" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                {{ __('Resubmitting replaces the files and notes you sent before.') }}
            </p>

            <div>
                <span class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Files') }}</span>
                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
                    :class="dragging ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400'"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors">
                    <x-icon name="cloud-arrow-up" class="h-7 w-7 text-neutral-400" />
                    <span class="mt-2 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ __('Choose files') }}</span> {{ __('or drag them here') }}</span>
                    <span class="mt-1 text-xs text-tertiary">{{ __('Up to 5 files, 10 MB each') }}</span>
                    <input type="file" name="submission_files[]" multiple x-ref="input" class="sr-only"
                        @change="pick($event.target.files)">
                </label>
                <p x-show="error" x-text="error" x-cloak class="mt-1.5 text-xs text-red-600" role="alert"></p>
                @error('submission_files')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                @error('submission_files.*')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror

                <ul class="mt-3 space-y-2" x-show="files.length" x-cloak>
                    <template x-for="(file, index) in files" :key="file.name + index">
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm">
                            <span class="min-w-0 truncate text-neutral-800" x-text="file.name"></span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span class="text-xs text-tertiary" x-text="size(file.size)"></span>
                                <button type="button" @click="remove(index)" class="text-neutral-400 hover:text-red-600 focus:outline-none focus-visible:text-red-600"
                                    aria-label="{{ __('Remove file') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
                            </span>
                        </li>
                    </template>
                </ul>
            </div>

            <div>
                <label for="submission_notes" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                    <span>{{ __('Notes for the client') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></span>
                    <span class="text-xs font-normal text-tertiary" x-text="notes.length + ' / 1000'"></span>
                </label>
                <textarea id="submission_notes" name="submission_notes" rows="4" maxlength="1000" x-model="notes"
                    placeholder="{{ __('Anything the client should know about this delivery…') }}"
                    class="block w-full resize-none rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                @error('submission_notes')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <x-modal.footer>
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ __('Cancel') }}</x-btn>
            <x-btn type="submit">
                <x-icon name="cloud-arrow-up" class="h-4 w-4" />
                <span x-text="payload.resubmit ? @js(__('Resubmit')) : @js(__('Submit for review'))"></span>
            </x-btn>
        </x-modal.footer>
    </form>
</x-modal>
