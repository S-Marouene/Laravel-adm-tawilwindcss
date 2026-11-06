@extends('admin.layouts.admin')

@section('title', 'Centre de notifications')

@section('content')

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">Centre de notifications</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">
            Supervision de toutes les notifications envoyées — usagers et staff.
        </p>
    </div>
    <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.notification-center.mark-all-read') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded border border-surface-dim bg-surface-container text-on-surface dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 hover:bg-surface-container-high dark:hover:bg-gray-700 transition-colors">
                <span class="material-symbols-outlined text-base">done_all</span>
                Tout marquer lu
            </button>
        </form>
        <form method="POST" action="{{ route('admin.notification-center.clear-read') }}"
              data-confirm data-confirm-style="warning"
              data-confirm-title="Supprimer les notifications lues ?"
              data-confirm-text="Toutes les notifications déjà lues (tous destinataires) seront supprimées définitivement.">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 transition-colors">
                <span class="material-symbols-outlined text-base">delete_sweep</span>
                Purger les lues
            </button>
        </form>
    </div>
</div>

@php
    $levelBadge = [
        'info'    => 'bg-secondary/10 text-secondary dark:text-gray-400',
        'success' => 'bg-primary/10 text-primary',
        'warning' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        'danger'  => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    ];
    $levelIcon = [
        'info'    => 'info',
        'success' => 'check_circle',
        'warning' => 'warning',
        'danger'  => 'error',
    ];
@endphp

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="admin-form-card p-4">
        <p class="text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-on-surface dark:text-gray-100 mt-1">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="admin-form-card p-4">
        <p class="text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Non lues</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['unread']) }}</p>
    </div>
    <div class="admin-form-card p-4">
        <p class="text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Envoyées au staff</p>
        <p class="text-2xl font-bold text-gov-600 dark:text-gov-400 mt-1">{{ number_format($stats['sent_staff']) }}</p>
    </div>
    <div class="admin-form-card p-4">
        <p class="text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Envoyées aux usagers</p>
        <p class="text-2xl font-bold text-primary mt-1">{{ number_format($stats['sent_citizens']) }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="admin-form-card p-4 mb-6">
    <form method="GET" action="{{ route('admin.notification-center.index') }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status" class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Statut</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-surface-dim/90 dark:border-gray-600/90 rounded-xl text-sm dark:bg-gray-900/55 dark:text-gray-100 hover:border-gov-300 dark:hover:border-gov-700 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all">
                    <option value="">Tous</option>
                    <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Non lues</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Lues</option>
                </select>
            </div>

            <div>
                <label for="level" class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Niveau</label>
                <select name="level" id="level" class="w-full px-3 py-2 border border-surface-dim/90 dark:border-gray-600/90 rounded-xl text-sm dark:bg-gray-900/55 dark:text-gray-100 hover:border-gov-300 dark:hover:border-gov-700 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all">
                    <option value="">Tous</option>
                    @foreach(['info', 'success', 'warning', 'danger'] as $lvl)
                        <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="to" class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Destinataire</label>
                <select name="to" id="to" class="w-full px-3 py-2 border border-surface-dim/90 dark:border-gray-600/90 rounded-xl text-sm dark:bg-gray-900/55 dark:text-gray-100 hover:border-gov-300 dark:hover:border-gov-700 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all">
                    <option value="">Tous types</option>
                    <option value="staff" {{ request('to') === 'staff' ? 'selected' : '' }}>Staff (back-office)</option>
                    <option value="citizens" {{ request('to') === 'citizens' ? 'selected' : '' }}>Usagers</option>
                </select>
            </div>

            <div>
                <label for="q" class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Recherche</label>
                <div class="flex gap-2">
                    <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Titre ou message..."
                        class="w-full px-3 py-2 border border-surface-dim/90 dark:border-gray-600/90 rounded-xl text-sm dark:bg-gray-900/55 dark:text-gray-100 hover:border-gov-300 dark:hover:border-gov-700 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all">
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.notification-center.index') }}" class="text-sm text-secondary hover:text-on-surface dark:text-gray-400 dark:hover:text-gray-200">Réinitialiser</a>
            <button type="submit" class="admin-add-button px-4 py-2 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Filtrer
            </button>
        </div>
    </form>
</div>

{{-- Notifications table --}}
<div class="admin-table-card overflow-hidden">
    <table class="w-full">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Date</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Destinataire</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Notification</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Niveau</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-secondary">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-dim dark:divide-gray-700">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $level = $data['level'] ?? 'info';
                    $notifTitle = $data['title_fr'] ?? $data['title'] ?? 'Notification';
                    $notifBody  = $data['body_fr'] ?? $data['body'] ?? '';
                    $recipient = $notification->notifiable;
                    $isStaff   = $recipient ? $recipient->hasAnyRole(\App\Models\User::ADMIN_ROLES) : false;
                @endphp
                <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">
                        <div>{{ $notification->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-outline">{{ $notification->created_at->format('H:i') }} — {{ $notification->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($recipient)
                            <div class="flex items-center">
                                <div class="admin-avatar w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 {{ $isStaff ? 'bg-gov-600' : '' }}">
                                    <span class="text-xs font-semibold text-white">{{ mb_substr($recipient->name ?? '?', 0, 1) }}</span>
                                </div>
                                <div class="ms-2 min-w-0">
                                    <p class="text-sm text-on-surface dark:text-gray-100 truncate max-w-[160px]">{{ $recipient->name }}</p>
                                    <p class="text-xs text-outline">{{ $isStaff ? 'Staff' : 'Usager' }}</p>
                                </div>
                            </div>
                        @else
                            <span class="text-sm text-outline italic">Supprimé</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 max-w-[340px]">
                        <p class="text-sm font-medium text-on-surface dark:text-gray-100 truncate">{{ $notifTitle }}</p>
                        <p class="text-xs text-secondary dark:text-gray-400 truncate mt-0.5">{{ \Illuminate\Support\Str::limit($notifBody, 90) }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold {{ $levelBadge[$level] ?? $levelBadge['info'] }}">
                            <span class="material-symbols-outlined text-[13px]">{{ $levelIcon[$level] ?? 'info' }}</span>
                            {{ $level }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($notification->read_at)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-secondary dark:text-gray-400">
                                <span class="material-symbols-outlined text-[15px]">check</span>
                                Lue
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary">
                                <span class="material-symbols-outlined text-[15px]">fiber_manual_record</span>
                                Non lue
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        @if(!empty($data['url']))
                            <a href="{{ url($data['url']) }}" class="admin-action-link mr-3">Ouvrir</a>
                        @endif
                        <form action="{{ route('admin.notification-center.destroy', $notification) }}" method="POST" class="inline"
                              data-confirm data-confirm-style="danger"
                              data-confirm-title="Supprimer cette notification ?"
                              data-confirm-text="Le destinataire ne la verra plus.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-danger-link">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-secondary dark:text-gray-400">
                        <span class="material-symbols-outlined text-4xl block mb-2 text-outline">notifications_none</span>
                        Aucune notification ne correspond aux filtres.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $notifications->links() }}
</div>

@endsection
