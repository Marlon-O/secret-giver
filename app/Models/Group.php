<?php

namespace App\Models;

use App\Exceptions\ImpossibleDrawException;
use App\Services\SecretGiverDrawer;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'budget', 'exchange_date', 'wishlists_required'];

    protected $hidden = ['admin_token_hash'];

    protected function casts(): array
    {
        return [
            'exchange_date' => 'date',
            'drawn_at' => 'datetime',
            'wishlists_required' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Create a group and return it with its plain admin token (only the hash is stored).
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: self, 1: string}
     */
    public static function createWithAdminToken(array $attributes): array
    {
        $token = Str::random(40);

        $group = new self($attributes);
        $group->slug = self::uniqueSlug();
        $group->admin_token_hash = hash('sha256', $token);
        $group->save();

        return [$group, $token];
    }

    public function verifyAdminToken(string $token): bool
    {
        return hash_equals($this->admin_token_hash, hash('sha256', $token));
    }

    public function isDrawn(): bool
    {
        return $this->drawn_at !== null;
    }

    /**
     * When wishlists are required, people claim their name before the draw so they can write one first.
     */
    public function canBeClaimed(): bool
    {
        return $this->isDrawn() || $this->wishlists_required;
    }

    /**
     * Names still owing a wishlist, which blocks the draw when wishlists are required.
     *
     * @return Collection<int, string>
     */
    public function missingWishlists(): Collection
    {
        if (! $this->wishlists_required) {
            return collect();
        }

        return $this->participants()
            ->where(fn ($query) => $query->whereNull('wishlist')->orWhere('wishlist', ''))
            ->pluck('name');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class)->orderBy('name');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(Exclusion::class);
    }

    /**
     * @throws ImpossibleDrawException
     */
    public function performDraw(SecretGiverDrawer $drawer): void
    {
        $assignments = $drawer->draw(
            $this->participants()->pluck('id')->all(),
            $this->exclusions()->get(['giver_id', 'receiver_id'])
                ->map(fn (Exclusion $e) => [$e->giver_id, $e->receiver_id])
                ->all(),
        );

        DB::transaction(function () use ($assignments) {
            foreach ($assignments as $giverId => $receiverId) {
                $this->participants()->whereKey($giverId)->update(['assigned_to_id' => $receiverId]);
            }

            // Names were claimed to write wishlists; reopen them so everyone taps their name to see their match.
            if ($this->wishlists_required) {
                $this->participants()->update(['claim_token_hash' => null, 'claimed_at' => null]);
            }

            $this->forceFill(['drawn_at' => now()])->save();
        });
    }

    public function undoDraw(): void
    {
        DB::transaction(function () {
            $this->participants()->update(['assigned_to_id' => null]);
            $this->forceFill(['drawn_at' => null])->save();
        });
    }

    private static function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(12));
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }
}
