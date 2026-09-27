<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Participant;
use App\Support\ClaimCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function index(Request $request, Group $group): View|RedirectResponse
    {
        if ($group->isDrawn() && ClaimCookie::resolve($request, $group)) {
            return redirect()->route('group.me', $group);
        }

        return view('group.index', [
            'group' => $group,
            'participants' => $group->participants()->get(),
        ]);
    }

    public function claim(Request $request, Group $group, Participant $participant): RedirectResponse
    {
        if (! $group->isDrawn()) {
            return redirect()->route('group.show', $group);
        }

        if (ClaimCookie::resolve($request, $group)) {
            return redirect()->route('group.me', $group);
        }

        $token = $participant->claim();

        if ($token === null) {
            throw ValidationException::withMessages([
                'claim' => "{$participant->name} has already been claimed. If that's you, ask the organizer to reset it.",
            ])->redirectTo(route('group.show', $group));
        }

        return redirect()
            ->route('group.me', $group)
            ->withCookie(ClaimCookie::make($group, $participant, $token));
    }

    public function reveal(Request $request, Group $group): View|RedirectResponse
    {
        $me = ClaimCookie::resolve($request, $group);

        if (! $me || ! $group->isDrawn()) {
            return redirect()->route('group.show', $group);
        }

        return view('group.reveal', [
            'group' => $group,
            'me' => $me,
            'match' => $me->assignedTo,
        ]);
    }

    public function updateWishlist(Request $request, Group $group): RedirectResponse
    {
        $me = ClaimCookie::resolve($request, $group);

        abort_unless($me, 403);

        $me->update($request->validate([
            'wishlist' => ['nullable', 'string', 'max:2000'],
        ]));

        return redirect()->route('group.me', $group)->with('status', 'Wishlist saved.');
    }
}
