<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ListingCloudinaryImageFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_feed_with_cloudinary_primary_image(): void
    {
        $listing = $this->createListing();

        $listing->images()->create([
            'path' => 'listings/1/cloud.jpg',
            'disk' => 'cloudinary',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $storage = Mockery::mock(ListingImageStorage::class);
        $storage->shouldReceive('url')
            ->once()
            ->with('listings/1/cloud.jpg', 'cloudinary')
            ->andReturn('https://res.cloudinary.com/demo/image/upload/listings/1/cloud.jpg');

        $this->app->instance(ListingImageStorage::class, $storage);

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk();
        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/listings/1/cloud.jpg',
            $response->json('data.0.primary_image_url')
        );
    }

    private function createListing(array $attributes = []): Listing
    {
        $user = $attributes['user'] ?? User::factory()->create();
        $category = $attributes['category'] ?? Category::firstOrCreate(['slug' => 'bikes'], ['name' => 'Bikes']);

        $status = $attributes['status'] ?? Listing::STATUS_ACTIVE;

        unset($attributes['user'], $attributes['category'], $attributes['status']);

        $listing = $user->listings()->create(array_merge([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition mountain bike.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.00,
            'municipality' => 'Naga City',
            'barangay' => 'Concepcion Grande',
        ], $attributes));

        $listing->status = $status;
        $listing->save();

        return $listing;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
