@props(['message', 'ticket', 'staff' => false])

{{-- The files on one ticket message. Names are escaped and only ever shown; a picture is drawn from the authorised route, a PDF is a download link. --}}
@if ($message->attachments->isNotEmpty())
    <ul class="mt-3 flex flex-wrap gap-2">
        @foreach ($message->attachments as $file)
            @php $href = $staff ? route('admin.support.tickets.attachments.show', [$ticket, $file->id]) : route('support.requests.attachments.show', [$ticket, $file->id]); @endphp
            <li>
                @if ($file->isImage())
                    <a href="{{ $href }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-neutral-200 bg-white" title="{{ $file->original_name }}">
                        <img src="{{ $href }}" alt="{{ $file->original_name }}" loading="lazy" class="max-h-40 max-w-[16rem] object-contain">
                    </a>
                @else
                    <a href="{{ $href }}" class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-800 hover:bg-neutral-50">
                        <x-icon name="document" class="h-4 w-4 text-neutral-500" />
                        <span class="max-w-[14rem] truncate">{{ $file->original_name }}</span>
                        <span class="text-xs text-neutral-500">{{ $file->humanSize() }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
@endif
