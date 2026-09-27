@props(['onPaper' => false, 'string' => true])

{{-- A kraft gift tag hanging from a length of twine. --}}
<div {{ $attributes->class(['relative pt-7']) }}>
    @if ($string)
        <svg class="pointer-events-none absolute top-0 left-1/2 h-[46px] w-16 -translate-x-1/2" viewBox="0 0 64 46" fill="none" aria-hidden="true">
            <path d="M32 46 C 30 28, 20 14, 8 0 M32 46 C 34 28, 44 14, 56 0" stroke="var(--color-twine)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    @endif
    <div @class(['tag px-4 pt-11 pb-5 text-center', 'tag-on-paper' => $onPaper])>
        {{ $slot }}
    </div>
</div>
