@php
    $adminLink = route('admin.show', [$group, $token]);
    $groupLink = route('group.show', $group);
    $drawn = $group->isDrawn();
    $claimedCount = $participants->filter->isClaimed()->count();
    $collectingWishlists = $group->wishlists_required && ! $drawn;
    $wishlistCount = $participants->count() - $missingWishlists->count();
@endphp

<x-layouts.app :title="$group->name">
    <div x-data x-init="window.giverGroups.remember(@js(['slug' => $group->slug, 'name' => $group->name, 'adminUrl' => $adminLink]))"></div>

    <section class="mb-6">
        <p class="label !text-twine">Organizer view</p>
        <h1 class="font-display text-4xl leading-tight text-paper">{{ $group->name }}</h1>
        <p class="mt-2 text-paper/70">
            {{ $participants->count() }} people
            @if ($group->budget) · {{ $group->budget }} budget @endif
            @if ($group->exchange_date) · {{ $group->exchange_date->format('D, M j') }} @endif
        </p>
    </section>

    {{-- The organizer link is the only way back in, so it gets top billing on a new group. --}}
    <section x-data="{ label: 'Copy' }" @class([
        'mb-6 rounded-2xl p-5 ring-1',
        'bg-twine/15 ring-twine/50' => session('created'),
        'bg-pine-soft/60 ring-paper/10' => ! session('created'),
    ])>
        <h2 class="font-semibold text-paper">{{ session('created') ? 'Save your organizer link' : 'Your organizer link' }}</h2>
        <p class="mt-1 text-sm text-paper/70">It's the only way back to this page, and there's no account to recover it. Keep it to yourself. We've also saved it in this browser.</p>
        <div class="mt-3 flex gap-2">
            <input readonly value="{{ $adminLink }}" aria-label="Organizer link" class="min-w-0 flex-1 rounded-lg bg-pine-ink/60 px-3 py-2 font-mono text-xs text-paper/80" @focus="$el.select()">
            <button type="button" class="btn shrink-0 bg-paper px-4 py-2 text-sm text-ink hover:bg-white"
                    @click="label = await window.copyLink(@js($adminLink)); setTimeout(() => label = 'Copy', 2000)"
                    x-text="label">Copy</button>
        </div>
    </section>

    <div class="space-y-5">
        {{-- Draw / share --}}
        <section class="card">
            @if ($drawn)
                <h2 class="section-title">Names are drawn</h2>
                <p class="mt-1 text-ink-soft">Send this link to everyone. Each person taps their own name to see their match.</p>

                <div x-data="{ label: 'Share group link' }" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <input readonly value="{{ $groupLink }}" aria-label="Group link" class="input font-mono text-sm" @focus="$el.select()">
                    <button type="button" class="btn-primary shrink-0"
                            @click="label = await window.shareLink(@js($groupLink), @js($group->name)); setTimeout(() => label = 'Share group link', 2000)"
                            x-text="label">Share group link</button>
                </div>

                <div class="mt-5">
                    <div class="flex justify-between text-sm">
                        <span class="font-semibold">{{ $claimedCount }} of {{ $participants->count() }} have seen their match</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink/10" role="presentation">
                        <div class="h-full rounded-full bg-pine" style="width: {{ $participants->count() ? round($claimedCount / $participants->count() * 100) : 0 }}%"></div>
                    </div>
                </div>
            @elseif ($collectingWishlists && $missingWishlists->isNotEmpty())
                <h2 class="section-title">Collecting wishlists</h2>
                <p class="mt-1 text-ink-soft">Send this link to everyone. Each person taps their own name and writes a wishlist. You can draw once everyone has.</p>

                <div x-data="{ label: 'Share group link' }" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <input readonly value="{{ $groupLink }}" aria-label="Group link" class="input font-mono text-sm" @focus="$el.select()">
                    <button type="button" class="btn-primary shrink-0"
                            @click="label = await window.shareLink(@js($groupLink), @js($group->name)); setTimeout(() => label = 'Share group link', 2000)"
                            x-text="label">Share group link</button>
                </div>

                <div class="mt-5">
                    <div class="flex justify-between text-sm">
                        <span class="font-semibold">{{ $wishlistCount }} of {{ $participants->count() }} wishlists in</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink/10" role="presentation">
                        <div class="h-full rounded-full bg-pine" style="width: {{ $participants->count() ? round($wishlistCount / $participants->count() * 100) : 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-ink-soft">Waiting on {{ $missingWishlists->join(', ', ' and ') }}.</p>
                </div>

                <button class="btn-primary mt-5 w-full" disabled>Draw names</button>
            @else
                <h2 class="section-title">Ready to draw?</h2>
                <p class="mt-1 text-ink-soft">
                    @if ($collectingWishlists) Everyone has written a wishlist. @endif
                    Check the names and rules below first. Once you draw, the list is locked until you undo the draw.
                    You won't see who got whom, so you can play too.
                </p>
                <form method="POST" action="{{ route('admin.draw', [$group, $token]) }}" class="mt-5">
                    @csrf
                    <button class="btn-primary w-full" @disabled($participants->count() < 3)>Draw names</button>
                </form>
                @if ($participants->count() < 3)
                    <p class="mt-2 text-center text-sm text-ink-soft">Add at least 3 people to draw.</p>
                @endif
            @endif
            <x-errors :keys="['draw', 'locked']" />
        </section>

        {{-- People --}}
        <section class="card">
            <h2 class="section-title mb-4">People</h2>
            <ul class="divide-y divide-ink/10">
                @foreach ($participants as $person)
                    <li class="flex items-center gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $person->name }}</p>
                            @if ($drawn)
                                <p class="text-sm text-ink-soft">
                                    {{ $person->isClaimed() ? 'Has seen their match' : "Hasn't opened the link yet" }}
                                    · {{ filled($person->wishlist) ? 'Wishlist written' : 'No wishlist yet' }}
                                </p>
                            @elseif ($collectingWishlists)
                                <p class="text-sm text-ink-soft">
                                    @if (filled($person->wishlist))
                                        Wishlist written
                                    @elseif ($person->isClaimed())
                                        Picked their name, no wishlist yet
                                    @else
                                        Hasn't opened the link yet
                                    @endif
                                </p>
                            @endif
                        </div>

                        @if (! $drawn)
                            <form method="POST" action="{{ route('admin.participants.destroy', [$group, $token, $person]) }}">
                                @csrf @method('DELETE')
                                <button class="btn-link" aria-label="Remove {{ $person->name }}">Remove</button>
                            </form>
                        @endif
                        @if ($person->isClaimed() && ($drawn || $collectingWishlists))
                            <form method="POST" action="{{ route('admin.claims.destroy', [$group, $token, $person]) }}"
                                  x-data @submit="if (! confirm(@js("Reset {$person->name}? Whoever claimed this name will lose access on their device, and anyone can claim it again."))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <button class="btn-link">Reset</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            @unless ($drawn)
                <form method="POST" action="{{ route('admin.participants.store', [$group, $token]) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input name="name" class="input" maxlength="60" placeholder="Add a person" aria-label="Name of person to add" required>
                    <button class="btn-quiet shrink-0">Add</button>
                </form>
                @unless (old('_details'))
                    <x-errors keys="name" />
                @endunless
            @endunless

            @if ($drawn || $collectingWishlists)
                <p class="mt-4 text-sm text-ink-soft">If someone tapped the wrong name, reset it so the right person can claim it.</p>
            @endif
        </section>

        {{-- Rules --}}
        <section class="card">
            <h2 class="section-title">Rules</h2>
            <p class="mt-1 text-ink-soft">Stop certain people from drawing each other, like couples or people who live together.</p>

            @if ($rules->isNotEmpty())
                <ul class="mt-4 divide-y divide-ink/10">
                    @foreach ($rules as ['exclusion' => $rule, 'mutual' => $mutual])
                        <li class="flex items-center gap-3 py-3">
                            <p class="flex-1">
                                @if ($mutual)
                                    <strong>{{ $rule->giver->name }}</strong> and <strong>{{ $rule->receiver->name }}</strong> won't draw each other
                                @else
                                    <strong>{{ $rule->giver->name }}</strong> won't draw <strong>{{ $rule->receiver->name }}</strong>
                                @endif
                            </p>
                            @unless ($drawn)
                                <form method="POST" action="{{ route('admin.exclusions.destroy', [$group, $token, $rule]) }}">
                                    @csrf @method('DELETE')
                                    @if ($mutual) <input type="hidden" name="both" value="1"> @endif
                                    <button class="btn-link">Remove</button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            @endif

            @unless ($drawn)
                <form method="POST" action="{{ route('admin.exclusions.store', [$group, $token]) }}" class="mt-4 rounded-xl bg-ink/[.03] p-4">
                    @csrf
                    <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                        <div>
                            <label for="giver_id" class="label">Person</label>
                            <select id="giver_id" name="giver_id" class="input" required>
                                @foreach ($participants as $person)
                                    <option value="{{ $person->id }}" @selected(old('giver_id') == $person->id)>{{ $person->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span class="pb-3 text-sm text-ink-soft">and</span>
                        <div>
                            <label for="receiver_id" class="label">Person</label>
                            <select id="receiver_id" name="receiver_id" class="input" required>
                                @foreach ($participants as $person)
                                    <option value="{{ $person->id }}" @selected(old('receiver_id', $participants->get(1)?->id) == $person->id)>{{ $person->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm">
                        <input type="hidden" name="mutual" value="0">
                        <input type="checkbox" name="mutual" value="1" checked class="size-4 accent-pine">
                        Both ways, so neither draws the other
                    </label>
                    <button class="btn-quiet mt-4 w-full">Add rule</button>
                    <x-errors :keys="['giver_id', 'receiver_id']" />
                </form>
            @endunless
        </section>

        {{-- Details --}}
        <details class="card group" @if ($errors->hasAny(['budget', 'exchange_date']) || old('_details')) open @endif>
            <summary class="section-title flex cursor-pointer list-none items-center justify-between">
                Budget, date, name and wishlists
                <span class="text-base text-ink-soft transition-transform group-open:rotate-180" aria-hidden="true">▾</span>
            </summary>
            <form method="POST" action="{{ route('admin.update', [$group, $token]) }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="_details" value="1">
                <div>
                    <label for="group_name" class="label">Group name</label>
                    <input id="group_name" name="name" class="input" required maxlength="80" value="{{ old('name', $group->name) }}">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="budget" class="label">Budget</label>
                        <input id="budget" name="budget" class="input" maxlength="40" value="{{ old('budget', $group->budget) }}" placeholder="$30">
                    </div>
                    <div>
                        <label for="exchange_date" class="label">Exchange day</label>
                        <input id="exchange_date" name="exchange_date" type="date" class="input" value="{{ old('exchange_date', $group->exchange_date?->toDateString()) }}">
                    </div>
                </div>
                <label class="flex items-start gap-3">
                    <input type="hidden" name="wishlists_required" value="0">
                    <input type="checkbox" name="wishlists_required" value="1" class="mt-1 size-4 shrink-0 accent-pine"
                           @checked(old('wishlists_required', $group->wishlists_required)) @disabled($drawn)>
                    <span>
                        <span class="block font-semibold">Collect wishlists first</span>
                        <span class="block text-sm text-ink-soft">
                            @if ($drawn)
                                Names are drawn, so this can't change now.
                            @else
                                Everyone picks their name and writes a wishlist before you can draw.
                            @endif
                        </span>
                    </span>
                </label>
                <x-errors :keys="old('_details') ? ['name', 'budget', 'exchange_date'] : ['budget', 'exchange_date']" />
                <button class="btn-quiet w-full">Save details</button>
            </form>
        </details>

        @if ($drawn)
            <form method="POST" action="{{ route('admin.draw.undo', [$group, $token]) }}" class="pt-2 text-center"
                  x-data @submit="if (! confirm('Undo the draw? Everyone who already looked will get a new match when you draw again.')) $event.preventDefault()">
                @csrf @method('DELETE')
                <button class="text-sm font-semibold text-paper/60 underline decoration-paper/30 underline-offset-4 hover:text-paper">Undo draw to change people or rules</button>
            </form>
        @endif
    </div>
</x-layouts.app>
