<?php

namespace Database\Factories;

use App\Models\StockItem;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockItemFactory extends Factory
{
    protected $model = StockItem::class;

    public function definition(): array
    {
        return [
            'category' => Category::inRandomOrder()->first()?->name ?? $this->faker->word(),
            'name' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(10, 100),
            'min_quantity' => $this->faker->numberBetween(5, 10),
            'unit' => $this->faker->randomElement(['kg', 'l', 'pcs', 'g']),
            'unit_cost' => $this->faker->randomFloat(2, 0.5, 10),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
        ];
    }
}
