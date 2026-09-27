<x-layouts.app :title="$group->name">
    <section class="mb-8 text-center">
        <p class="label !text-twine">Secret Santa</p>
        <h1 class="font-display text-4xl leading-tight text-paper">{{ $group->name }}</h1>
        @if ($group->budget || $group->exchange_date)
            <p class="mt-2 text-paper/70">
                @if ($group->budget) {{ $group->budget }} budget @endif
                @if ($group->budget && $group->exchange_date) · @endif
                @if ($group->exchange_date) Exchange {{ $group->exchange_date->format('D, M j') }} @endif
            </p>
        @endif
    </section>

    @if (! $group->isDrawn())
        <div class="card text-center">
            <h2 class="section-title">Waiting for the organizer to draw names</h2>
            <p class="mt-2 text-ink-soft">Come back to this same link once they have. You'll pick your name here and see who you're buying for.</p>
            <p class="mt-5 text-sm text-ink-soft">Taking part: {{ $participants->pluck('name')->join(', ', ' and ') }}</p>
        </div>
    @else
        <div x-data="{ picked: null }">
            <h2 class="mb-1 text-center font-display text-2xl text-paper">Which one is you?</h2>
            <p class="mb-6 text-center text-paper/70">Tap your own name. Once you pick it, it's yours on this device.</p>

            <x-errors keys="claim" class="!bg-paper mb-6" />

            <ul class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3">
                @foreach ($participants as $person)
                    <li>
                        @if ($person->isClaimed())
                            <x-tag class="opacity-45 saturate-50" aria-label="{{ $person->name }}, already taken">
                                <span class="block truncate font-hand text-2xl font-bold">{{ $person->name }}</span>
                                <span class="mt-1 block text-xs font-semibold tracking-[.12em] uppercase">Taken</span>
                            </x-tag>
                        @else
                            <button type="button" class="block w-full rounded-xl transition-transform hover:-translate-y-1 focus-visible:-translate-y-1"
                                    @click="picked = @js(['id' => $person->id, 'name' => $person->name])">
                                <x-tag>
                                    <span class="block truncate font-hand text-2xl font-bold">{{ $person->name }}</span>
                                    <span class="mt-1 block text-xs font-semibold tracking-[.12em] text-ink/60 uppercase">That's me</span>
                                </x-tag>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p class="mt-8 text-center text-sm text-paper/60">Is your name taken, but you haven't picked it? Ask the organizer to reset it.</p>

            {{-- Confirm dialog --}}
            <div x-show="picked" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-end bg-pine-ink/80 p-4 sm:place-items-center"
                 @keydown.escape.window="picked = null" @click.self="picked = null">
                <form method="POST" x-show="picked" x-trap.noscroll="picked" x-transition
                      :action="picked && @js(url("/g/{$group->slug}/claim")) + '/' + picked.id"
                      role="dialog" aria-modal="true" aria-labelledby="confirm-title"
                      class="card w-full max-w-sm text-center">
                    @csrf
                    <h3 id="confirm-title" class="font-display text-2xl">
                        Are you <span class="font-hand text-3xl font-bold" x-text="picked?.name"></span>?
                    </h3>
                    <p class="mt-2 text-ink-soft">This name will be locked to this device, so only pick it if it's really you.</p>
                    <div class="mt-6 flex flex-col gap-2">
                        <button class="btn-primary">Yes, that's me</button>
                        <button type="button" class="btn-quiet" @click="picked = null">Pick another name</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-layouts.app>
