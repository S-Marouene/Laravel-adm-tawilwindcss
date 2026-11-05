@php
    $isAdmin = auth()->user()->isAdministrator();
    $layout  = $isAdmin ? 'admin.layouts.admin' : 'layouts.app';
@endphp

@extends($layout)

@section('title', __('Notifications'))

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">
                    {{ app()->getLocale() === 'ar' ? 'كل الإشعارات' : 'Toutes les notifications' }}
                </h1>
                <p class="text-sm text-secondary dark:text-gray-400 mt-1">
                    {{ $notifications->total() }} {{ app()->getLocale() === 'ar' ? 'إشعار' : 'notification(s)' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="mark-all-read-btn"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-surface-container dark:bg-gray-700 text-on-surface dark:text-gray-200 hover:bg-surface-container-high dark:hover:bg-gray-600 transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">done_all</span>
                    <span class="hidden sm:inline">{{ app()->getLocale() === 'ar' ? 'قراءة الكل' : 'Tout marquer lu' }}</span>
                </button>
                <button type="button" id="clear-all-btn"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">delete_sweep</span>
                    <span class="hidden sm:inline">{{ app()->getLocale() === 'ar' ? 'مسح الكل' : 'Tout supprimer' }}</span>
                </button>
            </div>
        </div>

        {{-- Notification list --}}
        <div class="space-y-2" id="notif-list">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $level = $data['level'] ?? 'info';
                    $iconMap = [
                        'info'    => 'info',
                        'success' => 'check_circle',
                        'warning' => 'warning',
                        'danger'  => 'error',
                    ];
                    $colorMap = [
                        'info'    => 'bg-secondary/10 text-secondary dark:text-gray-400',
                        'success' => 'bg-primary/10 text-primary',
                        'warning' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
                        'danger'  => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                    ];
                @endphp

                <div class="flex items-start gap-3 p-4 rounded-xl border border-surface-dim dark:border-gray-700 {{ $notification->read_at ? '' : 'bg-primary/5 dark:bg-primary/10' }} hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors group"
                     data-id="{{ $notification->id }}"
                     data-read="{{ $notification->read_at ? '1' : '0' }}"
                     data-url="{{ $data['url'] ?? '' }}">

                    {{-- Icon --}}
                    <span class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $colorMap[$level] ?? $colorMap['info'] }}">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $iconMap[$level] ?? 'info' }}</span>
                    </span>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-on-surface dark:text-gray-200">{{ $data['title'] ?? 'Notification' }}</p>
                            @if(!$notification->read_at)
                                <span class="w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
                            @endif
                        </div>
                        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ $data['body'] ?? '' }}</p>
                        @if(!empty($data['reference']))
                            <p class="text-xs text-outline mt-1">ref: {{ $data['reference'] }}</p>
                        @endif
                        <p class="text-xs text-outline mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        @if(!$notification->read_at)
                            <button type="button" class="mark-read-btn p-2 rounded-lg text-secondary dark:text-gray-400 hover:text-primary hover:bg-surface-container dark:hover:bg-gray-700 transition-colors"
                                    data-id="{{ $notification->id }}"
                                    title="{{ app()->getLocale() === 'ar' ? 'تحديد كمقروء' : 'Marquer comme lu' }}">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">check</span>
                            </button>
                        @endif
                        <button type="button" class="delete-notif-btn p-2 rounded-lg text-secondary dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-surface-container dark:hover:bg-gray-700 transition-colors"
                                data-id="{{ $notification->id }}"
                                title="{{ app()->getLocale() === 'ar' ? 'حذف' : 'Supprimer' }}">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">delete</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <span class="material-symbols-outlined text-6xl text-outline mb-4 block" aria-hidden="true">notifications_off</span>
                    <p class="text-lg text-secondary dark:text-gray-400">
                        {{ app()->getLocale() === 'ar' ? 'لا توجد إشعارات' : 'Aucune notification' }}
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif

        {{-- Back link --}}
        <div class="mt-6">
            <a href="{{ $isAdmin ? route('admin.dashboard') : url('/') }}"
               class="inline-flex items-center gap-2 text-sm text-secondary hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_back</span>
                {{ app()->getLocale() === 'ar' ? 'العودة' : 'Retour' }}
            </a>
        </div>
    </div>

    <script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        async function api(url, method) {
            const res = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
            return res.ok;
        }

        // Mark one as read
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const ok = await api(`/notifications/${id}/read`, 'PATCH');
                if (ok) {
                    const row = btn.closest('[data-id]');
                    row.classList.remove('bg-primary/5', 'dark:bg-primary/10');
                    const dot = row.querySelector('.w-2.h-2.rounded-full.bg-primary');
                    if (dot) dot.remove();
                    btn.remove();
                }
            });
        });

        // Delete one
        document.querySelectorAll('.delete-notif-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const ok = await api(`/notifications/${id}`, 'DELETE');
                if (ok) {
                    btn.closest('[data-id]')?.remove();
                }
            });
        });

        // Mark all read
        document.getElementById('mark-all-read-btn')?.addEventListener('click', async () => {
            const ok = await api('/notifications/mark-all-read', 'POST');
            if (ok) {
                document.querySelectorAll('#notif-list [data-id]').forEach(row => {
                    row.classList.remove('bg-primary/5', 'dark:bg-primary/10');
                    const dot = row.querySelector('.w-2.h-2.rounded-full.bg-primary');
                    if (dot) dot.remove();
                    row.querySelectorAll('.mark-read-btn').forEach(b => b.remove());
                });
            }
        });

        // Clear all
        document.getElementById('clear-all-btn')?.addEventListener('click', async () => {
            if (!confirm('{{ app()->getLocale() === 'ar' ? 'هل أنت متأكد؟' : 'Supprimer toutes les notifications ?' }}')) return;
            const ok = await api('/notifications', 'DELETE');
            if (ok) {
                const list = document.getElementById('notif-list');
                list.innerHTML = `
                    <div class="text-center py-16">
                        <span class="material-symbols-outlined text-6xl text-outline mb-4 block" aria-hidden="true">notifications_off</span>
                        <p class="text-lg text-secondary dark:text-gray-400">
                            {{ app()->getLocale() === 'ar' ? 'لا توجد إشعارات' : 'Aucune notification' }}
                        </p>
                    </div>`;
            }
        });

        // Click row to navigate
        document.querySelectorAll('#notif-list [data-url]').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('button')) return; // don't navigate when clicking action buttons
                const url = row.dataset.url;
                if (url) window.location.href = url;
            });
        });
    })();
    </script>
@endsection
