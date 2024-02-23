<?php

namespace Database\Factories;

use App\Testing\Models\Bill;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition()
    {
        return [
            'ref' => $this->faker->name,
            'price' => $this->faker->randomNumber(2),
        ];
    }
}
