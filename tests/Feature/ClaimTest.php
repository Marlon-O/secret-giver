<?php

it('asks everyone to wait until the draw happens', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol']);

    $this->get("/g/{$group->slug}")->assertOk()->assertSee('Waiting for the organizer');
});

it('lists names after the draw', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->get("/g/{$group->slug}")->assertOk()->assertSee(['Alice', 'Bob', 'Carol']);
});

it('returns 404 for an unknown group', function () {
    $this->get('/g/doesnotexist')->assertNotFound();
});

it('claims a name, sets a cookie and shows the match', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $response = $this->post("/g/{$group->slug}/claim/{$people['Alice']->id}");

    $response->assertRedirect("/g/{$group->slug}/me")->assertCookie("giver_{$group->slug}");
    expect($people['Alice']->fresh()->isClaimed())->toBeTrue();

    $match = $people['Alice']->fresh()->assignedTo;
    $this->withCookie("giver_{$group->slug}", $response->getCookie("giver_{$group->slug}")->getValue())
        ->get("/g/{$group->slug}/me")
        ->assertOk()
        ->assertSee($match->name);
});

it('rejects claiming a name that is already claimed', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $people['Alice']->claim();

    $this->post("/g/{$group->slug}/claim/{$people['Alice']->id}")
        ->assertRedirect("/g/{$group->slug}")
        ->assertSessionHasErrors('claim')
        ->assertCookieMissing("giver_{$group->slug}");
});

it('cannot claim before the draw', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol']);

    $this->post("/g/{$group->slug}/claim/{$people['Alice']->id}")->assertRedirect("/g/{$group->slug}");

    expect($people['Alice']->fresh()->isClaimed())->toBeFalse();
});

it('sends a returning device straight to its reveal page', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $token = $people['Alice']->claim();

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|{$token}")
        ->get("/g/{$group->slug}")
        ->assertRedirect("/g/{$group->slug}/me");
});

it('ignores a forged or stale cookie', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $people['Alice']->claim();

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|forged")
        ->get("/g/{$group->slug}/me")
        ->assertRedirect("/g/{$group->slug}");
});

it('stops recognising a device after the organizer resets its claim', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $token = $people['Alice']->claim();
    $people['Alice']->fresh()->releaseClaim();

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|{$token}")
        ->get("/g/{$group->slug}/me")
        ->assertRedirect("/g/{$group->slug}");
});

it('shows the budget, date and the match wishlist', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $token = $people['Alice']->claim();
    $people['Alice']->assignedTo->update(['wishlist' => 'Fuzzy socks <script>']);

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|{$token}")
        ->get("/g/{$group->slug}/me")
        ->assertOk()
        ->assertSee($group->budget)
        ->assertSee('Fuzzy socks &lt;script&gt;', escape: false);
});

it('saves my wishlist', function () {
    [$group, $people] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);
    $token = $people['Alice']->claim();

    $this->withCookie("giver_{$group->slug}", "{$people['Alice']->id}|{$token}")
        ->patch("/g/{$group->slug}/me/wishlist", ['wishlist' => 'Books, tea'])
        ->assertRedirect("/g/{$group->slug}/me");

    expect($people['Alice']->fresh()->wishlist)->toBe('Books, tea');
});

it('refuses to save a wishlist without a claim', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->patch("/g/{$group->slug}/me/wishlist", ['wishlist' => 'Hacked'])->assertForbidden();
});
