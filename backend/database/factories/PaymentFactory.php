<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-' . strtoupper($this->faker->unique()->bothify('????-####')),
            'order_id' => Order::factory(),
            'cashier_id' => User::factory(),
            'amount' => 0,
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'mobile']),
            'status' => 'completed',
            'created_at' => now(),
        ];
    }
}
