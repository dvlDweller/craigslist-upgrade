<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogue = [
            [
                'name' => 'Vehicles',
                'slug' => 'vehicles',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Cars & Trucks', 'slug' => 'cars-trucks', 'sort_order' => 1],
                    ['name' => 'Motorcycles & Scooters', 'slug' => 'motorcycles-scooters', 'sort_order' => 2],
                    ['name' => 'Bicycles', 'slug' => 'bicycles', 'sort_order' => 3],
                    ['name' => 'Vehicle Parts & Accessories', 'slug' => 'vehicle-parts-accessories', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Mobile Phones & Tablets', 'slug' => 'mobile-phones-tablets', 'sort_order' => 1],
                    ['name' => 'Computers & Accessories', 'slug' => 'computers-accessories', 'sort_order' => 2],
                    ['name' => 'TVs & Audio', 'slug' => 'tvs-audio', 'sort_order' => 3],
                    ['name' => 'Cameras & Photography', 'slug' => 'cameras-photography', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-living',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Furniture', 'slug' => 'furniture', 'sort_order' => 1],
                    ['name' => 'Home Appliances', 'slug' => 'home-appliances', 'sort_order' => 2],
                    ['name' => 'Kitchen & Dining', 'slug' => 'kitchen-dining', 'sort_order' => 3],
                    ['name' => 'Home Improvement', 'slug' => 'home-improvement', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Fashion & Accessories',
                'slug' => 'fashion-accessories',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Clothing & Shoes', 'slug' => 'clothing-shoes', 'sort_order' => 1],
                    ['name' => 'Bags & Accessories', 'slug' => 'bags-accessories', 'sort_order' => 2],
                    ['name' => 'Jewelry & Watches', 'slug' => 'jewelry-watches', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'sort_order' => 5,
                'children' => [
                    ['name' => 'Sports Equipment', 'slug' => 'sports-equipment', 'sort_order' => 1],
                    ['name' => 'Fitness Equipment', 'slug' => 'fitness-equipment', 'sort_order' => 2],
                    ['name' => 'Outdoor & Camping', 'slug' => 'outdoor-camping', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Books, Toys & Hobbies',
                'slug' => 'books-toys-hobbies',
                'sort_order' => 6,
                'children' => [
                    ['name' => 'Books & School Supplies', 'slug' => 'books-school-supplies', 'sort_order' => 1],
                    ['name' => 'Toys & Games', 'slug' => 'toys-games', 'sort_order' => 2],
                    ['name' => 'Musical Instruments', 'slug' => 'musical-instruments', 'sort_order' => 3],
                    ['name' => 'Arts & Collectibles', 'slug' => 'arts-collectibles', 'sort_order' => 4],
                ],
            ],
        ];

        DB::transaction(function () use ($catalogue): void {
            foreach ($catalogue as $parentData) {
                $children = $parentData['children'];
                unset($parentData['children']);

                $parent = Category::updateOrCreate(
                    ['slug' => $parentData['slug']],
                    [
                        'name' => $parentData['name'],
                        'slug' => $parentData['slug'],
                        'parent_id' => null,
                        'description' => null,
                        'sort_order' => $parentData['sort_order'],
                        'is_active' => true,
                    ]
                );

                foreach ($children as $childData) {
                    Category::updateOrCreate(
                        ['slug' => $childData['slug']],
                        [
                            'name' => $childData['name'],
                            'slug' => $childData['slug'],
                            'parent_id' => $parent->id,
                            'description' => null,
                            'sort_order' => $childData['sort_order'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}
