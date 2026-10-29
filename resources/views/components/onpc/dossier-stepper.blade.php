{{-- ============================================================
     BARRE DE PROGRESSION HORIZONTALE MULTISTEP
     Répliquée du gabarit design/nouvelle.html (section 6) :
     carte par étape (border-l-4), achevée = émeraude,
     active = bloc rouge ONPC, à venir = atténuée.
     Props : steps = [
         ['state' => 'done'|'active'|'upcoming',
          'title' => string,
          'data'  => array<string>|null,       // lignes récapitulatif (isDone)
          'editUrl' => string|null,
          'editLabel' => string|null,
          'editing' => bool|null], ...
     ]
     Accessibilité : aria-current="step" sur l'étape active.
     ============================================================ --}}
@props(['steps' => []])

<section class="bg-surface-container-lowest p-4 sm:p-5 rounded border border-surface-dim shadow-[0_2px_8px_-2px_rgba(15,23,42,0.04)] dark:border-gray-700 dark:bg-gray-800"
    aria-label="{{ __('home.steps_title') }}">
    <ol class="grid grid-cols-1 md:grid-cols-3 gap-3 list-none">
        @foreach ($steps as $step)
            @php
                $state      = $step['state'] ?? 'upcoming';
                $isDone     = $state === 'done';
                $isActive   = $state === 'active';
                $data       = $step['data'] ?? [];
                $editUrl    = $step['editUrl'] ?? null;
                $editLabel  = $step['editLabel'] ?? null;
                $badgeText  = $isActive ? __('prevention_request.active_step')
                            : ($isDone ? __('prevention_request.step_done')
                            : __('prevention_request.draft_step', ['step' => $loop->iteration]));
                // Récapitulatif masqué pour une étape à venir
                $lines      = $isDone ? $data : [];
            @endphp

            <li class="flex items-start gap-3 p-2.5 rounded
                @if ($isActive)
                    bg-primary text-white shadow-sm ring-2 ring-primary/20
                @elseif ($isDone)
                    bg-surface-container-low/60 border-l-4 border-emerald-600 dark:bg-gray-900/50
                @else
                    bg-surface-container-low border border-surface-dim opacity-75 dark:bg-gray-900/40 dark:border-gray-700
                @endif"
                @if ($isActive) aria-current="step" @endif>

                {{-- Indicateur circulaire --}}
                @if ($isDone)
                    <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0"
                          title="{{ __('prevention_request.step_done') }}">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                @elseif ($isActive)
                    <span class="w-8 h-8 rounded-full bg-white text-primary flex items-center justify-center shrink-0 font-bold text-body-sm">
                        {{ $loop->iteration }}
                    </span>
                @else
                    <span class="w-8 h-8 rounded-full bg-surface-variant text-secondary flex items-center justify-center shrink-0 font-bold text-body-sm dark:bg-gray-700 dark:text-gray-400">
                        {{ $loop->iteration }}
                    </span>
                @endif

                {{-- Libellés + résumé --}}
                <div class="min-w-0 flex-1">
                    <div class="text-[11px] font-bold uppercase tracking-wider @if (!$isActive && !$isDone) pt-1.5 text-secondary @elseif ($isActive) text-primary-fixed @else text-emerald-800 dark:text-emerald-400 @endif">
                        {{ $badgeText }}
                    </div>
                    <div class="text-body-sm @if ($isActive) font-bold text-white @else font-semibold text-on-surface dark:text-gray-200 @endif truncate">
                        {{ $step['title'] }}
                    </div>

                    {{-- Récapitulatif des données (étapes complétées) --}}
                    @if (count($lines))
                        <div class="mt-1.5 space-y-0.5">
                            @foreach ($lines as $line)
                                <p class="text-xs text-secondary dark:text-gray-400 truncate">{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if ($isDone && $editUrl && $editLabel)
                        <a href="{{ $editUrl }}"
                           class="mt-1.5 inline-flex items-center gap-1.5 text-body-sm text-primary hover:underline font-bold whitespace-nowrap">
                            <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">edit</span>
                            {{ $editLabel }}
                        </a>
                    @endif

                    {{-- Badge "en cours de modification" sur l'étape active --}}
                    @if ($isActive && ($step['editing'] ?? false))
                        <div class="mt-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/15 text-white text-[11px] font-bold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">sync</span>
                                {{ __('prevention_request.step_editing') }}
                            </span>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>
