{{--
    One-file-per-request uploader with progress, used for preview images and model files.
    Props: $kind ('images'|'files'), $url (POST endpoint), $field (form field name), $items (existing, as arrays),
    $max (item cap), $maxMb (size cap per file), $accept (input accept attribute), $label, $hint.
    Each saved item carries delete_url (and, for images, url + cover_url).
--}}
@php $isImages = $kind === 'images'; @endphp
<div x-data="{
    items: @js($items),
    uploading: [],
    errors: [],
    max: {{ $max }},
    maxBytes: {{ $maxMb }} * 1048576,
    url: @js($url),
    field: @js($field),
    csrf: document.querySelector('meta[name=csrf-token]')?.content,
    dragging: false,
    announce() { this.$dispatch('uploads-changed', { kind: @js($kind), items: JSON.parse(JSON.stringify(this.items)) }); },
    headers() { return { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }; },
    pick(list) { this.errors = []; Array.from(list).forEach(file => this.send(file)); },
    problem(xhr) {
        try { const body = JSON.parse(xhr.responseText); return (body.errors ? Object.values(body.errors).flat()[0] : null) || body.message || @js(__('Upload failed.')); } catch (e) { return @js(__('Upload failed.')); }
    },
    send(file) {
        if (this.items.length + this.uploading.length >= this.max) { this.errors.push(@js($isImages ? __('You can add up to :max images.', ['max' => $max]) : __('You can add up to :max files.', ['max' => $max]))); return; }
        if (file.size > this.maxBytes) { this.errors.push(file.name + ': ' + @js(__('larger than :mb MB.', ['mb' => $maxMb]))); return; }
        this.uploading.push({ name: file.name, progress: 0 });
        const entry = this.uploading[this.uploading.length - 1];
        const xhr = new XMLHttpRequest();
        xhr.open('POST', this.url);
        Object.entries(this.headers()).forEach(([key, value]) => xhr.setRequestHeader(key, value));
        xhr.upload.onprogress = event => { if (event.lengthComputable) entry.progress = Math.round(event.loaded / event.total * 100); };
        xhr.onload = () => {
            this.uploading = this.uploading.filter(u => u !== entry);
            if (xhr.status === 201) { this.items.push(JSON.parse(xhr.responseText)); this.announce(); }
            else this.errors.push(file.name + ': ' + this.problem(xhr));
        };
        xhr.onerror = () => { this.uploading = this.uploading.filter(u => u !== entry); this.errors.push(file.name + ': ' + @js(__('the connection failed.'))); };
        const form = new FormData();
        form.append(this.field, file);
        xhr.send(form);
    },
    async remove(item) {
        const response = await fetch(item.delete_url, { method: 'DELETE', headers: this.headers() });
        if (response.ok) { this.items = this.items.filter(i => i.id !== item.id); this.announce(); }
        else this.errors.push(@js(__('Could not remove that.')));
    },
    async cover(item) {
        const response = await fetch(item.cover_url, { method: 'POST', headers: this.headers() });
        if (response.ok) this.items = [item, ...this.items.filter(i => i.id !== item.id)];
    },
}">
    <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pick($event.dataTransfer.files)"
        :class="dragging ? 'border-secondary bg-teal-50' : 'border-neutral-300 hover:border-neutral-400'"
        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-7 text-center transition-colors">
        <x-icon name="{{ $isImages ? 'photo' : 'cloud-arrow-up' }}" class="h-7 w-7 text-neutral-400" />
        <span class="mt-2 text-sm text-neutral-700"><span class="font-medium text-teal-700">{{ $label }}</span> {{ __('or drag them here') }}</span>
        <span class="mt-0.5 text-xs text-tertiary">{{ $hint }}</span>
        <input type="file" multiple class="sr-only" accept="{{ $accept }}" @change="pick($event.target.files); $event.target.value = ''">
    </label>

    <ul class="mt-3 space-y-1.5" x-show="errors.length" x-cloak role="alert">
        <template x-for="message in errors" :key="message"><li class="text-xs text-red-600" x-text="message"></li></template>
    </ul>

    <ul class="mt-3 space-y-2" x-show="uploading.length" x-cloak>
        <template x-for="entry in uploading" :key="entry.name">
            <li class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm">
                <div class="flex items-center justify-between gap-3"><span class="min-w-0 truncate text-neutral-800" x-text="entry.name"></span><span class="shrink-0 text-xs tabular-nums text-tertiary" x-text="entry.progress + '%'"></span></div>
                <div class="mt-1.5 h-1.5 rounded-full bg-neutral-200"><div class="h-1.5 rounded-full bg-teal-600 transition-all" :style="'width:' + entry.progress + '%'"></div></div>
            </li>
        </template>
    </ul>

    @if ($isImages)
        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3" x-show="items.length" x-cloak>
            <template x-for="(item, index) in items" :key="item.id">
                <li class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100">
                    <img :src="item.url" alt="" class="aspect-[4/3] w-full object-cover">
                    <span x-show="index === 0" class="absolute left-2 top-2 rounded-full bg-teal-600 px-2 py-0.5 text-[11px] font-semibold text-white shadow">{{ __('Cover') }}</span>
                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-neutral-900/70 to-transparent p-2 opacity-100 sm:opacity-0 sm:transition-opacity sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                        <button type="button" x-show="index !== 0" @click="cover(item)" class="rounded-md bg-white/90 px-2 py-1 text-xs font-medium text-neutral-800 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/60">{{ __('Make cover') }}</button>
                        <span x-show="index === 0"></span>
                        <button type="button" @click="remove(item)" class="rounded-md bg-white/90 px-2 py-1 text-xs font-medium text-red-700 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400">{{ __('Remove') }}</button>
                    </div>
                </li>
            </template>
        </ul>
    @else
        <ul class="mt-4 divide-y divide-neutral-100 rounded-xl border border-neutral-200" x-show="items.length" x-cloak>
            <template x-for="item in items" :key="item.id">
                <li class="flex items-center gap-3 px-4 py-2.5 text-sm">
                    <x-icon name="document" class="h-5 w-5 shrink-0 text-neutral-400" />
                    <span class="min-w-0 flex-1 truncate font-medium text-neutral-800" x-text="item.name"></span>
                    <span class="hidden rounded-full bg-neutral-100 px-2 py-0.5 text-xs capitalize text-neutral-600 sm:inline" x-text="item.kind"></span>
                    <span class="shrink-0 text-xs tabular-nums text-tertiary" x-text="item.size"></span>
                    <button type="button" @click="remove(item)" class="shrink-0 text-xs font-medium text-neutral-500 hover:text-red-600 focus:outline-none focus-visible:underline">{{ __('Remove') }}</button>
                </li>
            </template>
        </ul>
    @endif
</div>
