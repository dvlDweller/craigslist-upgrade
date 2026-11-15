<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerListingApiTest extends TestCase
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

    private function inactiveCategory(): Category
    {
        return Category::create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'is_active' => false,
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

        return $listing;
    }

    public function test_all_endpoints_require_authentication(): void
    {
        $category = $this->activeCategory();
        $user = $this->createUser('seller@example.com');
        $listing = $this->makeListing($user, $category);

        $this->getJson('/api/v1/seller/listings', [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Bike',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->getJson("/api/v1/seller/listings/{$listing->id}", [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'title' => 'Updated',
        ], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->deleteJson("/api/v1/seller/listings/{$listing->id}", [], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_successful_draft_creation_and_ownership(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $this->login($user);

        $response = $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Used Bike',
            'description' => 'Good condition mountain bike.',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.00,
            'municipality' => 'Naga City',
            'barangay' => 'Concepcion',
        ], $this->statefulHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.status', Listing::STATUS_DRAFT)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.currency', 'PHP')
            ->assertJsonPath('data.province', 'Camarines Sur');

        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'status' => Listing::STATUS_DRAFT,
            'currency' => 'PHP',
            'province' => 'Camarines Sur',
        ]);
    }

    public function test_invalid_and_inactive_categories_are_rejected(): void
    {
        $user = $this->createUser('seller@example.com');
        $inactive = $this->inactiveCategory();
        $this->login($user);

        $this->postJson('/api/v1/seller/listings', [
            'category_id' => 9999,
            'title' => 'Used Bike',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();

        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $inactive->id,
            'title' => 'Used Bike',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_price_validation_for_fixed_free_and_contact(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $this->login($user);

        // fixed without price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Fixed no price',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FIXED,
        ], $this->statefulHeaders())->assertUnprocessable();

        // fixed price zero
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Fixed zero',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 0,
        ], $this->statefulHeaders())->assertUnprocessable();

        // free with price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Free with price',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
            'price' => 10,
        ], $this->statefulHeaders())->assertUnprocessable();

        // contact with price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Contact with price',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_CONTACT,
            'price' => 10,
        ], $this->statefulHeaders())->assertUnprocessable();

        // free without price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Free ok',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertCreated()
            ->assertJsonPath('data.price', null);

        // contact without price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Contact ok',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_CONTACT,
        ], $this->statefulHeaders())->assertCreated()
            ->assertJsonPath('data.price', null);

        // fixed with price
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Fixed ok',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 50,
        ], $this->statefulHeaders())->assertCreated()
            ->assertJsonPath('data.price', '50.00');
    }

    public function test_price_consistency_on_partial_updates(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, [
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.00,
        ]);
        $this->login($user);

        // switch to free clears price
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price_type', Listing::PRICE_TYPE_FREE)
            ->assertJsonPath('data.price', null);

        // switch to contact clears price
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price_type' => Listing::PRICE_TYPE_CONTACT,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', null);

        // switch back to fixed requires price
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price_type' => Listing::PRICE_TYPE_FIXED,
        ], $this->statefulHeaders())->assertUnprocessable();

        // switch to fixed with price
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 75,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', '75.00');

        // update price only
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price' => 90,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', '90.00');
    }

    public function test_seller_index_isolation_and_pagination(): void
    {
        $user1 = $this->createUser('seller1@example.com');
        $user2 = $this->createUser('seller2@example.com');
        $category = $this->activeCategory();

        for ($i = 0; $i < 3; $i++) {
            $this->makeListing($user1, $category, ['title' => "User1 Bike {$i}"]);
        }
        $this->makeListing($user2, $category, ['title' => 'User2 Bike']);

        // draft included
        $draft = $this->makeListing($user1, $category, [
            'title' => 'Draft Bike',
            'status' => Listing::STATUS_DRAFT,
        ]);
        $draft->status = Listing::STATUS_DRAFT;
        $draft->save();

        $this->login($user1);

        $response = $this->getJson('/api/v1/seller/listings?per_page=2', $this->statefulHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 4);

        $this->getJson('/api/v1/seller/listings?per_page=51', $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_owner_can_view_draft_active_and_sold_listings(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        $draft = $this->makeListing($user, $category, ['status' => Listing::STATUS_DRAFT]);
        $draft->status = Listing::STATUS_DRAFT;
        $draft->save();

        $active = $this->makeListing($user, $category, ['status' => Listing::STATUS_ACTIVE]);
        $active->status = Listing::STATUS_ACTIVE;
        $active->save();

        $sold = $this->makeListing($user, $category, ['status' => Listing::STATUS_SOLD]);
        $sold->status = Listing::STATUS_SOLD;
        $sold->save();

        $this->login($user);

        $this->getJson("/api/v1/seller/listings/{$draft->id}", $this->statefulHeaders())->assertOk();
        $this->getJson("/api/v1/seller/listings/{$active->id}", $this->statefulHeaders())->assertOk();
        $this->getJson("/api/v1/seller/listings/{$sold->id}", $this->statefulHeaders())->assertOk();
    }

    public function test_non_owner_access_returns_404(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($owner, $category);

        $this->login($other);

        $this->getJson("/api/v1/seller/listings/{$listing->id}", $this->statefulHeaders())->assertNotFound();
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'title' => 'Hacked',
        ], $this->statefulHeaders())->assertNotFound();
        $this->deleteJson("/api/v1/seller/listings/{$listing->id}", [], $this->statefulHeaders())->assertNotFound();
    }

    public function test_protected_fields_cannot_be_tampered(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, [
            'title' => 'Original',
            'status' => Listing::STATUS_DRAFT,
            'currency' => 'PHP',
            'province' => 'Camarines Sur',
        ]);
        $listing->status = Listing::STATUS_DRAFT;
        $listing->save();

        $otherUser = $this->createUser('other@example.com');

        $this->login($user);

        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'title' => 'Updated',
            'user_id' => $otherUser->id,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->toDateTimeString(),
            'currency' => 'USD',
            'province' => 'Manila',
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_soft_delete_and_exclusion(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $this->deleteJson("/api/v1/seller/listings/{$listing->id}", [], $this->statefulHeaders())
            ->assertNoContent();

        $this->getJson("/api/v1/seller/listings/{$listing->id}", $this->statefulHeaders())
            ->assertNotFound();

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_public_endpoints_still_return_only_active_non_deleted(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        $active = $this->makeListing($user, $category, ['title' => 'Active']);
        $active->status = Listing::STATUS_ACTIVE;
        $active->save();

        $draft = $this->makeListing($user, $category, ['title' => 'Draft']);
        $draft->status = Listing::STATUS_DRAFT;
        $draft->save();

        $deleted = $this->makeListing($user, $category, ['title' => 'Deleted']);
        $deleted->status = Listing::STATUS_ACTIVE;
        $deleted->save();
        $deleted->delete();

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Active', $response->json('data.0.title'));
    }

    public function test_category_hierarchy_selectable_on_creation(): void
    {
        $user = $this->createUser('seller@example.com');
        $this->login($user);

        $activeRoot = Category::create(['name' => 'Root', 'slug' => 'root', 'is_active' => true]);
        $activeChild = Category::create(['name' => 'Child', 'slug' => 'child', 'is_active' => true, 'parent_id' => $activeRoot->id]);
        $inactiveRoot = Category::create(['name' => 'InactiveRoot', 'slug' => 'inactive-root', 'is_active' => false]);
        $inactiveChild = Category::create(['name' => 'InactiveChild', 'slug' => 'inactive-child', 'is_active' => false, 'parent_id' => $activeRoot->id]);
        $childOfInactiveRoot = Category::create(['name' => 'ChildOfInactive', 'slug' => 'child-inactive-root', 'is_active' => true, 'parent_id' => $inactiveRoot->id]);
        $grandchild = Category::create(['name' => 'Grandchild', 'slug' => 'grandchild', 'is_active' => true, 'parent_id' => $activeChild->id]);

        // active root accepted
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $activeRoot->id,
            'title' => 'Root listing',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertCreated();

        // active child of active root accepted
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $activeChild->id,
            'title' => 'Child listing',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertCreated();

        // inactive root rejected
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $inactiveRoot->id,
            'title' => 'Inactive root',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();

        // inactive child rejected
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $inactiveChild->id,
            'title' => 'Inactive child',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();

        // active child of inactive root rejected
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $childOfInactiveRoot->id,
            'title' => 'Child of inactive root',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();

        // active grandchild rejected
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $grandchild->id,
            'title' => 'Grandchild',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_category_hierarchy_selectable_on_update(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $activeRoot = Category::create(['name' => 'Root2', 'slug' => 'root2', 'is_active' => true]);
        $activeChild = Category::create(['name' => 'Child2', 'slug' => 'child2', 'is_active' => true, 'parent_id' => $activeRoot->id]);
        $grandchild = Category::create(['name' => 'Grandchild2', 'slug' => 'grandchild2', 'is_active' => true, 'parent_id' => $activeChild->id]);

        // active child accepted
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'category_id' => $activeChild->id,
        ], $this->statefulHeaders())->assertOk();

        // grandchild rejected
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'category_id' => $grandchild->id,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_protected_fields_tampering_on_creation_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $this->login($user);

        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Bike',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FREE,
            'user_id' => 999,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->toDateTimeString(),
            'currency' => 'USD',
            'province' => 'Manila',
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_protected_fields_tampering_on_update_returns_422(): void
    {
        $user = $this->createUser('seller@example.com');
        $otherUser = $this->createUser('other@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'title' => 'Updated',
            'user_id' => $otherUser->id,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->toDateTimeString(),
            'currency' => 'USD',
            'province' => 'Manila',
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_protected_fields_null_and_empty_rejected_on_creation(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $this->login($user);

        foreach (['', null] as $value) {
            foreach (['user_id', 'status', 'published_at', 'currency', 'province'] as $field) {
                $payload = [
                    'category_id' => $category->id,
                    'title' => 'Bike',
                    'description' => 'Desc',
                    'price_type' => Listing::PRICE_TYPE_FREE,
                    $field => $value,
                ];
                $this->postJson('/api/v1/seller/listings', $payload, $this->statefulHeaders())
                    ->assertUnprocessable();
            }
        }
    }

    public function test_protected_fields_null_and_empty_rejected_on_update(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category);
        $this->login($user);

        foreach ([null, ''] as $value) {
            foreach (['user_id', 'status', 'published_at', 'currency', 'province'] as $field) {
                $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
                    'title' => 'Updated',
                    $field => $value,
                ], $this->statefulHeaders())->assertUnprocessable();
            }
        }
    }

    public function test_regression_price_updates(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();

        // existing fixed + PATCH price=null -> 422
        $listing = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 100.00]);
        $this->login($user);
        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price' => null,
        ], $this->statefulHeaders())->assertUnprocessable();
        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'price' => 100.00]);

        // existing fixed + PATCH price omitted -> preserve
        $listing2 = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 100.00]);
        $this->patchJson("/api/v1/seller/listings/{$listing2->id}", [
            'title' => 'Renamed',
        ], $this->statefulHeaders())->assertOk();
        $this->assertDatabaseHas('listings', ['id' => $listing2->id, 'price' => 100.00]);

        // existing fixed + PATCH price=positive -> accept
        $listing3 = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 100.00]);
        $this->patchJson("/api/v1/seller/listings/{$listing3->id}", [
            'price' => 50,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', '50.00');

        // existing free + PATCH price=positive -> 422
        $freeListing = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FREE, 'price' => null]);
        $this->patchJson("/api/v1/seller/listings/{$freeListing->id}", [
            'price' => 10,
        ], $this->statefulHeaders())->assertUnprocessable();
        $this->assertDatabaseHas('listings', ['id' => $freeListing->id, 'price' => null]);

        // existing contact + PATCH price=positive -> 422
        $contactListing = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_CONTACT, 'price' => null]);
        $this->patchJson("/api/v1/seller/listings/{$contactListing->id}", [
            'price' => 10,
        ], $this->statefulHeaders())->assertUnprocessable();

        // existing free/contact + PATCH price=null -> accept
        $this->patchJson("/api/v1/seller/listings/{$freeListing->id}", [
            'price' => null,
        ], $this->statefulHeaders())->assertOk();
        $this->patchJson("/api/v1/seller/listings/{$contactListing->id}", [
            'price' => null,
        ], $this->statefulHeaders())->assertOk();

        // existing free/contact + PATCH price omitted -> preserve null
        $this->patchJson("/api/v1/seller/listings/{$freeListing->id}", [
            'title' => 'Free renamed',
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', null);

        // switch to free/contact clears price
        $fixedListing = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FIXED, 'price' => 100.00]);
        $this->patchJson("/api/v1/seller/listings/{$fixedListing->id}", [
            'price_type' => Listing::PRICE_TYPE_FREE,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', null);
        $this->patchJson("/api/v1/seller/listings/{$fixedListing->id}", [
            'price_type' => Listing::PRICE_TYPE_CONTACT,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', null);

        // switch to fixed requires new positive price
        $freeListing2 = $this->makeListing($user, $category, ['price_type' => Listing::PRICE_TYPE_FREE, 'price' => null]);
        $this->patchJson("/api/v1/seller/listings/{$freeListing2->id}", [
            'price_type' => Listing::PRICE_TYPE_FIXED,
        ], $this->statefulHeaders())->assertUnprocessable();

        // switch to fixed with valid price succeeds
        $this->patchJson("/api/v1/seller/listings/{$freeListing2->id}", [
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 25,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', '25.00');

        // price above 99999999.99 rejected on create
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Big price',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100000000.00,
        ], $this->statefulHeaders())->assertUnprocessable();

        // price above 99999999.99 rejected on update
        $this->patchJson("/api/v1/seller/listings/{$listing3->id}", [
            'price' => 100000000.00,
        ], $this->statefulHeaders())->assertUnprocessable();

        // more than 2 decimals rejected on create
        $this->postJson('/api/v1/seller/listings', [
            'category_id' => $category->id,
            'title' => 'Decimals',
            'description' => 'Desc',
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 10.999,
        ], $this->statefulHeaders())->assertUnprocessable();

        // more than 2 decimals rejected on update
        $this->patchJson("/api/v1/seller/listings/{$listing3->id}", [
            'price' => 10.999,
        ], $this->statefulHeaders())->assertUnprocessable();
    }

    public function test_regression_fixed_price_type_submitted_without_price_preserves_price(): void
    {
        $user = $this->createUser('seller@example.com');
        $category = $this->activeCategory();
        $listing = $this->makeListing($user, $category, [
            'price_type' => Listing::PRICE_TYPE_FIXED,
            'price' => 100.00,
        ]);
        $this->login($user);

        $this->patchJson("/api/v1/seller/listings/{$listing->id}", [
            'price_type' => Listing::PRICE_TYPE_FIXED,
        ], $this->statefulHeaders())->assertOk()
            ->assertJsonPath('data.price', '100.00');

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'price' => 100.00]);
    }
}