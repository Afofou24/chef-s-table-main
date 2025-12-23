<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('????-####')),
            'table_id' => RestaurantTable::factory(),
            'waiter_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'ready', 'delivered', 'paid', 'cancelled']),
            'order_type' => $this->faker->randomElement(['dine_in', 'takeaway', 'delivery']),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'guests_count' => $this->faker->numberBetween(1, 6),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
