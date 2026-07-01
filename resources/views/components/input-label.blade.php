@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5']) }}>
    {{ is_string($value) ? $value : (is_array($value) ? implode(', ', $value) : '') }}
</label>
