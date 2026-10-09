<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Kategori>
 */
class KategoriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_kategori' => strtoupper(fake()->unique()->lexify('???')),
            'nama_kategori' => fake()->unique()->words(2, true),
        ];
    }
}