<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_http_200(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
    }

    public function test_active_categories_are_returned(): void
    {
        Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonFragment(['slug' => 'vehicles']);
    }

    public function test_inactive_categories_are_excluded(): void
    {
        Category::create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonMissing(['slug' => 'inactive']);
    }

    public function test_active_children_are_included(): void
    {
        $parent = Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Cars',
            'slug' => 'cars',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonFragment(['slug' => 'cars']);
    }

    public function test_inactive_children_are_excluded(): void
    {
        $parent = Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonMissing(['slug' => 'hidden']);
    }

    public function test_category_ordering_is_correct(): void
    {
        Category::create(['name' => 'Zeta', 'slug' => 'zeta', 'sort_order' => 2]);
        Category::create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 1]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();

        $this->assertSame(['alpha', 'zeta'], $slugs);
    }

    public function test_empty_collection_returns_empty_data_array(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_endpoint_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
    }

    public function test_response_json_structure_is_correct(): void
    {
        Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
            'description' => null,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'parent_id',
                        'sort_order',
                        'children',
                    ],
                ],
            ]);
    }
}
