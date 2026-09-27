<?php

use App\Exceptions\ImpossibleDrawException;
use App\Services\SecretGiverDrawer;

function assertValidDraw(array $ids, array $forbidden, array $result): void
{
    expect(array_keys($result))->toEqualCanonicalizing($ids)
        ->and(array_values($result))->toEqualCanonicalizing($ids);

    foreach ($result as $giver => $receiver) {
        expect($receiver)->not->toBe($giver);
        expect(in_array([$giver, $receiver], $forbidden, true))->toBeFalse();
    }
}

it('produces a valid draw with no self-matches', function () {
    $ids = [1, 2, 3, 4, 5];

    assertValidDraw($ids, [], (new SecretGiverDrawer)->draw($ids));
});

it('always respects exclusions across many random draws', function () {
    $drawer = new SecretGiverDrawer;

    for ($run = 0; $run < 500; $run++) {
        $ids = range(1, random_int(3, 12));
        $forbidden = [];

        // Exclude a random pair or two, keeping the draw solvable (couples in a large group).
        foreach (array_slice($ids, 0, intdiv(count($ids), 4) * 2) as $i => $id) {
            if ($i % 2 === 0) {
                $forbidden[] = [$id, $id + 1];
                $forbidden[] = [$id + 1, $id];
            }
        }

        assertValidDraw($ids, $forbidden, $drawer->draw($ids, $forbidden));
    }
});

it('throws when exclusions make a draw impossible', function () {
    // Person 1 is excluded from everyone else.
    (new SecretGiverDrawer)->draw([1, 2, 3], [[1, 2], [1, 3]]);
})->throws(ImpossibleDrawException::class);

it('requires at least three participants', function () {
    (new SecretGiverDrawer)->draw([1, 2]);
})->throws(ImpossibleDrawException::class);

it('is not always the same result', function () {
    $drawer = new SecretGiverDrawer;
    $results = [];

    for ($i = 0; $i < 50; $i++) {
        $results[] = json_encode($drawer->draw([1, 2, 3, 4, 5, 6]));
    }

    expect(count(array_unique($results)))->toBeGreaterThan(1);
});
