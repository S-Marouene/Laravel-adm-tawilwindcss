@extends('admin.layouts.admin')

@section('title', 'Dashboard — Salle des Opérations')

@section('content')
    <x-onpc.page-header title="Dashboard" :subtitle="app()->getLocale() === 'ar' ? 'نظرة عامة على المنصة.' : 'Vue d’ensemble de la plateforme.'" icon="space_dashboard">
        <x-slot name="actions">
            <a href="{{ route('admin.activity-logs.index') }}" class="ui-soft-button text-body-sm">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">history</span>
                {{ app()->getLocale() === 'ar' ? 'سجل الأنشطة' : 'Journal d’activité' }}
            </a>
        </x-slot>
    </x-onpc.page-header>

    <!-- Stats Grid façon design/admin.html -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Users --}}
        <x-onpc.kpi-card label="Total Users" :value="$total_users" icon="group" tone="primary">
            <x-slot name="footer">
                <a href="{{ route('admin.users.index') }}" class="admin-action-link inline-flex items-center gap-1">
                    {{ app()->getLocale() === 'ar' ? 'إدارة المستخدمين' : 'Gérer les utilisateurs' }}
                    <span class="material-symbols-outlined text-[14px] rtl:rotate-180" aria-hidden="true">arrow_forward</span>
                </a>
            </x-slot>
        </x-onpc.kpi-card>

        {{-- Roles --}}
        <x-onpc.kpi-card label="Roles" :value="$total_roles" icon="manage_accounts" tone="neutral">
            <x-slot name="footer">
                <a href="{{ route('admin.roles.index') }}" class="admin-action-link inline-flex items-center gap-1">
                    {{ app()->getLocale() === 'ar' ? 'إدارة الأدوار' : 'Gérer les rôles' }}
                    <span class="material-symbols-outlined text-[14px] rtl:rotate-180" aria-hidden="true">arrow_forward</span>
                </a>
            </x-slot>
        </x-onpc.kpi-card>

        {{-- Permissions --}}
        <x-onpc.kpi-card label="Permissions" :value="$total_permissions" icon="key" tone="warning">
            <x-slot name="footer">
                <a href="{{ route('admin.permissions.index') }}" class="admin-action-link inline-flex items-center gap-1">
                    {{ app()->getLocale() === 'ar' ? 'إدارة الصلاحيات' : 'Gérer les permissions' }}
                    <span class="material-symbols-outlined text-[14px] rtl:rotate-180" aria-hidden="true">arrow_forward</span>
                </a>
            </x-slot>
        </x-onpc.kpi-card>

        {{-- Activity --}}
        <x-onpc.kpi-card label="Activity Today" :value="$activity_counts['today']" icon="pulse_alert" tone="success">
            <x-slot name="footer">
                <span class="flex items-center gap-3">
                    <span>{{ app()->getLocale() === 'ar' ? 'الأسبوع' : 'Semaine' }}: <strong class="text-on-surface">{{ $activity_counts['week'] }}</strong></span>
                    <span>{{ app()->getLocale() === 'ar' ? 'الشهر' : 'Mois' }}: <strong class="text-on-surface">{{ $activity_counts['month'] }}</strong></span>
                </span>
            </x-slot>
        </x-onpc.kpi-card>
    </div>

    <!-- Recent Activity & Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
        <!-- Recent Activity -->
        <div class="ynex-card overflow-hidden">
            <div class="ynex-card-header">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-title-section text-secondary uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">history</span>
                        {{ app()->getLocale() === 'ar' ? 'نشاط حديث' : 'Activité récente' }}
                    </h2>
                    <a href="{{ route('admin.activity-logs.index') }}" class="admin-action-link text-xs">{{ app()->getLocale() === 'ar' ? 'الكل' : 'Tout voir' }}</a>
                </div>
            </div>
            <div class="divide-y divide-surface-dim">
                @forelse($recent_activities as $log)
                    <div class="interactive-row px-5 py-3.5 flex items-center justify-between hover:bg-surface-container transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($log->user)
                                <div class="admin-avatar w-8 h-8 rounded flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-white">{{ substr($log->user->name, 0, 1) }}</span>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-surface-container border border-surface-dim rounded flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[16px] text-outline" aria-hidden="true">person_off</span>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-body-sm text-on-surface truncate">{{ $log->description }}</p>
                                <p class="text-xs text-secondary">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span class="admin-badge inline-flex items-center px-2 py-0.5 text-[11px] font-bold flex-shrink-0 ml-2 {{ $log->badgeColor() }}">
                            {{ ucfirst($log->type) }}
                        </span>
                    </div>
                @empty
                    <x-onpc.empty-state icon="history" class="border-none" />
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="ynex-card overflow-hidden">
            <div class="ynex-card-header">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-title-section text-secondary uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">group_add</span>
                        {{ app()->getLocale() === 'ar' ? 'Utilisateurs récents' : 'Utilisateurs récents' }}
                    </h2>
                    <a href="{{ route('admin.users.index') }}" class="admin-action-link text-xs">{{ app()->getLocale() === 'ar' ? 'الكل' : 'Tout voir' }}</a>
                </div>
            </div>
            <div class="divide-y divide-surface-dim">
                @forelse($recent_users as $user)
                    <div class="interactive-row px-5 py-3.5 flex flex-wrap items-center justify-between gap-2 hover:bg-surface-container transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="admin-avatar w-9 h-9 rounded flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-white">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-body-sm font-semibold text-on-surface truncate">{{ $user->name }}</p>
                                <p class="text-xs text-secondary truncate">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap gap-2">
                            @if($user->is_admin)
                                <span class="admin-badge inline-flex items-center px-2 py-0.5 text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">{{ app()->getLocale() === 'ar' ? 'مدير' : 'Admin' }}</span>
                            @endif
                            @foreach($user->roles as $role)
                                <span class="admin-badge inline-flex items-center px-2 py-0.5 text-[11px] font-bold bg-surface-container text-secondary border border-surface-dim">{{ $role->name }}</span>
                            @endforeach
                            <span class="text-xs text-outline">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <x-onpc.empty-state icon="person_off" class="border-none" />
                @endforelse
            </div>
        </div>
    </div>
@endsection
