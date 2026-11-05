{{-- Panneaux partagés d'un RDV en attente : résultat de visite + annulation.
     Attend $d (dossier) et $appt (RDV pending). Inclus depuis le bandeau
     "Next visit" et la carte du RDV dans la section "Site visits (RDV)". --}}
@if ($appt->isPending())
    {{-- Résultat de visite --}}
    <div x-show="openResult" x-cloak class="mt-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-3">
        <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.update', [$d, $appt]) }}">
            @csrf @method('PUT')
            <div class="flex gap-2 mb-2">
                <label class="flex-1 flex items-center gap-1.5 cursor-pointer rounded-lg border border-amber-300 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/20">
                    <input type="radio" name="status" value="passed" class="sr-only" required>
                    <span class="material-symbols-outlined" aria-hidden="true">task_alt</span> {{ __('prevention.status_passed') }}
                </label>
                <label class="flex-1 flex items-center gap-1.5 cursor-pointer rounded-lg border border-red-300 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="status" value="failed" class="sr-only" required>
                    <span class="material-symbols-outlined" aria-hidden="true">cancel</span> {{ __('prevention.status_failed') }}
                </label>
            </div>
            <textarea name="note" rows="3" placeholder="{{ __('prevention.inspector_note_placeholder') }}" class="text-xs"></textarea>
            <button type="submit" class="mt-2 w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-4 rounded transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">save</span> {{ __('prevention.save_result') }}
            </button>
        </form>
    </div>

    {{-- Annulation — avec motif obligatoire (le RDV n'est jamais supprimé) --}}
    <div x-show="cancelOpen" x-cloak class="mt-3 rounded-lg border border-red-200 dark:border-red-800/30 bg-red-50 dark:bg-red-900/10 p-3">
        <p class="text-[11px] font-bold text-red-700 dark:text-red-400 mb-1.5 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">warning</span>
            {{ __('prevention.cancel_panel_title') }}
        </p>
        <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.cancel', [$d, $appt]) }}"
              onsubmit="return confirm('Cancel this appointment? The applicant will be notified.')">
            @csrf
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1">Cancellation reason <span class="text-red-500">*</span></label>
            <textarea name="note" rows="3" required maxlength="2000" placeholder="{{ __('prevention.cancel_panel_placeholder') }}">{{ old('note') }}</textarea>
            <button type="submit" class="mt-2 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-4 rounded transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span> {{ __('prevention.confirm_cancellation') }}
            </button>
        </form>
    </div>
@else
    <p class="mt-3 text-[11px] font-semibold text-secondary">{{ __('prevention.already_recorded') }}</p>
@endif
