<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(): array
    {
        return [
            'vehicles' => [
                'name' => 'Vehicles',
                'sort_order' => 1,
                'children' => [
                    'cars-trucks' => ['name' => 'Cars & Trucks', 'sort_order' => 1],
                    'motorcycles-scooters' => ['name' => 'Motorcycles & Scooters', 'sort_order' => 2],
                    'bicycles' => ['name' => 'Bicycles', 'sort_order' => 3],
                    'vehicle-parts-accessories' => ['name' => 'Vehicle Parts & Accessories', 'sort_order' => 4],
                ],
            ],
            'electronics' => [
                'name' => 'Electronics',
                'sort_order' => 2,
                'children' => [
                    'mobile-phones-tablets' => ['name' => 'Mobile Phones & Tablets', 'sort_order' => 1],
                    'computers-accessories' => ['name' => 'Computers & Accessories', 'sort_order' => 2],
                    'tvs-audio' => ['name' => 'TVs & Audio', 'sort_order' => 3],
                    'cameras-photography' => ['name' => 'Cameras & Photography', 'sort_order' => 4],
                ],
            ],
            'home-living' => [
                'name' => 'Home & Living',
                'sort_order' => 3,
                'children' => [
                    'furniture' => ['name' => 'Furniture', 'sort_order' => 1],
                    'home-appliances' => ['name' => 'Home Appliances', 'sort_order' => 2],
                    'kitchen-dining' => ['name' => 'Kitchen & Dining', 'sort_order' => 3],
                    'home-improvement' => ['name' => 'Home Improvement', 'sort_order' => 4],
                ],
            ],
            'fashion-accessories' => [
                'name' => 'Fashion & Accessories',
                'sort_order' => 4,
                'children' => [
                    'clothing-shoes' => ['name' => 'Clothing & Shoes', 'sort_order' => 1],
                    'bags-accessories' => ['name' => 'Bags & Accessories', 'sort_order' => 2],
                    'jewelry-watches' => ['name' => 'Jewelry & Watches', 'sort_order' => 3],
                ],
            ],
            'sports-outdoors' => [
                'name' => 'Sports & Outdoors',
                'sort_order' => 5,
                'children' => [
                    'sports-equipment' => ['name' => 'Sports Equipment', 'sort_order' => 1],
                    'fitness-equipment' => ['name' => 'Fitness Equipment', 'sort_order' => 2],
                    'outdoor-camping' => ['name' => 'Outdoor & Camping', 'sort_order' => 3],
                ],
            ],
            'books-toys-hobbies' => [
                'name' => 'Books, Toys & Hobbies',
                'sort_order' => 6,
                'children' => [
                    'books-school-supplies' => ['name' => 'Books & School Supplies', 'sort_order' => 1],
                    'toys-games' => ['name' => 'Toys & Games', 'sort_order' => 2],
                    'musical-instruments' => ['name' => 'Musical Instruments', 'sort_order' => 3],
                    'arts-collectibles' => ['name' => 'Arts & Collectibles', 'sort_order' => 4],
                ],
            ],
        ];
    }

    private function managedSlugs(): array
    {
        $slugs = [];
        foreach ($this->catalogue() as $parentSlug => $parent) {
            $slugs[] = $parentSlug;
            foreach (array_keys($parent['children']) as $childSlug) {
                $slugs[] = $childSlug;
            }
        }

        return $slugs;
    }

    public function test_creates_exactly_28_managed_categories_in_clean_database(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(28, Category::count());
        $this->assertSame(28, Category::whereIn('slug', $this->managedSlugs())->count());
    }

    public function test_exactly_six_active_root_categories_exist(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(6, Category::whereNull('parent_id')->where('is_active', true)->count());
    }

    public function test_exactly_22_active_direct_children_exist(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(22, Category::whereNotNull('parent_id')->where('is_active', true)->count());
    }

    public function test_each_child_has_correct_parent_association(): void
    {
        $this->seed(CategorySeeder::class);

        foreach ($this->catalogue() as $parentSlug => $parent) {
            $parentModel = Category::where('slug', $parentSlug)->firstOrFail();
            foreach (array_keys($parent['children']) as $childSlug) {
                $child = Category::where('slug', $childSlug)->firstOrFail();
                $this->assertSame($parentModel->id, $child->parent_id, "Child {$childSlug} has wrong parent.");
            }
        }
    }

    public function test_all_expected_category_names_and_slugs_are_present(): void
    {
        $this->seed(CategorySeeder::class);

        foreach ($this->catalogue() as $parentSlug => $parent) {
            $this->assertDatabaseHas('categories', [
                'slug' => $parentSlug,
                'name' => $parent['name'],
            ]);

            foreach ($parent['children'] as $childSlug => $child) {
                $this->assertDatabaseHas('categories', [
                    'slug' => $childSlug,
                    'name' => $child['name'],
                ]);
            }
        }
    }

    public function test_parent_and_child_sort_orders_are_correct(): void
    {
        $this->seed(CategorySeeder::class);

        foreach ($this->catalogue() as $parentSlug => $parent) {
            $this->assertDatabaseHas('categories', [
                'slug' => $parentSlug,
                'sort_order' => $parent['sort_order'],
            ]);

            foreach ($parent['children'] as $childSlug => $child) {
                $this->assertDatabaseHas('categories', [
                    'slug' => $childSlug,
                    'sort_order' => $child['sort_order'],
                ]);
            }
        }
    }

    public function test_no_grandchildren_are_created(): void
    {
        $this->seed(CategorySeeder::class);

        $grandchildren = Category::whereHas('parent', function ($query): void {
            $query->whereNotNull('parent_id');
        })->count();

        $this->assertSame(0, $grandchildren);
    }

    public function test_running_seeder_twice_does_not_create_duplicates(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(28, Category::count());
        $this->assertSame(28, Category::whereIn('slug', $this->managedSlugs())->count());
    }

    public function test_running_seeder_again_restores_approved_attributes(): void
    {
        $this->seed(CategorySeeder::class);

        $parent = Category::where('slug', 'vehicles')->firstOrFail();
        $parent->update([
            'name' => 'Changed Parent',
            'sort_order' => 99,
            'is_active' => false,
            'description' => 'changed',
            'parent_id' => null,
        ]);

        $child = Category::where('slug', 'cars-trucks')->firstOrFail();
        $child->update([
            'name' => 'Changed Child',
            'sort_order' => 99,
            'is_active' => false,
            'description' => 'changed',
            'parent_id' => null,
        ]);

        $this->seed(CategorySeeder::class);

        $parent->refresh();
        $child->refresh();

        $this->assertSame('Vehicles', $parent->name);
        $this->assertSame(1, $parent->sort_order);
        $this->assertTrue($parent->is_active);
        $this->assertNull($parent->description);
        $this->assertNull($parent->parent_id);

        $this->assertSame('Cars & Trucks', $child->name);
        $this->assertSame(1, $child->sort_order);
        $this->assertTrue($child->is_active);
        $this->assertNull($child->description);
        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_unrelated_categories_remain_untouched(): void
    {
        $unrelated = Category::create([
            'name' => 'Unrelated',
            'slug' => 'unrelated',
            'parent_id' => null,
            'description' => 'keep me',
            'sort_order' => 77,
            'is_active' => false,
        ]);

        $original = $unrelated->fresh()->toArray();

        $this->seed(CategorySeeder::class);

        $after = Category::where('slug', 'unrelated')->firstOrFail();
        $this->assertSame($original['name'], $after->name);
        $this->assertSame($original['slug'], $after->slug);
        $this->assertSame($original['parent_id'], $after->parent_id);
        $this->assertSame($original['description'], $after->description);
        $this->assertSame($original['sort_order'], $after->sort_order);
        $this->assertSame((bool) $original['is_active'], $after->is_active);
        $this->assertSame(29, Category::count());
    }
}
