@props(['onPaper' => false, 'string' => true])

{{-- A kraft gift tag hanging from a length of twine. --}}
<div {{ $attributes->class(['relative pt-7']) }}>
    @if ($string)
        <x-twine />
    @endif
    <div @class(['tag px-4 pt-11 pb-5 text-center', 'tag-on-paper' => $onPaper])>
        {{ $slot }}
    </div>
</div>
