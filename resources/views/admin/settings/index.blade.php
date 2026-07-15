@extends('admin.layouts.admin')

@section('title', __('admin.settings'))

@section('content')
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
            <div class="p-2 rounded-xl bg-gov-100 dark:bg-gov-900/50 text-gov-600 dark:text-gov-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.settings') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.settings_desc') }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach($settings as $group => $groupSettings)
                <div class="admin-form-card">
                    <!-- Group Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                        <div class="flex items-center gap-3">
                            @if($group === 'general')
                                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @elseif($group === 'contact')
                                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @elseif($group === 'appearance')
                                <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                    </svg>
                                </div>
                            @elseif($group === 'seo')
                                <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            @elseif($group === 'mail')
                                <div class="p-2 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 capitalize">
                                    {{ __("admin.settings_{$group}") }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __("admin.settings_{$group}_desc") }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Group Fields -->
                    <div class="p-6 space-y-5">
                        @foreach($groupSettings as $setting)
                            <div>
                                <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    {{ $setting->label }}
                                    @if(in_array($setting->type, ['image']))
                                        <span class="text-xs text-gray-400 font-normal">({{ $setting->description }})</span>
                                    @endif
                                </label>

                                @if($setting->description && $setting->type !== 'image')
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $setting->description }}</p>
                                @endif

                                @if($setting->type === 'text')
                                    <textarea
                                        id="{{ $setting->key }}"
                                        name="{{ $setting->key }}"
                                        rows="3"
                                        class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200"
                                    >{{ old($setting->key, $setting->value) }}</textarea>

                                @elseif($setting->type === 'boolean')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="{{ $setting->key }}" value="1" class="sr-only peer" {{ $setting->value === '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-gov-500/30 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gov-600"></div>
                                        <span class="ms-3 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.enabled') }}</span>
                                    </label>

                                @elseif($setting->type === 'image')
                                    <div class="flex items-start gap-4">
                                        @if($setting->value)
                                            <div class="flex-shrink-0">
                                                <img src="{{ Storage::url($setting->value) }}" alt="{{ $setting->label }}" class="border border-gray-200 dark:border-gray-600 rounded-lg {{ $setting->key === 'app_favicon' ? 'w-8 h-8' : 'w-20 h-20' }} object-cover">
                                                <a href="{{ route('admin.settings.remove-image', $setting->key) }}" class="mt-1 inline-flex items-center text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('{{ __('admin.confirm_remove_image') }}')">
                                                    {{ __('admin.remove') }}
                                                </a>
                                            </div>
                                        @endif
                                        <div class="flex-1">
                                            <input type="file" id="{{ $setting->key }}" name="{{ $setting->key }}" accept="image/png,image/jpeg,image/x-icon" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gov-50 dark:file:bg-gov-900/30 file:text-gov-700 dark:file:text-gov-300 hover:file:bg-gov-100 dark:hover:file:bg-gov-900/50 file:transition-colors file:cursor-pointer cursor-pointer">
                                        </div>
                                    </div>

                                @elseif($setting->type === 'email')
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <input type="email" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }}">
                                    </div>

                                @elseif($setting->type === 'password')
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <input type="password" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key) }}" placeholder="{{ $setting->value ? '••••••••' : __('admin.no_password_set') }}" autocomplete="new-password" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }}">
                                    </div>

                                @elseif($setting->type === 'url')
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                        </div>
                                        <input type="url" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }}">
                                    </div>

                                @elseif($setting->type === 'select')
                                    <select id="{{ $setting->key }}" name="{{ $setting->key }}" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200">
                                        @if($setting->key === 'mail_mailer')
                                            <option value="smtp" {{ $setting->value === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                            <option value="sendmail" {{ $setting->value === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                            <option value="log" {{ $setting->value === 'log' ? 'selected' : '' }}>Log (dev only)</option>
                                        @elseif($setting->key === 'mail_encryption')
                                            <option value="tls" {{ $setting->value === 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ $setting->value === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            <option value="" {{ $setting->value === '' ? 'selected' : '' }}>{{ __('admin.none') }}</option>
                                        @endif
                                    </select>

                                @else
                                    <input type="text" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200">
                                @endif

                                @error($setting->key)
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Submit Button -->
        <div class="mt-8 flex items-center justify-end gap-4">
            <button type="submit" class="admin-add-button px-6 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ __('action.save') }}
            </button>
        </div>
    </form>

    <!-- Test Email Form (outside the main form) -->
    <div class="mt-6 admin-form-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.mail_test') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.mail_test_desc') }}</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('admin.settings.test-mail') }}" class="flex items-end gap-4">
                @csrf
                <div class="flex-1">
                    <label for="test_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ __('admin.mail_test_recipient') }}
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" id="test_email" name="test_email" value="{{ old('test_email') ?? Auth::user()->email }}" required placeholder="email@example.com" class="block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400 focus:outline-none transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }}">
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 dark:from-rose-500 dark:to-rose-600 dark:hover:from-rose-600 dark:hover:to-rose-700 rounded-xl font-semibold text-sm text-white shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-rose-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 ease-in-out active:scale-[0.98] shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    {{ __('admin.mail_test_send') }}
                </button>
            </form>
        </div>
    </div>
@endsection
