<?php

namespace App\Exceptions;

use RuntimeException;

class ImpossibleDrawException extends RuntimeException
{
    public static function tooFewParticipants(): self
    {
        return new self('A draw needs at least 3 people.');
    }

    public static function exclusionsTooStrict(): self
    {
        return new self('These exclusions make a draw impossible. Remove one and try again.');
    }
}
