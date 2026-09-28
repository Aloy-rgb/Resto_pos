<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect(['Starters', 'Mains', 'Drinks'])->mapWithKeys(fn ($name) => [$name => \App\Models\Category::firstOrCreate(['name' => $name])]);
        foreach ([
            ['Truffle Smash Burger', 'Mains', 'Aged cheddar, onion jam, house sauce', 14.50],
            ['Crispy Calamari', 'Starters', 'Lemon, herbs, smoked aioli', 9.00],
            ['Roasted Tomato Pasta', 'Mains', 'Basil, parmesan, chili oil', 13.00],
            ['Citrus Fizz', 'Drinks', 'Grapefruit, lime, sparkling water', 5.50],
        ] as [$name, $category, $description, $price]) {
            \App\Models\MenuItem::firstOrCreate(['name' => $name], [
                'category_id' => $categories[$category]->id,
                'description' => $description,
                'current_price' => $price,
                'is_available' => true,
            ]);
        }
    }
}
