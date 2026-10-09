<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Buku>
 */
class BukuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'isbn' => fake()->unique()->isbn13(),
            'judul' => fake()->sentence(3),
            'penulis' => fake()->name(),
            'penerbit' => fake()->randomElement([
                'Erlangga',
                'Gramedia Pustaka Utama',
                'Andi Offset',
                'Informatika Bandung',
                'Deepublish',
            ]),
            'tahun_terbit' => fake()->numberBetween(2005, 2025),
            'kategori_id' => Kategori::factory(),
            'stok' => fake()->numberBetween(0, 25),
            'sinopsis' => fake()->paragraph(3),
        ];
    }
}