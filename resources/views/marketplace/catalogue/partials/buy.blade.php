{{-- The buy box on a model's page: sign in, take a free licence, or pay for a Standard or Extended one by M-Pesa. Needs $product and $preview. --}}
@php
    use App\Enums\LicenceTier;
    use App\Support\Money;

    $user = auth()->user();
    $held = $user ? $user->issuedLicences()->active()->where('product_id', $product->id)->latest('issued_at')->get() : collect();
    $hasExtended = $held->contains(fn ($l) => $l->tier === LicenceTier::Extended);
    $isOwner = $user && $product->user_id === $user->id;
    $free = $product->isFree();
    // What this person can still buy: both tiers, or just Extended as an upgrade from Standard, or nothing
    $options = collect($product->offeredLicences())->reject(fn ($t) => $t === LicenceTier::Standard && $held->isNotEmpty())->reject(fn ($t) => $t === LicenceTier::Extended && $hasExtended)->values();
    $phone = old('phone', $user?->profile?->telephone_number);
@endphp

@if ($preview)
    <x-btn block size="lg" class="mt-3" type="button" disabled>{{ __('Preview only') }}</x-btn>
@elseif (! config('marketplace.purchases_enabled'))
    <x-btn block size="lg" class="mt-3" type="button" disabled>{{ __('Purchases open soon') }}</x-btn>
    <p class="mt-2 text-center text-xs text-tertiary">{{ __('Checkout is not open yet. Models are visible so sellers can see how their listings look.') }}</p>
@elseif (! $user)
    <x-btn block size="lg" class="mt-3" :href="route('login')">{{ $free ? __('Sign in to get this model') : __('Sign in to buy') }}</x-btn>
@elseif ($isOwner)
    <p class="mt-3 rounded-xl bg-neutral-50 px-4 py-3 text-center text-sm text-neutral-700">{{ __('This is your model.') }}</p>
@else
    @if ($held->isNotEmpty())
        <div class="mt-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm">
            <p class="font-semibold text-teal-900">{{ __('You own this model') }}</p>
            <p class="mt-0.5 text-teal-800">{{ __('Your :tier licence is ready.', ['tier' => $held->first()->tier->label()]) }}</p>
            <x-btn block class="mt-3" :href="route('licences.show', $held->first())" wire:navigate>{{ __('Open licence and files') }}</x-btn>
        </div>
    @endif

    @if ($options->isNotEmpty() && $free)
        <form method="POST" action="{{ route('checkout.free', $product) }}" class="mt-3">@csrf<x-btn block size="lg" type="submit">{{ __('Get the free licence') }}</x-btn></form>
    @elseif ($options->isNotEmpty())
        <form method="POST" action="{{ route('checkout.start', $product) }}" class="mt-3 space-y-3" x-data="{ tier: @js(old('tier', $options->first()->value)), prices: @js($options->mapWithKeys(fn ($t) => [$t->value => Money::formatMinor($product->priceFor($t))])->all()) }">
            @csrf
            @if ($options->count() > 1)
                <fieldset class="space-y-2">
                    <legend class="sr-only">{{ __('Licence') }}</legend>
                    @foreach ($options as $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 px-3.5 py-3 text-sm transition-colors has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/50">
                            <input type="radio" name="tier" value="{{ $option->value }}" x-model="tier" class="mt-0.5 h-4 w-4 border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                            <span class="min-w-0 flex-1"><span class="flex justify-between gap-3 font-semibold text-neutral-900"><span>{{ __($option->label()) }}</span><span class="tabular-nums">{{ Money::formatMinor($product->priceFor($option)) }}</span></span><span class="block text-xs font-normal text-tertiary">{{ __($option->summary()) }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
            @else
                <input type="hidden" name="tier" value="{{ $options->first()->value }}">
                @if ($held->isNotEmpty())<p class="text-xs text-tertiary">{{ __('Upgrade to the Extended licence: :summary', ['summary' => __($options->first()->summary())]) }}</p>@endif
            @endif

            <div>
                <label for="buy-phone" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('M-Pesa number') }}</label>
                <input id="buy-phone" type="tel" name="phone" value="{{ $phone }}" inputmode="tel" autocomplete="tel" required placeholder="0712 345 678" class="block w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $errors->has('phone') ? 'border-red-400 focus:ring-red-200' : 'border-neutral-300 focus:border-secondary focus:ring-secondary/25' }}">
                @error('phone')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-xs text-tertiary">{{ __('You will get a prompt on this phone to enter your M-Pesa PIN.') }}</p>
            </div>

            <x-btn block size="lg" type="submit"><span x-text="'{{ __('Pay') }} ' + prices[tier] + ' {{ __('with M-Pesa') }}'">{{ __('Pay with M-Pesa') }}</span></x-btn>
        </form>
    @endif
@endif
