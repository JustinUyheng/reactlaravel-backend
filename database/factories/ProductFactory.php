<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Meals', 'Drinks']),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'image_path' => null, // This would typically be set when actually uploading an image
        ];
    }

    public function meal(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'Meals',
            ];
        });
    }

    public function drink(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'Drinks',
            ];
        });
    }
} 