<?php

namespace App\Services;

use App\Exceptions\ImpossibleDrawException;

class SecretSantaDrawer
{
    /**
     * Assign every giver a unique receiver.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, array{0: int, 1: int}>  $forbiddenPairs  [giverId, receiverId] pairs that may not be matched
     * @return array<int, int> giverId => receiverId
     *
     * @throws ImpossibleDrawException
     */
    public function draw(array $ids, array $forbiddenPairs = []): array
    {
        if (count($ids) < 3) {
            throw ImpossibleDrawException::tooFewParticipants();
        }

        $forbidden = [];
        foreach ($forbiddenPairs as [$giver, $receiver]) {
            $forbidden[$giver][$receiver] = true;
        }

        $candidates = [];
        foreach ($ids as $giver) {
            $options = array_values(array_filter(
                $ids,
                fn ($receiver) => $receiver !== $giver && ! isset($forbidden[$giver][$receiver]),
            ));
            shuffle($options);
            $candidates[$giver] = $options;
        }

        $result = $this->assign($candidates, [], []);

        if ($result === null) {
            throw ImpossibleDrawException::exclusionsTooStrict();
        }

        return $result;
    }

    /**
     * Backtracking search that always expands the giver with the fewest remaining options.
     *
     * @param  array<int, array<int, int>>  $candidates
     * @param  array<int, int>  $assigned
     * @param  array<int, true>  $taken
     * @return array<int, int>|null
     */
    private function assign(array $candidates, array $assigned, array $taken): ?array
    {
        $next = null;
        $nextOptions = null;

        foreach ($candidates as $giver => $options) {
            if (isset($assigned[$giver])) {
                continue;
            }

            $open = array_values(array_filter($options, fn ($r) => ! isset($taken[$r])));

            if ($open === []) {
                return null;
            }

            if ($nextOptions === null || count($open) < count($nextOptions)) {
                [$next, $nextOptions] = [$giver, $open];
            }
        }

        if ($next === null) {
            return $assigned;
        }

        foreach ($nextOptions as $receiver) {
            $result = $this->assign(
                $candidates,
                $assigned + [$next => $receiver],
                $taken + [$receiver => true],
            );

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
