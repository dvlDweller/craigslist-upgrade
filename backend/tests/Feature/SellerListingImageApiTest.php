<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SellerListingImageApiTest extends TestCase
{
    use RefreshDatabase;

    private function statefulHeaders(): array
    {
        $response = $this->get('/sanctum/csrf-cookie', [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ]);

        return [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
            'Accept' => 'application/json',
            'X-XSRF-TOKEN' => $response->getCookie('XSRF-TOKEN')?->getValue(),
        ];
    }

    private function createUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function activeCategory(string $slug = 'bikes'): Category
    {
        return Category::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function login(User $user): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ], $this->statefulHeaders())->assertOk();
    }

    private function makeListing(User $user, Category $category, array $attributes = []): Listing
    {
        $listing = $user->listings()->create(array_merge([
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition mountain bike.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.00,
            'municipality' => 'Naga City',
            'barangay' => 'Concepcion Grande',
        ], $attributes));

        return $listing;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_all_image_endpoints_require_authentication(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $image = $listing->images()->create(['path' => 'listings/1/x.jpg']);

        $headers = ['Origin' => 'http://localhost:3000', 'Accept' => 'application/json'];

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [], $headers)->assertUnauthorized();
        $this->delete("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $headers)->assertUnauthorized();
        $this->post("/api/v1/seller/listings/{$listing->id}/images/{$image->id}/primary", [], $headers)->assertUnauthorized();
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", ['image_ids' => [$image->id]], $headers)->assertUnauthorized();
    }

    public function test_non_owner_access_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $image = $listing->images()->create(['path' => 'listings/1/x.jpg']);
        $image2 = $listing->images()->create(['path' => 'listings/1/y.jpg']);
        $this->login($other);

        $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", ['images' => [$file]], $this->statefulHeaders())->assertNotFound();
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNotFound();
        $this->postJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}/primary", [], $this->statefulHeaders())->assertNotFound();
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", ['image_ids' => [$image->id, $image2->id]], $this->statefulHeaders())->assertNotFound();
    }

    public function test_non_owner_upload_with_invalid_payload_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $this->login($other);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [],
        ], $this->statefulHeaders())->assertNotFound();
    }

    public function test_non_owner_reorder_with_invalid_payload_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $img = $listing->images()->create(['path' => 'listings/x.jpg']);
        $this->login($other);

        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img->id, $img->id],
        ], $this->statefulHeaders())->assertNotFound();
    }

    public function test_soft_deleted_listing_returns_404(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $image = $listing->images()->create(['path' => 'listings/1/x.jpg']);
        $listing->delete();
        $this->login($user);

        $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", ['images' => [$file]], $this->statefulHeaders())->assertNotFound();
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNotFound();
        $this->postJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}/primary", [], $this->statefulHeaders())->assertNotFound();
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", ['image_ids' => [$image->id]], $this->statefulHeaders())->assertNotFound();
    }

    public function test_valid_single_upload(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');

        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders());

        $response->assertCreated();
        $this->assertDatabaseHas('listing_images', ['listing_id' => $listing->id]);
        $image = $listing->images()->first();
        $this->assertTrue($image->is_primary);
        $this->assertSame('public', $image->disk);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_valid_multiple_upload_primary_first_only_if_no_primary(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        // existing primary
        $existing = $listing->images()->create(['path' => 'listings/existing.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);

        $file1 = UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg');

        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file1, $file2],
        ], $this->statefulHeaders());

        $response->assertCreated();
        $newImages = $listing->images()->where('id', '!=', $existing->id)->get();
        foreach ($newImages as $img) {
            $this->assertFalse($img->is_primary);
            Storage::disk('public')->assertExists($img->path);
        }
        $this->assertSame(3, $listing->images()->count());
    }

    public function test_valid_multiple_upload_no_existing_primary_first_becomes_primary(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file1 = UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file1, $file2],
        ], $this->statefulHeaders())->assertCreated();

        $images = $listing->images()->orderBy('sort_order')->get();
        $this->assertTrue($images->first()->is_primary);
        $this->assertFalse($images->last()->is_primary);
    }

    public function test_invalid_format_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file = UploadedFile::fake()->create('doc.txt', 10, 'text/plain');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_oversized_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file = UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_empty_images_array_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [],
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_single_file_instead_of_images_array_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => $file,
        ], $this->statefulHeaders())->assertUnprocessable();

        $this->assertDatabaseCount('listing_images', 0);
        $this->assertSame(0, count(Storage::disk('public')->allFiles('listings/' . $listing->id)));
    }

    public function test_more_than_8_incoming_files_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $files = [];
        for ($i = 0; $i < 9; $i++) {
            $files[] = UploadedFile::fake()->create("p{$i}.jpg", 10, "image/jpeg");
        }

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => $files,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_existing_plus_incoming_over_8_returns_422(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        for ($i = 0; $i < 7; $i++) {
            $listing->images()->create(['path' => "listings/existing{$i}.jpg", 'disk' => 'public', 'sort_order' => $i]);
        }
        $this->login($user);

        $files = [
            UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg'),
            UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg'),
        ];

        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => $files,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_upload_preserves_status_and_published_at(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        foreach ([Listing::STATUS_DRAFT, Listing::STATUS_ACTIVE, Listing::STATUS_SOLD] as $status) {
            $listing = $this->makeListing($user, $category, ['status' => $status]);
            $listing->status = $status;
            $publishedAt = $status === Listing::STATUS_DRAFT ? null : now()->subDay();
            if ($publishedAt) {
                $listing->published_at = $publishedAt;
            }
            $listing->save();

            $this->login($user);
            $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');
            $this->post("/api/v1/seller/listings/{$listing->id}/images", [
                'images' => [$file],
            ], $this->statefulHeaders())->assertCreated();

            $listing->refresh();
            $this->assertSame($status, $listing->status);
            if ($publishedAt) {
                $this->assertNotNull($listing->published_at);
            } else {
                $this->assertNull($listing->published_at);
            }
            // logout for next iteration
            $this->postJson('/api/v1/auth/logout', [], $this->statefulHeaders());
        }
    }

    public function test_delete_owner_success(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $primary = $listing->images()->create(['path' => 'listings/primary.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        $second = $listing->images()->create(['path' => 'listings/second.jpg', 'disk' => 'public', 'is_primary' => false, 'sort_order' => 1]);
        Storage::disk('public')->put($primary->path, 'x');
        Storage::disk('public')->put($second->path, 'x');
        $this->login($user);

        $this->delete("/api/v1/seller/listings/{$listing->id}/images/{$primary->id}", [], $this->statefulHeaders())->assertNoContent();

        $this->assertDatabaseMissing('listing_images', ['id' => $primary->id]);
        Storage::disk('public')->assertMissing($primary->path);
        $second->refresh();
        $this->assertTrue($second->is_primary);
    }

    public function test_delete_last_image_no_primary(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $only = $listing->images()->create(['path' => 'listings/only.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('public')->put($only->path, 'x');
        $this->login($user);

        $this->delete("/api/v1/seller/listings/{$listing->id}/images/{$only->id}", [], $this->statefulHeaders())->assertNoContent();

        $this->assertSame(0, $listing->images()->count());
        $this->assertSame(0, $listing->images()->where('is_primary', true)->count());
    }

    public function test_delete_non_owner_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $image = $listing->images()->create(['path' => 'listings/x.jpg']);

        $this->login($other);
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNotFound();
    }

    public function test_delete_foreign_image_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $foreignListing = $this->makeListing($other, $category);
        $foreignImage = $foreignListing->images()->create(['path' => 'listings/y.jpg']);

        $this->login($owner);
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$foreignImage->id}", [], $this->statefulHeaders())->assertNotFound();
    }

    public function test_delete_soft_deleted_listing_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $image = $listing->images()->create(['path' => 'listings/x.jpg']);

        $this->login($owner);
        $listing->delete();
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNotFound();
    }
    public function test_set_primary_owner_success(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $img1 = $listing->images()->create(['path' => 'listings/1.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        $img2 = $listing->images()->create(['path' => 'listings/2.jpg', 'disk' => 'public', 'is_primary' => false, 'sort_order' => 1]);
        $this->login($user);

        $this->post("/api/v1/seller/listings/{$listing->id}/images/{$img2->id}/primary", [], $this->statefulHeaders())->assertOk();

        $img1->refresh();
        $img2->refresh();
        $this->assertFalse($img1->is_primary);
        $this->assertTrue($img2->is_primary);
        $this->assertSame(1, $listing->images()->where('is_primary', true)->count());
    }

    public function test_set_primary_non_owner_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $image = $listing->images()->create(['path' => 'listings/x.jpg']);

        $this->login($other);
        $this->postJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}/primary", [], $this->statefulHeaders())->assertNotFound();
    }

    public function test_set_primary_foreign_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);
        $foreignListing = $this->makeListing($other, $category);
        $foreignImage = $foreignListing->images()->create(['path' => 'listings/y.jpg']);

        $this->login($owner);
        $this->postJson("/api/v1/seller/listings/{$listing->id}/images/{$foreignImage->id}/primary", [], $this->statefulHeaders())->assertNotFound();
    }
    public function test_set_primary_sold_listing(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_SOLD]);
        $listing->status = Listing::STATUS_SOLD;
        $listing->save();
        $img1 = $listing->images()->create(['path' => 'listings/1.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        $img2 = $listing->images()->create(['path' => 'listings/2.jpg', 'disk' => 'public', 'is_primary' => false, 'sort_order' => 1]);
        $this->login($user);

        $this->post("/api/v1/seller/listings/{$listing->id}/images/{$img2->id}/primary", [], $this->statefulHeaders())->assertOk();
        $img2->refresh();
        $this->assertTrue($img2->is_primary);
    }

    public function test_reorder_owner_success(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $img1 = $listing->images()->create(['path' => 'listings/1.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        $img2 = $listing->images()->create(['path' => 'listings/2.jpg', 'disk' => 'public', 'is_primary' => false, 'sort_order' => 1]);
        $img3 = $listing->images()->create(['path' => 'listings/3.jpg', 'disk' => 'public', 'is_primary' => false, 'sort_order' => 2]);
        $this->login($user);

        $newOrder = [$img3->id, $img1->id, $img2->id];
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => $newOrder,
        ], $this->statefulHeaders())->assertOk();

        $sorted = $listing->images()->orderBy('sort_order')->pluck('id')->all();
        $this->assertSame($newOrder, $sorted);
        foreach (range(0, 2) as $i) {
            $this->assertSame($i, $listing->images()->where('id', $newOrder[$i])->first()->sort_order);
        }
        // primary unchanged
        $this->assertTrue($img1->fresh()->is_primary);
    }

    public function test_reorder_sold_listing(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_SOLD]);
        $listing->status = Listing::STATUS_SOLD;
        $listing->save();
        $img1 = $listing->images()->create(['path' => 'listings/1.jpg', 'disk' => 'public', 'sort_order' => 0]);
        $img2 = $listing->images()->create(['path' => 'listings/2.jpg', 'disk' => 'public', 'sort_order' => 1]);
        $this->login($user);

        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img2->id, $img1->id],
        ], $this->statefulHeaders())->assertOk();
    }

    public function test_reorder_invalid_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $img1 = $listing->images()->create(['path' => 'listings/1.jpg']);
        $img2 = $listing->images()->create(['path' => 'listings/2.jpg']);
        $otherListing = $this->makeListing($user, $category);
        $foreignImg = $otherListing->images()->create(['path' => 'listings/f.jpg']);
        $this->login($user);

        // duplicate
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img1->id, $img1->id],
        ], $this->statefulHeaders())->assertUnprocessable();

        // missing (subset)
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img1->id],
        ], $this->statefulHeaders())->assertUnprocessable();

        // foreign
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img1->id, $foreignImg->id],
        ], $this->statefulHeaders())->assertUnprocessable();

        // incomplete (extra nonexistent)
        $this->patchJson("/api/v1/seller/listings/{$listing->id}/images/reorder", [
            'image_ids' => [$img1->id, $img2->id, 99999],
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_public_listing_summary_primary_image_url(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $listing->status = Listing::STATUS_ACTIVE;
        $listing->save();
        $this->login($user);

        $file = UploadedFile::fake()->create('primary.jpg', 10, 'image/jpeg');
        $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders())->assertCreated();

        $response = $this->getJson('/api/v1/listings');
        $response->assertOk();
        $this->assertStringContainsString('.jpg', (string) $response->json('data.0.primary_image_url'));
    }

    public function test_partial_storage_failure_cleanup(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file1 = UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg');

        $firstPath = 'listings/' . $listing->id . '/a.jpg';

        $diskMock = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        // first uploaded file stores successfully
        $diskMock->shouldReceive('putFileAs')->once()->andReturn($firstPath);
        // second storage operation throws
        $diskMock->shouldReceive('putFileAs')->once()->andThrow(new \RuntimeException('storage failure'));
        // cleanup of the first file is invoked
        $diskMock->shouldReceive('delete')->with($firstPath)->once()->andReturn(false);

        Storage::shouldReceive('disk')->with('public')->andReturn($diskMock);

        \Illuminate\Support\Facades\Log::spy();

        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file1, $file2],
        ], $this->statefulHeaders());

        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
        $this->assertSame(0, $listing->images()->count());
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->once();
    }

    public function test_database_failure_after_storage_cleanup(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        for ($i = 0; $i < 7; $i++) {
            $listing->images()->create(['path' => "listings/existing{$i}.jpg", 'disk' => 'public', 'sort_order' => $i]);
        }
        $this->login($user);

        // Bypass form validation by using 1 file (8 total) to test DB failure
        \Illuminate\Support\Facades\DB::shouldReceive('transaction')->andThrow(new \RuntimeException('DB failure'));

        $file = UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');
        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders());

        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
        $this->assertSame(7, $listing->fresh()->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_uses_configured_s3_disk(): void
    {
        Storage::fake('s3');
        config(['filesystems.listing_images_disk' => 's3']);

        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file = UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg');

        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file],
        ], $this->statefulHeaders());

        $response->assertCreated();
        $image = $listing->images()->first();
        $this->assertSame('s3', $image->disk);
        Storage::disk('s3')->assertExists($image->path);
        $this->assertSame(Storage::disk('s3')->url($image->path), $response->json('data.images.0.url'));
    }

    public function test_delete_uses_recorded_disk_not_current_configured_disk(): void
    {
        Storage::fake('public');
        Storage::fake('s3');
        config(['filesystems.listing_images_disk' => 's3']);

        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $image = $listing->images()->create([
            'path' => 'listings/recorded.jpg',
            'disk' => 'public',
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put($image->path, 'x');
        $this->login($user);

        $this->deleteJson("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())
            ->assertNoContent();

        Storage::disk('public')->assertMissing($image->path);
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_failed_upload_cleanup_uses_configured_disk(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $file1 = UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg');

        $firstPath = 'listings/' . $listing->id . '/a.jpg';

        $diskMock = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $diskMock->shouldReceive('putFileAs')->once()->andReturn($firstPath);
        $diskMock->shouldReceive('putFileAs')->once()->andThrow(new \RuntimeException('storage failure'));
        $diskMock->shouldReceive('delete')->with($firstPath)->once()->andReturn(false);

        Storage::shouldReceive('disk')->with('s3')->andReturn($diskMock);
        config(['filesystems.listing_images_disk' => 's3']);

        \Illuminate\Support\Facades\Log::spy();

        $response = $this->post("/api/v1/seller/listings/{$listing->id}/images", [
            'images' => [$file1, $file2],
        ], $this->statefulHeaders());

        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
        $this->assertSame(0, $listing->images()->count());
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->once();
    }

    public function test_multi_disk_image_urls_use_recorded_disk(): void
    {
        Storage::fake('public');
        Storage::fake('s3');

        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $publicImage = $listing->images()->create([
            'path' => 'listings/public.jpg',
            'disk' => 'public',
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        $s3Image = $listing->images()->create([
            'path' => 'listings/s3.jpg',
            'disk' => 's3',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put($publicImage->path, 'x');
        Storage::disk('s3')->put($s3Image->path, 'x');
        $this->login($user);

        $response = $this->getJson("/api/v1/seller/listings/{$listing->id}", $this->statefulHeaders())->assertOk();

        $urls = collect($response->json('data.images'))->pluck('url', 'id');
        $this->assertSame(Storage::disk('public')->url($publicImage->path), $urls[$publicImage->id]);
        $this->assertSame(Storage::disk('s3')->url($s3Image->path), $urls[$s3Image->id]);
    }

    public function test_physical_deletion_failure_still_204(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $image = $listing->images()->create(['path' => 'listings/x.jpg', 'disk' => 'public', 'sort_order' => 0]);
        Storage::disk('public')->put($image->path, 'x');
        $this->login($user);

        // Mock disk delete to fail
        $disk = Storage::disk('public');
        // Use Mockery to replace disk
        $mockDisk = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $mockDisk->shouldReceive('delete')->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($mockDisk);

        $this->delete("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNoContent();
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_delete_sold_listing_success(): void
    {
        Storage::fake('public');
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_SOLD]);
        $listing->status = Listing::STATUS_SOLD;
        $publishedAt = now()->subDay();
        $listing->published_at = $publishedAt;
        $listing->save();

        $image = $listing->images()->create(['path' => 'listings/sold.jpg', 'disk' => 'public', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('public')->put($image->path, 'x');

        $originalStatus = $listing->status;
        $originalPublishedAt = $listing->published_at;

        $this->login($user);
        $this->delete("/api/v1/seller/listings/{$listing->id}/images/{$image->id}", [], $this->statefulHeaders())->assertNoContent();

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);

        $listing->refresh();
        $this->assertSame($originalStatus, $listing->status);
        $this->assertEquals($originalPublishedAt->format('Y-m-d H:i:s'), $listing->published_at->format('Y-m-d H:i:s'));
    }
}
