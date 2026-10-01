<x-staff-guest>
    <h1 class="font-tertiary text-xl font-semibold text-neutral-900">{{ __('Reset your password') }}</h1>
    <p class="mt-1 text-sm text-tertiary">{{ __('Enter your staff email address and we will send you a link to set a new password.') }}</p>

    @if (session('status'))<p class="mt-4 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">{{ session('status') }}</p>@endif

    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="block w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            @error('email')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <x-btn type="submit" block size="lg">{{ __('Send the link') }}</x-btn>
        <p class="text-center text-sm"><a href="{{ route('admin.login') }}" class="font-medium text-teal-700 hover:underline">{{ __('Back to sign in') }}</a></p>
    </form>
</x-staff-guest>
