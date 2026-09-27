<x-layouts.app>
    <section class="mb-10">
        <h1 class="font-display text-[2.6rem] leading-[1.05] text-paper sm:text-5xl">
            Draw names.<br>
            <span class="text-twine">Nobody signs up.</span>
        </h1>
        <p class="mt-4 max-w-md text-lg text-paper/75">
            Add everyone's name and share one link. Each person taps their name to see who they're buying for, and nobody else can see it, not even you.
        </p>
    </section>

    {{-- Groups this browser has organized --}}
    <section x-data="{ groups: window.santaGroups.all() }" x-show="groups.length" x-cloak class="mb-8">
        <h2 class="label !text-paper/60">Your groups</h2>
        <ul class="space-y-2">
            <template x-for="group in groups" :key="group.slug">
                <li class="flex items-center gap-3 rounded-xl bg-pine-soft/80 px-4 py-3 ring-1 ring-paper/10">
                    <a :href="group.adminUrl" class="flex-1 truncate font-semibold text-paper hover:text-twine" x-text="group.name"></a>
                    <button type="button" class="text-sm text-paper/50 hover:text-paper"
                            @click="window.santaGroups.forget(group.slug); groups = window.santaGroups.all()"
                            :aria-label="`Remove ${group.name} from this list`">Hide</button>
                </li>
            </template>
        </ul>
    </section>

    <form method="POST" action="{{ route('groups.store') }}" class="card"
          x-data="{
              names: @js(old('participants', ['', '', ''])),
              add() { this.names.push(''); this.$nextTick(() => this.$refs.names.querySelector('li:last-child input').focus()) },
              remove(i) { this.names.splice(i, 1) },
              get filled() { return this.names.filter(n => n.trim()).length },
          }">
        @csrf
        <h2 class="section-title mb-5">Start a group</h2>

        <div class="space-y-4">
            <div>
                <label for="name" class="label">Group name</label>
                <input id="name" name="name" class="input" required maxlength="80" value="{{ old('name') }}" placeholder="Family Christmas {{ now()->year }}">
                <x-errors keys="name" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="budget" class="label">Budget <span class="normal-case tracking-normal font-normal">(optional)</span></label>
                    <input id="budget" name="budget" class="input" maxlength="40" value="{{ old('budget') }}" placeholder="$30">
                </div>
                <div>
                    <label for="exchange_date" class="label">Exchange day</label>
                    <input id="exchange_date" name="exchange_date" type="date" class="input" value="{{ old('exchange_date') }}">
                </div>
            </div>
            <x-errors :keys="['budget', 'exchange_date']" />

            <fieldset>
                <legend class="label">Who's taking part?</legend>
                <ol x-ref="names" class="space-y-2">
                    <template x-for="(name, i) in names" :key="i">
                        <li class="flex items-center gap-2">
                            <span class="w-6 shrink-0 text-right text-sm tabular-nums text-ink-soft" x-text="i + 1"></span>
                            <input name="participants[]" class="input" maxlength="60" x-model="names[i]"
                                   :aria-label="`Person ${i + 1}`" placeholder="Name"
                                   @keydown.enter.prevent="i === names.length - 1 ? add() : $el.closest('li').nextElementSibling.querySelector('input').focus()">
                            <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full text-ink-soft hover:bg-ink/5 hover:text-cranberry"
                                    @click="remove(i)" x-show="names.length > 3" :aria-label="`Remove person ${i + 1}`">
                                <svg viewBox="0 0 20 20" class="size-4" fill="currentColor" aria-hidden="true"><path d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"/></svg>
                            </button>
                        </li>
                    </template>
                </ol>
                <button type="button" class="btn-link mt-3 ml-8" @click="add()">+ Add a person</button>
                <x-errors keys="participants" />
                @if ($nameError = collect($errors->get('participants.*'))->flatten()->first())
                    <p class="mt-2 rounded-lg bg-cranberry/10 px-3 py-2 text-sm font-medium text-cranberry" role="alert">{{ $nameError }}</p>
                @endif
            </fieldset>
        </div>

        <div class="mt-7 flex flex-col-reverse items-center gap-3 border-t border-ink/10 pt-5 sm:flex-row sm:justify-between">
            <p class="text-sm text-ink-soft" x-text="filled < 3 ? `Add at least ${3 - filled} more` : `${filled} people`"></p>
            <button class="btn-primary w-full sm:w-auto">Create group</button>
        </div>
        <p class="mt-4 text-sm text-ink-soft">You'll get a private organizer link next. You can add rules there, like keeping couples from drawing each other, before you draw names.</p>
    </form>

    <section class="mt-12">
        <h2 class="label !text-paper/60">How it works</h2>
        <ol class="space-y-3 text-paper/80">
            <li class="flex gap-3"><span class="font-display text-twine">1</span> Add everyone's name. Set a budget and a date if you like.</li>
            <li class="flex gap-3"><span class="font-display text-twine">2</span> Draw names, then share the group link in your family chat.</li>
            <li class="flex gap-3"><span class="font-display text-twine">3</span> Each person taps their own name and flips their tag to see their match.</li>
        </ol>
    </section>
</x-layouts.app>
