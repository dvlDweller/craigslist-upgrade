<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublishListingRequest;
use App\Http\Requests\Api\V1\ReorderListingImagesRequest;
use App\Http\Requests\Api\V1\StoreListingImageRequest;
use App\Http\Requests\Api\V1\StoreListingRequest;
use App\Http\Requests\Api\V1\UpdateListingRequest;
use App\Http\Resources\ListingDetailResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SellerListingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $listings = $request->user()
            ->listings()
            ->with([
                'category:id,name,slug',
                'user:id,name',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate($this->perPage($request));

        return ListingResource::collection($listings);
    }

    public function store(StoreListingRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['currency'] = 'PHP';
        $data['province'] = 'Camarines Sur';

        $listing = $request->user()->listings()->create($data);

        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return (new ListingDetailResource($listing))->response()->setStatusCode(201);
    }

    public function show(Request $request, Listing $listing): ListingDetailResource
    {
        $this->authorizeListing($request, 'view', $listing);

        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    public function update(UpdateListingRequest $request, Listing $listing): ListingDetailResource
    {
        $this->authorizeListing($request, 'update', $listing);

        $data = $request->validated();

        if (array_key_exists('price_type', $data)) {
            if (in_array($data['price_type'], [Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT], true)) {
                $data['price'] = null;
            }
        }

        unset($data['user_id'], $data['status'], $data['published_at'], $data['currency'], $data['province']);

        $listing = DB::transaction(function () use ($request, $listing, $data): Listing {
            $originalStatus = $listing->status;

            $listing->refresh();
            $fresh = $listing;

            if ($fresh->status === Listing::STATUS_ACTIVE && $request->has('municipality')) {
                $submittedMunicipality = $request->input('municipality');
                if ($submittedMunicipality === null || $submittedMunicipality === '') {
                    abort(422);
                }
            }

            $changes = [];
            foreach ($data as $key => $value) {
                $current = $fresh->{$key};
                if ($key === 'price') {
                    $current = $current === null ? null : (string) $current;
                    $value = ($value === null || $value === '') ? null : (string) $value;
                }
                if ($current != $value) {
                    $changes[$key] = $data[$key];
                }
            }

            if (empty($changes)) {
                $fresh->load([
                    'category:id,name,slug',
                    'user:id,name',
                    'images' => fn ($query) => $query
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order'),
                ]);

                return $fresh;
            }

            $updated = Listing::whereKey($fresh->id)
                ->where('updated_at', $fresh->updated_at)
                ->where('status', $originalStatus)
                ->update(array_merge($changes, ['updated_at' => now()]));

            if ($updated === 0) {
                abort(409);
            }

            $fresh->refresh();
            $fresh->load([
                'category:id,name,slug',
                'user:id,name',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),
            ]);

            return $fresh;
        });

        return new ListingDetailResource($listing);
    }

    public function destroy(Request $request, Listing $listing): \Illuminate\Http\Response
    {
        $this->authorizeListing($request, 'delete', $listing);

        $listing->delete();

        return response()->noContent();
    }

    public function publish(PublishListingRequest $request, Listing $listing): ListingDetailResource
    {
        $this->authorizeListing($request, 'publish', $listing);

        $updated = DB::transaction(function () use ($listing, $request): int {
            $listing->refresh();

            if ($listing->status !== Listing::STATUS_DRAFT) {
                abort(409);
            }

            $request->validateListing($listing);

            $query = Listing::whereKey($listing->id)
                ->where('status', Listing::STATUS_DRAFT)
                ->where('updated_at', $listing->updated_at);

            foreach (['title', 'description', 'category_id', 'price_type', 'currency', 'province'] as $field) {
                $query->where($field, $listing->{$field});
            }

            foreach (['price', 'municipality', 'barangay'] as $field) {
                if ($listing->{$field} === null) {
                    $query->whereNull($field);
                } else {
                    $query->where($field, $listing->{$field});
                }
            }

            return $query->update([
                'status' => Listing::STATUS_ACTIVE,
                'published_at' => now(),
            ]);
        });

        if ($updated === 0) {
            abort(409);
        }

        $listing->refresh();
        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    public function markSold(Request $request, Listing $listing): ListingDetailResource
    {
        $this->authorizeListing($request, 'markSold', $listing);

        $updated = DB::transaction(function () use ($listing): int {
            return Listing::whereKey($listing->id)
                ->where('status', Listing::STATUS_ACTIVE)
                ->update(['status' => Listing::STATUS_SOLD]);
        });

        if ($updated === 0) {
            abort(409);
        }

        $listing->refresh();
        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    public function storeImages(StoreListingImageRequest $request, Listing $listing): \Illuminate\Http\JsonResponse
    {
        $this->authorizeListing($request, 'manageImages', $listing);

        $files = $request->file('images', []);
        $disk = config('filesystems.listing_images_disk', 'public');
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $path = $file->store("listings/{$listing->id}", $disk);
                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('Unable to store image.');
                }
                $storedPaths[] = $path;
            }

            DB::transaction(function () use ($listing, $storedPaths, $disk): void {
                $existingCount = $listing->images()->count();
                if ($existingCount + count($storedPaths) > 8) {
                    throw ValidationException::withMessages([
                        'images' => ['A listing may not have more than 8 images.'],
                    ]);
                }

                $hasPrimary = $listing->images()->where('is_primary', true)->exists();
                $sortStart = (int) ($listing->images()->max('sort_order') ?? -1) + 1;

                foreach ($storedPaths as $index => $path) {
                    $listing->images()->create([
                        'path' => $path,
                        'disk' => $disk,
                        'sort_order' => $sortStart + $index,
                        'is_primary' => ! $hasPrimary && $index === 0,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                try {
                    $deleted = Storage::disk($disk)->delete($path);
                    if (! $deleted) {
                        Log::warning('Listing image cleanup failed after upload error.', [
                            'listing_id' => $listing->id,
                            'path' => $path,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::warning('Listing image cleanup failed after upload error.', [
                        'listing_id' => $listing->id,
                        'path' => $path,
                    ]);
                }
            }

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            Log::error('Listing image upload failed.', [
                'listing_id' => $listing->id,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }

        $listing->refresh();
        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return (new ListingDetailResource($listing))->response()->setStatusCode(201);
    }

    public function destroyImage(Request $request, Listing $listing, ListingImage $image): \Illuminate\Http\Response
    {
        $this->authorizeListing($request, 'manageImages', $listing);

        if ($image->listing_id !== $listing->id) {
            abort(404);
        }

        $path = $image->path;

        DB::transaction(function () use ($listing, $image): void {
            if ($image->is_primary) {
                $nextPrimary = ListingImage::query()
                    ->where('listing_id', $listing->id)
                    ->where('id', '!=', $image->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                if ($nextPrimary !== null) {
                    $nextPrimary->update(['is_primary' => true]);
                }
            }

            $image->delete();
        });

        try {
            $disk = $image->disk ?: config('filesystems.listing_images_disk', 'public');

            if (! Storage::disk($disk)->delete($path)) {
                Log::warning('Listing image physical deletion failed.', [
                    'listing_id' => $listing->id,
                    'image_id' => $image->id,
                    'path' => $path,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Listing image physical deletion failed.', [
                'listing_id' => $listing->id,
                'image_id' => $image->id,
                'path' => $path,
            ]);
        }

        return response()->noContent();
    }

    public function setPrimaryImage(Request $request, Listing $listing, ListingImage $image): ListingDetailResource
    {
        $this->authorizeListing($request, 'manageImages', $listing);

        if ($image->listing_id !== $listing->id) {
            abort(404);
        }

        DB::transaction(function () use ($listing, $image): void {
            ListingImage::where('listing_id', $listing->id)->update(['is_primary' => false]);
            ListingImage::where('id', $image->id)
                ->where('listing_id', $listing->id)
                ->update(['is_primary' => true]);
        });

        $listing->refresh();
        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    public function reorderImages(ReorderListingImagesRequest $request, Listing $listing): ListingDetailResource
    {
        $this->authorizeListing($request, 'manageImages', $listing);

        $imageIds = $request->validated('image_ids');

        DB::transaction(function () use ($listing, $imageIds): void {
            $currentIds = $listing->images()->pluck('id')->sort()->values()->all();
            $submittedIds = collect($imageIds)->sort()->values()->all();

            if ($currentIds !== $submittedIds) {
                abort(422);
            }

            foreach ($imageIds as $index => $imageId) {
                ListingImage::where('id', $imageId)
                    ->where('listing_id', $listing->id)
                    ->update(['sort_order' => $index]);
            }
        });

        $listing->refresh();
        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 15);

        return max(1, min(50, $perPage));
    }

    private function authorizeListing(Request $request, string $ability, Listing $listing): void
    {
        try {
            Gate::forUser($request->user())->authorize($ability, $listing);
        } catch (AuthorizationException $e) {
            abort(404);
        }
    }
}
