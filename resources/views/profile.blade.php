<x-app-layout>
    @php
        $tabs = [
            'overview' => ['label' => __('Overview'), 'icon' => 'user'],
            'profile' => ['label' => __('Edit profile'), 'icon' => 'pencil-square'],
            'security' => ['label' => __('Security'), 'icon' => 'shield-check'],
            'account' => ['label' => __('Account'), 'icon' => 'trash'],
        ];
    @endphp

    {{-- Tab state lives in the URL hash so a tab can be linked to and survives a reload. --}}
    <div class="container mx-auto max-w-7xl px-4 py-8" @hashchange.window="sync()" x-data="{
        tab: 'overview',
        tabs: @js(array_keys($tabs)),
        init() { this.sync(); },
        sync() {
            const hash = window.location.hash.slice(1);
            if (this.tabs.includes(hash)) this.tab = hash;
        },
        show(tab) {
            this.tab = tab;
            history.replaceState(null, '', '#' + tab);
            window.scrollTo({ top: 0 });
        },
        move(step) {
            const next = this.tabs[(this.tabs.indexOf(this.tab) + step + this.tabs.length) % this.tabs.length];
            this.tab = next;
            history.replaceState(null, '', '#' + next);
            this.$nextTick(() => this.$refs['tab-' + next].focus());
        },
    }">
        <!-- Tab bar -->
        <div class="-mx-4 mb-6 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
            <div role="tablist" aria-label="{{ __('Profile sections') }}"
                class="inline-flex min-w-full gap-1 border-b border-neutral-200 sm:flex"
                @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)">
                @foreach ($tabs as $key => $tab)
                    <button type="button" role="tab" id="tab-{{ $key }}" x-ref="tab-{{ $key }}"
                        aria-controls="panel-{{ $key }}" :aria-selected="(tab === '{{ $key }}').toString()"
                        :tabindex="tab === '{{ $key }}' ? 0 : -1" @click="show('{{ $key }}')"
                        :class="tab === '{{ $key }}'
                            ?
                            'border-teal-600 text-neutral-900' :
                            'border-transparent text-neutral-500 hover:text-neutral-800'"
                        class="-mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:rounded-t-lg focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <x-icon :name="$tab['icon']" class="h-4 w-4" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Overview -->
        <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" x-cloak>
            <livewire:profile.overview />
        </div>

        <!-- Edit profile: account + links on the left, everything else on the right -->
        <div id="panel-profile" role="tabpanel" aria-labelledby="tab-profile" x-show="tab === 'profile'" x-cloak
            class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
            <div class="space-y-6 lg:sticky lg:top-24">
                <livewire:profile.update-profile-information-form />
                <livewire:profile.social-links-form />
            </div>
            <livewire:profile.user-profile-form />
        </div>

        <!-- Security -->
        <div id="panel-security" role="tabpanel" aria-labelledby="tab-security" x-show="tab === 'security'" x-cloak
            class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
            <livewire:profile.update-password-form />
            <livewire:profile.two-factor-form />
            <x-authenticator-card :account="auth()->user()" prefix="authenticator" />
        </div>

        <!-- Account -->
        <div id="panel-account" role="tabpanel" aria-labelledby="tab-account" x-show="tab === 'account'" x-cloak
            class="max-w-3xl">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
