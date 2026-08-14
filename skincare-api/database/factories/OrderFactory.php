<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address_id' => null,
            'order_number' => 'FD'.Str::padLeft((string) fake()->unique()->numberBetween(0, 99999999), 8, '0'),
            'status' => 'processing',
            'total' => fake()->numberBetween(500, 5000),
            'points_earned' => fake()->numberBetween(50, 500),
            'payment_method' => fake()->randomElement(['apple_pay', 'paypal']),
            'payment_status' => 'paid',
            'shipping_method' => fake()->randomElement(['dhl', 'inpost']),
        ];
    }
}
