@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5']) }}>
    {{ is_string($value) ? $value : (is_array($value) ? implode(', ', $value) : '') }}
</label>
