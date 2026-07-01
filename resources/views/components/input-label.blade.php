@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ is_string($value) ? $value : (is_array($value) ? implode(', ', $value) : '') }}
</label>
