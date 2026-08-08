<?php

namespace Database\Seeders;

use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    /**
     * Seeds the "cheat" reward catalog — redeeming here is just the System
     * granting permission; the player buys/eats the real thing themselves.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Ice Cream', 'emoji' => '🍦', 'description' => 'A scoop (or two) of ice cream.', 'cost' => 100],
            ['name' => 'Coffee', 'emoji' => '☕', 'description' => 'Your favorite coffee order.', 'cost' => 50],
            ['name' => 'Chocolate Bar', 'emoji' => '🍫', 'description' => 'One full bar, guilt-free.', 'cost' => 80],
            ['name' => 'Soda', 'emoji' => '🥤', 'description' => 'One can or bottle of soda.', 'cost' => 60],
            ['name' => 'Donut', 'emoji' => '🍩', 'description' => 'One donut of your choice.', 'cost' => 90],
            ['name' => 'Boba Tea', 'emoji' => '🧋', 'description' => 'One cup of boba/bubble tea.', 'cost' => 120],
            ['name' => 'Fried Chicken', 'emoji' => '🍗', 'description' => 'A proper fried chicken meal.', 'cost' => 200],
            ['name' => 'Pizza Slice', 'emoji' => '🍕', 'description' => 'One slice from your favorite pizza place.', 'cost' => 150],
        ];

        foreach ($items as $item) {
            ShopItem::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
