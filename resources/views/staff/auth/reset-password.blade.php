<x-staff-guest>
    <h1 class="font-tertiary text-xl font-semibold text-neutral-900">{{ __('Set your password') }}</h1>
    <p class="mt-1 text-sm text-tertiary">{{ __('At least 12 characters, with letters and numbers.') }}</p>

    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"
                class="block w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            @error('email')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('New password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="block w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            @error('password')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="block w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
        </div>
        <x-btn type="submit" block size="lg">{{ __('Set password') }}</x-btn>
    </form>
</x-staff-guest>
