@extends('admin.layouts.admin')

@section('title', 'Create User')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-secondary hover:text-on-surface dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100 mt-2">Create User</h1>
    </div>

    @php
        // Build delegation map for JS: { govId: [{id, name}, ...] }
        $delegationsMap = $governorates->mapWithKeys(fn ($g) => [
            $g->id => $g->delegations->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values()
        ]);

        $roleDescriptions = [
            'super-admin' => 'Full access: settings, theme, users, all dossiers.',
            'admin'       => 'Same as super-admin, except website & theme settings.',
            'centrale'    => 'Sees & manages ALL dossiers across all territories (service-filtered).',
            'manager'     => 'Scoped to assigned territories; each territory is a whole governorate or a single delegation (service-filtered).',
            'user'        => 'Simple citizen account — no back-office access.',
        ];

        $oldTerritories = old('territories', []);
    @endphp

    <div class="max-w-2xl">
        <form action="{{ route('admin.users.store') }}" method="POST" class="admin-form-card p-6 space-y-6" id="user-form">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('name') border-red-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('email') border-red-500 @enderror">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('password') border-red-500 @enderror">
                @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
            </div>

            {{-- Role --}}
            <div>
                <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-3">Role</label>
                <div class="space-y-2">
                    @foreach($roles as $role)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-surface-dim dark:border-gray-600 cursor-pointer hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50 dark:has-[:checked]:bg-purple-900/20">
                            <input type="radio" name="role" value="{{ $role->name }}"
                                {{ old('role') === $role->name ? 'checked' : '' }}
                                @if($role->name === 'super-admin' && ! auth()->user()->hasRole('super-admin')) disabled title="Only a super-admin can grant this role" @endif
                                class="mt-0.5 text-purple-600 border-surface-dim focus:ring-purple-500">
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-on-surface dark:text-gray-200">{{ $role->name }}</span>
                                @if(isset($roleDescriptions[$role->name]))
                                    <span class="text-xs text-secondary dark:text-gray-400 mt-0.5">{{ $roleDescriptions[$role->name] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Services (centrale + manager) --}}
            <div id="services-section" class="{{ !in_array(old('role'), ['centrale','manager']) ? 'hidden' : '' }} p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-3">
                <p class="text-sm font-medium text-on-surface dark:text-gray-200">Assign Services</p>
                <p class="text-xs text-secondary dark:text-gray-400">Which services this user handles. At least one required.</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach($services as $service)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                {{ in_array($service->id, old('services', [])) ? 'checked' : '' }}
                                class="rounded border-surface-dim text-purple-600 shadow-sm focus:ring-purple-500">
                            <span class="ml-2 text-sm text-on-surface dark:text-gray-300">{{ $service->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('services')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Territory assignments (manager only) --}}
            <div id="territory-section" class="{{ old('role') !== 'manager' ? 'hidden' : '' }} p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-on-surface dark:text-gray-200">Administrative Territories</p>
                        <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">
                            Each territory is a governorate (covers all its delegations) or a specific delegation.
                            Add as many as needed.
                        </p>
                    </div>
                    <button type="button" id="add-territory"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Territory
                    </button>
                </div>

                @error('territories')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

                <div id="territory-list" class="space-y-3">
                    {{-- Existing rows from old() on validation failure --}}
                    @forelse($oldTerritories as $i => $territory)
                        @include('admin.users._territory_row', [
                            'index'        => $i,
                            'governorates' => $governorates,
                            'govId'        => $territory['governorate_id'] ?? '',
                            'delId'        => $territory['delegation_id'] ?? '',
                        ])
                    @empty
                        {{-- Empty state placeholder --}}
                        <p id="no-territory-hint" class="text-xs text-secondary dark:text-gray-400 italic">
                            No territories added yet. Click "Add Territory" to start.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-on-surface dark:text-gray-300 hover:text-secondary">Cancel</a>
                <button type="submit" class="admin-add-button px-4 py-2 text-sm">Create User</button>
            </div>
        </form>
    </div>

    {{-- Hidden template row cloned by JS --}}
    <template id="territory-row-tpl">
        @include('admin.users._territory_row', [
            'index'        => '__IDX__',
            'governorates' => $governorates,
            'govId'        => '',
            'delId'        => '',
        ])
    </template>

    <script>
    (function () {
        const SERVICE_ROLES    = ['centrale', 'manager'];
        const TERRITORY_ROLES  = ['manager'];

        const servicesSection  = document.getElementById('services-section');
        const territorySection = document.getElementById('territory-section');
        const territoryList    = document.getElementById('territory-list');
        const addBtn           = document.getElementById('add-territory');
        const tpl              = document.getElementById('territory-row-tpl');
        const hint             = document.getElementById('no-territory-hint');

        // Per-governorate delegation data from PHP
        const delegationsMap = @json($delegationsMap);

        let rowIndex = {{ count($oldTerritories) }};

        // ------------------------------------------------------------------
        // Role radio → show/hide panels
        // ------------------------------------------------------------------
        function updatePanels() {
            const role = document.querySelector('input[name="role"]:checked')?.value;
            servicesSection.classList.toggle('hidden', !SERVICE_ROLES.includes(role));
            territorySection.classList.toggle('hidden', !TERRITORY_ROLES.includes(role));
        }

        document.querySelectorAll('input[name="role"]')
            .forEach(r => r.addEventListener('change', updatePanels));

        updatePanels();

        // ------------------------------------------------------------------
        // Territory rows
        // ------------------------------------------------------------------
        function buildDelegationOptions(govId, selectedDelId) {
            const delegations = delegationsMap[govId] || [];
            let html = '<option value="">— Whole governorate —</option>';
            delegations.forEach(d => {
                const sel = String(d.id) === String(selectedDelId) ? ' selected' : '';
                html += `<option value="${d.id}"${sel}>${d.name}</option>`;
            });
            return html;
        }

        function attachRowEvents(row) {
            const govSel = row.querySelector('.gov-select');
            const delSel = row.querySelector('.del-select');
            const delWrap = row.querySelector('.del-wrap');

            function syncDelegations() {
                const govId = govSel.value;
                const dels  = delegationsMap[govId] || [];
                delWrap.classList.toggle('hidden', !govId);
                if (govId) {
                    delSel.innerHTML = buildDelegationOptions(govId, delSel.value);
                }
            }

            govSel.addEventListener('change', syncDelegations);
            syncDelegations(); // run once on attach

            row.querySelector('.remove-territory')?.addEventListener('click', () => {
                row.remove();
                if (!territoryList.querySelector('.territory-row')) {
                    if (hint) hint.classList.remove('hidden');
                }
            });
        }

        // Attach events to any server-rendered rows (old() on error)
        document.querySelectorAll('.territory-row').forEach(attachRowEvents);

        addBtn.addEventListener('click', () => {
            if (hint) hint.classList.add('hidden');

            const html = tpl.innerHTML.replace(/__IDX__/g, rowIndex++);
            const tmp  = document.createElement('div');
            tmp.innerHTML = html;
            const row = tmp.firstElementChild;
            territoryList.appendChild(row);
            attachRowEvents(row);
        });
    })();
    </script>
@endsection
