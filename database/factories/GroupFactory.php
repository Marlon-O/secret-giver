<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'budget' => '$30',
            'exchange_date' => now()->addMonths(2)->toDateString(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Group $group) {
            $group->slug ??= Str::lower(Str::random(12));
            $group->admin_token_hash ??= hash('sha256', 'secret-admin-token');
        });
    }
}
