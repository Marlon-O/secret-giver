<?php

namespace App\Http\Controllers;

use App\Exceptions\ImpossibleDrawException;
use App\Models\Exclusion;
use App\Models\Group;
use App\Models\Participant;
use App\Services\SecretGiverDrawer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function show(Group $group, string $token): View
    {
        return view('admin.show', [
            'group' => $group,
            'token' => $token,
            'participants' => $group->participants()->get(),
            'missingWishlists' => $group->missingWishlists(),
            'rules' => $this->rulesFor($group),
        ]);
    }

    public function update(Request $request, Group $group, string $token): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'budget' => ['nullable', 'string', 'max:40'],
            'exchange_date' => ['nullable', 'date'],
            'wishlists_required' => ['sometimes', 'boolean'],
        ]);

        // The wishlist step happens before the draw, so it can't change once names are drawn.
        if ($group->isDrawn()) {
            unset($data['wishlists_required']);
        }

        $group->update($data);

        return $this->back($group, $token, 'Details saved.');
    }

    public function addParticipant(Request $request, Group $group, string $token): RedirectResponse
    {
        $this->ensureNotDrawn($group);

        $request->merge(['name' => trim((string) $request->input('name'))]);

        $existing = $group->participants()->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', function ($attribute, $value, $fail) use ($existing) {
                if (in_array(mb_strtolower($value), $existing, true)) {
                    $fail('Someone with that name is already in the group.');
                }
            }],
        ]);

        $group->participants()->create($data);

        return $this->back($group, $token, "{$data['name']} added.");
    }

    public function removeParticipant(Group $group, string $token, Participant $participant): RedirectResponse
    {
        $this->ensureNotDrawn($group);

        $participant->delete();

        return $this->back($group, $token, "{$participant->name} removed.");
    }

    public function resetClaim(Group $group, string $token, Participant $participant): RedirectResponse
    {
        $participant->releaseClaim();

        return $this->back($group, $token, "{$participant->name} can be claimed again.");
    }

    public function addExclusion(Request $request, Group $group, string $token): RedirectResponse
    {
        $this->ensureNotDrawn($group);

        $inGroup = Rule::exists('participants', 'id')->where('group_id', $group->id);

        $data = $request->validate([
            'giver_id' => ['required', 'integer', $inGroup],
            'receiver_id' => ['required', 'integer', 'different:giver_id', $inGroup],
            'mutual' => ['sometimes', 'boolean'],
        ], [
            'receiver_id.different' => 'Pick two different people.',
        ]);

        $pairs = [[$data['giver_id'], $data['receiver_id']]];
        if ($request->boolean('mutual')) {
            $pairs[] = [$data['receiver_id'], $data['giver_id']];
        }

        foreach ($pairs as [$giver, $receiver]) {
            $group->exclusions()->firstOrCreate(['giver_id' => $giver, 'receiver_id' => $receiver]);
        }

        return $this->back($group, $token, 'Rule added.');
    }

    public function removeExclusion(Request $request, Group $group, string $token, Exclusion $exclusion): RedirectResponse
    {
        $this->ensureNotDrawn($group);

        if ($request->boolean('both')) {
            $group->exclusions()
                ->where('giver_id', $exclusion->receiver_id)
                ->where('receiver_id', $exclusion->giver_id)
                ->delete();
        }

        $exclusion->delete();

        return $this->back($group, $token, 'Rule removed.');
    }

    public function draw(Group $group, string $token, SecretGiverDrawer $drawer): RedirectResponse
    {
        $this->ensureNotDrawn($group);

        $missing = $group->missingWishlists();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'draw' => 'Still waiting on a wishlist from '.$missing->join(', ', ' and ').'.',
            ]);
        }

        try {
            $group->performDraw($drawer);
        } catch (ImpossibleDrawException $e) {
            throw ValidationException::withMessages(['draw' => $e->getMessage()]);
        }

        return $this->back($group, $token, 'Names drawn! Share the group link with everyone.');
    }

    public function undoDraw(Group $group, string $token): RedirectResponse
    {
        $group->undoDraw();

        return $this->back($group, $token, 'Draw undone. You can edit the group and draw again.');
    }

    /**
     * Exclusions for display, folding a pair that goes both ways into one "mutual" rule.
     *
     * @return Collection<int, array{exclusion: Exclusion, mutual: bool}>
     */
    private function rulesFor(Group $group)
    {
        $exclusions = $group->exclusions()->with('giver', 'receiver')->get();
        $pairs = $exclusions->mapWithKeys(fn (Exclusion $e) => ["{$e->giver_id}-{$e->receiver_id}" => true]);

        return $exclusions
            ->map(fn (Exclusion $e) => [
                'exclusion' => $e,
                'mutual' => $pairs->has("{$e->receiver_id}-{$e->giver_id}"),
            ])
            // Keep one row per mutual pair.
            ->reject(fn ($rule) => $rule['mutual'] && $rule['exclusion']->giver_id > $rule['exclusion']->receiver_id)
            ->sortBy(fn ($rule) => $rule['exclusion']->giver->name)
            ->values();
    }

    private function ensureNotDrawn(Group $group): void
    {
        if ($group->isDrawn()) {
            throw ValidationException::withMessages([
                'locked' => 'Names are already drawn. Undo the draw to make changes.',
            ]);
        }
    }

    private function back(Group $group, string $token, string $status): RedirectResponse
    {
        return redirect()->route('admin.show', [$group, $token])->with('status', $status);
    }
}
