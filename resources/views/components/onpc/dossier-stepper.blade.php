{{-- ============================================================
     BARRE DE PROGRESSION HORIZONTALE MULTISTEP — v2
     Design uniforme : hauteur constante sur toutes les étapes,
     une seule couleur d'accent (primaire ONPC) au lieu du mix
     vert/rouge, récapitulatif sur une ligne réservée (truncate),
     édition via bouton icône compact.
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
                $state     = $step['state'] ?? 'upcoming';
                $isDone    = $state === 'done';
                $isActive  = $state === 'active';
                $lines     = $isDone ? array_values(array_filter($step['data'] ?? [])) : [];
                $summary   = implode(' — ', $lines);
                $editUrl   = $step['editUrl'] ?? null;
                $editLabel = $step['editLabel'] ?? null;
                $badgeText = $isActive ? __('prevention_request.active_step')
                           : ($isDone ? __('prevention_request.step_done')
                           : __('prevention_request.draft_step', ['step' => $loop->iteration]));
            @endphp

            <li class="relative flex items-center gap-3 p-3 rounded-lg transition
                @if ($isActive)
                    border border-primary/60 bg-primary/5 ring-1 ring-primary/20 dark:bg-primary/10
                @elseif ($isDone)
                    border border-surface-dim bg-surface-container-low/60 dark:border-gray-700 dark:bg-gray-900/40
                @else
                    border border-dashed border-surface-dim dark:border-gray-700
                @endif"
                @if ($isActive) aria-current="step" @endif>

                {{-- Indicateur circulaire — taille fixe partout --}}
                <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center font-bold text-body-sm transition
                    @if ($isActive) bg-primary text-white shadow-sm
                    @elseif ($isDone) bg-primary/10 text-primary
                    @else bg-surface-container text-secondary border border-surface-dim dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600
                    @endif"
                    @if ($isDone) title="{{ __('prevention_request.step_done') }}" @endif>
                    @if ($isDone)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>

                {{-- Libellés — structure identique sur chaque étape → hauteur uniforme --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider truncate
                            @if ($isActive) text-primary @elseif ($isDone) text-primary/90 dark:text-primary @else text-secondary @endif">
                            {{ $badgeText }}
                        </span>
                        @if ($isActive && ($step['editing'] ?? false))
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wide shrink-0">
                                <span class="material-symbols-outlined text-[12px]" aria-hidden="true">sync</span>
                                {{ __('prevention_request.step_editing') }}
                            </span>
                        @endif
                    </div>
                    <div class="text-body-sm truncate
                        @if ($isActive) font-bold text-on-surface @elseif ($isDone) font-semibold text-on-surface dark:text-gray-200 @else text-secondary dark:text-gray-400 @endif">
                        {{ $step['title'] }}
                    </div>

                    {{-- Récapitulatif — une seule ligne réservée (tronquée) → aucune croissance de carte --}}
                    <div class="mt-0.5 h-4 flex items-center gap-1 min-w-0">
                        @if ($summary)
                            <p class="text-[11px] text-secondary dark:text-gray-400 truncate">{{ $summary }}</p>
                        @endif
                        @if ($isDone && $editUrl)
                            <a href="{{ $editUrl }}" title="{{ $editLabel }}" aria-label="{{ $editLabel }}"
                               class="ms-auto inline-flex items-center shrink-0 rounded p-0.5 text-primary transition hover:bg-primary/10 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">edit</span>
                            </a>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
</section>
