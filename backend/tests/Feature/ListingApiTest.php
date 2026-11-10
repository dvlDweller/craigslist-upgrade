<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingApiTest extends TestCase
{
    use RefreshDatabase;

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
            'barangay' => 'Concepcion',
        ], $attributes));

        $listing->status = $status;
        $listing->save();

        return $listing;
    }

    public function test_public_listing_retrieval_returns_active_listings(): void
    {
        $this->createListing();

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(1, 'data');
    }

    public function test_empty_results_returns_empty_data_array(): void
    {
        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_draft_listings_are_excluded(): void
    {
        $this->createListing(['status' => Listing::STATUS_DRAFT]);

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_sold_listings_are_excluded(): void
    {
        $this->createListing(['status' => Listing::STATUS_SOLD]);

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_soft_deleted_listings_are_excluded(): void
    {
        $listing = $this->createListing();
        $listing->delete();

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_active_listing_detail_is_returned(): void
    {
        $listing = $this->createListing();

        $response = $this->getJson("/api/v1/listings/{$listing->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'price_type',
                    'price',
                    'currency',
                    'municipality',
                    'barangay',
                    'province',
                    'status',
                    'published_at',
                    'created_at',
                    'updated_at',
                    'category' => ['id', 'name', 'slug'],
                    'user' => ['id', 'name'],
                    'images' => [
                        '*' => ['id', 'url', 'sort_order', 'is_primary'],
                    ],
                ],
            ]);
    }

    public function test_non_active_listing_detail_returns_404(): void
    {
        $draft = $this->createListing(['status' => Listing::STATUS_DRAFT]);
        $sold = $this->createListing(['status' => Listing::STATUS_SOLD]);

        $this->getJson("/api/v1/listings/{$draft->id}")->assertNotFound();
        $this->getJson("/api/v1/listings/{$sold->id}")->assertNotFound();
    }

    public function test_deleted_listing_detail_returns_404(): void
    {
        $listing = $this->createListing();
        $listing->delete();

        $this->getJson("/api/v1/listings/{$listing->id}")->assertNotFound();
    }

    public function test_category_filter(): void
    {
        $otherCategory = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $this->createListing();
        $this->createListing(['category' => $otherCategory]);

        $response = $this->getJson('/api/v1/listings?category=bikes');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_keyword_search(): void
    {
        $this->createListing(['title' => 'Mountain Bike']);
        $this->createListing(['title' => 'Road Bike', 'description' => 'Fast aluminum frame.']);

        $response = $this->getJson('/api/v1/listings?q=mountain');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_municipality_and_barangay_filtering(): void
    {
        $this->createListing(['municipality' => 'Naga City', 'barangay' => 'Concepcion']);
        $this->createListing(['municipality' => 'Pili', 'barangay' => 'Cadlan']);

        $response = $this->getJson('/api/v1/listings?municipality[]=Naga+City');

        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/v1/listings?barangay=Cadlan');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_price_range_and_price_type_filtering(): void
    {
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 50]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 150]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FREE, 'price' => null]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FREE, 'price' => 10]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_CONTACT, 'price' => 20]);

        $response = $this->getJson('/api/v1/listings?min_price=100&max_price=200');

        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/v1/listings?price_type=free');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_sorting(): void
    {
        $older = $this->createListing(['title' => 'Older', 'price' => 50]);
        $older->created_at = now()->subDay();
        $older->save();

        $newer = $this->createListing(['title' => 'Newer', 'price' => 150]);

        $newest = $this->getJson('/api/v1/listings?sort=newest');
        $this->assertSame('Newer', $newest->json('data.0.title'));

        $asc = $this->getJson('/api/v1/listings?sort=price_asc');
        $this->assertSame('Older', $asc->json('data.0.title'));

        $desc = $this->getJson('/api/v1/listings?sort=price_desc');
        $this->assertSame('Newer', $desc->json('data.0.title'));
    }

    public function test_pagination_and_maximum_per_page(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createListing(['title' => "Bike {$i}"]);
        }

        $response = $this->getJson('/api/v1/listings?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);

        $this->getJson('/api/v1/listings?per_page=51')->assertUnprocessable();
    }

    public function test_invalid_filters_return_422(): void
    {
        $this->getJson('/api/v1/listings?price_type=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/listings?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/listings?min_price=200&max_price=100')->assertUnprocessable();
    }

    public function test_endpoint_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/listings');

        $response->assertOk();
    }

    public function test_price_range_excludes_non_fixed_listings_with_prices(): void
    {
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 150]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_FREE, 'price' => 125]);
        $this->createListing(['price_type' => Listing::PRICE_TYPE_CONTACT, 'price' => 125]);

        $response = $this->getJson('/api/v1/listings?min_price=100&max_price=200');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_stable_price_asc_ordering(): void
    {
        $first = $this->createListing(['title' => 'First', 'price' => 100]);
        $second = $this->createListing(['title' => 'Second', 'price' => 100]);

        $response = $this->getJson('/api/v1/listings?sort=price_asc');

        $response->assertOk();
        $this->assertSame($first->id, $response->json('data.0.id'));
        $this->assertSame($second->id, $response->json('data.1.id'));
    }

    public function test_stable_price_desc_ordering(): void
    {
        $first = $this->createListing(['title' => 'First', 'price' => 100]);
        $second = $this->createListing(['title' => 'Second', 'price' => 100]);

        $response = $this->getJson('/api/v1/listings?sort=price_desc');

        $response->assertOk();
        $this->assertSame($first->id, $response->json('data.0.id'));
        $this->assertSame($second->id, $response->json('data.1.id'));
    }

    public function test_stable_newest_ordering(): void
    {
        $first = $this->createListing(['title' => 'First']);
        $second = $this->createListing(['title' => 'Second']);

        $first->created_at = now();
        $second->created_at = now();
        $first->save();
        $second->save();

        $response = $this->getJson('/api/v1/listings?sort=newest');

        $response->assertOk();
        $this->assertSame($first->id, $response->json('data.0.id'));
        $this->assertSame($second->id, $response->json('data.1.id'));
    }


    public function test_invalid_page_values_return_422(): void
    {
        $this->getJson('/api/v1/listings?page=0')->assertUnprocessable();
        $this->getJson('/api/v1/listings?page=-1')->assertUnprocessable();
        $this->getJson('/api/v1/listings?page=abc')->assertUnprocessable();
    }

    public function test_multiple_municipality_filtering(): void
    {
        $this->createListing(['municipality' => 'Naga City']);
        $this->createListing(['municipality' => 'Pili']);
        $this->createListing(['municipality' => 'Iriga City']);

        $response = $this->getJson('/api/v1/listings?municipality[]=Naga+City&municipality[]=Pili');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_invalid_municipality_input_returns_422(): void
    {
        $this->getJson('/api/v1/listings?municipality=Naga+City')->assertUnprocessable();
    }

    public function test_primary_image_selection(): void
    {
        $listing = $this->createListing();
        $listing->images()->create([
            'path' => 'listings/primary.jpg',
            'disk' => 'public',
            'sort_order' => 5,
            'is_primary' => true,
        ]);
        $listing->images()->create([
            'path' => 'listings/secondary.jpg',
            'disk' => 'public',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk();
        $this->assertStringContainsString('primary.jpg', $response->json('data.0.primary_image_url'));
    }

    public function test_image_url_serialization_and_ordering(): void
    {
        $listing = $this->createListing();
        $listing->images()->create([
            'path' => 'listings/secondary.jpg',
            'disk' => 'public',
            'sort_order' => 2,
            'is_primary' => false,
        ]);
        $listing->images()->create([
            'path' => 'listings/primary.jpg',
            'disk' => 'public',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->id}");

        $response->assertOk();
        $images = $response->json('data.images');

        $this->assertSame('primary.jpg', basename($images[0]['url']));
        $this->assertTrue($images[0]['is_primary']);
        $this->assertSame('secondary.jpg', basename($images[1]['url']));
    }

    public function test_stable_sorting_for_shared_price_or_timestamp(): void
    {
        $first = $this->createListing(['title' => 'First', 'price' => 100]);
        $second = $this->createListing(['title' => 'Second', 'price' => 100]);

        $first->created_at = now();
        $second->created_at = now();
        $first->save();
        $second->save();

        $response = $this->getJson('/api/v1/listings?sort=price_asc');

        $response->assertOk();
        $this->assertSame($first->id, $response->json('data.0.id'));
        $this->assertSame($second->id, $response->json('data.1.id'));
    }
}

