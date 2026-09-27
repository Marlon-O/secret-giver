<?php

namespace App\Support;

use App\Models\Group;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Remembers which name this device claimed in a group, via a long-lived encrypted cookie.
 */
class ClaimCookie
{
    private const ONE_YEAR_IN_MINUTES = 60 * 24 * 365;

    public static function name(Group $group): string
    {
        return "giver_{$group->slug}";
    }

    public static function make(Group $group, Participant $participant, string $token): SymfonyCookie
    {
        return Cookie::make(self::name($group), "{$participant->id}|{$token}", self::ONE_YEAR_IN_MINUTES);
    }

    /**
     * The participant this device has claimed in the group, if the cookie is still valid.
     */
    public static function resolve(Request $request, Group $group): ?Participant
    {
        $value = $request->cookie(self::name($group));

        if (! is_string($value) || ! str_contains($value, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $value, 2);

        $participant = $group->participants()->whereKey((int) $id)->first();

        return $participant?->verifyClaimToken($token) ? $participant : null;
    }
}
