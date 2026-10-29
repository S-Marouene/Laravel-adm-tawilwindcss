@extends('layouts.guest')

@section('auth-title', __('login.title'))
@section('auth-subtitle', __('login.subtitle'))


@section('content')
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('login.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                leadingIcon='<svg class="w-5 h-5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>'
                placeholder="email@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('login.password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password"
                leadingIcon='<svg class="w-5 h-5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>'
                trailingIcon="password"
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer group">
                <div class="relative">
                    <input id="remember_me" type="checkbox" name="remember"
                        class="peer sr-only" />
                    <div class="w-5 h-5 border-2 border-surface-dim dark:border-outline rounded-md
                                peer-checked:border-primary peer-checked:bg-primary
                                transition-all duration-200
                                group-hover:border-outline group-hover:peer-checked:bg-primary-container">
                    </div>
                    <svg class="absolute inset-0 w-5 h-5 text-white opacity-0 peer-checked:opacity-100 transition-opacity" aria-hidden="true"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm text-secondary group-hover:text-on-surface transition-colors">{{ __('login.remember') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                    class="text-sm font-medium text-primary hover:text-primary-container underline-offset-2 hover:underline transition-colors">
                    {{ __('login.forgot') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <x-primary-button>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            {{ __('login.submit') }}
        </x-primary-button>

        <!-- Security note -->
        <p class="flex items-center justify-center gap-1.5 text-[11px] text-secondary">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
            {{ __('login.secure') }}
        </p>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="pt-4 border-t border-surface-dim text-center">
                <p class="text-sm text-secondary">
                    {{ __('login.no_account') }}
                    <a href="{{ route('register') }}" class="font-semibold text-primary hover:text-primary-container underline-offset-2 hover:underline transition-colors">
                        {{ __('nav.register') }}
                    </a>
                </p>
            </div>
        @endif
    </form>
@endsection
