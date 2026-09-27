<?php

use App\Models\Participant;

it('adds and removes participants before the draw', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol']);

    $this->post(adminUrl($group, 'participants'), ['name' => 'Dave'])->assertRedirect(adminUrl($group));
    expect($group->participants()->pluck('name')->all())->toContain('Dave');

    $this->delete(adminUrl($group, "participants/{$people['Alice']->id}"))->assertRedirect(adminUrl($group));
    expect($group->participants()->pluck('name')->all())->not->toContain('Alice');
});

it('rejects a duplicate participant name', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol']);

    $this->post(adminUrl($group, 'participants'), ['name' => 'alice'])->assertSessionHasErrors('name');
});

it('cannot touch participants from another group', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol']);
    [, $others] = groupWith(['Xena', 'Yuri', 'Zoe']);

    $this->delete(adminUrl($group, "participants/{$others['Xena']->id}"))->assertNotFound();
});

it('adds a mutual exclusion for a couple', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol', 'Dave']);

    $this->post(adminUrl($group, 'exclusions'), [
        'giver_id' => $people['Alice']->id,
        'receiver_id' => $people['Bob']->id,
        'mutual' => '1',
    ])->assertRedirect(adminUrl($group));

    expect($group->exclusions()->get(['giver_id', 'receiver_id'])->map(fn ($e) => [$e->giver_id, $e->receiver_id])->all())
        ->toEqualCanonicalizing([
            [$people['Alice']->id, $people['Bob']->id],
            [$people['Bob']->id, $people['Alice']->id],
        ]);
});

it('removes both directions of a couple rule at once', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol', 'Dave']);
    $rule = $group->exclusions()->create(['giver_id' => $people['Alice']->id, 'receiver_id' => $people['Bob']->id]);
    $group->exclusions()->create(['giver_id' => $people['Bob']->id, 'receiver_id' => $people['Alice']->id]);

    $this->delete(adminUrl($group, "exclusions/{$rule->id}"), ['both' => '1'])->assertRedirect(adminUrl($group));

    expect($group->exclusions()->count())->toBe(0);
});

it('draws, respecting exclusions', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol', 'Dave']);
    $group->exclusions()->create(['giver_id' => $people['Alice']->id, 'receiver_id' => $people['Bob']->id]);

    $this->post(adminUrl($group, 'draw'))->assertRedirect(adminUrl($group));

    expect($group->fresh()->isDrawn())->toBeTrue();
    $assignments = Participant::where('group_id', $group->id)->pluck('assigned_to_id', 'id');
    expect($assignments->filter()->count())->toBe(4)
        ->and($assignments->unique()->count())->toBe(4)
        ->and($assignments[$people['Alice']->id])->not->toBe($people['Bob']->id);
});

it('shows a friendly error when the draw is impossible', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol']);
    $group->exclusions()->create(['giver_id' => $people['Alice']->id, 'receiver_id' => $people['Bob']->id]);
    $group->exclusions()->create(['giver_id' => $people['Alice']->id, 'receiver_id' => $people['Carol']->id]);

    $this->post(adminUrl($group, 'draw'))->assertSessionHasErrors('draw');

    expect($group->fresh()->isDrawn())->toBeFalse();
});

it('locks participants and exclusions after the draw', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->post(adminUrl($group, 'participants'), ['name' => 'Dave'])->assertSessionHasErrors('locked');
    $this->delete(adminUrl($group, "participants/{$people['Alice']->id}"))->assertSessionHasErrors('locked');
    $this->post(adminUrl($group, 'exclusions'), [
        'giver_id' => $people['Alice']->id,
        'receiver_id' => $people['Bob']->id,
    ])->assertSessionHasErrors('locked');

    expect($group->participants()->count())->toBe(3)
        ->and($group->exclusions()->count())->toBe(0);
});

it('undoes a draw', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->delete(adminUrl($group, 'draw'))->assertRedirect(adminUrl($group));

    expect($group->fresh()->isDrawn())->toBeFalse()
        ->and($group->participants()->whereNotNull('assigned_to_id')->count())->toBe(0);
});

it('never reveals who got whom to the organizer', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $response = $this->get(adminUrl($group))->assertOk();

    foreach ($people as $person) {
        expect($response->viewData('participants')->firstWhere('id', $person->id)->relationLoaded('assignedTo'))->toBeFalse();
    }
    $response->assertDontSee('buying for');
});

it('resets a claim', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $people['Alice']->claim();

    $this->delete(adminUrl($group, "participants/{$people['Alice']->id}/claim"))->assertRedirect(adminUrl($group));

    expect($people['Alice']->fresh()->isClaimed())->toBeFalse();
});
