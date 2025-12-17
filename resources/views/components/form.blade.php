@props([
    'method' => 'POST',
    'action' => '',
    'id' => null,
])

@php
    $formMethod = strtoupper($method);
    $needsMethodOverride = in_array($formMethod, ['PUT', 'PATCH', 'DELETE']);
@endphp

<form 
    method="{{ $needsMethodOverride ? 'POST' : $formMethod }}"
    action="{{ $action }}"
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    @csrf
    
    @if($needsMethodOverride)
        @method($formMethod)
    @endif
    
    {{ $slot }}
</form>











