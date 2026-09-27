<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Participant extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipantFactory> */
    use HasFactory;

    protected $fillable = ['name', 'wishlist'];

    protected $hidden = ['claim_token_hash', 'assigned_to_id'];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'assigned_to_id');
    }

    public function isClaimed(): bool
    {
        return $this->claim_token_hash !== null;
    }

    /**
     * Atomically claim this name. Returns the plain claim token, or null if someone got there first.
     */
    public function claim(): ?string
    {
        $token = Str::random(40);

        $updated = self::whereKey($this->id)
            ->whereNull('claim_token_hash')
            ->update([
                'claim_token_hash' => hash('sha256', $token),
                'claimed_at' => now(),
            ]);

        if ($updated === 0) {
            return null;
        }

        $this->refresh();

        return $token;
    }

    public function verifyClaimToken(string $token): bool
    {
        return $this->claim_token_hash !== null
            && hash_equals($this->claim_token_hash, hash('sha256', $token));
    }

    public function releaseClaim(): void
    {
        $this->forceFill(['claim_token_hash' => null, 'claimed_at' => null])->save();
    }
}
