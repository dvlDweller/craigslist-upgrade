<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreListingRequest;
use App\Http\Requests\Api\V1\UpdateListingRequest;
use App\Http\Resources\ListingDetailResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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

        // Never allow protected fields to be changed.
        unset($data['user_id'], $data['status'], $data['published_at'], $data['currency'], $data['province']);

        $listing->fill($data);
        $listing->save();

        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }

    public function destroy(Request $request, Listing $listing): \Illuminate\Http\Response
    {
        $this->authorizeListing($request, 'delete', $listing);

        $listing->delete();

        return response()->noContent();
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