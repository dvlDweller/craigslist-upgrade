<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_image_can_be_created(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertDatabaseHas('listing_images', [
            'id' => $image->id,
            'listing_id' => $listing->id,
            'path' => 'listings/1/photo.jpg',
        ]);
    }

    public function test_default_disk_value(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertSame('public', $image->disk);
    }

    public function test_default_sort_order_value(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertSame(0, $image->sort_order);
    }

    public function test_default_is_primary_value(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertFalse($image->is_primary);
    }

    public function test_listing_image_belongs_to_listing(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertTrue($image->listing->is($listing));
    }

    public function test_listing_has_many_images(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $this->assertTrue($listing->images->contains($image));
    }

    public function test_explicit_primary_and_order_values_persist(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
            'sort_order' => 2,
            'is_primary' => true,
        ]);

        $this->assertSame(2, $image->sort_order);
        $this->assertTrue($image->is_primary);
    }

    public function test_hard_delete_cascades_image_records(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $listing->forceDelete();

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_soft_delete_preserves_image_records(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);
        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $image = $listing->images()->create([
            'path' => 'listings/1/photo.jpg',
        ]);

        $listing->delete();

        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
    }
}
