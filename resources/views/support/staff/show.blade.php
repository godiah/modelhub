@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $status = Tickets::staffStatus($t['status']);
    $isApproval = $t['kind'] === 'approval';
    // Design preview only: the approval screen in each of its states (?state=ready|changed|self|done)
    $state = $isApproval && in_array(request('state'), ['changed', 'self', 'done'], true) ? request('state') : 'ready';
    $sevTone = ['urgent' => 'red', 'high' => 'amber', 'normal' => 'neutral', 'low' => 'neutral'][$t['severity']];

    // The live re-check made at the moment of approval, against what the assistant saw when it asked
    $checks = [
        ['ok', __('Payment is still paid and not refunded'), __('Receipt SIA8K2M771')],
        ['ok', __('Licence is still active'), __('Issued 25 days ago')],
        ['warn', __('Files were downloaded 3 times'), __('Policy says not after download, unless the file is broken or not as described. You decide.')],
        $state === 'changed'
            ? ['bad', __('The seller has withdrawn this money since the request was sent'), __('The refund would fail. Ask a manager how to handle it.')]
            : ['ok', __("Seller's balance covers Ksh434"), __('Nothing withdrawn since the request')],
        ['ok', __('No other refund for this payment'), null],
        $state === 'self'
            ? ['bad', __('This payment belongs to your own account'), __('You cannot approve a request about yourself. Ask a colleague.')]
            : ['ok', __('Not your own account'), null],
    ];
    $blocked = collect($checks)->contains(fn ($c) => $c[0] === 'bad');
@endphp

