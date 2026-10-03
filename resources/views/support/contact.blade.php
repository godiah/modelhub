@php
    // value => [title, one line, icon]. Each topic changes what the form asks for and what it promises.
    $topics = [
        'access' => [__("I can't sign in"), __('Locked out, lost your phone, or no recovery codes'), 'lock-closed'],
        'suspended' => [__('My account or store was suspended'), __('Ask staff to take another look'), 'user'],
        'payment' => [__('A payment'), __('Paid but nothing happened, wrong amount, a receipt'), 'currency-dollar'],
        'withdrawal' => [__('A withdrawal'), __('Stuck, rejected, or not received'), 'banknotes'],
        'abuse' => [__('A scam or abuse'), __('Someone asked you to pay outside ModelHub, or is pretending to be someone else'), 'exclamation-triangle'],
        'copyright' => [__('A copyright claim'), __('A model on ModelHub copies your work'), 'document-text'],
        'data' => [__('My personal data'), __('Get a copy of your data, or ask for it to be deleted'), 'shield-check'],
        'other' => [__('Something else'), __('Anything these do not cover'), 'chat-bubble-text'],
    ];

    $input = 'mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600';
@endphp

<x-app-layout :title="__('Contact support')" crumb="Contact support">
    <div class="container mx-auto max-w-3xl px-4 py-10" x-data="{ topic: '{{ request('topic', '') }}', sent: false, files: [], email: '' }">
        <header>
            <h1 class="font-tertiary text-3xl font-bold tracking-tight text-neutral-900">{{ __('Contact ModelHub support') }}</h1>
            <p class="mt-2 max-w-xl text-neutral-600">{{ __('Tell us what happened and a person will reply by email. If you can sign in, the Help button at the bottom right is quicker, and it can look at your account.') }}</p>
        </header>

        {{-- After sending --}}
        <section x-show="sent" x-cloak class="mt-8 overflow-hidden rounded-2xl border border-teal-200 bg-white shadow-sm" aria-live="polite">
            <div class="flex items-center gap-3 border-b border-teal-100 bg-teal-50 px-6 py-4">
                <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-600 text-white"><x-icon name="check-solid" class="h-5 w-5" /></span>
                <div>
                    <h2 class="font-tertiary text-lg font-semibold text-teal-900">{{ __('We have your request') }}</h2>
                    <p class="text-sm text-teal-800">{{ __('Reference') }} <span class="font-semibold tabular-nums">SUP-1052</span></p>
                </div>
            </div>
            <div class="space-y-5 p-6">
                <p class="text-neutral-700">{{ __('We will reply to') }} <span class="font-medium text-neutral-900" x-text="email || '{{ __('the email you gave') }}'"></span>. {{ __('Keep your reference handy if you write to us again.') }}</p>
                <ol class="space-y-3 text-sm text-neutral-700">
                    <li class="flex gap-3"><span aria-hidden="true" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-white"><x-icon name="check-solid" class="h-3 w-3" /></span>{{ __('Your request reached staff.') }}</li>
                    <li class="flex gap-3"><span aria-hidden="true" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white ring-2 ring-teal-600"><span class="h-2 w-2 rounded-full bg-teal-600"></span></span>{{ __('A person reads it. First reply within 4 business hours (weekdays, 8am to 6pm EAT).') }}</li>
                    <li class="flex gap-3"><span aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 rounded-full bg-white ring-2 ring-neutral-300"></span><span class="text-neutral-500">{{ __('For account matters, staff first check it is really you, using the email or phone already on the account.') }}</span></li>
                </ol>
                <div class="flex flex-wrap gap-3">
                    <x-btn href="{{ route('dev.support.contact') }}" variant="secondary">{{ __('Send another request') }}</x-btn>
                </div>
            </div>
        </section>

        <form x-show="! sent" @submit.prevent="sent = true; window.scrollTo({ top: 0, behavior: 'smooth' })" class="mt-8 space-y-8">
            <fieldset>
                <legend class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What is it about?') }}</legend>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($topics as $value => [$title, $line, $icon])
                        <label class="relative block cursor-pointer">
                            <input type="radio" name="topic" value="{{ $value }}" x-model="topic" class="peer sr-only" required>
                            <span class="flex h-full items-start gap-3 rounded-xl border border-neutral-200 bg-white p-4 transition peer-checked:border-teal-600 peer-checked:bg-teal-50/60 peer-checked:ring-1 peer-checked:ring-teal-600 peer-hover:border-neutral-300 peer-focus-visible:ring-4 peer-focus-visible:ring-secondary/30">
                                <span aria-hidden="true" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                                <span class="min-w-0">
                                    <span class="block font-secondary text-sm font-semibold text-neutral-900">{{ $title }}</span>
                                    <span class="mt-0.5 block text-sm leading-snug text-neutral-600">{{ $line }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div x-show="topic" x-cloak class="space-y-8">
                {{-- Say plainly what this topic can and cannot do, before they type anything --}}
                <div x-show="topic === 'access'" class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-snug text-amber-950">
                    <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                    <p>{{ __("We can't change anything on an account from this form alone. A person will contact you using the email or phone already on the account to check it is really you, then help you back in.") }}</p>
                </div>
                <div x-show="topic === 'abuse'" class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-snug text-amber-950">
                    <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                    <p>{{ __('Do not pay anyone outside ModelHub. Payments made outside it are not protected by escrow, and we cannot get them back.') }}</p>
                </div>
                <div x-show="topic === 'data'" class="flex gap-3 rounded-xl border border-neutral-200 bg-white p-4 text-sm leading-snug text-neutral-700">
                    <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                    <p>{{ __('We will confirm we have your request and check it is really you before we send or delete anything. Some records, such as payments, may have to be kept by law.') }}</p>
                </div>

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Your details') }}</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="c-name" class="block text-sm font-medium text-neutral-700">{{ __('Your name') }}</label>
                            <input id="c-name" type="text" autocomplete="name" required class="{{ $input }}">
                        </div>
                        <div>
                            <label for="c-email" class="block text-sm font-medium text-neutral-700"><span x-text="topic === 'access' ? '{{ __('Email on the account') }}' : '{{ __('Email') }}'"></span></label>
                            <input id="c-email" type="email" x-model="email" autocomplete="email" required class="{{ $input }}">
                            <p class="mt-1 text-xs text-neutral-500">{{ __('We reply here. We never ask for your password, PIN or codes.') }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What happened?') }}</h2>
                    <div class="mt-4 space-y-4">
                        <div x-show="topic === 'payment' || topic === 'withdrawal'">
                            <label for="c-ref" class="block text-sm font-medium text-neutral-700">{{ __('Payment reference or M-Pesa receipt code') }} <span class="font-normal text-neutral-500">({{ __('if you have one') }})</span></label>
                            <input id="c-ref" type="text" placeholder="MH7K2P9QX1" class="{{ $input }} sm:max-w-xs">
                        </div>

                        <div x-show="topic === 'abuse'">
                            <label for="c-who" class="block text-sm font-medium text-neutral-700">{{ __('Link or name of the person or listing') }}</label>
                            <input id="c-who" type="text" class="{{ $input }}">
                        </div>

                        <fieldset x-show="topic === 'data'">
                            <legend class="text-sm font-medium text-neutral-700">{{ __('What would you like?') }}</legend>
                            <div class="mt-2 space-y-2 text-sm text-neutral-700">
                                <label class="flex items-center gap-2"><input type="radio" name="data-kind" class="text-teal-600 focus:ring-teal-600">{{ __('A copy of my data') }}</label>
                                <label class="flex items-center gap-2"><input type="radio" name="data-kind" class="text-teal-600 focus:ring-teal-600">{{ __('Delete my data') }}</label>
                            </div>
                        </fieldset>

                        <div x-show="topic === 'copyright'" class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="c-owner" class="block text-sm font-medium text-neutral-700">{{ __('Who owns the work?') }}</label>
                                    <input id="c-owner" type="text" class="{{ $input }}">
                                    <p class="mt-1 text-xs text-neutral-500">{{ __('You, or the person or company you act for.') }}</p>
                                </div>
                                <div>
                                    <label for="c-link" class="block text-sm font-medium text-neutral-700">{{ __('Link to the ModelHub listing') }}</label>
                                    <input id="c-link" type="url" placeholder="https://" class="{{ $input }}">
                                </div>
                            </div>
                            <label class="flex items-start gap-2 text-sm text-neutral-700"><input type="checkbox" class="mt-0.5 rounded border-neutral-300 text-teal-600 focus:ring-teal-600">{{ __('I confirm this information is accurate and I am allowed to act for the owner of the work.') }}</label>
                            <p class="text-xs text-neutral-500">{{ __('Staff review each claim. We do not decide who is right from this form.') }}</p>
                        </div>

                        <div>
                            <label for="c-message" class="block text-sm font-medium text-neutral-700"><span x-text="topic === 'copyright' ? '{{ __('What was copied, and how can we see it is yours?') }}' : '{{ __('Tell us what happened') }}'"></span></label>
                            <textarea id="c-message" rows="5" required class="{{ $input }} leading-snug"></textarea>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-neutral-500"><x-icon name="shield-check" class="h-3.5 w-3.5 text-teal-600" />{{ __('Never include your M-Pesa PIN or a code from an SMS.') }}</p>
                        </div>

                        <div>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-600 transition hover:border-teal-400 hover:bg-teal-50/40 focus-within:ring-4 focus-within:ring-secondary/20">
                                <x-icon name="photo" class="h-5 w-5 text-neutral-400" />
                                <span><span class="font-medium text-teal-700">{{ __('Add screenshots or a PDF') }}</span> <span class="text-neutral-500">{{ __('(optional, up to 5 files, 10 MB each)') }}</span></span>
                                <input type="file" multiple accept="image/png,image/jpeg,application/pdf" class="sr-only" @change="files = [...$event.target.files].map(f => f.name)">
                            </label>
                            <ul class="mt-2 flex flex-wrap gap-1.5" x-show="files.length" x-cloak>
                                <template x-for="f in files"><li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs text-neutral-700"><x-icon name="document" class="h-3.5 w-3.5 text-neutral-400" /><span x-text="f"></span></li></template>
                            </ul>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="max-w-md text-sm text-neutral-600">{{ __('A person reads every request. First reply within 4 business hours (weekdays, 8am to 6pm EAT).') }}</p>
                    <x-btn type="submit" size="lg">{{ __('Send to staff') }}</x-btn>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
