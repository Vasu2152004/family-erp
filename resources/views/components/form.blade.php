@props([
    'method' => 'POST',
    'action' => '',
    'id' => null,
    'validate' => true,
])

@php
    $formMethod = strtoupper($method);
    $needsMethodOverride = in_array($formMethod, ['PUT', 'PATCH', 'DELETE']);
    $formClasses = $attributes->get('class', 'space-y-6');
    if ($validate) {
        $formClasses .= ' needs-validation';
    }
@endphp

<form 
    method="{{ $needsMethodOverride ? 'POST' : $formMethod }}"
    action="{{ $action }}"
    @if($id) id="{{ $id }}" @endif
    @if($validate) data-validate="true" @endif
    {{ $attributes->merge(['class' => $formClasses]) }}
>
    @csrf
    
    @if($needsMethodOverride)
        @method($formMethod)
    @endif
    
    {{ $slot }}
</form>





