<x-staff-layout :title="$t['ref']">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8" x-data="{ tab: 'reply', approving: false, declining: false, showChat: false, reason: @js($t['proposed_reason'] ?? ''), assignee: @js($t['assignee'] ?? ''), sev: @js($t['severity']), assigning: false, changingSeverity: false, pick: '', newSev: @js($t['severity']), order: { urgent: 0, high: 1, normal: 2, low: 3 }, label(s) { return s.charAt(0).toUpperCase() + s.slice(1); } }">
        <a href="{{ route('admin.dev.support.tickets') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All requests') }}</a>

        <x-staff.header class="!mb-0" :title="$t['category']">
            <x-slot:badges>
                <x-badge :tone="$status['tone']" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __($status['label']) }}</x-badge>
                <x-badge :tone="$sevTone" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __(ucfirst($t['severity'])) }}</x-badge>
                @if ($isApproval)<x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __('Approval') }}</x-badge>@endif
            </x-slot:badges>
            <span class="font-mono">{{ $t['ref'] }}</span> · {{ $t['source'] === 'bot' ? __('Sent by the assistant') : __('Sent from the contact form') }} · {{ __('Updated') }} {{ $t['updated'] }}
        </x-staff.header>

        @if ($isApproval)
            <nav aria-label="Preview state" class="flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-neutral-300 bg-white/60 px-3 py-2 text-xs text-neutral-600">
                <span class="font-medium text-neutral-700">Design preview, show the screen when:</span>
                @foreach (['ready' => 'all checks pass', 'changed' => 'something changed', 'self' => 'it is your own account', 'done' => 'already approved'] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['state' => $key]) }}" @class(['rounded-full px-2.5 py-1 font-medium', 'bg-teal-600 text-white' => $state === $key, 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200' => $state !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
        @endif

        @unless ($t['verified'])
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <p class="font-semibold">{{ __('Nobody has checked who sent this.') }}</p>
                <p class="mt-1">{{ __('It came from the contact form, from someone who was not signed in. Do not act on anything it asks for. If it concerns an account, call or email the details already on that account, not the ones typed here.') }}</p>
            </div>
        @endunless

        @if ($isApproval && $state === 'changed')
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900" role="alert">
                <p class="font-semibold">{{ __('Something changed since this was requested.') }}</p>
                <p class="mt-1">{{ __('Approving is switched off until you have looked at the checks below. Nothing was recorded.') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            {{-- Left: what was asked, the conversation, and the reply --}}
            <div class="space-y-6 lg:col-span-2">
                <x-panel :title="__('What was sent')">
                    <p class="border-l-2 border-neutral-200 pl-3 text-[15px] leading-snug text-neutral-800">{{ $t['summary'] }}</p>
                    @if ($t['entities'])
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($t['entities'] as $e)
                                <li><a href="#" class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs text-neutral-700 hover:border-neutral-300 hover:text-neutral-900"><x-icon :name="$e['icon']" class="h-3.5 w-3.5 text-neutral-400" />{{ $e['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>

                @if ($t['cards'] || $t['facts'])
                    <x-panel :title="__('What the system saw')" :description="__('Collected by the system when the request was made. The assistant did not write this.')">
                        <div class="space-y-4">
                            @foreach ($t['cards'] as $card)
                                <x-support.card-static :card="$card" />
                            @endforeach
                            @if ($t['facts'])
                                <dl class="space-y-2 text-sm">
                                    @foreach ($t['facts'] as $fact)
                                        <div class="flex items-baseline justify-between gap-4"><dt class="shrink-0 text-neutral-500">{{ $fact['k'] }}</dt><dd @class(['text-right font-medium', 'text-neutral-900' => empty($fact['flag']), 'text-amber-800' => ! empty($fact['flag'])])>{{ $fact['v'] }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    </x-panel>
                @endif

                <x-panel :title="__('Conversation')">
                    <x-support.thread :messages="$t['messages']" audience="staff" />
                </x-panel>

                <x-panel :title="__('Reply')">
                    <div class="mb-4 inline-flex rounded-lg bg-neutral-100 p-0.5 text-sm font-medium" role="tablist">
                        <button type="button" role="tab" @click="tab = 'reply'" :aria-selected="(tab === 'reply').toString()" :class="tab === 'reply' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'" class="rounded-md px-3 py-1.5 transition">{{ __('Reply to member') }}</button>
                        <button type="button" role="tab" @click="tab = 'note'" :aria-selected="(tab === 'note').toString()" :class="tab === 'note' ? 'bg-amber-100 text-amber-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'" class="rounded-md px-3 py-1.5 transition">{{ __('Internal note') }}</button>
                    </div>

                    <form class="space-y-4" @submit.prevent>
                        <div class="flex flex-wrap items-center gap-3" x-show="tab === 'reply'">
                            <label for="macro" class="text-sm text-neutral-600">{{ __('Saved reply') }}</label>
                            <select id="macro" class="min-w-0 max-w-full rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                                <option>{{ __('Choose one…') }}</option>
                                <option>{{ __('Payment needs review: what happens next') }}</option>
                                <option>{{ __('Refund: how it is paid back') }}</option>
                                <option>{{ __('Lock-out: we will call you back') }}</option>
                                <option>{{ __('Withdrawal: why it is still being sent') }}</option>
                            </select>
                        </div>
                        <p x-show="tab === 'note'" x-cloak class="text-sm text-amber-900">{{ __('Only staff can read internal notes.') }}</p>
                        <label for="reply-body" class="sr-only">{{ __('Message') }}</label>
                        <textarea id="reply-body" rows="5" :placeholder="tab === 'reply' ? '{{ __('Write to the member') }}' : '{{ __('Write a note for the team') }}'"
                            :class="tab === 'note' ? 'border-amber-300 bg-amber-50 focus:border-amber-500 focus:ring-amber-500' : 'border-neutral-300 focus:border-teal-600 focus:ring-teal-600'"
                            class="block w-full rounded-xl text-[15px] leading-snug"></textarea>
                        @unless ($t['verified'])
                            <p x-show="tab === 'reply'" class="text-xs text-amber-800">{{ __('This reply goes to the email typed into the form. Do not share account details in it.') }}</p>
                        @endunless
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2" x-show="tab === 'reply'">
                                <label for="resolution" class="text-sm text-neutral-600">{{ __('If resolving, why?') }}</label>
                                <select id="resolution" class="min-w-0 max-w-full rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                                    <option>{{ __('The assistant could have answered') }}</option>
                                    <option>{{ __('A help article is missing') }}</option>
                                    <option>{{ __('The assistant lacked a tool') }}</option>
                                    <option>{{ __('Policy decision') }}</option>
                                    <option>{{ __('A bug in ModelHub') }}</option>
                                    <option>{{ __('Something else') }}</option>
                                </select>
                            </div>
                            <span x-show="tab === 'note'"></span>
                            <div class="flex items-center gap-2">
                                <x-btn type="button" variant="secondary" x-show="tab === 'reply'">{{ __('Send and resolve') }}</x-btn>
                                <x-btn type="button"><span x-text="tab === 'reply' ? '{{ __('Send reply') }}' : '{{ __('Save note') }}'"></span></x-btn>
                            </div>
                        </div>
                    </form>
                </x-panel>
            </div>

            {{-- Right: the decision (if any), who, and the evidence --}}
            <div class="space-y-6">
                @if ($isApproval)
                    @if ($state === 'done')
                        <x-panel :title="__('Refund recorded')">
                            <p class="text-sm text-neutral-700">{{ __('Recorded by :name on :date.', ['name' => 'Grace W.', 'date' => '3 Oct, 15:20']) }}</p>
                            <p class="mt-2 rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">{{ $t['proposed_reason'] }}</p>
                            <dl class="mt-4 space-y-1.5 border-t border-neutral-100 pt-4 text-xs text-neutral-500">
                                <div class="flex justify-between gap-3"><dt>{{ __('Asked for by') }}</dt><dd class="text-neutral-700">{{ __('The assistant') }}</dd></div>
                                <div class="flex justify-between gap-3"><dt>{{ __('Approved by') }}</dt><dd class="text-neutral-700">Grace W.</dd></div>
                                <div class="flex justify-between gap-3"><dt>{{ __('Activity log') }}</dt><dd><a href="#" class="font-medium text-teal-700 hover:underline">payment.refunded</a></dd></div>
                            </dl>
                            <p class="mt-4 text-sm text-amber-800">{{ __('Now send the money back from the M-Pesa portal (reverse receipt :receipt).', ['receipt' => 'SIA8K2M771']) }}</p>
                        </x-panel>
                    @else
                        <x-panel :title="__('Refund request')" :description="__('Asked for by the assistant. Nothing happens until you approve it.')">
                            <h3 class="text-sm font-semibold text-neutral-900">{{ __('Checked just now') }}</h3>
                            <ul class="mt-2 divide-y divide-neutral-100">
                                @foreach ($checks as [$kind, $label, $detail])
                                    <li class="flex items-start gap-3 py-2.5">
                                        <span aria-hidden="true" @class([
                                            'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full',
                                            'bg-teal-600 text-white' => $kind === 'ok',
                                            'bg-amber-100 text-amber-700 ring-2 ring-amber-500' => $kind === 'warn',
                                            'bg-red-600 text-white' => $kind === 'bad',
                                        ])>
                                            @if ($kind === 'ok')<x-icon name="check-solid" class="h-3 w-3" />@elseif ($kind === 'bad')<x-icon name="x-mark" class="h-3 w-3" />@else<span class="text-[11px] font-bold leading-none">!</span>@endif
                                        </span>
                                        <div class="min-w-0">
                                            <p @class(['text-sm leading-5', 'text-neutral-700' => $kind === 'ok', 'font-semibold text-amber-900' => $kind === 'warn', 'font-semibold text-red-800' => $kind === 'bad'])>{{ $label }}<span class="sr-only">: {{ ['ok' => __('passed'), 'warn' => __('needs your judgement'), 'bad' => __('failed')][$kind] }}</span></p>
                                            @if ($detail)<p @class(['text-xs leading-snug', 'text-neutral-500' => $kind === 'ok', 'text-amber-800' => $kind === 'warn', 'text-red-700' => $kind === 'bad'])>{{ $detail }}</p>@endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-4">
                                <label for="refund-reason" class="block text-sm font-medium text-neutral-800">{{ __('Reason the buyer will see') }}</label>
                                <textarea id="refund-reason" x-model="reason" rows="3" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm leading-snug focus:border-teal-600 focus:ring-teal-600"></textarea>
                                <p class="mt-1 text-xs text-neutral-500">{{ __('The assistant suggested this. Edit it: it is shown to the buyer and saved on the licence.') }}</p>
                            </div>

                            <ul class="mt-4 space-y-1.5 text-sm text-neutral-700">
                                <li>{{ __('The licence ends and the files can no longer be downloaded.') }}</li>
                                <li>{{ __('Ksh434 is taken back from the seller\'s earnings and the Ksh76 commission is reversed.') }}</li>
                                <li>{{ __('Send the money back from the M-Pesa portal (reverse receipt :receipt). Recording the refund keeps the books right.', ['receipt' => 'SIA8K2M771']) }}</li>
                            </ul>

                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                <x-btn type="button" variant="danger-outline" @click="approving = true" :disabled="$blocked">{{ __('Approve and record refund') }}</x-btn>
                                <x-btn type="button" variant="ghost" @click="declining = true">{{ __('Decline') }}</x-btn>
                            </div>
                            @if ($blocked)<p class="mt-2 text-xs text-red-700">{{ __('Approving is off while a check has failed.') }}</p>@endif
                        </x-panel>
                    @endif
                @endif

                <x-panel :title="__('Who')">
                    <p class="text-sm font-semibold text-neutral-900">{{ $t['requester']['name'] }}</p>
                    <p class="mt-0.5 text-sm text-neutral-600">{{ $t['requester']['contact'] }}</p>
                    <p class="mt-1 text-xs text-neutral-500">{{ $t['requester']['since'] }}</p>
                    <p class="mt-3">
                        @if ($t['verified'])
                            <x-badge tone="green" class="px-2 py-0.5 text-xs font-medium">{{ __('Signed-in member') }}</x-badge>
                            <a href="#" class="ml-2 text-sm font-medium text-teal-700 hover:underline">{{ __('Open member') }}</a>
                        @else
                            <x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Unverified') }}</x-badge>
                        @endif
                    </p>
                </x-panel>

                @if ($t['rationale'])
                    <x-panel :title="__('The assistant\'s note')" :description="__('Written by the assistant and not checked. Use the facts above, not this.')">
                        <p class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-3 py-2 text-sm italic leading-snug text-neutral-700">{{ $t['rationale'] }}</p>
                    </x-panel>
                @endif

                @if ($t['transcript'])
                    <x-panel :title="__('Chat with the assistant')">
                        <x-btn type="button" variant="secondary" @click="showChat = ! showChat"><span x-text="showChat ? '{{ __('Hide the chat') }}' : '{{ __('Read the chat') }}'"></span></x-btn>
                        <p class="mt-2 text-xs text-neutral-500">{{ __('Reading the chat is recorded in the activity log.') }}</p>
                        <ol x-show="showChat" x-cloak class="mt-4 space-y-3 border-t border-neutral-100 pt-4">
                            @foreach ($t['transcript'] as $line)
                                <li class="text-sm leading-snug"><span class="font-semibold text-neutral-900">{{ $line['who'] === 'You' ? __('Member') : $line['who'] }}</span><span class="text-neutral-400"> · </span><span class="text-neutral-700">{{ $line['text'] }}</span></li>
                            @endforeach
                        </ol>
                    </x-panel>
                @endif

                <x-panel :title="__('Handling')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-neutral-500">{{ __('Assigned to') }}</dt><dd class="font-medium text-neutral-900" x-text="assignee || '{{ __('Nobody yet') }}'"></dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-neutral-500">{{ __('Severity') }}</dt><dd class="font-medium text-neutral-900" x-text="label(sev)"></dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-btn type="button" variant="secondary" size="sm" @click="assignee = 'Grace W.'" x-show="assignee !== 'Grace W.'">{{ __('Take this') }}</x-btn>
                        <x-btn type="button" variant="secondary" size="sm" @click="pick = assignee; assigning = true"><span x-text="assignee ? '{{ __('Reassign…') }}' : '{{ __('Assign…') }}'"></span></x-btn>
                        <x-btn type="button" variant="secondary" size="sm" @click="newSev = sev; changingSeverity = true">{{ __('Change severity…') }}</x-btn>
                    </div>
                </x-panel>
            </div>
        </div>

        @if ($isApproval)
            <x-modal name="approve-refund" bind="approving" max-width="lg">
                <x-modal.header :title="__('Record this refund?')" icon="banknotes" />
                <div class="space-y-3 px-6 py-5 text-sm text-neutral-700">
                    <p>{{ __('You are recording a refund of Ksh510 for Alarm Clock 01 – Clock Time PBR. This cannot be undone.') }}</p>
                    <p class="rounded-lg bg-neutral-50 px-3 py-2" x-text="reason"></p>
                    <p class="text-amber-800">{{ __('Recording it does not send money. You still return it from the M-Pesa portal.') }}</p>
                </div>
                <x-modal.footer>
                    <x-btn type="button" variant="secondary" @click="approving = false">{{ __('Cancel') }}</x-btn>
                    <x-btn type="button" variant="danger" @click="approving = false">{{ __('Record refund') }}</x-btn>
                </x-modal.footer>
            </x-modal>

            <x-modal name="decline-refund" bind="declining" max-width="lg">
                <x-modal.header :title="__('Decline this request?')" icon="x-mark" />
                <div class="space-y-3 px-6 py-5 text-sm text-neutral-700">
                    <label for="decline-reason" class="block font-medium text-neutral-800">{{ __('Tell the buyer why') }}</label>
                    <textarea id="decline-reason" rows="3" class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                    <p class="text-xs text-neutral-500">{{ __('They get this in a reply on their request. Nothing is refunded.') }}</p>
                </div>
                <x-modal.footer>
                    <x-btn type="button" variant="secondary" @click="declining = false">{{ __('Cancel') }}</x-btn>
                    <x-btn type="button" @click="declining = false">{{ __('Decline and reply') }}</x-btn>
                </x-modal.footer>
            </x-modal>
        @endif

        <x-modal name="assign-request" bind="assigning" max-width="md">
            <x-modal.header :title="__('Assign this request')" icon="user" />
            <div class="space-y-4 px-6 py-5">
                <fieldset class="space-y-2">
                    <legend class="sr-only">{{ __('Assign to') }}</legend>
                    @foreach (Tickets::team() as $person)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 p-3 text-sm transition has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/60 hover:border-neutral-300">
                            <input type="radio" name="assignee" value="{{ $person['name'] }}" x-model="pick" class="text-teal-600 focus:ring-teal-600/30">
                            <span aria-hidden="true" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700">{{ mb_substr($person['name'], 0, 1) }}</span>
                            <span class="min-w-0 flex-1"><span class="block font-medium text-neutral-900">{{ $person['name'] }}</span><span class="block text-xs text-neutral-500">{{ $person['role'] }}</span></span>
                            <span class="text-right text-xs tabular-nums text-neutral-500">{{ $person['open'] }} {{ __('open') }}@if ($person['overdue'])<span class="block font-medium text-red-700">{{ $person['overdue'] }} {{ __('overdue') }}</span>@endif</span>
                        </label>
                    @endforeach
                </fieldset>
                <div>
                    <label for="assign-note" class="block text-sm font-medium text-neutral-800">{{ __('Note for them') }} <span class="font-normal text-neutral-500">({{ __('optional') }})</span></label>
                    <textarea id="assign-note" rows="2" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm leading-snug focus:border-teal-600 focus:ring-teal-600" placeholder="{{ __('What they need to know before they start') }}"></textarea>
                    <p class="mt-1 text-xs text-neutral-500">{{ __('Saved as an internal note. They are told they have a new request.') }}</p>
                </div>
            </div>
            <x-modal.footer>
                <x-btn type="button" variant="secondary" @click="assigning = false">{{ __('Cancel') }}</x-btn>
                <x-btn type="button" x-bind:disabled="! pick" @click="assignee = pick; assigning = false">{{ __('Assign') }}</x-btn>
            </x-modal.footer>
        </x-modal>

        <x-modal name="change-severity" bind="changingSeverity" max-width="md">
            <x-modal.header :title="__('Change severity')" icon="clock" />
            <div class="space-y-4 px-6 py-5">
                <p class="text-sm text-neutral-600">{{ __('Severity sets how soon a reply is due. Money and safety requests are raised automatically.') }}</p>
                <fieldset class="space-y-2">
                    <legend class="sr-only">{{ __('Severity') }}</legend>
                    @foreach (Tickets::serviceLevels() as $level)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 p-3 text-sm transition has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/60 hover:border-neutral-300">
                            <input type="radio" name="severity" value="{{ $level['severity'] }}" x-model="newSev" class="text-teal-600 focus:ring-teal-600/30">
                            <span class="min-w-0 flex-1"><span class="block font-medium text-neutral-900">{{ __($level['label']) }}</span><span class="block text-xs text-neutral-500">{{ __('First reply within :t', ['t' => $level['first']]) }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <div x-show="order[newSev] > order[sev]" x-cloak>
                    <label for="sev-reason" class="block text-sm font-medium text-neutral-800">{{ __('Why lower it?') }}</label>
                    <textarea id="sev-reason" rows="2" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm leading-snug focus:border-teal-600 focus:ring-teal-600"></textarea>
                    <p class="mt-1 text-xs text-neutral-500">{{ __('Lowering severity needs a reason. It is saved in the activity log.') }}</p>
                </div>
            </div>
            <x-modal.footer>
                <x-btn type="button" variant="secondary" @click="changingSeverity = false">{{ __('Cancel') }}</x-btn>
                <x-btn type="button" @click="sev = newSev; changingSeverity = false">{{ __('Change severity') }}</x-btn>
            </x-modal.footer>
        </x-modal>
    </div>
</x-staff-layout>
