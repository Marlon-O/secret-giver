<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->unique()->firstName(),
        ];
    }
}
