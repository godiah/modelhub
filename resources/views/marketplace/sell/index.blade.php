@use('App\Enums\SellerStatus')
@php
    $status = $profile?->status;
    $canApply = ! $profile || $profile->canReapply();
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $steps = [
        [__('Apply'), __('Tell us who you are and what you plan to sell.')],
        [__('Get reviewed'), __('We check every seller so buyers can trust what they download.')],
        [__('List your models'), __('When the marketplace opens, approved sellers add their first models.')],
    ];
@endphp
<x-app-layout title="Sell models">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Sell your 3D models') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('A marketplace for 3D models is coming to :app. Apply now to be among the first approved sellers.', ['app' => config('app.name')]) }}</p>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                {{-- Where the member stands --}}
                @if ($profile)
                    <x-panel :title="__('Your seller application')">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-badge :tone="$status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($status->label()) }}</x-badge>
                            <span class="text-sm text-tertiary">{{ __('Store name: :name', ['name' => $profile->display_name]) }}</span>
                        </div>

                        <p class="mt-4 text-sm leading-relaxed text-neutral-700">
                            @switch($status)
                                @case(SellerStatus::Pending)
                                    {{ __('Thanks, we have your application. A reviewer will look at it soon and we will tell you the outcome by email and in your notifications.') }}
                                    @break
                                @case(SellerStatus::Approved)
                                    {{ __('You are approved to sell. You will be able to add your first model as soon as the marketplace opens, and we will let you know.') }}
                                    @break
                                @case(SellerStatus::Rejected)
                                    {{ __('We could not approve your application this time. You can improve it below and send it again.') }}
                                    @break
                                @case(SellerStatus::Suspended)
                                    {{ __('Your seller account is suspended, so you cannot list or sell models for now.') }}
                                    @break
                            @endswitch
                        </p>

                        @if ($profile->review_notes && in_array($status, [SellerStatus::Rejected, SellerStatus::Suspended], true))
                            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                                <p class="font-medium">{{ __('Reason from the reviewer') }}</p>
                                <p class="mt-1 whitespace-pre-line break-words">{{ $profile->review_notes }}</p>
                            </div>
                        @endif

                        <dl class="mt-5 grid gap-4 border-t border-neutral-100 pt-5 text-sm sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-tertiary">{{ __('About you') }}</dt>
                                <dd class="mt-1 whitespace-pre-line break-words text-neutral-800">{{ $profile->bio }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-tertiary">{{ __('What you plan to sell') }}</dt>
                                <dd class="mt-1 whitespace-pre-line break-words text-neutral-800">{{ $profile->focus }}</dd>
                            </div>
                            @if ($profile->portfolio_url)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-tertiary">{{ __('Portfolio') }}</dt>
                                    <dd class="mt-1 break-all"><a href="{{ $profile->portfolio_url }}" target="_blank" rel="noopener nofollow" class="text-teal-700 hover:underline">{{ $profile->portfolio_url }}</a></dd>
                                </div>
                            @endif
                        </dl>
                    </x-panel>
                @endif

                {{-- The application form: first time, or again after a rejection --}}
                @if ($canApply)
                    <form action="{{ route('seller.apply') }}" method="POST">
                        @csrf
                        <x-panel :title="$profile ? __('Update and send again') : __('Apply to sell')" :description="__('Takes a couple of minutes. Reviewers read every application.')">
                            <div class="space-y-5">
                                <div>
                                    <label for="display_name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Store name') }}</label>
                                    <input type="text" id="display_name" name="display_name" value="{{ old('display_name', $profile?->display_name) }}" maxlength="60" required
                                        class="{{ $fieldClass }} {{ $errors->has('display_name') ? $badField : $okField }}" @if ($errors->has('display_name')) aria-invalid="true" @endif>
                                    @error('display_name')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    <p class="mt-1.5 text-xs text-tertiary">{{ __('Shown to buyers on your models. It must be unique.') }}</p>
                                </div>

                                <div>
                                    <label for="bio" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('About you') }}</label>
                                    <textarea id="bio" name="bio" rows="4" maxlength="1000" required placeholder="{{ __('Your experience, the work you are proud of, the tools you use…') }}"
                                        class="{{ $fieldClass }} {{ $errors->has('bio') ? $badField : $okField }} resize-none">{{ old('bio', $profile?->bio) }}</textarea>
                                    @error('bio')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="focus" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('What do you plan to sell?') }}</label>
                                    <textarea id="focus" name="focus" rows="3" maxlength="500" required placeholder="{{ __('For example: furniture and interior props for architectural visualisation, as FBX and Blender files.') }}"
                                        class="{{ $fieldClass }} {{ $errors->has('focus') ? $badField : $okField }} resize-none">{{ old('focus', $profile?->focus) }}</textarea>
                                    @error('focus')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="portfolio_url" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Portfolio link') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></label>
                                    <input type="url" id="portfolio_url" name="portfolio_url" value="{{ old('portfolio_url', $profile?->portfolio_url) }}" maxlength="255" placeholder="https://"
                                        class="{{ $fieldClass }} {{ $errors->has('portfolio_url') ? $badField : $okField }}">
                                    @error('portfolio_url')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="flex items-start gap-2.5 text-sm text-neutral-700">
                                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                        <span>{{ __('I agree to the') }} <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-medium text-teal-700 hover:underline">{{ __('Terms of Service') }}</a>. {{ __('I understand that separate seller terms, covering licences, commission and payouts, will be shown to me before I publish my first model.') }}</span>
                                    </label>
                                    @error('terms')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <x-slot:footer>
                                <span class="text-xs text-tertiary">{{ __('You can only have one application at a time.') }}</span>
                                <x-btn type="submit">
                                    <x-icon name="paper-airplane" class="h-4 w-4" />
                                    {{ $profile ? __('Send again') : __('Send application') }}
                                </x-btn>
                            </x-slot:footer>
                        </x-panel>
                    </form>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel :title="__('How it works')">
                    <ol class="space-y-4">
                        @foreach ($steps as [$title, $text])
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-semibold text-white">{{ $loop->iteration }}</span>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">{{ $title }}</p>
                                    <p class="mt-0.5 text-sm text-tertiary">{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-panel>
                <x-panel :title="__('Good to know')">
                    <ul class="space-y-2.5 text-sm text-neutral-700">
                        <li>{{ __('Only approved sellers can list models.') }}</li>
                        <li>{{ __('You keep the rights to your work. Buyers get a licence to use it.') }}</li>
                        <li>{{ __('Commission and payout terms are shown before you publish.') }}</li>
                    </ul>
                </x-panel>
            </aside>
        </div>
    </div>
</x-app-layout>
