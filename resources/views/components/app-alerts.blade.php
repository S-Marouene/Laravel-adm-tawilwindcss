{{-- ─────────────────────────────────────────────────────────────────────────
     Alertes globales de l'application — composant à inclure dans chaque layout :
       1. Notifications flottantes (SweetAlert2 toasts) pour les messages flash
          de session : success / error / warning / info — auto-destruction.
       2. Libellés par défaut des boîtes de confirmation SweetAlert2
          (lues par resources/js/app.js pour tout [data-confirm]).
──────────────────────────────────────────────────────────────────────────── --}}
@php
    $appFlash = array_filter([
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
        'info'    => session('info'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div id="app-alerts" class="hidden" aria-hidden="true"
     data-confirm-title="{{ __('alerts.confirm_title') }}"
     data-confirm-ok="{{ __('alerts.confirm_ok') }}"
     data-confirm-cancel="{{ __('alerts.cancel') }}"
     data-delete-title="{{ __('alerts.delete_title') }}"
     data-delete-ok="{{ __('alerts.delete_ok') }}"
     data-cancel-ok="{{ __('alerts.cancel_ok') }}">
@if ($appFlash)
    <script type="application/json" id="app-flash-json">{!! json_encode($appFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
</div>
