<x-layouts.app :title="$group->name">
    <div x-data="{
             key: @js("santa.revealed.{$group->slug}"),
             revealed: false,
             init() {
                 try { this.revealed = sessionStorage.getItem(this.key) === '1' } catch {}
                 if (@js((bool) session('status'))) this.revealed = true
             },
             reveal() {
                 this.revealed = true
                 try { sessionStorage.setItem(this.key, '1') } catch {}
             },
         }">
        <section class="mb-2 text-center">
            <p class="label !text-twine">{{ $group->name }}</p>
            <h1 class="font-display text-4xl leading-tight text-paper">Hi, {{ $me->name }}</h1>
            <p class="mt-2 text-paper/70" x-show="! revealed">Your tag is ready. Make sure nobody's looking over your shoulder.</p>
            <p class="mt-2 text-paper/70" x-show="revealed" x-cloak>Keep it a secret until the exchange.</p>
        </section>

        {{-- Signature moment: the tag flips to show the match. --}}
        <div class="flip sway mx-auto mt-6 w-64 sm:w-72">
            <div class="flip-inner" :class="revealed && 'is-flipped'">
                <button type="button" class="flip-face block w-full text-left" @click="reveal()" :tabindex="revealed ? -1 : 0" :aria-hidden="revealed">
                    <x-tag>
                        <span class="block text-xs font-semibold tracking-[.14em] text-ink/60 uppercase">For {{ $me->name }}'s eyes only</span>
                        <span class="mt-6 block font-display text-3xl">Tap to flip</span>
                        <span class="mt-6 mb-3 block text-sm text-ink/60">to see who you're buying for</span>
                    </x-tag>
                </button>

                <div class="flip-face flip-back" :aria-hidden="! revealed">
                    <x-tag :string="false" class="h-full">
                        <div class="text-left" aria-live="polite">
                            <p class="text-xs font-semibold tracking-[.14em] text-ink/60 uppercase">To</p>
                            <p class="font-hand text-5xl leading-tight font-bold break-words" x-text="revealed ? @js($match?->name) : ''"></p>
                            <div class="my-3 border-t border-dashed border-ink/30"></div>
                            <p class="text-xs font-semibold tracking-[.14em] text-ink/60 uppercase">From</p>
                            <p class="font-hand text-2xl">Your Secret Santa</p>
                        </div>
                    </x-tag>
                </div>
            </div>
        </div>

        <div x-show="revealed" x-cloak x-transition.opacity.duration.500ms class="mt-10 space-y-5">
            @if ($group->budget || $group->exchange_date)
                <dl class="grid grid-cols-2 gap-3">
                    @if ($group->budget)
                        <div class="rounded-2xl bg-pine-soft/70 px-4 py-3 ring-1 ring-paper/10">
                            <dt class="text-xs font-semibold tracking-[.12em] text-paper/60 uppercase">Budget</dt>
                            <dd class="font-display text-xl text-paper">{{ $group->budget }}</dd>
                        </div>
                    @endif
                    @if ($group->exchange_date)
                        <div class="rounded-2xl bg-pine-soft/70 px-4 py-3 ring-1 ring-paper/10">
                            <dt class="text-xs font-semibold tracking-[.12em] text-paper/60 uppercase">Exchange</dt>
                            <dd class="font-display text-xl text-paper">{{ $group->exchange_date->format('D, M j') }}</dd>
                        </div>
                    @endif
                </dl>
            @endif

            <section class="card">
                <h2 class="section-title">{{ $match?->name }}'s wishlist</h2>
                @if (filled($match?->wishlist))
                    <p class="mt-3 whitespace-pre-line text-ink">{{ $match->wishlist }}</p>
                @else
                    <p class="mt-2 text-ink-soft">{{ $match?->name }} hasn't written one yet. Check back later, since it shows up here as soon as they do.</p>
                @endif
            </section>

            <section class="card">
                <h2 class="section-title">Your wishlist</h2>
                <p class="mt-1 text-ink-soft">Your Secret Santa will see this. A few ideas, sizes or links help a lot.</p>
                <form method="POST" action="{{ route('group.wishlist', $group) }}" class="mt-4">
                    @csrf @method('PATCH')
                    <label for="wishlist" class="sr-only">Your wishlist</label>
                    <textarea id="wishlist" name="wishlist" rows="4" maxlength="2000" class="input"
                              placeholder="Warm socks (size 9), a mystery novel, good coffee…">{{ old('wishlist', $me->wishlist) }}</textarea>
                    <x-errors keys="wishlist" />
                    <button class="btn-primary mt-3 w-full">Save wishlist</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
