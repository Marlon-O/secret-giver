<x-layouts.app :title="$group->name">
    <section class="mb-8 text-center">
        <p class="label !text-twine">{{ $group->name }}</p>
        <h1 class="font-display text-4xl leading-tight text-paper">Hi, {{ $me->name }}</h1>
        <p class="mt-2 text-paper/70">
            @if (filled($me->wishlist))
                You're all set. Once the organizer draws names, come back to this link and tap your name again to see who you're buying for, along with their wishlist.
            @else
                Before names are drawn, write your wishlist so your Secret Giver knows what you'd like.
            @endif
        </p>
    </section>

    <section class="card">
        <h2 class="section-title">Your wishlist</h2>
        <p class="mt-1 text-ink-soft">Your Secret Giver will see this. A few ideas, sizes or links help a lot.</p>
        <form method="POST" action="{{ route('group.wishlist', $group) }}" class="mt-4">
            @csrf @method('PATCH')
            <label for="wishlist" class="sr-only">Your wishlist</label>
            <textarea id="wishlist" name="wishlist" rows="5" maxlength="2000" class="input" required
                      placeholder="Warm socks (size 9), a mystery novel, good coffee…">{{ old('wishlist', $me->wishlist) }}</textarea>
            <x-errors keys="wishlist" />
            <button class="btn-primary mt-3 w-full">{{ filled($me->wishlist) ? 'Update wishlist' : 'Save wishlist' }}</button>
        </form>
        <p class="mt-4 text-sm text-ink-soft">You can change it any time before the exchange.</p>
    </section>
</x-layouts.app>
