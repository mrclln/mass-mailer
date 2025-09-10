@props(['field', 'framework' => 'bootstrap'])

@if ($errors->has($field))
    @if($framework === 'tailwind')
        <div class="text-red-500 text-sm mt-1">
            {{ $errors->first($field) }}
        </div>
    @else
        <div class="text-danger small mt-1">
            {{ $errors->first($field) }}
        </div>
    @endif
@endif
