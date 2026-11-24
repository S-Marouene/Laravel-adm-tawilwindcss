{{-- ============================================================
     CARTE À ONGLETS — خدمات التكوين والرسكلة بمقابل
     Props : drafts (collection TrainingRequest)
     ============================================================ --}}

<div class="rounded-lg border border-surface-dim bg-surface-container-lowest shadow-sm overflow-hidden dark:border-gray-700 dark:bg-gray-800" data-training-tabs>

    <div class="h-1.5 bg-gradient-to-r from-primary via-primary-container to-transparent" aria-hidden="true"></div>

    <div class="flex items-center gap-3 px-4 sm:px-5 pt-4 pb-0">
        <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-primary/10">
            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">school</span>
        </span>
        <div class="min-w-0">
            <span class="text-label-caps text-primary uppercase tracking-wider block">{{ __('training.card_title') }}</span>
            <h2 class="text-headline-sm text-on-surface">{{ __('training.card_description') }}</h2>
        </div>
        @if (isset($drafts) && $drafts->isNotEmpty())
            <span class="ms-auto hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-200 text-[11px] font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                {{ $drafts->count() }} &bull; {{ __('training.drafts_title') }}
            </span>
        @endif
    </div>

    {{-- Tab bar --}}
    <div class="mt-3 border-b border-surface-dim dark:border-gray-700 px-2 sm:px-4">
        <div role="tablist" class="flex gap-1 overflow-x-auto -mb-px no-scrollbar">
            @foreach ([
                'dossier' => __('training.my_requests'),
                'process' => __('training.section_training'),
                'docs'    => __('training.documents_title'),
                'fees'    => __('training.fees_title'),
            ] as $key => $label)
                <button type="button" role="tab" id="trTab-{{ $key }}" data-tab="{{ $key }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-body-sm font-semibold whitespace-nowrap rounded-t border-b-2 transition-colors cursor-pointer text-secondary hover:text-on-surface hover:bg-surface-container focus:outline-none"
                        @if ($loop->first)
                            aria-selected="true" data-active
                            style="border-color: var(--color-primary); color: var(--color-primary); background: color-mix(in srgb, var(--color-primary) 6%, transparent);"
                        @else
                            aria-selected="false" style="border-color: transparent;"
                        @endif>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Panel 1 : Votre demande --}}
    <div role="tabpanel" id="trPanel-dossier" tabindex="0" class="tr-panel p-4 sm:p-5 focus:outline-none">
        @if (isset($drafts) && $drafts->isNotEmpty())
            <p class="text-body-sm text-secondary mb-3">{{ __('training.review_note') }}</p>
            <div class="rounded border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/10 divide-y divide-amber-100">
                @foreach ($drafts as $draft)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="text-body-sm font-bold text-on-surface truncate">{{ $draft->nom_prenom }}</p>
                            <p class="text-xs text-secondary mt-0.5">
                                {{ $draft->created_at->diffForHumans() }}
                                @if ($draft->training_type) &bull; {{ __('training.type_' . $draft->training_type) }} @endif
                                @if ($draft->governorate?->name) &bull; {{ $draft->governorate->name }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('training.destroy', $draft->id) }}" data-confirm data-confirm-style="danger">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 cursor-pointer">
                                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">delete</span>
                                    {{ __('training.cancel') }}
                                </button>
                            </form>
                            <a href="{{ route($draft->current_step >= 3
                                        ? 'training.step3.show'
                                        : ($draft->current_step >= 2 ? 'training.step2.show' : 'training.step1.edit'), $draft) }}" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-body-sm cursor-pointer">
                                {{ __('training.continue') }}
                                <svg class="h-3.5 w-3.5 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-3 text-center">
                <button type="button" data-start-new-training class="inline-flex items-center justify-center gap-1.5 rounded border border-primary/30 bg-primary/5 px-4 py-2 text-body-sm font-semibold text-primary transition hover:bg-primary/10 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">note_add</span>
                    {{ __('training.new_request') }}
                </button>
            </div>
        @else
            <button type="button" data-start-new-training aria-label="{{ __('training.new_request') }}"
                    class="group flex w-full items-start gap-4 rounded border border-red-300 bg-red-50 p-5 text-start transition hover:border-red-500 hover:bg-red-100 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/50 dark:border-red-900/50 dark:bg-red-950/20">
                <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-red-100 dark:bg-red-900/30">
                    <span class="material-symbols-outlined filled text-xl text-red-600 group-hover:scale-110 transition" aria-hidden="true">add</span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-body-md font-bold text-red-700 dark:text-red-300">{{ __('training.new_request') }}</p>
                    <p class="text-body-sm text-red-700/80 mt-1 leading-relaxed dark:text-red-200/80">{{ __('training.card_description') }}</p>
                </div>
                <span class="material-symbols-outlined flex-none text-red-500 group-hover:translate-x-1 transition {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" aria-hidden="true">chevron_right</span>
            </button>
        @endif
    </div>

    {{-- Panel 2 : La démarche --}}
    <div role="tabpanel" id="trPanel-process" tabindex="0" class="tr-panel hidden p-4 sm:p-6 space-y-4 focus:outline-none">
        <div class="rounded border border-surface-dim bg-surface-container-lowest p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">move_to_inbox</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-body-sm font-bold text-on-surface">الجهة الإدارية المتعهدة بقبول المطلب</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-[11px] font-bold text-primary">مركزي / جهوي</span>
                    </div>
                    <ul class="mt-1.5 space-y-0.5 text-body-sm text-secondary">
                        <li>الإدارة المركزية للديوان الوطني للحماية المدنية</li>
                        <li>الإدارة الجهوية للحماية المدنية بأريانة</li>
                        <li>الإدارة الجهوية للحماية المدنية بالكاف</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900 dark:bg-amber-950/30">
            <span class="material-symbols-outlined filled text-amber-600" aria-hidden="true">payments</span>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400">{{ __('training.fees_title') }}</p>
                <p class="text-body-md font-black text-amber-700 dark:text-amber-400">{{ __('training.fees_info') }}</p>
            </div>
        </div>
    </div>

    {{-- Panel 3 : Documents --}}
    <div role="tabpanel" id="trPanel-docs" tabindex="0" class="tr-panel hidden p-4 sm:p-6 focus:outline-none">
        <ol class="space-y-2.5">
            @foreach ([
                ['text' => 'مطبوعة طلب القيام بخدمة', 'tag' => 'form'],
                ['text' => 'نسخة من بطاقة تعريف وطنية للمترشح', 'tag' => 'required'],
                ['text' => 'نسخة من شهادة تكوين (في صورة الرسكلة)', 'tag' => 'required'],
                ['text' => 'مطلب مشاركة في دورة تكوينية', 'tag' => 'required'],
                ['text' => 'وصل خلاص معلوم الخدمة', 'tag' => 'required'],
                ['text' => 'قائمة المشاركين + إذن شراء (للمؤسسات)', 'tag' => 'required'],
            ] as $doc)
                <li class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <span class="w-6 h-6 shrink-0 mt-0.5 rounded-full bg-primary text-white text-label-caps flex items-center justify-center">{{ $loop->iteration }}</span>
                    <p class="min-w-0 flex-1 text-body-sm text-on-surface leading-relaxed">{{ $doc['text'] }}</p>
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold {{ $doc['tag'] === 'form' ? 'text-primary bg-primary/10 rounded border border-primary/20' : 'text-emerald-800 bg-emerald-50 rounded border border-emerald-200' }}">
                        @if ($doc['tag'] === 'form')<span class="material-symbols-outlined text-[12px]" aria-hidden="true">edit_note</span>استمارة
                        @else<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>وثيقة مطلوبة@endif
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Panel 4 : Fees table --}}
    <div role="tabpanel" id="trPanel-fees" tabindex="0" class="tr-panel hidden p-4 sm:p-6 focus:outline-none">
        <div class="mb-4 flex items-center gap-3 rounded border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/20">
            <span class="material-symbols-outlined filled text-amber-600 text-lg" aria-hidden="true">payments</span>
            <p class="text-body-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('training.fee_note') }}</p>
        </div>
        <div class="overflow-x-auto rounded border border-surface-dim dark:border-gray-700">
            <table class="min-w-full divide-y divide-surface-dim dark:divide-gray-700 text-xs">
                <thead class="bg-surface-container-low dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-bold uppercase tracking-wider text-secondary">#</th>
                        <th class="px-4 py-2.5 text-start font-bold uppercase tracking-wider text-secondary">{{ __('training.training_type') }}</th>
                        <th class="px-4 py-2.5 text-center font-bold uppercase tracking-wider text-secondary">{{ __('training.fee_duration') }}</th>
                        <th class="px-4 py-2.5 text-end font-bold uppercase tracking-wider text-secondary">{{ __('training.fee_new') }}</th>
                        <th class="px-4 py-2.5 text-end font-bold uppercase tracking-wider text-secondary">{{ __('training.fee_recycling') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-dim dark:divide-gray-700/50 bg-surface-container-lowest dark:bg-gray-900/20">
                    @foreach (\App\Models\TrainingRequest::TYPES as $key => $data)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-4 py-2.5 text-secondary">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2.5 text-on-surface dark:text-gray-200">{{ __('training.type_' . $key) }}</td>
                            <td class="px-4 py-2.5 text-center text-secondary" dir="ltr">{{ $data['weeks'] }} {{ __('training.fee_weeks') }}</td>
                            <td class="px-4 py-2.5 text-end font-mono font-bold text-on-surface dark:text-gray-200" dir="ltr">{{ number_format($data['price'], 0) }} {{ __('training.fee_tnd') }}</td>
                            <td class="px-4 py-2.5 text-end font-mono font-bold text-amber-700 dark:text-amber-400" dir="ltr">{{ number_format($data['price'] * 0.75, 0) }} {{ __('training.fee_tnd') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <style>
        @keyframes trPanelIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .tr-panel.tr-in { animation: trPanelIn 0.22s ease-out both; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script>
    (function () {
        var root   = document.currentScript.closest('[data-training-tabs]');
        var tabs   = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
        function activate(tab) {
            tabs.forEach(function (t) {
                var sel = t === tab;
                if (sel) { t.setAttribute('aria-selected','true'); t.dataset.active=''; t.style.borderColor='var(--color-primary)'; t.style.color='var(--color-primary)'; t.style.background='color-mix(in srgb, var(--color-primary) 6%, transparent)'; }
                else { t.setAttribute('aria-selected','false'); delete t.dataset.active; t.style.borderColor='transparent'; t.style.color=''; t.style.background=''; }
            });
            panels.forEach(function (p) {
                var show = p.id === 'trPanel-' + tab.dataset.tab;
                if (show) { p.classList.remove('hidden','tr-in'); void p.offsetWidth; p.classList.add('tr-in'); }
                else { p.classList.add('hidden'); p.classList.remove('tr-in'); }
            });
        }
        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { activate(tab); });
        });
    }());
    </script>
</div>
