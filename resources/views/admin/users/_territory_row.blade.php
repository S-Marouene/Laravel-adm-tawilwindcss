{{--
  Reusable territory row partial.
  Variables expected:
    $index        – numeric index (or '__IDX__' for JS template)
    $governorates – Governorate collection with delegations eager-loaded
    $govId        – pre-selected governorate id ('' if none)
    $delId        – pre-selected delegation id ('' if none)
--}}
<div class="territory-row flex gap-2 items-start p-3 rounded-lg border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-800">

    {{-- Governorate --}}
    <div class="flex-1 min-w-0">
        <label class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Governorate <span class="text-red-500">*</span></label>
        <select name="territories[{{ $index }}][governorate_id]"
            class="gov-select w-full px-2.5 py-2 border border-surface-dim dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            <option value="">— Select —</option>
            @foreach($governorates as $gov)
                <option value="{{ $gov->id }}" {{ (string)$govId === (string)$gov->id ? 'selected' : '' }}>
                    {{ $gov->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Delegation (hidden until a governorate is picked) --}}
    <div class="flex-1 min-w-0 del-wrap {{ $govId ? '' : 'hidden' }}">
        <label class="block text-xs font-medium text-secondary dark:text-gray-400 mb-1">Delegation <span class="text-xs font-normal">(optional — leave blank for all)</span></label>
        <select name="territories[{{ $index }}][delegation_id]"
            class="del-select w-full px-2.5 py-2 border border-surface-dim dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            <option value="">— Whole governorate —</option>
            @if($govId)
                @foreach($governorates->firstWhere('id', $govId)?->delegations ?? [] as $del)
                    <option value="{{ $del->id }}" {{ (string)$delId === (string)$del->id ? 'selected' : '' }}>
                        {{ $del->name }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    {{-- Remove button --}}
    <button type="button"
        class="remove-territory mt-6 flex-shrink-0 p-1.5 rounded text-secondary hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        title="Remove this territory">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
