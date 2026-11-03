<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoffeeDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('coffeespace.stories') as $story) {
            $user = User::firstOrCreate(
                ['email' => $story['handle'].'@demo.coffeespace.invalid'],
                ['name' => $story['name'], 'password' => Str::random(64)],
            );
            $user->posts()->firstOrCreate(['demo_key' => 'coffee-demo-'.$story['id']], [
                'author_handle' => $story['handle'], 'title' => $story['title'],
                'body' => $story['story'], 'cafe_name' => $story['cafe'], 'city' => $story['city'],
                'image_path' => $story['image'], 'image_alt' => $story['alt'],
                'rating' => $story['rating'], 'price_centavos' => (int) $story['price'] * 100,
                'tag' => $story['tag'], 'visibility' => 'public',
            ]);
        }
    }
}
