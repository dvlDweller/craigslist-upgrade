<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $category = Category::create([
            'name' => 'Bikes',
            'slug' => 'bikes',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Bikes',
            'slug' => 'bikes',
        ]);
    }

    public function test_default_sort_order_and_is_active(): void
    {
        $category = Category::create([
            'name' => 'Bikes',
            'slug' => 'bikes',
        ]);

        $this->assertSame(0, $category->sort_order);
        $this->assertTrue($category->is_active);
    }

    public function test_parent_child_relationship(): void
    {
        $parent = Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
        ]);

        $child = Category::create([
            'name' => 'Cars',
            'slug' => 'cars',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_slug_must_be_unique(): void
    {
        Category::create([
            'name' => 'Bikes',
            'slug' => 'bikes',
        ]);

        $this->expectException(QueryException::class);

        Category::create([
            'name' => 'Bicycles',
            'slug' => 'bikes',
        ]);
    }

    public function test_cannot_delete_parent_with_children(): void
    {
        $parent = Category::create([
            'name' => 'Vehicles',
            'slug' => 'vehicles',
        ]);

        Category::create([
            'name' => 'Cars',
            'slug' => 'cars',
            'parent_id' => $parent->id,
        ]);

        $this->expectException(QueryException::class);

        $parent->delete();
    }
}
