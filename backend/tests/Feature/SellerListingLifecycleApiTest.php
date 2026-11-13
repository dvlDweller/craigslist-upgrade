<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerListingLifecycleApiTest extends TestCase
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
            'barangay' => 'Concepcion',
        ], $attributes));

        if (array_key_exists('status', $attributes)) {
            $listing->status = $attributes['status'];
            $listing->save();
        }
        if (array_key_exists('published_at', $attributes)) {
            $listing->published_at = $attributes['published_at'];
            $listing->save();
        }

        return $listing;
    }

    public function test_auth_required_for_lifecycle_endpoints(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_non_owner_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);

        $this->login($other);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertNotFound();
        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertNotFound();
    }

    public function test_soft_deleted_returns_404(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $listing->delete();

        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertNotFound();
        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertNotFound();
    }

    public function test_valid_draft_publish_succeeds(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $response = $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', Listing::STATUS_ACTIVE);
        $this->assertNotNull($response->json('data.published_at'));

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'status' => Listing::STATUS_ACTIVE,
        ]);
    }

    public function test_invalid_persisted_title_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['title' => '']);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();
    }

    public function test_invalid_persisted_description_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['description' => '']);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();
    }

    public function test_invalid_persisted_category_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $inactive = Category::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);
        $listing = $this->makeListing($user, $category);
        $listing->category_id = $inactive->id;
        $listing->save();
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();
    }

    public function test_invalid_persisted_price_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        // fixed without price
        $listing = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => null]);
        $this->login($user);
        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();

        // price zero
        $listing2 = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 0]);
        // price 0 may be stored; validation gt:0 fails
        $this->postJson("/api/v1/seller/listings/{$listing2->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();
    }

    public function test_missing_empty_municipality_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        $listing = $this->makeListing($user, $category, ['municipality' => null]);
        $this->login($user);
        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();

        $listing2 = $this->makeListing($user, $category, ['municipality' => '']);
        $this->postJson("/api/v1/seller/listings/{$listing2->id}/publish", [], $this->statefulHeaders())
            ->assertUnprocessable();
    }

    public function test_barangay_null_accepted_on_publish(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['barangay' => null]);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertOk()
            ->assertJsonPath('data.barangay', null);
    }

    public function test_arbitrary_municipality_accepted_on_publish(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['municipality' => 'Testville']);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertOk()
            ->assertJsonPath('data.municipality', 'Testville');
    }

    public function test_client_cannot_control_status_published_at_on_publish(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $future = now()->addDays(5)->toDateTimeString();
        $response = $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [
            'status' => Listing::STATUS_SOLD,
            'published_at' => $future,
        ], $this->statefulHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', Listing::STATUS_ACTIVE);
        $this->assertNotSame($future, $response->json('data.published_at'));
    }

    public function test_mark_sold_active_succeeds_and_preserves_published_at(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $publishedAt = now()->subDay();
        $listing = $this->makeListing($user, $category, [
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => $publishedAt,
        ]);
        $originalPublishedAt = $listing->published_at->toJSON();
        $this->login($user);

        $response = $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', Listing::STATUS_SOLD);
        $this->assertSame($originalPublishedAt, $response->json('data.published_at'));

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'status' => Listing::STATUS_SOLD,
        ]);
    }

    public function test_draft_mark_sold_returns_409(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category); // draft
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertStatus(409);
    }

    public function test_already_sold_mark_sold_returns_409(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_SOLD]);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertStatus(409);
    }

    public function test_repeated_publish_returns_409(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertOk();

        $this->postJson("/api/v1/seller/listings/{$listing->id}/publish", [], $this->statefulHeaders())
            ->assertStatus(409);
    }

    public function test_repeated_mark_sold_returns_409(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_ACTIVE]);
        $this->login($user);

        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertOk();

        $this->postJson("/api/v1/seller/listings/{$listing->id}/mark-sold", [], $this->statefulHeaders())
            ->assertStatus(409);
    }

    public function test_public_visibility_lifecycle(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        $draft = $this->makeListing($user, $category, ['title' => 'Draft', 'status' => Listing::STATUS_DRAFT]);
        $active = $this->makeListing($user, $category, ['title' => 'Active', 'status' => Listing::STATUS_ACTIVE]);
        $sold = $this->makeListing($user, $category, ['title' => 'Sold', 'status' => Listing::STATUS_SOLD]);

        // index only active
        $response = $this->getJson('/api/v1/listings');
        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Active', $response->json('data.0.title'));

        // detail 404 for draft/sold
        $this->getJson("/api/v1/listings/{$draft->id}")->assertNotFound();
        $this->getJson("/api/v1/listings/{$sold->id}")->assertNotFound();
        $this->getJson("/api/v1/listings/{$active->id}")->assertOk();
    }

    public function test_active_patch_municipality_cannot_be_cleared(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, ['status' => Listing::STATUS_ACTIVE]);
        $this->login($user);

        foreach ([null, ''] as $value) {
            $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
                'municipality' => $value,
            ], $this->statefulHeaders())->assertUnprocessable();
        }
    }
}