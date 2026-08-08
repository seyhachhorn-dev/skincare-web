<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'province' => fake()->state(),
            'district' => fake()->city(),
            'commune' => fake()->streetName(),
            'house_no' => fake()->buildingNumber(),
            'pickup_point' => null,
            'location' => null,
            'type' => fake()->randomElement(['home', 'office', 'school', 'other']),
            'is_default' => false,
        ];
    }
}
