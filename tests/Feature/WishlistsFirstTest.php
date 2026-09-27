<?php

use App\Models\Group;
use App\Services\SecretGiverDrawer;

function wishlistGroupWith(array $names): array
{
    [$group, $people] = groupWith($names);
    $group->update(['wishlists_required' => true]);

    return [$group->fresh(), $people];
}

it('creates a group that collects wishlists first', function () {
    $this->post('/groups', [
        'name' => 'Office',
        'wishlists_required' => '1',
        'participants' => ['Alice', 'Bob', 'Carol'],
    ]);

    expect(Group::firstOrFail()->wishlists_required)->toBeTrue();
});

it('lets people claim their name before the draw', function () {
    [$group, $people] = wishlistGroupWith(['Alice', 'Bob', 'Carol']);

    $this->get("/g/{$group->slug}")->assertOk()->assertSee('write your wishlist');

    $response = $this->post("/g/{$group->slug}/claim/{$people['Alice']->id}")
        ->assertRedirect("/g/{$group->slug}/me");

    $this->withCookie("giver_{$group->slug}", $response->getCookie("giver_{$group->slug}")->getValue())
        ->get("/g/{$group->slug}/me")
        ->assertOk()
        ->assertSee('Your wishlist')
        ->assertDontSee('Tap to flip');
});

it('requires a non-empty wishlist', function () {
    [$group, $people] = wishlistGroupWith(['Alice', 'Bob', 'Carol']);
    $token = $people['Alice']->claim();

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|{$token}")
        ->patch("/g/{$group->slug}/me/wishlist", ['wishlist' => '   '])
        ->assertSessionHasErrors('wishlist');
});

it('blocks the draw until everyone has a wishlist', function () {
    [$group, $people] = wishlistGroupWith(['Alice', 'Bob', 'Carol']);
    $people['Alice']->update(['wishlist' => 'Socks']);
    $people['Bob']->update(['wishlist' => 'Tea']);

    $this->post(adminUrl($group, 'draw'))->assertSessionHasErrors(['draw' => 'Still waiting on a wishlist from Carol.']);
    expect($group->fresh()->isDrawn())->toBeFalse();

    $people['Carol']->update(['wishlist' => 'Books']);

    $this->post(adminUrl($group, 'draw'))->assertSessionHasNoErrors();
    expect($group->fresh()->isDrawn())->toBeTrue();
});

it('shows wishlist progress to the organizer', function () {
    [$group, $people] = wishlistGroupWith(['Alice', 'Bob', 'Carol']);
    $people['Alice']->update(['wishlist' => 'Socks']);

    $this->get(adminUrl($group))->assertOk()->assertSee('1 of 3 wishlists in')->assertSee('Waiting on Bob and Carol');
});

it('reopens every name after the draw so people can pick again and see their match', function () {
    [$group, $people] = wishlistGroupWith(['Alice', 'Bob', 'Carol']);
    $people->each(function ($person) {
        $person->claim();
        $person->update(['wishlist' => 'Something nice']);
    });

    $group->performDraw(new SecretGiverDrawer);

    expect($group->participants()->whereNotNull('claim_token_hash')->count())->toBe(0);
    $this->get("/g/{$group->slug}")->assertOk()->assertDontSee('Taken');

    $response = $this->post("/g/{$group->slug}/claim/{$people['Alice']->id}")
        ->assertRedirect("/g/{$group->slug}/me");

    $this->withCookie("giver_{$group->slug}", $response->getCookie("giver_{$group->slug}")->getValue())
        ->get("/g/{$group->slug}/me")
        ->assertOk()
        ->assertSee($people['Alice']->fresh()->assignedTo->name)
        ->assertSee('Something nice');
});

it('cannot change the wishlist setting after the draw', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->patch(adminUrl($group), ['name' => $group->name, 'wishlists_required' => '1']);

    expect($group->fresh()->wishlists_required)->toBeFalse();
});
