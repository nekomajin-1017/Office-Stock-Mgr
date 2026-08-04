@props([
    'name',
    'id' => null,
    'label' => '',
    'type' => 'text',
    'value' => null,
    'useOld' => true,
])

@php
    $fieldValue = $useOld ? old($name, $value) : $value;
    $errorMessage = $errors->first($name);
    $fieldId = $id ?? $name;
@endphp

<div class="content-block form-group">
    @if($label !== '')
        <label class="field-label form-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif

    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if(! is_null($fieldValue) && $type !== 'password')
            value="{{ $fieldValue }}"
        @endif
        {{ $attributes->class(['form-element', 'form-control']) }}
    >

    @if($errorMessage)
        <p class="text-content field-error">{{ $errorMessage }}</p>
    @endif
</div>
