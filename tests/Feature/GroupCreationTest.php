<?php

use App\Models\Group;

it('shows the landing page', function () {
    $this->get('/')->assertOk()->assertSee('Start a group');
});

it('creates a group and redirects to the admin link', function () {
    $response = $this->post('/groups', [
        'name' => 'Family Gift Swap',
        'budget' => '$30',
        'exchange_date' => '2026-12-24',
        'participants' => ['Alice', ' Bob ', '', 'Carol'],
    ]);

    $group = Group::firstOrFail();

    expect($group->name)->toBe('Family Gift Swap')
        ->and($group->participants->pluck('name')->all())->toBe(['Alice', 'Bob', 'Carol'])
        ->and($group->slug)->toHaveLength(12);

    $response->assertRedirectContains("/g/{$group->slug}/admin/");
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Family Gift Swap');
});

it('requires at least three distinct names', function (array $names, string $errorKey) {
    $this->post('/groups', ['name' => 'Tiny', 'participants' => $names])
        ->assertSessionHasErrors($errorKey);

    expect(Group::count())->toBe(0);
})->with([
    'two names' => [['Alice', 'Bob'], 'participants'],
    'duplicates' => [['Alice', 'alice', 'Bob'], 'participants.1'],
]);

it('rejects a wrong admin token', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol']);

    $this->get("/g/{$group->slug}/admin/wrong-token")->assertNotFound();
});

it('updates budget and date at any time', function () {
    [$group] = groupWith(['Alice', 'Bob', 'Carol'], drawn: true);

    $this->patch(adminUrl($group), ['name' => 'Renamed', 'budget' => '$50', 'exchange_date' => '2026-12-20'])
        ->assertRedirect(adminUrl($group));

    expect($group->fresh())
        ->name->toBe('Renamed')
        ->budget->toBe('$50')
        ->exchange_date->toDateString()->toBe('2026-12-20');
});
