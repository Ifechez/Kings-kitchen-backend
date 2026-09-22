<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account - CHANGE THIS PASSWORD after first login.
        User::updateOrCreate(
            ['email' => 'admin@kingskitchen.ng'],
            [
                'member_id' => 'KK-ADMIN1',
                'name' => "Kings' Kitchen Admin",
                'phone' => '09018300789',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'membership' => 'premium',
                'free_first_order_used' => true,
            ]
        );

        $mains = Category::updateOrCreate(['slug' => 'main-dishes'], ['name' => 'Main Dishes', 'sort_order' => 1]);
        $swallow = Category::updateOrCreate(['slug' => 'swallow-soups'], ['name' => 'Swallow & Soups', 'sort_order' => 2]);
        $grills = Category::updateOrCreate(['slug' => 'grills-proteins'], ['name' => 'Grills & Proteins', 'sort_order' => 3]);
        $drinks = Category::updateOrCreate(['slug' => 'drinks'], ['name' => 'Drinks', 'sort_order' => 4]);

        $products = [
            ['cat' => $mains, 'name' => 'Royal Jollof Rice', 'price' => 3500, 'discount_price' => 3000, 'is_hot' => true],
            ['cat' => $mains, 'name' => 'Fried Rice Supreme', 'price' => 3800, 'discount_price' => null, 'is_hot' => true],
            ['cat' => $swallow, 'name' => 'Pounded Yam & Egusi', 'price' => 4500, 'discount_price' => null, 'is_hot' => false],
            ['cat' => $swallow, 'name' => 'Afang Soup & Fufu', 'price' => 4800, 'discount_price' => null, 'is_hot' => true],
            ['cat' => $grills, 'name' => 'Grilled Chicken (Full)', 'price' => 5000, 'discount_price' => 4500, 'is_hot' => true],
            ['cat' => $grills, 'name' => 'Peppered Turkey', 'price' => 5500, 'discount_price' => null, 'is_hot' => false],
            ['cat' => $drinks, 'name' => 'Chapman (1L)', 'price' => 2000, 'discount_price' => null, 'is_hot' => false],
            ['cat' => $drinks, 'name' => 'Zobo Special', 'price' => 1500, 'discount_price' => null, 'is_hot' => false],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['slug' => Str::slug($p['name'])],
                [
                    'category_id' => $p['cat']->id,
                    'name' => $p['name'],
                    'description' => "A Kings' Kitchen favorite, prepared fresh and fit for royals.",
                    'price' => $p['price'],
                    'discount_price' => $p['discount_price'],
                    'is_available' => true,
                    'is_hot' => $p['is_hot'],
                ]
            );
        }

        MembershipPlan::updateOrCreate(['name' => 'Weekly Royal'], ['billing_cycle' => 'weekly', 'price' => 1500, 'discount_percent' => 10, 'is_active' => true]);
        MembershipPlan::updateOrCreate(['name' => 'Monthly Royal'], ['billing_cycle' => 'monthly', 'price' => 4500, 'discount_percent' => 15, 'is_active' => true]);
        MembershipPlan::updateOrCreate(['name' => 'Yearly Royal'], ['billing_cycle' => 'yearly', 'price' => 45000, 'discount_percent' => 20, 'is_active' => true]);

        Setting::set('delivery_fee', '1500');
        Setting::set('currency_symbol', '₦');
        Setting::set('whatsapp_number', '09018300789');
        Setting::set('hero_video_url', '/hero-video.mp4');
        Setting::set('brand_tagline', 'where Royals dine');
        // Default to central Lagos — change this in Admin > Settings to your real kitchen location.
        Setting::set('restaurant_lat', '6.5244');
        Setting::set('restaurant_lng', '3.3792');
    }
}
