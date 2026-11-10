<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_can_be_created(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.50,
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'title' => 'Used Bike',
            'status' => Listing::STATUS_DRAFT,
            'user_id' => $user->id,
        ]);
    }

    public function test_default_values(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ]);

        $this->assertSame(Listing::STATUS_DRAFT, $listing->status);
        $this->assertSame('PHP', $listing->currency);
        $this->assertSame('Camarines Sur', $listing->province);
        $this->assertNull($listing->price);
        $this->assertNull($listing->municipality);
        $this->assertNull($listing->published_at);
    }

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_CONTACT,
        ]);

        $this->assertTrue($listing->user->is($user));
        $this->assertTrue($user->listings->contains($listing));
    }

    public function test_category_relationship(): void
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

        $this->assertTrue($listing->category->is($category));
        $this->assertTrue($category->listings->contains($listing));
    }

    public function test_soft_deletion(): void
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

        $listing->delete();

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_cannot_delete_user_with_listings(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $this->expectException(QueryException::class);

        $user->delete();
    }

    public function test_cannot_delete_category_with_listings(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ]);

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_nullable_draft_municipality_and_price(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Bikes', 'slug' => 'bikes']);

        $listing = $user->listings()->create([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition.',
            'price_type' => Listing::PRICE_TYPE_CONTACT,
            'municipality' => null,
            'price' => null,
        ]);

        $this->assertNull($listing->municipality);
        $this->assertNull($listing->price);
    }
}
