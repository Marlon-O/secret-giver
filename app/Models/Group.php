<?php

namespace App\Models;

use App\Exceptions\ImpossibleDrawException;
use App\Services\SecretSantaDrawer;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'budget', 'exchange_date'];

    protected $hidden = ['admin_token_hash'];

    protected function casts(): array
    {
        return [
            'exchange_date' => 'date',
            'drawn_at' => 'datetime',
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
    public function performDraw(SecretSantaDrawer $drawer): void
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
