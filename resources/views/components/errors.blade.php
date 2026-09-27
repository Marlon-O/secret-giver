@props(['keys'])

@foreach ((array) $keys as $key)
    @error($key)
        <p {{ $attributes->merge(['class' => 'mt-2 rounded-lg bg-cranberry/10 px-3 py-2 text-sm font-medium text-cranberry']) }} role="alert">{{ $message }}</p>
    @enderror
@endforeach
