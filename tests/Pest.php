<?php

use App\Models\Group;
use App\Services\SecretSantaDrawer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Create a group with the given participant names. The admin token is always "secret-admin-token".
 *
 * @return array{0: Group, 1: Collection<string, \App\Models\Participant>}
 */
function groupWith(array $names, bool $drawn = false): array
{
    $group = Group::factory()->create();

    $people = collect($names)->mapWithKeys(fn ($name) => [
        $name => $group->participants()->create(['name' => $name]),
    ]);

    if ($drawn) {
        $group->performDraw(new SecretSantaDrawer);
    }

    return [$group->fresh(), $people->map->fresh()];
}

function adminUrl(Group $group, string $path = ''): string
{
    return "/g/{$group->slug}/admin/secret-admin-token".($path ? "/{$path}" : '');
}
