@php
    use App\Enums\LicenceTier;
    $app = config('app.name');
    $effective = \Illuminate\Support\Carbon::parse(config('legal.effective'))->format('F j, Y');
    $sections = [
        'overview' => __('How licences work'),
        'standard' => __('The Standard licence'),
        'extended' => __('The Extended licence'),
        'not-allowed' => __('What no licence allows'),
        'general' => __('Terms for every licence'),
        'refunds' => __('Refunds'),
    ];
@endphp
<x-app-layout title="Licences">
    <x-legal.document :title="__('Model licences')" :sections="$sections" :effective="$effective" :updated="$effective" :kicker="__('Marketplace')"
        :intro="__('Every model on :app is sold under a licence. It says what you may do with the files you buy. This is version :version of the wording.', ['app' => $app, 'version' => LicenceTier::TERMS_VERSION])">

        <x-policy.section id="overview" number="1" :title="$sections['overview']">
            <p>{{ __('Each model is sold with a Standard licence, and some sellers also offer an Extended licence for a higher price. You choose one when you buy, and the licence you buy is recorded on your account with the price you paid and the date.') }}</p>
            <p>{{ __('Free models come with the Standard licence at no cost.') }}</p>
        </x-policy.section>

        @foreach ([LicenceTier::Standard, LicenceTier::Extended] as $tier)
            <x-policy.section :id="$tier->value" :number="$loop->iteration + 1" :title="$sections[$tier->value]">
                <p class="font-medium text-neutral-900">{{ __($tier->summary()) }}</p>
                <x-policy.list :items="array_map('__', $tier->permitted())" />
            </x-policy.section>
        @endforeach

        <x-policy.section id="not-allowed" number="4" :title="$sections['not-allowed']">
            <x-policy.list :items="array_map('__', LicenceTier::restrictions())" />
        </x-policy.section>

        <x-policy.section id="general" number="5" :title="$sections['general']">
            <x-policy.list :items="array_map('__', LicenceTier::general())" />
        </x-policy.section>

        <x-policy.section id="refunds" number="6" :title="$sections['refunds']">
            <p>{{ __('Digital files cannot be returned, so a purchase is not refunded once you have downloaded it. The exception is a file that is broken or not as the seller described it: tell us within :days days of buying and we will refund it. A refunded purchase ends its licence.', ['days' => \App\Support\Settings\FeePolicy::saleHoldDays()]) }}</p>
        </x-policy.section>
    </x-legal.document>
</x-app-layout>
