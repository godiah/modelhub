<x-staff-guest>
    <h1 class="font-tertiary text-xl font-semibold text-neutral-900">{{ __('Staff sign in') }}</h1>
    <p class="mt-1 text-sm text-tertiary">{{ __('Use your staff account. It is separate from any member account you may have.') }}</p>

    @if ($errors->has('code'))<p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first('code') }}</p>@endif

    @if (session('status'))<p class="mt-4 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">{{ session('status') }}</p>@endif

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="block w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:ring-red-200' : 'border-neutral-300 focus:border-secondary focus:ring-secondary/25' }}">
            @error('email')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="block w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            @error('password')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-neutral-700"><input type="checkbox" name="remember" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">{{ __('Keep me signed in') }}</label>
            <a href="{{ route('admin.password.request') }}" class="text-sm font-medium text-teal-700 hover:underline">{{ __('Forgot your password?') }}</a>
        </div>
        <x-btn type="submit" block size="lg">{{ __('Sign in') }}</x-btn>
    </form>
</x-staff-guest>
